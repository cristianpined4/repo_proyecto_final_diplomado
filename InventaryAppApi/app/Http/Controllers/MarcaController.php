<?php

namespace App\Http\Controllers;

use App\Models\Marca;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class MarcaController extends Controller
{
    /**
     * Listado de marcas.
     */
    public function index(Request $request)
    {
        $search = $request->input('search', '');
        $limit = $request->input('limit', 15);
        $sortBy = $request->input('sortBy', 'nombre');
        $descending = $request->input('descending', 'false');

        $query = Marca::query();

        if ($request->filled('estado')) {
            $estadoVal = ($request->input('estado') === '1' || $request->input('estado') === 'true' || $request->input('estado') === 1) ? 1 : 0;
            $query->where('estado', $estadoVal);
        }

        if (!empty($search)) {
            $query->where('nombre', 'ILIKE', "%{$search}%");
        }

        $direction = ($descending === 'true' || $descending === true || $descending === 1 || $descending === '1') ? 'desc' : 'asc';
        if (in_array($sortBy, ['id', 'nombre', 'estado'])) {
            $query->orderBy($sortBy, $direction);
        } else {
            $query->orderBy('nombre', 'asc');
        }

        if ($limit > 0) {
            $marcas = $query->paginate($limit);
        } else {
            $marcas = $query->get();
        }

        return response()->json(['success' => true, 'data' => $marcas]);
    }

    /**
     * Crear una marca.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255|unique:marcas,nombre',
            'estado' => 'nullable|boolean'
        ]);

        $marca = Marca::create([
            'nombre' => $request->nombre,
            'estado' => $request->input('estado', true)
        ]);

        // Invalidar cache de catalogos
        Cache::forget('productos_catalogos');

        return response()->json(['success' => true, 'message' => 'Marca creada con éxito', 'data' => $marca], 201);
    }

    /**
     * Ver detalles.
     */
    public function show($id)
    {
        $marca = Marca::findOrFail($id);
        return response()->json(['success' => true, 'data' => $marca]);
    }

    /**
     * Actualizar una marca.
     */
    public function update(Request $request, $id)
    {
        $marca = Marca::findOrFail($id);

        $request->validate([
            'nombre' => 'required|string|max:255|unique:marcas,nombre,' . $id,
            'estado' => 'nullable|boolean'
        ]);

        $marca->update([
            'nombre' => $request->nombre,
            'estado' => $request->input('estado', $marca->estado)
        ]);

        Cache::forget('productos_catalogos');

        return response()->json(['success' => true, 'message' => 'Marca actualizada con éxito', 'data' => $marca]);
    }

    /**
     * Eliminar una marca (desactivar o borrar si no tiene relaciones).
     */
    public function destroy($id)
    {
        $marca = Marca::findOrFail($id);

        // Validar si tiene productos vinculados
        $hasProducts = \App\Models\Productos::where('marca_id', $id)->exists();
        $hasModels = \App\Models\Modelo::where('marca_id', $id)->exists();

        if ($hasProducts || $hasModels) {
            // Desactivar en vez de borrar si tiene dependencias
            $marca->estado = false;
            $marca->save();
            Cache::forget('productos_catalogos');
            return response()->json(['success' => true, 'message' => 'La marca está siendo utilizada, por lo que ha sido desactivada en vez de eliminada.']);
        }

        $marca->delete();
        Cache::forget('productos_catalogos');
        return response()->json(['success' => true, 'message' => 'Marca eliminada con éxito']);
    }
}
