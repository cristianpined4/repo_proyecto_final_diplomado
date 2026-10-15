<?php

namespace App\Http\Controllers;

use App\Models\Unidad;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class UnidadController extends Controller
{
    /**
     * Listado de unidades con filtros y paginación.
     */
    public function index(Request $request)
    {
        $search = $request->input('search', '');
        $limit = $request->input('limit', 15);
        $sortBy = $request->input('sortBy', 'nombre');
        $descending = $request->input('descending', 'false') === 'true' ? 'desc' : 'asc';

        $allowedSorts = ['id', 'nombre', 'abreviatura', 'estado', 'created_at'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'nombre';
        }

        $query = Unidad::query();

        if ($request->filled('estado')) {
            $estadoVal = ($request->input('estado') === '1' || $request->input('estado') === 'true' || $request->input('estado') === 1) ? 1 : 0;
            $query->where('estado', $estadoVal);
        }

        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('nombre', 'ILIKE', "%{$search}%")
                  ->orWhere('abreviatura', 'ILIKE', "%{$search}%");
            });
        }

        $query->orderBy($sortBy, $descending);

        if ($limit > 0) {
            $unidades = $query->paginate($limit);
        } else {
            $unidades = $query->get();
        }

        return response()->json(['success' => true, 'data' => $unidades]);
    }

    /**
     * Crear una unidad.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|min:2|max:50|regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑ0-9\s.,-]+$/|unique:unidads,nombre',
            'abreviatura' => 'required|string|max:10|regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑ0-9\/.-]+$/',
            'estado' => 'nullable|boolean'
        ], [
            'nombre.required' => 'El nombre de la unidad es obligatorio.',
            'nombre.min' => 'El nombre debe tener al menos 2 caracteres.',
            'nombre.regex' => 'El nombre contiene caracteres no permitidos.',
            'nombre.unique' => 'Ya existe una unidad de medida con este nombre.',
            'abreviatura.required' => 'La abreviatura es obligatoria.',
            'abreviatura.max' => 'La abreviatura no debe exceder los 10 caracteres.',
            'abreviatura.regex' => 'La abreviatura contiene un formato no válido (ej: kg, pza, l).',
        ]);

        $unidad = Unidad::create([
            'nombre' => trim($request->nombre),
            'abreviatura' => trim($request->abreviatura),
            'estado' => $request->input('estado', true)
        ]);

        Cache::forget('productos_catalogos');

        return response()->json(['success' => true, 'message' => 'Unidad creada con éxito', 'data' => $unidad], 201);
    }

    /**
     * Ver detalles.
     */
    public function show($id)
    {
        $unidad = Unidad::findOrFail($id);
        return response()->json(['success' => true, 'data' => $unidad]);
    }

    /**
     * Actualizar una unidad.
     */
    public function update(Request $request, $id)
    {
        $unidad = Unidad::findOrFail($id);

        $request->validate([
            'nombre' => 'required|string|min:2|max:50|regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑ0-9\s.,-]+$/|unique:unidads,nombre,' . $id,
            'abreviatura' => 'required|string|max:10|regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑ0-9\/.-]+$/',
            'estado' => 'nullable|boolean'
        ], [
            'nombre.required' => 'El nombre de la unidad es obligatorio.',
            'nombre.min' => 'El nombre debe tener al menos 2 caracteres.',
            'nombre.regex' => 'El nombre contiene caracteres no permitidos.',
            'nombre.unique' => 'Ya existe una unidad de medida con este nombre.',
            'abreviatura.required' => 'La abreviatura es obligatoria.',
            'abreviatura.max' => 'La abreviatura no debe exceder los 10 caracteres.',
            'abreviatura.regex' => 'La abreviatura contiene un formato no válido (ej: kg, pza, l).',
        ]);

        $unidad->update([
            'nombre' => trim($request->nombre),
            'abreviatura' => trim($request->abreviatura),
            'estado' => $request->input('estado', $unidad->estado)
        ]);

        Cache::forget('productos_catalogos');

        return response()->json(['success' => true, 'message' => 'Unidad actualizada con éxito', 'data' => $unidad]);
    }

    /**
     * Eliminar o desactivar una unidad.
     */
    public function destroy($id)
    {
        $unidad = Unidad::findOrFail($id);

        // Validar si tiene productos vinculados
        $hasProducts = \App\Models\Productos::where('unidad_id', $id)->exists();

        if ($hasProducts) {
            $unidad->estado = false;
            $unidad->save();
            Cache::forget('productos_catalogos');
            return response()->json(['success' => true, 'message' => 'La unidad de medida está siendo utilizada por productos, por lo que ha sido desactivada en vez de eliminada.']);
        }

        $unidad->delete();
        Cache::forget('productos_catalogos');
        return response()->json(['success' => true, 'message' => 'Unidad de medida eliminada con éxito']);
    }
}
