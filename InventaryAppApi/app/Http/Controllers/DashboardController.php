<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Ventas;
use App\Models\Compras;
use App\Models\Productos;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $isAdmin = $user && $user->hasRole('admin');

        $sucursalId = null;
        if (!$isAdmin) {
            $sucursalId = app()->bound('active_sucursal_id')
                ? app('active_sucursal_id')
                : ($user->sucursal_id ?? 1);
        }

        $cacheKey = $isAdmin
            ? 'dashboard_metrics_admin_v5'
            : "dashboard_metrics_branch_{$sucursalId}_v5";

        $data = Cache::remember($cacheKey, 60, function () use ($isAdmin, $sucursalId) {
            $salesQuery = Ventas::where('status', 'pagada');
            $purchasesQuery = Compras::where('status', 'pagado');

            if (!$isAdmin) {
                $salesQuery->where('sucursal_id', $sucursalId);
                $purchasesQuery->where('sucursal_id', $sucursalId);
            }

            $salesLastDay = (clone $salesQuery)->where('created_at', '>=', now()->subDay())->sum('total_venta');
            $salesLast7Days = (clone $salesQuery)->where('created_at', '>=', now()->subDays(7))->sum('total_venta');
            $salesLastMonth = (clone $salesQuery)->where('created_at', '>=', now()->subMonth())->sum('total_venta');

            $purchasesLastDay = (clone $purchasesQuery)->where('created_at', '>=', now()->subDay())->sum('total_compra');
            $purchasesLast7Days = (clone $purchasesQuery)->where('created_at', '>=', now()->subDays(7))->sum('total_compra');
            $purchasesLastMonth = (clone $purchasesQuery)->where('created_at', '>=', now()->subMonth())->sum('total_compra');

            $lastSalesQuery = Ventas::selectRaw("ventas.id, clientes.nombre as cliente, clientes.dui, sum(detalles_ventas.cantidad) as total_productos, ventas.total_venta, ventas.status, ventas.created_at as fecha, COALESCE(CONCAT(users.name, ' ', users.lastname),'Usuario Eliminado') as vendedor")
                ->join('users', 'users.id', '=', 'ventas.user_id', 'left')
                ->join('clientes', 'ventas.cliente_id', '=', 'clientes.id')
                ->join('detalles_ventas', 'ventas.id', '=', 'detalles_ventas.venta_id');

            if (!$isAdmin) {
                $lastSalesQuery->where('ventas.sucursal_id', $sucursalId);
            }

            $lastSales = $lastSalesQuery
                ->groupBy('ventas.id', 'clientes.nombre', 'clientes.dui', 'ventas.total_venta', 'ventas.status', 'ventas.created_at', 'users.name', 'users.lastname')
                ->with('detallesVenta', 'detallesVenta.producto')
                ->orderBy('ventas.created_at', 'desc')
                ->take(10)
                ->get();

            $method = \App\Helpers\InventoryValuator::getValuationMethod();

            if ($isAdmin) {
                $totalAgotados = Productos::where('stock', '<=', 0)->where('status', 1)->count();
                $totalPorAgotarse = Productos::where('stock', '>', 0)->where('stock', '<', 15)->where('status', 1)->count();
                $lowStockProducts = Productos::where('stock', '<=', 15)
                    ->where('status', 1)
                    ->orderBy('stock', 'asc')
                    ->take(8)
                    ->get(['id', 'nombre', 'sku', 'stock']);
            } else {
                $totalAgotados = \Illuminate\Support\Facades\DB::table('productos')
                    ->where('productos.status', 1)
                    ->whereRaw("(SELECT COALESCE(stock_actual, 0) FROM producto_sucursal WHERE producto_id = productos.id AND sucursal_id = ?) <= 0", [$sucursalId])
                    ->count();

                $totalPorAgotarse = \Illuminate\Support\Facades\DB::table('productos')
                    ->where('productos.status', 1)
                    ->whereRaw("(SELECT COALESCE(stock_actual, 0) FROM producto_sucursal WHERE producto_id = productos.id AND sucursal_id = ?) > 0", [$sucursalId])
                    ->whereRaw("(SELECT COALESCE(stock_actual, 0) FROM producto_sucursal WHERE producto_id = productos.id AND sucursal_id = ?) < 15", [$sucursalId])
                    ->count();

                $lowStockProducts = Productos::select('productos.id', 'productos.nombre', 'productos.sku')
                    ->selectSub(function ($q) use ($sucursalId) {
                        $q->selectRaw('COALESCE(stock_actual, 0)')
                            ->from('producto_sucursal')
                            ->whereColumn('producto_id', 'productos.id')
                            ->where('sucursal_id', $sucursalId)
                            ->limit(1);
                    }, 'stock')
                    ->where('productos.status', 1)
                    ->whereRaw("(SELECT COALESCE(stock_actual, 0) FROM producto_sucursal WHERE producto_id = productos.id AND sucursal_id = ?) <= 15", [$sucursalId])
                    ->orderBy('stock', 'asc')
                    ->take(8)
                    ->get();
            }

            $sucursalNombre = null;
            if (!$isAdmin && $sucursalId) {
                $sucursalNombre = \App\Models\Sucursal::where('id', $sucursalId)->value('nombre');
            }

            return compact(
                'salesLastDay',
                'salesLast7Days',
                'salesLastMonth',
                'purchasesLastDay',
                'purchasesLast7Days',
                'purchasesLastMonth',
                'lastSales',
                'totalAgotados',
                'totalPorAgotarse',
                'lowStockProducts',
                'method',
                'isAdmin',
                'sucursalId',
                'sucursalNombre'
            );
        });

        return response()->json($data);
    }

    public function lowStock(Request $request)
    {
        $user = $request->user();
        $isAdmin = $user && $user->hasRole('admin');

        $limit = (int) $request->input('limit', 10);
        $sortBy = $request->input('sortBy', 'stock');
        $descending = $request->input('descending', 'false') === 'true' ? 'desc' : 'asc';
        $search = $request->input('search', '');

        $allowedSorts = ['id', 'sku', 'nombre', 'stock', 'precio_venta', 'costo_promedio', 'created_at'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'stock';
        }

        $method = \App\Helpers\InventoryValuator::getValuationMethod();

        if ($isAdmin) {
            $query = Productos::select('productos.*')
                ->with(['categoria', 'subcategoria'])
                ->where('stock', '<', 15)
                ->where('status', 1);

            if ($method === 'fifo') {
                $query->selectRaw("COALESCE((SELECT dc.precio_compra FROM detalles_compras dc JOIN compras c ON c.id = dc.compra_id WHERE dc.producto_id = productos.id AND dc.cantidad_restante > 0 AND c.status = 'pagado' ORDER BY dc.created_at ASC, dc.id ASC LIMIT 1), productos.costo_promedio) as costo_actual");
            } elseif ($method === 'lifo') {
                $query->selectRaw("COALESCE((SELECT dc.precio_compra FROM detalles_compras dc JOIN compras c ON c.id = dc.compra_id WHERE dc.producto_id = productos.id AND dc.cantidad_restante > 0 AND c.status = 'pagado' ORDER BY dc.created_at DESC, dc.id DESC LIMIT 1), productos.costo_promedio) as costo_actual");
            } else {
                $query->selectRaw('productos.costo_promedio as costo_actual');
            }
        } else {
            $sucursalId = app()->bound('active_sucursal_id')
                ? app('active_sucursal_id')
                : ($user->sucursal_id ?? 1);

            $query = Productos::select('productos.*')
                ->selectSub(function ($q) use ($sucursalId) {
                    $q->selectRaw('COALESCE(stock_actual, 0)')
                        ->from('producto_sucursal')
                        ->whereColumn('producto_id', 'productos.id')
                        ->where('sucursal_id', $sucursalId)
                        ->limit(1);
                }, 'stock')
                ->with(['categoria', 'subcategoria'])
                ->whereRaw("(SELECT COALESCE(stock_actual, 0) FROM producto_sucursal WHERE producto_id = productos.id AND sucursal_id = ?) < 15", [$sucursalId])
                ->where('productos.status', 1);

            if ($method === 'fifo') {
                $query->selectRaw("COALESCE((SELECT dc.precio_compra FROM detalles_compras dc JOIN compras c ON c.id = dc.compra_id WHERE dc.producto_id = productos.id AND dc.cantidad_restante > 0 AND c.status = 'pagado' AND c.sucursal_id = " . (int)$sucursalId . " ORDER BY dc.created_at ASC, dc.id ASC LIMIT 1), productos.costo_promedio) as costo_actual");
            } elseif ($method === 'lifo') {
                $query->selectRaw("COALESCE((SELECT dc.precio_compra FROM detalles_compras dc JOIN compras c ON c.id = dc.compra_id WHERE dc.producto_id = productos.id AND dc.cantidad_restante > 0 AND c.status = 'pagado' AND c.sucursal_id = " . (int)$sucursalId . " ORDER BY dc.created_at DESC, dc.id DESC LIMIT 1), productos.costo_promedio) as costo_actual");
            } else {
                $query->selectRaw('productos.costo_promedio as costo_actual');
            }
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'ILIKE', "%{$search}%")
                  ->orWhere('sku', 'ILIKE', "%{$search}%");
            });
        }

        if ($isAdmin) {
            $query->orderBy("productos.{$sortBy}", $descending);
        } else {
            if ($sortBy === 'stock') {
                $query->orderByRaw("(SELECT COALESCE(stock_actual, 0) FROM producto_sucursal WHERE producto_id = productos.id AND sucursal_id = " . (int)$sucursalId . " LIMIT 1) {$descending}");
            } else {
                $query->orderBy("productos.{$sortBy}", $descending);
            }
        }

        if ($limit > 0) {
            $productos = $query->paginate($limit);
        } else {
            $productos = $query->get();
        }

        return response()->json([
            'success' => true,
            'metodo_valuacion' => $method,
            'is_admin' => $isAdmin,
            'data' => $productos,
        ]);
    }
}