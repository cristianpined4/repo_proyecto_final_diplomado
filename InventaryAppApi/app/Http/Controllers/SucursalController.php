<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Sucursal;
use Illuminate\Support\Facades\Storage;

class SucursalController extends Controller
{
    /**
     * Listado de sucursales activas.
     */
    public function index(Request $request)
    {
        $limit = $request->input('limit', 15);
        $search = $request->input('search', '');
        $sortBy = $request->input('sortBy', 'nombre');
        $descending = $request->input('descending', 'false') === 'true' ? 'desc' : 'asc';

        $allowedSorts = ['id', 'nombre', 'direccion', 'telefono', 'correo', 'ciudad', 'estado', 'pais', 'activa', 'created_at'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'nombre';
        }

        $query = Sucursal::query();

        if ($request->filled('activa')) {
            $activaVal = ($request->input('activa') === '1' || $request->input('activa') === 'true' || $request->input('activa') === true);
            $query->where('activa', $activaVal);
        }

        if ($request->filled('ciudad')) {
            $query->where('ciudad', 'ILIKE', "%{$request->input('ciudad')}%");
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'ILIKE', "%{$search}%")
                  ->orWhere('direccion', 'ILIKE', "%{$search}%")
                  ->orWhere('telefono', 'ILIKE', "%{$search}%")
                  ->orWhere('correo', 'ILIKE', "%{$search}%");
            });
        }

        $query->orderBy($sortBy, $descending);

        if ($limit > 0) {
            $sucursales = $query->paginate($limit);
        } else {
            $sucursales = $query->get();
        }

        return response()->json(['data' => $sucursales, 'status' => true]);
    }

    /**
     * Crear una nueva sucursal.
     */
    public function store(Request $request)
    {
        $rules = [
            'nombre' => 'required|string|min:3|max:100|regex:/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑüÜ\s.,&\'()-]+$/',
            'direccion' => 'nullable|string|min:5|max:250',
            'telefono' => ['nullable', 'string', 'regex:/^[0-9]{4}-[0-9]{4}$/'],
            'correo' => 'nullable|email:filter|max:150',
            'ciudad' => 'nullable|string|min:2|max:100|regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s.-]+$/',
            'estado' => 'nullable|string|min:2|max:100|regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s.-]+$/',
            'pais' => 'nullable|string|min:2|max:100|regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s.-]+$/',
            'activa' => 'nullable|boolean',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
        ];

        $messages = [
            'nombre.required' => 'El nombre de la sucursal es obligatorio.',
            'nombre.min' => 'El nombre de la sucursal debe tener al menos 3 caracteres.',
            'nombre.max' => 'El nombre de la sucursal no debe exceder 100 caracteres.',
            'nombre.regex' => 'El nombre de la sucursal contiene caracteres no válidos.',
            'telefono.regex' => 'El teléfono de la sucursal debe tener el formato 0000-0000.',
            'correo.email' => 'El correo electrónico debe ser una dirección válida.',
            'correo.max' => 'El correo electrónico no debe exceder 150 caracteres.',
            'direccion.min' => 'La dirección debe tener al menos 5 caracteres.',
            'direccion.max' => 'La dirección no debe exceder 250 caracteres.',
            'ciudad.min' => 'La ciudad debe tener al menos 2 caracteres.',
            'ciudad.max' => 'La ciudad no debe exceder 100 caracteres.',
            'ciudad.regex' => 'La ciudad contiene caracteres no válidos.',
            'estado.min' => 'El estado o departamento debe tener al menos 2 caracteres.',
            'estado.max' => 'El estado o departamento no debe exceder 100 caracteres.',
            'estado.regex' => 'El estado o departamento contiene caracteres no válidos.',
            'pais.min' => 'El país debe tener al menos 2 caracteres.',
            'pais.max' => 'El país no debe exceder 100 caracteres.',
            'pais.regex' => 'El país contiene caracteres no válidos.',
            'logo.image' => 'El archivo seleccionado debe ser una imagen.',
            'logo.mimes' => 'El logo debe ser un archivo de tipo: jpeg, png, jpg, gif, svg, webp.',
            'logo.max' => 'El logo no debe superar los 2MB de tamaño.',
        ];

        $request->validate($rules, $messages);

        $sucursal = Sucursal::create($request->only([
            'nombre', 'direccion', 'telefono', 'correo', 'ciudad', 'estado', 'pais', 'activa'
        ]));

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('sucursales', 'public');
            $sucursal->logo = $path;
            $sucursal->save();
        }

        return response()->json(['data' => $sucursal->fresh(), 'status' => true, 'message' => 'Sucursal creada con éxito'], 201);
    }

    /**
     * Ver una sucursal específica.
     */
    public function show(string $id)
    {
        $sucursal = Sucursal::find($id);
        if (!$sucursal) {
            return response()->json(['message' => 'Sucursal no encontrada', 'status' => false], 404);
        }
        return response()->json(['data' => $sucursal, 'status' => true]);
    }

    /**
     * Actualizar una sucursal.
     */
    public function update(Request $request, string $id)
    {
        $rules = [
            'nombre' => 'required|string|min:3|max:100|regex:/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑüÜ\s.,&\'()-]+$/',
            'direccion' => 'nullable|string|min:5|max:250',
            'telefono' => ['nullable', 'string', 'regex:/^[0-9]{4}-[0-9]{4}$/'],
            'correo' => 'nullable|email:filter|max:150',
            'ciudad' => 'nullable|string|min:2|max:100|regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s.-]+$/',
            'estado' => 'nullable|string|min:2|max:100|regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s.-]+$/',
            'pais' => 'nullable|string|min:2|max:100|regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s.-]+$/',
            'activa' => 'nullable|boolean',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
        ];

        $messages = [
            'nombre.required' => 'El nombre de la sucursal es obligatorio.',
            'nombre.min' => 'El nombre de la sucursal debe tener al menos 3 caracteres.',
            'nombre.max' => 'El nombre de la sucursal no debe exceder 100 caracteres.',
            'nombre.regex' => 'El nombre de la sucursal contiene caracteres no válidos.',
            'telefono.regex' => 'El teléfono de la sucursal debe tener el formato 0000-0000.',
            'correo.email' => 'El correo electrónico debe ser una dirección válida.',
            'correo.max' => 'El correo electrónico no debe exceder 150 caracteres.',
            'direccion.min' => 'La dirección debe tener al menos 5 caracteres.',
            'direccion.max' => 'La dirección no debe exceder 250 caracteres.',
            'ciudad.min' => 'La ciudad debe tener al menos 2 caracteres.',
            'ciudad.max' => 'La ciudad no debe exceder 100 caracteres.',
            'ciudad.regex' => 'La ciudad contiene caracteres no válidos.',
            'estado.min' => 'El estado o departamento debe tener al menos 2 caracteres.',
            'estado.max' => 'El estado o departamento no debe exceder 100 caracteres.',
            'estado.regex' => 'El estado o departamento contiene caracteres no válidos.',
            'pais.min' => 'El país debe tener al menos 2 caracteres.',
            'pais.max' => 'El país no debe exceder 100 caracteres.',
            'pais.regex' => 'El país contiene caracteres no válidos.',
            'logo.image' => 'El archivo seleccionado debe ser una imagen.',
            'logo.mimes' => 'El logo debe ser un archivo de tipo: jpeg, png, jpg, gif, svg, webp.',
            'logo.max' => 'El logo no debe superar los 2MB de tamaño.',
        ];

        $request->validate($rules, $messages);

        $sucursal = Sucursal::findOrFail($id);
        $sucursal->update($request->only([
            'nombre', 'direccion', 'telefono', 'correo', 'ciudad', 'estado', 'pais', 'activa'
        ]));

        if ($request->hasFile('logo')) {
            if ($sucursal->logo && Storage::disk('public')->exists($sucursal->logo)) {
                Storage::disk('public')->delete($sucursal->logo);
            }
            $path = $request->file('logo')->store('sucursales', 'public');
            $sucursal->logo = $path;
            $sucursal->save();
        }

        return response()->json(['data' => $sucursal->fresh(), 'status' => true, 'message' => 'Sucursal actualizada con éxito']);
    }

    /**
     * Desactivar (borrado lógico) una sucursal.
     */
    public function destroy(string $id)
    {
        $sucursal = Sucursal::find($id);
        if (!$sucursal) {
            return response()->json(['message' => 'Sucursal no encontrada', 'status' => false], 404);
        }

        if ($id == 1) {
            return response()->json([
                'message' => 'No se puede eliminar la Casa Matriz del sistema.',
                'status' => false
            ], 400);
        }

        // Si hay usuarios, ventas, compras o kardex/movimientos vinculados, no permitir borrar
        $userExists = \App\Models\User::where('sucursal_id', $id)->exists();
        $ventasExists = \App\Models\Ventas::where('sucursal_id', $id)->exists();
        $comprasExists = \App\Models\Compras::where('sucursal_id', $id)->exists();
        $kardexExists = \App\Models\Kardex::where('sucursal_id', $id)->exists();
        $movimientosExists = \App\Models\MovimientosInventario::where('sucursal_id', $id)->exists();

        if ($userExists || $ventasExists || $comprasExists || $kardexExists || $movimientosExists) {
            return response()->json([
                'message' => 'No se puede eliminar la sucursal porque ya ha generado información en el sistema (tiene usuarios, ventas, compras, o movimientos de inventario vinculados).',
                'status' => false
            ], 400);
        }

        // Borrar logo si existe
        if ($sucursal->logo) {
            if (Storage::disk('public')->exists($sucursal->logo)) {
                Storage::disk('public')->delete($sucursal->logo);
            }
        }

        $sucursal->delete();
        return response()->json(['message' => 'Sucursal eliminada con éxito', 'status' => true]);
    }

    /**
     * Subir logo de la sucursal.
     */
    public function uploadLogo(Request $request, $id)
    {
        $request->validate([
            'logo' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048'
        ]);

        $sucursal = Sucursal::findOrFail($id);

        if ($request->hasFile('logo')) {
            // Borrar logo anterior si existe
            if ($sucursal->logo) {
                if (Storage::disk('public')->exists($sucursal->logo)) {
                    Storage::disk('public')->delete($sucursal->logo);
                }
            }

            // Guardar logo nuevo
            $path = $request->file('logo')->store('sucursales', 'public');
            $sucursal->logo = $path;
            $sucursal->save();

            return response()->json([
                'success' => true,
                'message' => 'Logo de sucursal subido correctamente',
                'logo_path' => $path
            ], 200);
        }

        return response()->json(['success' => false, 'message' => 'No se recibió ninguna imagen'], 400);
    }
}
