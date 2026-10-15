<?php

namespace App\Policies;

use App\Models\Compras;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class CompraPolicy
{
  /**
   * Determine whether the user can view any models.
   */
  public function viewAny(User $user): bool
  {
    return $user->hasPermissionTo('compras.ver') || $user->hasPermissionTo('compras.ver_todo');
  }

  /**
   * Determine whether the user can view the model.
   */
  public function view(User $user, Compras $compras): bool
  {
    if ($user->hasPermissionTo('compras.ver_todo')) {
      return true;
    }

    if ($user->sucursal_id !== null && $compras->sucursal_id !== null) {
      return $user->sucursal_id === $compras->sucursal_id;
    }

    return $user->hasPermissionTo('compras.ver');
  }

  /**
   * Determine whether the user can create models.
   */
  public function create(User $user): bool
  {
    return $user->hasPermissionTo('compras.crear');
  }

  /**
   * Determine whether the user can update the model.
   */
  public function update(User $user, Compras $compras): bool
  {
    return false; // Las compras generalmente no se editan después de recepción
  }

  /**
   * Determine whether the user can delete (cancelar) the model.
   */
  public function delete(User $user, Compras $compras): bool
  {
    return $user->hasPermissionTo('compras.cancelar');
  }

  /**
   * Perform pre-authorization checks.
   */
  public function before(User $user, string $ability): bool|null
  {
    if ($user->hasRole('admin') || $user->hasRole('gerente')) {
      return true;
    }

    return null;
  }
}