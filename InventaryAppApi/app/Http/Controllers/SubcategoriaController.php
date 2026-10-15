<?php

namespace App\Http\Controllers;

use App\Models\Subcategoria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SubcategoriaController extends Controller
{
    /**
     * Listado de subcategorías con su categoría.
     */
    public function index(Request $request)
    {
        $search = $request->input('search', '');
        $limit = $request->input('limit', 15);
        $categoriaId = $request->input('categoria_id', null);

        $query = Subcategoria::with('categoria');

        if ($categoriaId) {
            $query->where('categoria_id', $categoriaId);
        }

        if (!empty($search)) {
            $query->where('nombre', 'ILIKE', "%{$search}%");
        }

        $query->orderBy('nombre', 'asc');

        if ($limit > 0) {
            $subcategorias = $query->paginate($limit);
        } else {
            $subcategorias = $query->get();
        }

        return response()->json(['success' => true, 'data' => $subcategorias]);
    }

    /**
     * Crear una subcategoría.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'categoria_id' => 'required|exists:categorias,id',
            'estado' => 'nullable|boolean'
        ]);

        $subcategoria = Subcategoria::create([
            'nombre' => $request->nombre,
            'categoria_id' => $request->categoria_id,
            'estado' => $request->input('estado', true)
        ]);

        Cache::forget('productos_catalogos');

        return response()->json(['success' => true, 'message' => 'Subcategoría creada con éxito', 'data' => $subcategoria->load('categoria')], 201);
    }

    /**
     * Ver detalles.
     */
    public function show($id)
    {
        $subcategoria = Subcategoria::with('categoria')->findOrFail($id);
        return response()->json(['success' => true, 'data' => $subcategoria]);
    }

    /**
     * Actualizar una subcategoría.
     */
    public function update(Request $request, $id)
    {
        $subcategoria = Subcategoria::findOrFail($id);

        $request->validate([
            'nombre' => 'required|string|max:255',
            'categoria_id' => 'required|exists:categorias,id',
            'estado' => 'nullable|boolean'
        ]);

        $subcategoria->update([
            'nombre' => $request->nombre,
            'categoria_id' => $request->categoria_id,
            'estado' => $request->input('estado', $subcategoria->estado)
        ]);

        Cache::forget('productos_catalogos');

        return response()->json(['success' => true, 'message' => 'Subcategoría actualizada con éxito', 'data' => $subcategoria->load('categoria')]);
    }

    /**
     * Eliminar una subcategoría.
     */
    public function destroy($id)
    {
        $subcategoria = Subcategoria::findOrFail($id);

        $hasProducts = \App\Models\Productos::where('subcategoria_id', $id)->exists();

        if ($hasProducts) {
            $subcategoria->estado = false;
            $subcategoria->save();
            Cache::forget('productos_catalogos');
            return response()->json(['success' => true, 'message' => 'La subcategoría está siendo utilizada, por lo que ha sido desactivada en vez de eliminada.']);
        }

        $subcategoria->delete();
        Cache::forget('productos_catalogos');
        return response()->json(['success' => true, 'message' => 'Subcategoría eliminada con éxito']);
    }
}
