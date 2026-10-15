<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\StoreVentaRequest;
use App\Models\Ventas as Venta;
use App\Models\Clientes;
use App\Models\Productos as Producto;
use App\Services\VentaService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class VentasController extends Controller
{
    use AuthorizesRequests;
    /**
     * Devuelve catálogo inicial para realizar ventas.
     */
    public function index()
    {
        $sucursalId = app()->bound('active_sucursal_id') ? app('active_sucursal_id') : 1;

        $productos = Producto::select('productos.*')
            ->selectSub(function ($q) use ($sucursalId) {
                $q->selectRaw('COALESCE(stock_actual, 0)')
                    ->from('producto_sucursal')
                    ->whereColumn('producto_id', 'productos.id')
                    ->where('sucursal_id', $sucursalId)
                    ->limit(1);
            }, 'stock')
            ->where('status', 1)
            ->paginate(100);

        $clientes = Clientes::where('status', 1)->paginate(100);

        return response()->json(['productos' => $productos, 'clientes' => $clientes, 'status' => true], 200);
    }

    /**
     * Registrar una nueva venta.
     */
    public function store(StoreVentaRequest $request)
    {
        $this->authorize('create', Venta::class);

        $ventaService = new VentaService();

        try {
            $result = $ventaService->registrarVenta($request->all());

            return response()->json($result, 200);

        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'message' => $th->getMessage()], 500);
        }
    }

    /**
     * Anular una venta y restaurar stock.
     */
    public function anular(Request $request, $id)
    {
        $ventaService = new VentaService();

        try {
            $result = $ventaService->anularVenta($id);

            return response()->json($result, 200);
        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'message' => $th->getMessage()], 500);
        }
    }

    /**
     * Registrar devolución de una venta.
     */
    public function devolucion(Request $request, $id)
    {
        $ventaService = new VentaService();

        try {
            $result = $ventaService->devolverVenta($id);

            return response()->json($result, 200);
        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'message' => $th->getMessage()], 500);
        }
    }

    /**
     * Ver historial general de ventas.
     */
    public function showAllVentas(Request $request)
    {
        $ventaService = new VentaService();

        $filters = $request->only(['limit', 'search', 'sortBy', 'descending', 'metodo_pago', 'status', 'fecha_desde', 'fecha_hasta', 'user_id', 'monto_min', 'monto_max']);

        $ventas = $ventaService->getHistorialVentas($filters);

        return response()->json(['data' => $ventas], 200);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $ventaService = new VentaService();

        try {
            $venta = $ventaService->getVenta($id);

            return response()->json(['data' => $venta, 'status' => true], 200);
        } catch (\Throwable $th) {
            return response()->json(['message' => $th->getMessage(), 'status' => false], 403);
        }
    }

    /**
     * Obtener el archivo ticket HTML listo para imprimir.
     */
    public function getTicket($id)
    {
        $ventaService = new VentaService();

        try {
            $venta = $ventaService->getVenta($id);

            $settingsRaw = DB::table('settings')->get();
            $settings = [];
            foreach ($settingsRaw as $setting) {
                $settings[$setting->key] = $setting->value;
            }

            return view('templates.ticket', compact('venta', 'settings'));
        } catch (\Throwable $th) {
            abort(403, $th->getMessage());
        }
    }
}