<?php

namespace App\Http\Controllers;

use App\Models\Productos as Producto;
use App\Models\MovimientosInventario as MovimientoInventario;
use App\Models\Kardex;
use App\Helpers\InventoryValuator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventarioController extends Controller
{
    /**
     * Registrar un ajuste manual de inventario (Entrada / Salida).
     */
    public function registrarMovimiento(Request $request)
    {
        $sucursalId = app()->bound('active_sucursal_id') ? app('active_sucursal_id') : 1;

        $rules = [
            'producto_id' => 'required|exists:productos,id',
            'tipo' => 'required|in:entrada,salida',
            'cantidad' => 'required|integer|min:1',
            'costo_unitario' => 'nullable|numeric|min:0', // Requerido para entradas
            'razon' => 'required|string|max:300',
        ];

        $messages = [
            'producto_id.required' => 'El producto es requerido.',
            'producto_id.exists' => 'El producto seleccionado no existe o no es válido.',
            'tipo.required' => 'El tipo de movimiento es requerido.',
            'tipo.in' => 'El tipo de movimiento debe ser entrada o salida.',
            'cantidad.required' => 'La cantidad es requerida.',
            'cantidad.integer' => 'La cantidad debe ser un número entero.',
            'cantidad.min' => 'La cantidad debe ser al menos 1.',
            'costo_unitario.numeric' => 'El costo unitario debe ser un número válido.',
            'costo_unitario.min' => 'El costo unitario no puede ser negativo.',
            'razon.required' => 'La razón o motivo del movimiento es requerida.',
            'razon.max' => 'La razón no puede superar los 300 caracteres.',
        ];

        $request->validate($rules, $messages);

        try {
            DB::beginTransaction();
            $producto = Producto::lockForUpdate()->findOrFail($request->input('producto_id'));
            $cantidad = $request->input('cantidad');
            $tipo = $request->input('tipo');
            $razon = $request->input('razon');

            if ($tipo == 'entrada') {
                $costoUnitario = (float)$request->input('costo_unitario', 0.0);
                
                // Registrar entrada
                InventoryValuator::registerPurchase(
                    $producto,
                    $cantidad,
                    $costoUnitario,
                    $sucursalId,
                    'Ajuste entrada: ' . $razon,
                    0 // 0 indica ajuste, no asociado a una factura de compra
                );
            } else {
                $stockSucursal = DB::table('producto_sucursal')
                    ->where('producto_id', $producto->id)
                    ->where('sucursal_id', $sucursalId)
                    ->value('stock_actual') ?? 0;

                if ($stockSucursal < $cantidad) {
                    return response()->json(['message' => 'Stock insuficiente en esta sucursal para realizar el ajuste de salida.', 'status' => false], 400);
                }

                InventoryValuator::reduceStock(
                    $producto,
                    $cantidad,
                    $sucursalId,
                    'Ajuste salida: ' . $razon
                );
            }

            DB::commit();
            return response()->json(['status' => true, 'message' => 'Movimiento de inventario registrado con éxito']);

        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => $th->getMessage()]);
        }
    }

    /**
     * Buscar o listar razones únicas de movimientos para autocompletado y filtros.
     */
    public function searchRazones(Request $request)
    {
        $q = trim((string)$request->input('q', ''));
        $query = DB::table('movimientos_inventarios')
            ->select('razon')
            ->whereNotNull('razon')
            ->where('razon', '!=', '');

        if ($q !== '') {
            $query->where('razon', 'ILIKE', "%{$q}%");
        }

        $razones = $query->groupBy('razon')
            ->orderByRaw('COUNT(*) DESC')
            ->limit(30)
            ->pluck('razon');

        return response()->json(['data' => $razones, 'status' => true], 200);
    }

    /**
     * Mostrar movimientos de Kardex con filtros y paginación.
     */
    public function mostrarKardexTodos(Request $request)
    {
        $limit = $request->input('limit', 15);
        $search = $request->input('search', '');
        $sortBy = $request->input('sortBy', 'created_at');
        $descending = $request->input('descending', 'true') === 'true' ? 'desc' : 'asc';
        
        $sucursalId = app()->bound('active_sucursal_id') ? app('active_sucursal_id') : null;

        $sortMap = [
            'fecha' => 'kardexes.created_at',
            'created_at' => 'kardexes.created_at',
            'id' => 'kardexes.id'
        ];
        $sortBy = $sortMap[$sortBy] ?? 'kardexes.created_at';

        $kardexQuery = Kardex::select(
            'kardexes.*',
            'productos.sku as producto_sku',
            'productos.nombre as producto_name',
            'categorias.name as producto_categoria',
            'mi.razon as razon',
            'mi.tipo as tipo'
        )
            ->join('productos', 'productos.id', '=', 'kardexes.producto_id', 'left')
            ->join('categorias', 'categorias.id', '=', 'productos.categoria_id', 'left')
            ->leftJoin('movimientos_inventarios as mi', function ($join) {
                $join->on('mi.producto_id', '=', 'kardexes.producto_id')
                    ->on('mi.sucursal_id', '=', 'kardexes.sucursal_id')
                    ->on('mi.created_at', '=', 'kardexes.created_at')
                    ->on('mi.user_id', '=', 'kardexes.user_id');
            });       

        if ($sucursalId) {
            $kardexQuery->where('kardexes.sucursal_id', $sucursalId);
        }

        $user = auth()->user();
        if ($user && !$user->hasRole('admin') && !$user->hasPermissionTo('inventario.ver_todo')) {
            $kardexQuery->where('kardexes.user_id', $user->id);
        }

        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        if (!empty($startDate) && !empty($endDate)) {
            $start = \Carbon\Carbon::parse($startDate)->startOfDay();
            $end = \Carbon\Carbon::parse($endDate)->endOfDay();
            $kardexQuery->whereBetween('kardexes.created_at', [$start, $end]);
        } elseif (!empty($startDate)) {
            $start = \Carbon\Carbon::parse($startDate)->startOfDay();
            $kardexQuery->where('kardexes.created_at', '>=', $start);
        } elseif (!empty($endDate)) {
            $end = \Carbon\Carbon::parse($endDate)->endOfDay();
            $kardexQuery->where('kardexes.created_at', '<=', $end);
        }

        if ($request->filled('tipo')) {
            $tipo = $request->input('tipo');
            $kardexQuery->where(function ($q) use ($tipo) {
                $q->where('mi.tipo', $tipo);
                if ($tipo === 'salida') {
                    $q->orWhere(function ($sub) {
                        $sub->whereNull('mi.tipo')->where('kardexes.cantidad_movimiento', '<', 0);
                    });
                } elseif ($tipo === 'entrada') {
                    $q->orWhere(function ($sub) {
                        $sub->whereNull('mi.tipo')->where('kardexes.cantidad_movimiento', '>=', 0);
                    });
                }
            });
        }

        if ($request->filled('producto_id')) {
            $kardexQuery->where('kardexes.producto_id', $request->input('producto_id'));
        }

        if ($request->filled('razon')) {
            $razon = trim((string)$request->input('razon'));
            $kardexQuery->where('mi.razon', 'ILIKE', "%{$razon}%");
        }

        if (!empty($search)) {
            $kardexQuery->where(function($q) use ($search) {
                $q->where('productos.sku', 'ILIKE', "%{$search}%")
                  ->orWhere('productos.nombre', 'ILIKE', "%{$search}%")
                  ->orWhere('categorias.name', 'ILIKE', "%{$search}%")
                  ->orWhere('mi.razon', 'ILIKE', "%{$search}%");
            });
        }

        $kardexQuery->orderBy($sortBy, $descending);

        if ($limit > 0) {
            $movimientos = $kardexQuery->paginate($limit);
        } else {
            $movimientos = $kardexQuery->get();
        }

        $kardexData = $movimientos->map(function ($movimiento) {
            return [
                'producto_sku' => $movimiento->producto_sku ?? 'N/A',
                'producto_name' => $movimiento->producto_name ?? 'N/A',
                'producto_categoria' => $movimiento->producto_categoria ?? 'N/A',
                'cantidad_antes' => $movimiento->cantidad_antes,
                'costo_antes' => $movimiento->costo_antes,
                'cantidad_movimiento' => $movimiento->cantidad_movimiento,
                'costo_movimiento' => $movimiento->costo_movimiento,
                'cantidad_despues' => $movimiento->cantidad_despues,
                'costo_despues' => $movimiento->costo_despues,
                'tipo' => $movimiento->tipo ?? ($movimiento->cantidad_movimiento < 0 ? 'salida' : 'entrada'),
                'razon' => $movimiento->razon ?? ($movimiento->cantidad_movimiento < 0 ? 'Salida de inventario' : 'Entrada de inventario'),
                'fecha' => $movimiento->created_at,
            ];
        });

        if ($limit > 0) {
            $paginatedData = $movimientos->toArray();
            $paginatedData['data'] = $kardexData;
        } else {
            $paginatedData = ['data' => $kardexData, 'total' => count($kardexData)];
        }

        $productos = Producto::where('status', 1)->get(['id', 'nombre', 'sku']);

        return response()->json(['data' => $paginatedData, 'productos' => $productos], 200);
    }
}