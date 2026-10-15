<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Ventas;
use App\Models\Compras;
use App\Models\Productos;
use App\Models\Clientes;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportesController extends Controller
{
    /**
     * Resuelve el rango de fechas garantizando inclusión completa de inicio a fin del día.
     */
    private function resolveDateRange(Request $request): array
    {
        $startInput = $request->input('start_date');
        $endInput = $request->input('end_date');

        $start = $startInput ? Carbon::parse($startInput)->startOfDay() : Carbon::now()->startOfMonth()->startOfDay();
        $end = $endInput ? Carbon::parse($endInput)->endOfDay() : Carbon::now()->endOfDay();

        return [$start, $end];
    }

    /**
     * 1. Dashboard Metrics (Resumen general)
     */
    public function dashboardMetrics()
    {
        $sucursalId = app()->bound('active_sucursal_id') ? app('active_sucursal_id') : null;

        $startToday = Carbon::today()->startOfDay();
        $endToday = Carbon::today()->endOfDay();
        $thisMonth = Carbon::now()->startOfMonth()->startOfDay();
        $thisYear = Carbon::now()->startOfYear()->startOfDay();

        // Ventas Query
        $vqHoy = Ventas::with('detallesVenta')->whereBetween('created_at', [$startToday, $endToday])->where('status', 'pagada');
        $vqMes = Ventas::with('detallesVenta')->where('created_at', '>=', $thisMonth)->where('status', 'pagada');
        $vqAnio = Ventas::with('detallesVenta')->where('created_at', '>=', $thisYear)->where('status', 'pagada');

        if ($sucursalId) {
            $vqHoy->where('sucursal_id', $sucursalId);
            $vqMes->where('sucursal_id', $sucursalId);
            $vqAnio->where('sucursal_id', $sucursalId);
        }

        $ventasHoy = $vqHoy->sum('total_venta');
        $ventasMes = $vqMes->sum('total_venta');
        $ventasAnio = $vqAnio->sum('total_venta');

        // Ganancias/Utilidad aproximada
        $utilQuery = DB::table('detalles_ventas')
            ->join('ventas', 'ventas.id', '=', 'detalles_ventas.venta_id')
            ->join('productos', 'productos.id', '=', 'detalles_ventas.producto_id')
            ->where('ventas.status', 'pagada')
            ->where('ventas.created_at', '>=', $thisMonth);

        if ($sucursalId) {
            $utilQuery->where('ventas.sucursal_id', $sucursalId);
        }

        $utilidadMes = $utilQuery->sum(DB::raw('detalles_ventas.cantidad * (detalles_ventas.precio_venta - productos.costo_promedio)'));

        // Inventario
        $totalProductos = Productos::count();
        $stockBajo = Productos::where('stock', '<', 15)->where('status', 1)->count();

        // Top Clientes
        $clientQuery = Clientes::select('clientes.nombre', DB::raw('SUM(ventas.total_venta) as total_comprado'))
            ->join('ventas', 'ventas.cliente_id', '=', 'clientes.id')
            ->where('ventas.status', 'pagada');

        if ($sucursalId) {
            $clientQuery->where('ventas.sucursal_id', $sucursalId);
        }

        $topClientes = $clientQuery->groupBy('clientes.id', 'clientes.nombre')
            ->orderBy('total_comprado', 'desc')
            ->take(5)
            ->get();

        // Productos más vendidos
        $prodQuery = DB::table('detalles_ventas')
            ->select('productos.nombre', DB::raw('SUM(detalles_ventas.cantidad) as total_vendido'))
            ->join('productos', 'productos.id', '=', 'detalles_ventas.producto_id')
            ->join('ventas', 'ventas.id', '=', 'detalles_ventas.venta_id')
            ->where('ventas.status', 'pagada');

        if ($sucursalId) {
            $prodQuery->where('ventas.sucursal_id', $sucursalId);
        }

        $topProductos = $prodQuery->groupBy('productos.id', 'productos.nombre')
            ->orderBy('total_vendido', 'desc')
            ->take(5)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'ventas_hoy' => $ventasHoy,
                'ventas_mes' => $ventasMes,
                'ventas_anio' => $ventasAnio,
                'utilidad_mes' => $utilidadMes,
                'total_productos' => $totalProductos,
                'stock_bajo' => $stockBajo,
                'top_clientes' => $topClientes,
                'top_productos' => $topProductos
            ]
        ], 200);
    }

    /**
     * Helper para exportar a CSV de forma nativa e instantánea.
     */
    private function exportToCsv($filename, $headers, $data)
    {
        $callback = function () use ($headers, $data) {
            $file = fopen('php://output', 'w');
            // Forzar codificación UTF-8
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($file, $headers);
            foreach ($data as $row) {
                fputcsv($file, (array) $row);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, [
            "Content-type" => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename={$filename}.csv",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ]);
    }

    /**
     * 2. Ventas por Sucursal
     */
    public function ventasPorSucursal(Request $request)
    {
        [$start, $end] = $this->resolveDateRange($request);

        $data = DB::table('ventas')
            ->join('sucursals', 'sucursals.id', '=', 'ventas.sucursal_id')
            ->where('ventas.status', 'pagada')
            ->whereBetween('ventas.created_at', [$start, $end])
            ->selectRaw('sucursals.nombre as sucursal, COUNT(ventas.id) as cantidad_transacciones, SUM(ventas.subtotal) as subtotal, SUM(ventas.monto_iva) as iva, SUM(ventas.total_venta) as total')
            ->groupBy('sucursals.id', 'sucursals.nombre')
            ->get();

        if ($request->input('export') === 'csv') {
            return $this->exportToCsv('ventas_por_sucursal', ['Sucursal', 'Transacciones', 'Subtotal', 'IVA', 'Total'], $data);
        }

        return response()->json(['success' => true, 'data' => $data]);
    }

    /**
     * 3. Ventas por Vendedor (Performance)
     */
    public function ventasPorVendedor(Request $request)
    {
        [$start, $end] = $this->resolveDateRange($request);
        $sucursalId = app()->bound('active_sucursal_id') ? app('active_sucursal_id') : null;

        $query = DB::table('ventas')
            ->join('users', 'users.id', '=', 'ventas.user_id')
            ->where('ventas.status', 'pagada')
            ->whereBetween('ventas.created_at', [$start, $end]);

        if ($sucursalId) {
            $query->where('ventas.sucursal_id', $sucursalId);
        }

        $data = $query->selectRaw("CONCAT(users.name, ' ', COALESCE(users.lastname, '')) as vendedor, COUNT(ventas.id) as transacciones, SUM(ventas.total_venta) as total")
            ->groupBy('users.id', 'users.name', 'users.lastname')
            ->get();

        if ($request->input('export') === 'csv') {
            return $this->exportToCsv('ventas_por_vendedor', ['Vendedor', 'Transacciones', 'Total Vendido'], $data);
        }

        return response()->json(['success' => true, 'data' => $data]);
    }

    /**
     * 4. Rentabilidad y Margen de Productos
     */
    public function rentabilidadProductos(Request $request)
    {
        [$start, $end] = $this->resolveDateRange($request);
        $sucursalId = app()->bound('active_sucursal_id') ? app('active_sucursal_id') : null;

        $query = DB::table('detalles_ventas')
            ->join('ventas', 'ventas.id', '=', 'detalles_ventas.venta_id')
            ->join('productos', 'productos.id', '=', 'detalles_ventas.producto_id')
            ->where('ventas.status', 'pagada')
            ->whereBetween('ventas.created_at', [$start, $end]);

        if ($sucursalId) {
            $query->where('ventas.sucursal_id', $sucursalId);
        }

        $data = $query->selectRaw('productos.sku, productos.nombre as producto, SUM(detalles_ventas.cantidad) as unidades_vendidas, SUM(detalles_ventas.cantidad * detalles_ventas.precio_venta) as ingresos, SUM(detalles_ventas.cantidad * productos.costo_promedio) as costo_total, (SUM(detalles_ventas.cantidad * detalles_ventas.precio_venta) - SUM(detalles_ventas.cantidad * productos.costo_promedio)) as utilidad, ROUND(((SUM(detalles_ventas.cantidad * detalles_ventas.precio_venta) - SUM(detalles_ventas.cantidad * productos.costo_promedio)) / NULLIF(SUM(detalles_ventas.cantidad * detalles_ventas.precio_venta), 0)) * 100, 2) as margen_porcentaje')
            ->groupBy('productos.id', 'productos.sku', 'productos.nombre')
            ->orderBy('utilidad', 'desc')
            ->get();

        if ($request->input('export') === 'csv') {
            return $this->exportToCsv('rentabilidad_productos', ['SKU', 'Producto', 'Unidades Vendidas', 'Ingresos', 'Costo Total', 'Utilidad', 'Margen (%)'], $data);
        }

        return response()->json(['success' => true, 'data' => $data]);
    }

    /**
     * 5. Rotación de Inventario
     */
    public function rotacionInventario(Request $request)
    {
        [$start, $end] = $this->resolveDateRange($request);
        $sucursalId = app()->bound('active_sucursal_id') ? app('active_sucursal_id') : null;

        $query = DB::table('productos')
            ->leftJoin('detalles_ventas', 'productos.id', '=', 'detalles_ventas.producto_id')
            ->leftJoin('ventas', function ($join) use ($start, $end, $sucursalId) {
                $join->on('ventas.id', '=', 'detalles_ventas.venta_id')
                    ->where('ventas.status', 'pagada')
                    ->whereBetween('ventas.created_at', [$start, $end]);
                if ($sucursalId) {
                    $join->where('ventas.sucursal_id', $sucursalId);
                }
            });

        $data = $query->selectRaw('productos.sku, productos.nombre as producto, productos.stock as stock_actual, productos.costo_promedio, COALESCE(SUM(detalles_ventas.cantidad), 0) as unidades_vendidas, COALESCE(SUM(detalles_ventas.cantidad * productos.costo_promedio), 0) as costo_articulos_vendidos, COALESCE(ROUND(COALESCE(SUM(detalles_ventas.cantidad), 0)::numeric / NULLIF(productos.stock, 0), 2), 0.00) as ratio_rotacion')
            ->groupBy('productos.id', 'productos.sku', 'productos.nombre', 'productos.stock', 'productos.costo_promedio')
            ->orderBy('ratio_rotacion', 'desc')
            ->orderBy('unidades_vendidas', 'desc')
            ->get();

        if ($request->input('export') === 'csv') {
            $method = \App\Helpers\InventoryValuator::getValuationMethod();
            $costoLabel = $method === 'fifo' ? 'Costo PEPS' : ($method === 'lifo' ? 'Costo UEPS' : 'Costo Promedio');
            return $this->exportToCsv('rotacion_inventario', ['SKU', 'Producto', 'Stock Actual', $costoLabel, 'Uds Vendidas', 'Costo Venta (COGS)', 'Ratio Rotación'], $data);
        }

        return response()->json(['success' => true, 'data' => $data]);
    }

    /**
     * 6. Compras por Proveedor
     */
    public function comprasPorProveedor(Request $request)
    {
        [$start, $end] = $this->resolveDateRange($request);
        $sucursalId = app()->bound('active_sucursal_id') ? app('active_sucursal_id') : null;

        $query = DB::table('compras')
            ->join('proveedors', 'proveedors.id', '=', 'compras.proveedor_id')
            ->where('compras.status', 'pagado')
            ->whereBetween('compras.created_at', [$start, $end]);

        if ($sucursalId) {
            $query->where('compras.sucursal_id', $sucursalId);
        }

        $data = $query->selectRaw('proveedors.nombre as proveedor, proveedors.nit, COUNT(compras.id) as compras_realizadas, SUM(compras.subtotal) as subtotal, SUM(compras.total_compra) as total')
            ->groupBy('proveedors.id', 'proveedors.nombre', 'proveedors.nit')
            ->get();

        if ($request->input('export') === 'csv') {
            return $this->exportToCsv('compras_por_proveedor', ['Proveedor', 'NIT/ID', 'Compras Realizadas', 'Subtotal', 'Total Compra'], $data);
        }

        return response()->json(['success' => true, 'data' => $data]);
    }

    /**
     * 7. Alertas de Stock Mínimo (Críticos)
     */
    public function alertasStockMinimo(Request $request)
    {
        $threshold = max(1, (int)$request->input('min_stock', 15));

        $data = DB::table('productos')
            ->leftJoin('categorias', 'categorias.id', '=', 'productos.categoria_id')
            ->where('productos.stock', '<', $threshold)
            ->where('productos.status', 1)
            ->selectRaw("productos.sku, productos.nombre as producto, COALESCE(categorias.name, 'Sin Categoría') as categoria, productos.stock, productos.costo_promedio, productos.precio_venta")
            ->orderBy('productos.stock', 'asc')
            ->get();

        if ($request->input('export') === 'csv') {
            return $this->exportToCsv('alertas_stock_minimo', ['SKU', 'Producto', 'Categoría', 'Stock Actual', 'Costo Unitario', 'Precio Venta'], $data);
        }

        return response()->json(['success' => true, 'data' => $data]);
    }

    /**
     * 8. Libro / Reporte de Impuestos (IVA)
     */
    public function impuestosIva(Request $request)
    {
        [$start, $end] = $this->resolveDateRange($request);
        $sucursalId = app()->bound('active_sucursal_id') ? app('active_sucursal_id') : null;

        // IVA Debito (Ventas)
        $ventasQuery = DB::table('ventas')
            ->where('status', 'pagada')
            ->whereBetween('created_at', [$start, $end]);
        if ($sucursalId) {
            $ventasQuery->where('sucursal_id', $sucursalId);
        }
        $ventasIva = $ventasQuery->selectRaw('SUM(subtotal) as base_ventas, SUM(monto_iva) as iva_debito, SUM(total_venta) as total_ventas')->first();

        // IVA Credito (Compras)
        $comprasQuery = DB::table('compras')
            ->where('status', 'pagado')
            ->whereBetween('created_at', [$start, $end]);
        if ($sucursalId) {
            $comprasQuery->where('sucursal_id', $sucursalId);
        }
        $comprasIva = $comprasQuery->selectRaw('SUM(subtotal) as base_compras, SUM(monto_iva) as iva_credito, SUM(total_compra) as total_compras')->first();

        $balance = ($ventasIva->iva_debito ?? 0) - ($comprasIva->iva_credito ?? 0);

        $data = [
            [
                'base_ventas' => $ventasIva->base_ventas ?? 0,
                'iva_debito' => $ventasIva->iva_debito ?? 0,
                'base_compras' => $comprasIva->base_compras ?? 0,
                'iva_credito' => $comprasIva->iva_credito ?? 0,
                'iva_por_pagar' => $balance
            ]
        ];

        if ($request->input('export') === 'csv') {
            return $this->exportToCsv('libro_iva', ['Base Ventas', 'IVA Débito (Ventas)', 'Base Compras', 'IVA Crédito (Compras)', 'IVA Balance (a Pagar / Saldo a Favor)'], $data);
        }

        return response()->json(['success' => true, 'data' => $data[0]]);
    }

    /**
     * 9. Ventas por Categoría
     */
    public function ventasPorCategoria(Request $request)
    {
        [$start, $end] = $this->resolveDateRange($request);
        $sucursalId = app()->bound('active_sucursal_id') ? app('active_sucursal_id') : null;

        $query = DB::table('detalles_ventas')
            ->join('ventas', 'ventas.id', '=', 'detalles_ventas.venta_id')
            ->join('productos', 'productos.id', '=', 'detalles_ventas.producto_id')
            ->leftJoin('categorias', 'categorias.id', '=', 'productos.categoria_id')
            ->where('ventas.status', 'pagada')
            ->whereBetween('ventas.created_at', [$start, $end]);

        if ($sucursalId) {
            $query->where('ventas.sucursal_id', $sucursalId);
        }

        $data = $query->selectRaw("COALESCE(categorias.name, 'Sin Categoría') as categoria, COUNT(DISTINCT ventas.id) as transacciones, SUM(detalles_ventas.cantidad) as productos_vendidos, SUM(detalles_ventas.cantidad * detalles_ventas.precio_venta) as total_venta")
            ->groupBy('categorias.id', 'categorias.name')
            ->orderBy('total_venta', 'desc')
            ->get();

        if ($request->input('export') === 'csv') {
            return $this->exportToCsv('ventas_por_categoria', ['Categoría', 'Transacciones', 'Productos Vendidos', 'Total Ventas'], $data);
        }

        return response()->json(['success' => true, 'data' => $data]);
    }

    /**
     * 10. Kardex Consolidado (Auditoría de Movimientos)
     */
    public function kardexConsolidado(Request $request)
    {
        [$start, $end] = $this->resolveDateRange($request);
        $sucursalId = app()->bound('active_sucursal_id') ? app('active_sucursal_id') : null;

        $query = DB::table('kardexes')
            ->join('productos', 'productos.id', '=', 'kardexes.producto_id')
            ->whereBetween('kardexes.created_at', [$start, $end]);

        if ($sucursalId) {
            $query->where('kardexes.sucursal_id', $sucursalId);
        }

        $data = $query->selectRaw('kardexes.created_at as fecha, productos.sku, productos.nombre as producto, kardexes.cantidad_antes, kardexes.cantidad_movimiento, kardexes.costo_movimiento, kardexes.cantidad_despues, kardexes.costo_despues')
            ->orderBy('kardexes.created_at', 'desc')
            ->get();

        if ($request->input('export') === 'csv') {
            $method = \App\Helpers\InventoryValuator::getValuationMethod();
            $costoLabel = $method === 'fifo' ? 'Costo Post (PEPS)' : ($method === 'lifo' ? 'Costo Post (UEPS)' : 'Costo Prom Posterior');
            return $this->exportToCsv('kardex_consolidado', ['Fecha/Hora', 'SKU', 'Producto', 'Stock Anterior', 'Movimiento', 'Costo Unitario Mov', 'Stock Posterior', $costoLabel], $data);
        }

        return response()->json(['success' => true, 'data' => $data]);
    }
}