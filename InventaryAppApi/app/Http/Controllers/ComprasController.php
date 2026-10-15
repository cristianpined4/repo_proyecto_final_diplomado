<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\StoreCompraRequest;
use App\Models\Productos as Producto;
use App\Models\Compras as Compra;
use App\Models\DetallesCompra as DetalleCompra;
use App\Helpers\InventoryValuator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ComprasController extends Controller
{
    use AuthorizesRequests;
    /**
     * Catálogo inicial de productos para realizar compras.
     */
    public function index()
    {
        $productos = Producto::where('status', 1)->paginate(100);
        return response()->json(['productos' => $productos, 'status' => true], 200);
    }

    /**
     * Registrar una nueva compra.
     */
    public function store(StoreCompraRequest $request)
    {
        $this->authorize('create', Compra::class);

        $user = Auth::user();
        $sucursalId = app()->bound('active_sucursal_id') ? app('active_sucursal_id') : 1;

        try {
            DB::beginTransaction();
            $productosData = $request->input('productos');
            $proveedorId = $request->input('proveedor_id');

            $valIva = DB::table('settings')->where('key', 'iva_enabled')->value('value');
            $ivaEnabled = filter_var($valIva, FILTER_VALIDATE_BOOLEAN);
            $ivaPercentage = $ivaEnabled ? (float) DB::table('settings')->where('key', 'iva_percentage')->value('value') : 0;

            $subtotalGlobal = 0;
            $montoIvaGlobal = 0;

            foreach ($productosData as $prodData) {
                $producto = Producto::findOrFail($prodData['producto_id']);
                $cantidad = $prodData['cantidad'];
                $precio = $prodData['precio_unitario'];
                $totalLinea = $cantidad * $precio;

                if ($ivaEnabled) {
                    if ($producto->aplica_iva) {
                        // TRUE: El precio incluye IVA. Desglosar.
                        $subtotalLinea = $totalLinea / (1 + ($ivaPercentage / 100));
                        $ivaLinea = $totalLinea - $subtotalLinea;
                    } else {
                        // FALSE: El precio no incluye IVA. Sumar.
                        $subtotalLinea = $totalLinea;
                        $ivaLinea = $subtotalLinea * ($ivaPercentage / 100);
                    }
                } else {
                    $subtotalLinea = $totalLinea;
                    $ivaLinea = 0;
                }

                $subtotalGlobal += $subtotalLinea;
                $montoIvaGlobal += $ivaLinea;
            }

            $subtotalGlobal = round($subtotalGlobal, 2);
            $montoIvaGlobal = round($montoIvaGlobal, 2);
            $totalCompra = round($subtotalGlobal + $montoIvaGlobal, 2);

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
                'numero_factura' => $request->input('numero_factura')
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

            \Illuminate\Support\Facades\Cache::forget('dashboard_metrics');
            DB::commit();

            return response()->json(['status' => true, 'message' => 'Compra registrada con éxito'], 200);

        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => $th->getMessage()], 500);
        }
    }

    /**
     * Ver historial general de compras.
     */
    public function showAllCompras(Request $request)
    {
        $limit = $request->input('limit', 15);
        $search = $request->input('search', '');
        $sortBy = $request->input('sortBy', 'fecha');
        $descending = $request->input('descending', 'true') === 'true' ? 'desc' : 'asc';

        $sucursalId = app()->bound('active_sucursal_id') ? app('active_sucursal_id') : null;

        $sortMap = [
            'fecha' => 'compras.created_at',
            'id' => 'compras.id',
            'total_compra' => 'compras.total_compra',
            'subtotal' => 'compras.subtotal',
            'monto_iva' => 'compras.monto_iva',
            'status' => 'compras.status'
        ];
        $sortBy = $sortMap[$sortBy] ?? 'compras.created_at';

        $query = Compra::selectRaw("compras.id, proveedors.nombre as nombre_proveedor, proveedors.contacto as proveedor_contacto, proveedors.telefono as proveedor_telefono, compras.numero_factura, sum(detalles_compras.cantidad) as total_productos, compras.total_compra, compras.subtotal, compras.monto_iva, compras.porcentaje_iva_aplicado, compras.status, COALESCE(CONCAT(users.name, ' ', users.lastname),'Usuario Eliminado') as usuario, compras.created_at as fecha")
            ->join('users', 'users.id', '=', 'compras.user_id', 'left')
            ->join('proveedors', 'proveedors.id', '=', 'compras.proveedor_id', 'left')
            ->join('detalles_compras', 'compras.id', '=', 'detalles_compras.compra_id')
            ->groupBy(
                'compras.id',
                'proveedors.nombre',
                'proveedors.contacto',
                'proveedors.telefono',
                'compras.numero_factura',
                'compras.total_compra',
                'compras.subtotal',
                'compras.monto_iva',
                'compras.porcentaje_iva_aplicado',
                'compras.status',
                'compras.created_at',
                'users.name',
                'users.lastname'
            )
            ->with('detallesCompra', 'detallesCompra.producto');

        if ($sucursalId) {
            $query->where('compras.sucursal_id', $sucursalId);
        }

        $user = auth()->user();
        if ($user && !$user->hasRole('admin') && !$user->hasPermissionTo('compras.ver_todo')) {
            $query->where('compras.user_id', $user->id);
        }

        if ($request->filled('status')) {
            $query->where('compras.status', $request->input('status'));
        }

        if ($request->filled('proveedor_id')) {
            $query->where('compras.proveedor_id', $request->input('proveedor_id'));
        }

        if ($request->filled('monto_min')) {
            $query->where('compras.total_compra', '>=', (float)$request->input('monto_min'));
        }

        if ($request->filled('monto_max')) {
            $query->where('compras.total_compra', '<=', (float)$request->input('monto_max'));
        }

        $startDate = $request->input('fecha_desde');
        $endDate = $request->input('fecha_hasta');

        if (!empty($startDate) && !empty($endDate)) {
            $start = \Carbon\Carbon::parse($startDate)->startOfDay();
            $end = \Carbon\Carbon::parse($endDate)->endOfDay();
            $query->whereBetween('compras.created_at', [$start, $end]);
        } elseif (!empty($startDate)) {
            $start = \Carbon\Carbon::parse($startDate)->startOfDay();
            $query->where('compras.created_at', '>=', $start);
        } elseif (!empty($endDate)) {
            $end = \Carbon\Carbon::parse($endDate)->endOfDay();
            $query->where('compras.created_at', '<=', $end);
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
            $compras = $query->paginate($limit);
        } else {
            $compras = $query->get();
        }

        return response()->json(['data' => $compras], 200);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $compra = Compra::with(['proveedor', 'user', 'sucursal', 'detallesCompra.producto'])->findOrFail($id);

        $user = auth()->user();
        if ($user && !$user->hasRole('admin') && !$user->hasPermissionTo('compras.ver_todo') && $compra->user_id !== $user->id) {
            return response()->json(['message' => 'No autorizado para ver esta compra', 'status' => false], 403);
        }

        return response()->json(['data' => $compra, 'status' => true], 200);
    }

    /**
     * Cancelar una compra.
     */
    public function cancelarCompra($id)
    {
        try {
            DB::beginTransaction();
            $compra = Compra::with('detallesCompra.producto')->find($id);

            if (!$compra) {
                return response()->json(['message' => 'Compra no encontrada', 'status' => false], 404);
            }

            $user = auth()->user();
            if ($user && !$user->hasRole('admin') && !$user->hasPermissionTo('compras.ver_todo') && $compra->user_id !== $user->id) {
                return response()->json(['message' => 'No autorizado para cancelar esta compra', 'status' => false], 403);
            }

            if ($compra->status !== 'pagado') {
                return response()->json(['message' => 'Solo se pueden cancelar compras pagadas', 'status' => false], 400);
            }

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

            \Illuminate\Support\Facades\Cache::forget('dashboard_metrics');
            DB::commit();

            return response()->json(['message' => 'Compra cancelada con éxito', 'status' => true], 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(['message' => $th->getMessage(), 'status' => false], 500);
        }
    }
}