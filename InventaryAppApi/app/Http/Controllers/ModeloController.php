<?php

namespace App\Http\Controllers;

use App\Models\Modelo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ModeloController extends Controller
{
    /**
     * Listado de modelos con su marca.
     */
    public function index(Request $request)
    {
        $search = $request->input('search', '');
        $limit = $request->input('limit', 15);
        $marcaId = $request->input('marca_id', null);
        $sortBy = $request->input('sortBy', 'nombre');
        $descending = $request->input('descending', 'false') === 'true' ? 'desc' : 'asc';

        $allowedSorts = ['id', 'nombre', 'estado', 'created_at'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'nombre';
        }

        $query = Modelo::with('marca');

        if ($marcaId) {
            $query->where('marca_id', $marcaId);
        }

        if (!empty($search)) {
            $query->where('nombre', 'ILIKE', "%{$search}%");
        }

        $query->orderBy($sortBy, $descending);

        if ($limit > 0) {
            $modelos = $query->paginate($limit);
        } else {
            $modelos = $query->get();
        }

        return response()->json(['success' => true, 'data' => $modelos]);
    }

    /**
     * Crear un modelo.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'marca_id' => 'required|exists:marcas,id',
            'estado' => 'nullable|boolean'
        ]);

        $modelo = Modelo::create([
            'nombre' => $request->nombre,
            'marca_id' => $request->marca_id,
            'estado' => $request->input('estado', true)
        ]);

        Cache::forget('productos_catalogos');

        return response()->json(['success' => true, 'message' => 'Modelo creado con éxito', 'data' => $modelo->load('marca')], 201);
    }

    /**
     * Ver detalles.
     */
    public function show($id)
    {
        $modelo = Modelo::with('marca')->findOrFail($id);
        return response()->json(['success' => true, 'data' => $modelo]);
    }

    /**
     * Actualizar un modelo.
     */
    public function update(Request $request, $id)
    {
        $modelo = Modelo::findOrFail($id);

        $request->validate([
            'nombre' => 'required|string|max:255',
            'marca_id' => 'required|exists:marcas,id',
            'estado' => 'nullable|boolean'
        ]);

        $modelo->update([
            'nombre' => $request->nombre,
            'marca_id' => $request->marca_id,
            'estado' => $request->input('estado', $modelo->estado)
        ]);

        Cache::forget('productos_catalogos');

        return response()->json(['success' => true, 'message' => 'Modelo actualizado con éxito', 'data' => $modelo->load('marca')]);
    }

    /**
     * Eliminar un modelo.
     */
    public function destroy($id)
    {
        $modelo = Modelo::findOrFail($id);

        $hasProducts = \App\Models\Productos::where('modelo_id', $id)->exists();

        if ($hasProducts) {
            $modelo->estado = false;
            $modelo->save();
            Cache::forget('productos_catalogos');
            return response()->json(['success' => true, 'message' => 'El modelo está siendo utilizado, por lo que ha sido desactivado en vez de eliminado.']);
        }

        $modelo->delete();
        Cache::forget('productos_catalogos');
        return response()->json(['success' => true, 'message' => 'Modelo eliminado con éxito']);
    }
}
