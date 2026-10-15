<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Proveedor;
use Illuminate\Support\Facades\DB;

class ProveedorController extends Controller
{
    public function index(Request $request)
    {
        $limit = $request->input('limit', 15);
        $search = $request->input('search', '');
        $sortBy = $request->input('sortBy', 'nombre');
        $descending = $request->input('descending', 'false') === 'true' ? 'desc' : 'asc';

        $allowedSorts = ['id', 'nombre', 'contacto', 'telefono', 'correo', 'estado', 'created_at'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'nombre';
        }

        $query = Proveedor::query();

        if ($request->filled('estado')) {
            $estadoVal = ($request->input('estado') === '1' || $request->input('estado') === 'true' || $request->input('estado') === 1) ? 1 : 0;
            $query->where('estado', $estadoVal);
        }

        if ($request->filled('tipo_documento')) {
            $tipo = $request->input('tipo_documento');
            if ($tipo === 'con_nrc') {
                $query->whereNotNull('nrc')->where('nrc', '!=', '');
            } elseif ($tipo === 'sin_nrc') {
                $query->where(function($q) {
                    $q->whereNull('nrc')->orWhere('nrc', '');
                });
            }
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'ILIKE', "%{$search}%")
                    ->orWhere('contacto', 'ILIKE', "%{$search}%")
                    ->orWhere('telefono', 'ILIKE', "%{$search}%")
                    ->orWhere('correo', 'ILIKE', "%{$search}%");
            });
        }

        $query->orderBy($sortBy, $descending);

        if ($limit > 0) {
            $proveedores = $query->paginate($limit);
        } else {
            $proveedores = $query->get();
        }

        return response()->json(['success' => true, 'data' => $proveedores]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'contacto' => 'nullable|string|max:255',
            'telefono' => 'nullable|string|max:50',
            'correo' => 'nullable|email|max:255',
            'direccion' => 'nullable|string|max:500',
            'nit' => 'nullable|string|max:20',
            'nrc' => 'nullable|string|max:50',
            'estado' => 'nullable',
        ]);

        $data = $request->only([
            'nombre',
            'nit',
            'contacto',
            'telefono',
            'correo',
            'direccion',
            'nrc',
            'estado'
        ]);
        if (isset($data['estado'])) {
            $data['estado'] = ($data['estado'] === 1 || $data['estado'] === '1' || $data['estado'] === true || $data['estado'] === 'true') ? 1 : 0;
        } else {
            $data['estado'] = 1;
        }

        $proveedor = Proveedor::create($data);

        return response()->json(['success' => true, 'message' => 'Proveedor creado', 'data' => $proveedor], 201);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'contacto' => 'nullable|string|max:255',
            'telefono' => 'nullable|string|max:50',
            'correo' => 'nullable|email|max:255',
            'direccion' => 'nullable|string|max:500',
            'nit' => 'nullable|string|max:20',
            'nrc' => 'nullable|string|max:50',
            'estado' => 'nullable',
        ]);

        $proveedor = Proveedor::findOrFail($id);
        $data = $request->only([
            'nombre',
            'nit',
            'contacto',
            'telefono',
            'correo',
            'direccion',
            'nrc',
            'estado'
        ]);
        if (isset($data['estado'])) {
            $data['estado'] = ($data['estado'] === 1 || $data['estado'] === '1' || $data['estado'] === true || $data['estado'] === 'true') ? 1 : 0;
        }
        $proveedor->update($data);

        return response()->json(['success' => true, 'message' => 'Proveedor actualizado', 'data' => $proveedor], 200);
    }

    public function destroy($id)
    {
        $proveedor = Proveedor::find($id);
        if (!$proveedor) {
            return response()->json(['success' => false, 'message' => 'Proveedor no encontrado'], 404);
        }
        $proveedor->delete();
        return response()->json(['success' => true, 'message' => 'Proveedor eliminado'], 200);
    }

    /**
     * Búsqueda asíncrona rápida de proveedores para selects con miles de registros.
     */
    public function search(Request $request)
    {
        $term = trim($request->input('q', $request->input('search', '')));
        $limit = min(50, max(1, (int)$request->input('limit', 25)));

        $query = Proveedor::select(['id', 'nombre', 'contacto', 'telefono', 'nit'])
            ->where('estado', 1);

        if (!empty($term)) {
            $query->where(function ($q) use ($term) {
                $q->where('nombre', 'ILIKE', "%{$term}%")
                    ->orWhere('contacto', 'ILIKE', "%{$term}%")
                    ->orWhere('nit', 'ILIKE', "%{$term}%")
                    ->orWhere('telefono', 'ILIKE', "%{$term}%");
            });
        }

        $proveedores = $query->orderBy('nombre', 'asc')->limit($limit)->get();

        return response()->json([
            'success' => true,
            'data' => $proveedores
        ], 200);
    }
}