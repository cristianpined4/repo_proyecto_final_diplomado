<?php

namespace App\Services;

use App\Models\Ventas as Venta;
use App\Models\DetallesVenta as DetalleVenta;
use App\Models\Clientes;
use App\Models\Productos as Producto;
use App\Helpers\InventoryValuator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class VentaService
{
  /**
   * Registrar una nueva venta con toda la lógica de negocio.
   */
  public function registrarVenta(array $data): array
  {
    $user = \Illuminate\Support\Facades\Auth::user();
    $sucursalId = app()->bound('active_sucursal_id') ? app('active_sucursal_id') : 1;

    DB::beginTransaction();

    try {
      $productosData = $data['productos'];
      $clienteId = $data['cliente_id'];

      // 1. Obtener configuraciones de IVA
      $ivaEnabled = DB::table('settings')->where('key', 'iva_enabled')->value('value') === 'true';
      $ivaPercentage = $ivaEnabled ? (float) DB::table('settings')->where('key', 'iva_percentage')->value('value') : 0;

      // 2. Obtener Series y Correlativos de forma segura con LOCK
      $serie = DB::table('settings')->where('key', 'venta_serie')->value('value') ?? 'F001';

      $correlativoRow = DB::table('settings')
        ->where('key', 'venta_ultimo_correlativo')
        ->lockForUpdate()
        ->first();

      $nextCorrelativo = 1;
      if ($correlativoRow) {
        $nextCorrelativo = (int) $correlativoRow->value + 1;
        DB::table('settings')
          ->where('key', 'venta_ultimo_correlativo')
          ->update(['value' => (string) $nextCorrelativo]);
      } else {
        DB::table('settings')->insert([
          'key' => 'venta_ultimo_correlativo',
          'value' => '1',
          'type' => 'integer',
          'group' => 'ventas',
          'created_at' => now(),
          'updated_at' => now()
        ]);
      }

      $correlativoStr = str_pad($nextCorrelativo, 8, '0', STR_PAD_LEFT);

      $subtotalGlobal = 0;
      $montoIvaGlobal = 0;
      $productosLocked = [];

      // 3. Loop inicial para bloquear registros de stock y validar disponibilidad
      foreach ($productosData as $prodData) {
        $producto = Producto::lockForUpdate()->findOrFail($prodData['producto_id']);
        $cantidad = $prodData['cantidad'];

        $stockSucursal = DB::table('producto_sucursal')
          ->where('producto_id', $producto->id)
          ->where('sucursal_id', $sucursalId)
          ->value('stock_actual') ?? 0;

        if ($stockSucursal < $cantidad) {
          DB::rollBack();
          throw new \Exception('No hay suficiente stock en esta sucursal para el producto: ' . $producto->nombre);
        }

        $precio = $prodData['precio_venta'];
        if ($ivaEnabled) {
          if ($producto->aplica_iva) {
            // TRUE: El precio incluye IVA. Desglosar.
            $totalLinea = $cantidad * $precio;
            $subtotalLinea = $totalLinea / (1 + ($ivaPercentage / 100));
            $ivaLinea = $totalLinea - $subtotalLinea;
          } else {
            // FALSE: El precio no incluye IVA. Sumar.
            $subtotalLinea = $cantidad * $precio;
            $ivaLinea = $subtotalLinea * ($ivaPercentage / 100);
          }
        } else {
          $subtotalLinea = $cantidad * $precio;
          $ivaLinea = 0;
        }

        $subtotalGlobal += $subtotalLinea;
        $montoIvaGlobal += $ivaLinea;

        $productosLocked[] = [
          'producto' => $producto,
          'cantidad' => $cantidad,
          'precio_venta' => $prodData['precio_venta'],
        ];
      }

      $totalVenta = $subtotalGlobal + $montoIvaGlobal;

      // 4. Determinar método de pago y calcular vuelto si es efectivo
      $metodoPago = in_array($data['metodo_pago'] ?? '', ['efectivo', 'tarjeta_debito', 'tarjeta_credito'])
          ? $data['metodo_pago']
          : 'efectivo';

      if ($metodoPago === 'efectivo') {
          $montoRecibido = isset($data['monto_recibido']) ? (float)$data['monto_recibido'] : $totalVenta;
          $vuelto = isset($data['vuelto']) ? (float)$data['vuelto'] : max(0, round($montoRecibido - $totalVenta, 2));
      } else {
          $montoRecibido = $totalVenta;
          $vuelto = 0.00;
      }

      // 5. Crear la venta
      $venta = Venta::create([
        'total_venta' => $totalVenta,
        'subtotal' => $subtotalGlobal,
        'monto_iva' => $montoIvaGlobal,
        'porcentaje_iva_aplicado' => $ivaPercentage,
        'cliente_id' => $clienteId,
        'user_id' => $user->id,
        'sucursal_id' => $sucursalId,
        'status' => 'pagada',
        'serie' => $serie,
        'correlativo' => $correlativoStr,
        'metodo_pago' => $metodoPago,
        'monto_recibido' => $montoRecibido,
        'vuelto' => $vuelto,
      ]);

      // 5. Procesar productos y reducir stock según FIFO/LIFO/Promedio
      foreach ($productosLocked as $item) {
        $producto = $item['producto'];
        $cantidad = $item['cantidad'];
        $precioVenta = $item['precio_venta'];

        // Crear detalle de venta
        DetalleVenta::create([
          'venta_id' => $venta->id,
          'producto_id' => $producto->id,
          'cantidad' => $cantidad,
          'precio_venta' => $precioVenta,
        ]);

        // Aplicar reducción de stock usando el valuador correspondiente
        InventoryValuator::reduceStock(
          $producto,
          $cantidad,
          $sucursalId,
          'Venta #V' . str_pad($venta->id, 8, '0', STR_PAD_LEFT)
        );
      }

      // Invalidar cache de dashboard
      Cache::forget('dashboard_metrics');
      Cache::forget('dashboard_metrics_v3');

      DB::commit();

      return [
        'status' => true,
        'message' => 'Venta registrada con éxito',
        'venta_id' => $venta->id,
        'venta' => $venta
      ];

    } catch (\Throwable $th) {
      DB::rollBack();
      throw $th;
    }
  }

  /**
   * Anular una venta y restaurar stock.
   */
  public function anularVenta(int $id): array
  {
    $venta = Venta::findOrFail($id);

    if ($venta->status !== 'pagada') {
      throw new \Exception('Solo se pueden anular ventas pagadas');
    }

    DB::beginTransaction();

    try {
      $venta->status = 'anulada';
      $venta->save();

      $detallesVenta = DetalleVenta::where('venta_id', $id)->get();
      foreach ($detallesVenta as $detalle) {
        $producto = Producto::find($detalle->producto_id);

        // Restaurar stock
        InventoryValuator::restoreStock(
          $producto,
          $detalle->cantidad,
          $venta->sucursal_id,
          'Anulación V' . str_pad($id, 8, '0', STR_PAD_LEFT)
        );
      }

      Cache::forget('dashboard_metrics');
      Cache::forget('dashboard_metrics_v3');

      DB::commit();

      return ['status' => true, 'message' => 'Venta anulada con éxito'];

    } catch (\Throwable $th) {
      DB::rollBack();
      throw $th;
    }
  }

  /**
   * Registrar devolución de una venta.
   */
  public function devolverVenta(int $id): array
  {
    $venta = Venta::findOrFail($id);

    if ($venta->status !== 'pagada') {
      throw new \Exception('Solo se pueden devolver ventas pagadas');
    }

    DB::beginTransaction();

    try {
      $venta->status = 'devolucion';
      $venta->save();

      $detallesVenta = DetalleVenta::where('venta_id', $id)->get();
      foreach ($detallesVenta as $detalle) {
        $producto = Producto::find($detalle->producto_id);

        // Restaurar stock
        InventoryValuator::restoreStock(
          $producto,
          $detalle->cantidad,
          $venta->sucursal_id,
          'Devolución V' . str_pad($id, 8, '0', STR_PAD_LEFT)
        );
      }

      Cache::forget('dashboard_metrics');
      Cache::forget('dashboard_metrics_v3');

      DB::commit();

      return ['status' => true, 'message' => 'Venta devuelta con éxito'];

    } catch (\Throwable $th) {
      DB::rollBack();
      throw $th;
    }
  }

  /**
   * Obtener historial de ventas con filtros.
   */
  public function getHistorialVentas(array $filters = []): mixed
  {
    $limit = $filters['limit'] ?? 15;
    $search = $filters['search'] ?? '';
    $sortBy = $filters['sortBy'] ?? 'fecha';
    $descending = ($filters['descending'] ?? 'true') === 'true' ? 'desc' : 'asc';
    $sucursalId = app()->bound('active_sucursal_id') ? app('active_sucursal_id') : null;

    $sortMap = [
      'fecha' => 'ventas.created_at',
      'id' => 'ventas.id',
      'total_venta' => 'ventas.total_venta',
      'subtotal' => 'ventas.subtotal',
      'monto_iva' => 'ventas.monto_iva',
      'metodo_pago' => 'ventas.metodo_pago',
      'status' => 'ventas.status',
      'correlativo' => 'ventas.correlativo'
    ];
    $sortBy = $sortMap[$sortBy] ?? 'ventas.created_at';

    $query = Venta::selectRaw("ventas.id, ventas.serie, ventas.correlativo, clientes.nombre as cliente, clientes.dui, sum(detalles_ventas.cantidad) as total_productos, ventas.total_venta, ventas.subtotal, ventas.monto_iva, ventas.porcentaje_iva_aplicado, ventas.metodo_pago, ventas.monto_recibido, ventas.vuelto, ventas.status, ventas.created_at as fecha, COALESCE(CONCAT(users.name, ' ', users.lastname),'Usuario Eliminado') as vendedor")
      ->join('users', 'users.id', '=', 'ventas.user_id', 'left')
      ->join('clientes', 'ventas.cliente_id', '=', 'clientes.id')
      ->join('detalles_ventas', 'ventas.id', '=', 'detalles_ventas.venta_id')
      ->groupBy('ventas.id', 'ventas.serie', 'ventas.correlativo', 'clientes.nombre', 'clientes.dui', 'ventas.total_venta', 'ventas.subtotal', 'ventas.monto_iva', 'ventas.porcentaje_iva_aplicado', 'ventas.metodo_pago', 'ventas.monto_recibido', 'ventas.vuelto', 'ventas.status', 'ventas.created_at', 'users.name', 'users.lastname')
      ->with(['detallesVenta', 'detallesVenta.producto']);

    if ($sucursalId) {
      $query->where('ventas.sucursal_id', $sucursalId);
    }

    $user = auth()->user();
    if ($user && !$user->hasRole('admin') && !$user->hasPermissionTo('ventas.ver_todo')) {
      $query->where('ventas.user_id', $user->id);
    }

    if (!empty($filters['metodo_pago'])) {
      $query->where('ventas.metodo_pago', $filters['metodo_pago']);
    }

    if (!empty($filters['status'])) {
      $query->where('ventas.status', $filters['status']);
    }

    if (!empty($filters['user_id'])) {
      $query->where('ventas.user_id', $filters['user_id']);
    }

    if (!empty($filters['monto_min'])) {
      $query->where('ventas.total_venta', '>=', (float)$filters['monto_min']);
    }

    if (!empty($filters['monto_max'])) {
      $query->where('ventas.total_venta', '<=', (float)$filters['monto_max']);
    }

    if (!empty($filters['fecha_desde']) && !empty($filters['fecha_hasta'])) {
      $start = \Carbon\Carbon::parse($filters['fecha_desde'])->startOfDay();
      $end = \Carbon\Carbon::parse($filters['fecha_hasta'])->endOfDay();
      $query->whereBetween('ventas.created_at', [$start, $end]);
    } elseif (!empty($filters['fecha_desde'])) {
      $start = \Carbon\Carbon::parse($filters['fecha_desde'])->startOfDay();
      $query->where('ventas.created_at', '>=', $start);
    } elseif (!empty($filters['fecha_hasta'])) {
      $end = \Carbon\Carbon::parse($filters['fecha_hasta'])->endOfDay();
      $query->where('ventas.created_at', '<=', $end);
    }

    if (!empty($search)) {
      $query->where(function ($q) use ($search) {
        $q->where('clientes.nombre', 'ILIKE', "%{$search}%")
          ->orWhereRaw("CONCAT(users.name, ' ', users.lastname) ILIKE ?", ["%{$search}%"])
          ->orWhereRaw("CAST(ventas.id AS TEXT) ILIKE ?", ["%{$search}%"])
          ->orWhere('ventas.correlativo', 'ILIKE', "%{$search}%")
          ->orWhere('clientes.dui', 'ILIKE', "%{$search}%");
      });
    }

    $query->orderBy($sortBy, $descending);

    if ($limit > 0) {
      return $query->paginate($limit);
    }

    return $query->get();
  }

  /**
   * Obtener una venta específica con relaciones.
   */
  public function getVenta(int $id): Venta
  {
    $venta = Venta::with(['cliente', 'user', 'sucursal', 'detallesVenta.producto'])->findOrFail($id);

    $user = auth()->user();
    if ($user && !$user->hasRole('admin') && !$user->hasPermissionTo('ventas.ver_todo') && $venta->user_id !== $user->id) {
      throw new \Exception('No autorizado para ver esta venta');
    }

    return $venta;
  }
}