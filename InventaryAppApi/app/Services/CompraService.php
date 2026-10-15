<?php

namespace App\Services;

use App\Models\Compras as Compra;
use App\Models\DetallesCompra as DetalleCompra;
use App\Models\Productos as Producto;
use App\Helpers\InventoryValuator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class CompraService
{
  /**
   * Registrar una nueva compra con lógica de negocio.
   */
  public function registrarCompra(array $data): array
  {
    $user = Auth::user();
    $sucursalId = app()->bound('active_sucursal_id') ? app('active_sucursal_id') : 1;

    DB::beginTransaction();

    try {
      $productosData = $data['productos'];
      $proveedorId = $data['proveedor_id'];

      $ivaEnabled = DB::table('settings')->where('key', 'iva_enabled')->value('value') === 'true';
      $ivaPercentage = $ivaEnabled ? (float) DB::table('settings')->where('key', 'iva_percentage')->value('value') : 0;

      $subtotalGlobal = 0;
      $montoIvaGlobal = 0;

      foreach ($productosData as $prodData) {
        $producto = Producto::findOrFail($prodData['producto_id']);
        $cantidad = $prodData['cantidad'];
        $precio = $prodData['precio_unitario'];

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
      }

      $totalCompra = $subtotalGlobal + $montoIvaGlobal;

      // Crear compra
      $compra = Compra::create([
        'total_compra' => $totalCompra,
        'subtotal' => $subtotalGlobal,
        'monto_iva' => $montoIvaGlobal,
        'porcentaje_iva_aplicado' => $ivaPercentage,
        'user_id' => $user->id,
        'sucursal_id' => $sucursalId,
        'proveedor_id' => $proveedorId,
        'status' => 'pagado',
        'numero_factura' => $data['numero_factura'] ?? null
      ]);

      foreach ($productosData as $productoData) {
        $producto = Producto::lockForUpdate()->findOrFail($productoData['producto_id']);
        $cantidad = $productoData['cantidad'];
        $precioCompra = $productoData['precio_unitario'];

        // Registrar detalle de compra con cantidad_restante = cantidad
        DetalleCompra::create([
          'compra_id' => $compra->id,
          'producto_id' => $producto->id,
          'cantidad' => $cantidad,
          'cantidad_restante' => $cantidad, // Para FIFO / LIFO
          'precio_compra' => $precioCompra,
        ]);

        // Registrar entrada de inventario a través de valuador
        InventoryValuator::registerPurchase(
          $producto,
          $cantidad,
          $precioCompra,
          $sucursalId,
          'Compra #C' . str_pad($compra->id, 8, '0', STR_PAD_LEFT),
          $compra->id
        );
      }

      Cache::forget('dashboard_metrics');
      Cache::forget('dashboard_metrics_v3');

      DB::commit();

      return [
        'status' => true,
        'message' => 'Compra registrada con éxito',
        'compra_id' => $compra->id
      ];

    } catch (\Throwable $th) {
      DB::rollBack();
      throw $th;
    }
  }

  /**
   * Cancelar una compra.
   */
  public function cancelarCompra(int $id): array
  {
    $compra = Compra::with('detallesCompra.producto')->findOrFail($id);

    if ($compra->status !== 'pagado') {
      throw new \Exception('Solo se pueden cancelar compras pagadas');
    }

    DB::beginTransaction();

    try {
      $compra->status = 'cancelada';
      $compra->save();

      // Descontar el stock agregado por la compra
      foreach ($compra->detallesCompra as $detalle) {
        $producto = $detalle->producto;

        // Restar el stock utilizando reduceStock
        InventoryValuator::reduceStock(
          $producto,
          $detalle->cantidad,
          $compra->sucursal_id,
          'Cancelación compra #C' . str_pad($compra->id, 8, '0', STR_PAD_LEFT)
        );

        // Marcar el lote de compra como agotado
        $detalle->cantidad_restante = 0;
        $detalle->save();
      }

      Cache::forget('dashboard_metrics');
      Cache::forget('dashboard_metrics_v3');

      DB::commit();

      return ['status' => true, 'message' => 'Compra cancelada con éxito'];

    } catch (\Throwable $th) {
      DB::rollBack();
      throw $th;
    }
  }

  /**
   * Obtener historial de compras con filtros.
   */
  public function getHistorialCompras(array $filters = []): mixed
  {
    $limit = $filters['limit'] ?? 15;
    $search = $filters['search'] ?? '';
    $sortBy = $filters['sortBy'] ?? 'fecha';
    $descending = ($filters['descending'] ?? 'true') === 'true' ? 'desc' : 'asc';
    $sucursalId = app()->bound('active_sucursal_id') ? app('active_sucursal_id') : null;

    $sortMap = [
      'fecha' => 'compras.created_at',
      'id' => 'compras.id',
      'total_compra' => 'compras.total_compra',
      'subtotal' => 'compras.subtotal',
      'monto_iva' => 'compras.monto_iva',
      'status' => 'compras.status',
      'numero_factura' => 'compras.numero_factura',
      'nombre_proveedor' => 'proveedors.nombre'
    ];
    $sortBy = $sortMap[$sortBy] ?? 'compras.created_at';

    $query = Compra::selectRaw("compras.id, proveedors.nombre as nombre_proveedor, proveedors.telefono as proveedor_telefono, proveedors.contacto as proveedor_contacto, compras.numero_factura, sum(detalles_compras.cantidad) as total_productos, compras.subtotal, compras.monto_iva, compras.porcentaje_iva_aplicado, compras.total_compra, compras.status, COALESCE(CONCAT(users.name, ' ', users.lastname),'Usuario Eliminado') as usuario, compras.created_at as fecha")
      ->join('users', 'users.id', '=', 'compras.user_id', 'left')
      ->join('proveedors', 'proveedors.id', '=', 'compras.proveedor_id', 'left')
      ->join('detalles_compras', 'compras.id', '=', 'detalles_compras.compra_id')
      ->groupBy('compras.id', 'proveedors.nombre', 'proveedors.telefono', 'proveedors.contacto', 'compras.numero_factura', 'compras.subtotal', 'compras.monto_iva', 'compras.porcentaje_iva_aplicado', 'compras.total_compra', 'compras.status', 'compras.created_at', 'users.name', 'users.lastname')
      ->with(['detallesCompra', 'detallesCompra.producto']);

    if ($sucursalId) {
      $query->where('compras.sucursal_id', $sucursalId);
    }

    $user = Auth::user();
    if ($user && !$user->hasRole('admin') && !$user->hasPermissionTo('compras.ver_todo')) {
      $query->where('compras.user_id', $user->id);
    }

    if (!empty($search)) {
      $query->where(function ($q) use ($search) {
        $q->where('proveedors.nombre', 'ILIKE', "%{$search}%")
          ->orWhere('compras.numero_factura', 'ILIKE', "%{$search}%")
          ->orWhereRaw("CAST(compras.id AS TEXT) ILIKE ?", ["%{$search}%"])
          ->orWhereRaw("CONCAT(users.name, ' ', users.lastname) ILIKE ?", ["%{$search}%"]);
      });
    }

    $query->orderBy($sortBy, $descending);

    if ($limit > 0) {
      return $query->paginate($limit);
    }

    return $query->get();
  }
}