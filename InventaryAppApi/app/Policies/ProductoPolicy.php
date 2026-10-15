<?php

namespace App\Policies;

use App\Models\Productos;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ProductoPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('productos.ver') || $user->hasPermissionTo('productos.ver_todo');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Productos $productos): bool
    {
        if ($user->hasPermissionTo('productos.ver_todo')) {
            return true;
        }

        // Usuarios por sucursal solo ven productos de su sucursal
        if ($user->sucursal_id !== null && $productos->sucursal_id !== null) {
            return $user->sucursal_id === $productos->sucursal_id;
        }

        return $user->hasPermissionTo('productos.ver');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('productos.crear');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Productos $productos): bool
    {
        if ($user->hasPermissionTo('productos.editar')) {
            if ($user->hasPermissionTo('productos.ver_todo')) {
                return true;
            }

            if ($user->sucursal_id !== null && $productos->sucursal_id !== null) {
                return $user->sucursal_id === $productos->sucursal_id;
            }

            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Productos $productos): bool
    {
        return $user->hasPermissionTo('productos.eliminar');
    }

    /**
     * Perform pre-authorization checks.
     */
    public function before(User $user, string $ability): bool|null
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return null;
    }
}