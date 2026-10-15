<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Ventas;
use Illuminate\Auth\Access\Response;

class VentaPolicy
{
  /**
   * Determine whether the user can view any models.
   */
  public function viewAny(User $user): bool
  {
    return $user->hasPermissionTo('ventas.ver') || $user->hasPermissionTo('ventas.ver_todo');
  }

  /**
   * Determine whether the user can view the model.
   */
  public function view(User $user, Ventas $ventas): bool
  {
    if ($user->hasPermissionTo('ventas.ver_todo')) {
      return true;
    }

    // Usuarios por sucursal solo ven ventas de su sucursal
    if ($user->sucursal_id !== null && $ventas->sucursal_id !== null) {
      return $user->sucursal_id === $ventas->sucursal_id;
    }

    return $user->hasPermissionTo('ventas.ver');
  }

  /**
   * Determine whether the user can create models.
   */
  public function create(User $user): bool
  {
    return $user->hasPermissionTo('ventas.crear');
  }

  /**
   * Determine whether the user can update the model.
   */
  public function update(User $user, Ventas $ventas): bool
  {
    // Generalmente las ventas no se editan, solo anulan/devoluciones
    return false;
  }

  /**
   * Determine whether the user can delete (anular) the model.
   */
  public function delete(User $user, Ventas $ventas): bool
  {
    return $user->hasPermissionTo('ventas.anular');
  }

  /**
   * Anular o devolver venta.
   */
  public function anular(User $user, Ventas $ventas): bool
  {
    return $user->hasPermissionTo('ventas.anular') || $user->hasPermissionTo('ventas.devolver');
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