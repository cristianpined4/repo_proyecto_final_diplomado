<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;

class RolesController extends Controller
{
    /**
     * Obtener listado de roles con sus permisos.
     */
    public function index()
    {
        $roles = Role::with('permissions')->get();
        return response()->json(['success' => true, 'data' => $roles]);
    }

    /**
     * Obtener todos los permisos agrupados por sección y acción.
     */
    public function permissions()
    {
        $permissions = Permission::all();
        $grouped = [];

        foreach ($permissions as $p) {
            $parts = explode('.', $p->name);
            $section = count($parts) > 1 ? $parts[0] : 'otros';
            $action = count($parts) > 1 ? $parts[1] : $p->name;

            if (!isset($grouped[$section])) {
                $grouped[$section] = [];
            }

            $grouped[$section][] = [
                'id' => $p->id,
                'name' => $p->name,
                'action' => $action
            ];
        }

        // Formatear para Quasar: lista de secciones con sus permisos
        $formatted = [];
        foreach ($grouped as $sectionName => $actionsList) {
            $formatted[] = [
                'seccion' => ucfirst($sectionName),
                'permisos' => $actionsList
            ];
        }

        return response()->json(['success' => true, 'data' => $formatted]);
    }

    /**
     * Crear un nuevo rol con permisos.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:roles,name',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name'
        ]);

        try {
            DB::beginTransaction();

            $role = Role::create([
                'name' => $request->name,
                'guard_name' => 'web'
            ]);

            if ($request->has('permissions')) {
                $role->syncPermissions($request->permissions);
            }

            // Limpiar caché de permisos
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Rol creado correctamente', 'data' => $role->load('permissions')], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Error al crear rol: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Actualizar un rol y sus permisos.
     */
    public function update(Request $request, $id)
    {
        $role = Role::findOrFail($id);

        if ($role->name === 'admin') {
            return response()->json(['success' => false, 'message' => 'El rol administrador no puede ser modificado'], 403);
        }

        $request->validate([
            'name' => 'required|string|unique:roles,name,' . $id,
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name'
        ]);

        try {
            DB::beginTransaction();

            $role->update([
                'name' => $request->name
            ]);

            if ($request->has('permissions')) {
                $role->syncPermissions($request->permissions);
            }

            // Limpiar caché de permisos
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Rol actualizado correctamente', 'data' => $role->load('permissions')]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Error al actualizar rol: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Eliminar un rol.
     */
    public function destroy($id)
    {
        $role = Role::findOrFail($id);

        if (in_array($role->name, ['admin', 'gerente', 'vendedor', 'almacenero'])) {
            return response()->json(['success' => false, 'message' => 'Los roles por defecto del sistema no pueden ser eliminados'], 403);
        }

        try {
            DB::beginTransaction();
            $role->delete();
            
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
            DB::commit();
            
            return response()->json(['success' => true, 'message' => 'Rol eliminado correctamente']);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Error al eliminar rol: ' . $e->getMessage()], 500);
        }
    }
}
