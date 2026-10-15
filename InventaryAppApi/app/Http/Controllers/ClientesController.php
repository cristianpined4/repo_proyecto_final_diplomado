<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Clientes as Cliente;
use App\Http\Requests\StoreClienteRequest;
use App\Http\Requests\UpdateClienteRequest;
use Illuminate\Support\Facades\DB;

class ClientesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $limit = $request->input('limit', 15);
        $search = $request->input('search', '');
        $sortBy = $request->input('sortBy', 'nombre');
        $descending = $request->input('descending', 'false') === 'true' ? 'desc' : 'asc';

        $allowedSorts = ['id', 'nombre', 'correo', 'dui', 'nit', 'nrc', 'telefono', 'created_at'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'nombre';
        }

        $query = Cliente::query();

        $user = auth()->user();
        if ($user && !$user->hasRole('admin') && !$user->hasPermissionTo('clientes.ver_todo')) {
            $query->where('user_id', $user->id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('tipo_contribuyente')) {
            $tipo = $request->input('tipo_contribuyente');
            if ($tipo === 'credito_fiscal') {
                $query->whereNotNull('nrc')->where('nrc', '!=', '');
            } elseif ($tipo === 'consumidor_final') {
                $query->where(function($q) {
                    $q->whereNull('nrc')->orWhere('nrc', '');
                });
            }
        }

        $startDate = $request->input('fecha_desde');
        $endDate = $request->input('fecha_hasta');

        if (!empty($startDate) && !empty($endDate)) {
            $start = \Carbon\Carbon::parse($startDate)->startOfDay();
            $end = \Carbon\Carbon::parse($endDate)->endOfDay();
            $query->whereBetween('clientes.created_at', [$start, $end]);
        } elseif (!empty($startDate)) {
            $start = \Carbon\Carbon::parse($startDate)->startOfDay();
            $query->where('clientes.created_at', '>=', $start);
        } elseif (!empty($endDate)) {
            $end = \Carbon\Carbon::parse($endDate)->endOfDay();
            $query->where('clientes.created_at', '<=', $end);
        }

        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('nombre', 'ILIKE', "%{$search}%")
                  ->orWhere('correo', 'ILIKE', "%{$search}%")
                  ->orWhere('dui', 'ILIKE', "%{$search}%")
                  ->orWhere('nit', 'ILIKE', "%{$search}%")
                  ->orWhere('nrc', 'ILIKE', "%{$search}%")
                  ->orWhere('telefono', 'ILIKE', "%{$search}%");
            });
        }

        $query->orderBy($sortBy, $descending);

        if ($limit > 0) {
            $clientes = $query->paginate($limit);
        } else {
            $clientes = $query->get();
        }

        return response()->json(['data' => $clientes, 'status' => true], 200);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $cliente = Cliente::find($id);
        if (!$cliente) {
            return response()->json(['message' => 'Cliente no encontrado', 'status' => false], 404);
        }

        $user = auth()->user();
        if ($user && !$user->hasRole('admin') && !$user->hasPermissionTo('clientes.ver_todo') && $cliente->user_id !== $user->id) {
            return response()->json(['message' => 'No autorizado para ver este cliente', 'status' => false], 403);
        }

        return response()->json(['data' => $cliente, 'status' => true], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreClienteRequest $request)
    {
        try {
            DB::beginTransaction();
            $cliente = new Cliente();
            $cliente->nombre = $request->input('nombre');
            $cliente->correo = $request->filled('correo') ? trim($request->input('correo')) : null;
            $cliente->dui = $request->filled('dui') ? trim($request->input('dui')) : null;
            $cliente->nit = $request->filled('nit') ? trim($request->input('nit')) : null;
            $cliente->nrc = $request->filled('nrc') ? trim($request->input('nrc')) : null;
            $cliente->telefono = $request->input('telefono');
            $cliente->direccion = $request->input('direccion');
            
            $statusVal = $request->input('status', 1);
            $cliente->status = in_array($statusVal, [1, '1', true, 'true', 'Activo'], true) ? 1 : 0;
            
            $cliente->user_id = auth()->id();
            $cliente->save();
            DB::commit();

            return response()->json(['status' => true, 'message' => 'Cliente creado', 'data' => $cliente], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => 'Error al crear el cliente: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateClienteRequest $request, string $id)
    {
        try {
            DB::beginTransaction();
            $cliente = Cliente::find($id);
            if (!$cliente) {
                return response()->json(['status' => false, 'message' => 'Cliente no encontrado'], 404);
            }

            $user = auth()->user();
            if ($user && !$user->hasRole('admin') && !$user->hasPermissionTo('clientes.ver_todo') && $cliente->user_id !== $user->id) {
                return response()->json(['message' => 'No autorizado para modificar este cliente', 'status' => false], 403);
            }

            $cliente->nombre = $request->input('nombre');
            $cliente->correo = $request->filled('correo') ? trim($request->input('correo')) : null;
            $cliente->dui = $request->filled('dui') ? trim($request->input('dui')) : null;
            $cliente->nit = $request->filled('nit') ? trim($request->input('nit')) : null;
            $cliente->nrc = $request->filled('nrc') ? trim($request->input('nrc')) : null;
            $cliente->telefono = $request->input('telefono');
            $cliente->direccion = $request->input('direccion');

            if ($request->has('status')) {
                $statusVal = $request->input('status');
                $cliente->status = in_array($statusVal, [1, '1', true, 'true', 'Activo'], true) ? 1 : 0;
            }

            $cliente->save();
            DB::commit();

            return response()->json(['status' => true, 'message' => 'Cliente actualizado', 'data' => $cliente], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => 'Error al actualizar el cliente: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            DB::beginTransaction();
            $cliente = Cliente::find($id);
            if (!$cliente) {
                return response()->json(['message' => 'Cliente no encontrado', 'status' => false], 404);
            }

            $user = auth()->user();
            if ($user && !$user->hasRole('admin') && !$user->hasPermissionTo('clientes.ver_todo') && $cliente->user_id !== $user->id) {
                return response()->json(['message' => 'No autorizado para eliminar este cliente', 'status' => false], 403);
            }

            $cliente->delete();
            DB::commit();
            return response()->json(['message' => 'Cliente eliminado', 'status' => true], 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(['message' => $th->getMessage(), 'status' => false], 500);
        }
    }

    /**
     * Búsqueda asíncrona rápida de clientes para el selector POS/Ventas con miles de registros.
     */
    public function search(Request $request)
    {
        $term = trim($request->input('q', $request->input('search', '')));
        $limit = min(50, max(1, (int)$request->input('limit', 25)));

        $query = Cliente::select(['id', 'nombre', 'dui', 'nit', 'telefono', 'correo'])
            ->where('status', 1);

        $user = auth()->user();
        if ($user && !$user->hasRole('admin') && !$user->hasPermissionTo('clientes.ver_todo')) {
            $query->where('user_id', $user->id);
        }

        if (!empty($term)) {
            $query->where(function ($q) use ($term) {
                $q->where('nombre', 'ILIKE', "%{$term}%")
                    ->orWhere('dui', 'ILIKE', "%{$term}%")
                    ->orWhere('nit', 'ILIKE', "%{$term}%")
                    ->orWhere('telefono', 'ILIKE', "%{$term}%");
            });
        }

        $clientes = $query->orderBy('nombre', 'asc')->limit($limit)->get();

        return response()->json([
            'status' => true,
            'data' => $clientes
        ], 200);
    }
}
