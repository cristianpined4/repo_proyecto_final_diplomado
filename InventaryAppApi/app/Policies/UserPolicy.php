<?php

namespace App\Policies;

use App\Models\User as UserModel;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
  /**
   * Determine whether the user can view any models.
   */
  public function viewAny(UserModel $user): bool
  {
    return $user->hasPermissionTo('usuarios.ver');
  }

  /**
   * Determine whether the user can view the model.
   */
  public function view(UserModel $user, UserModel $model): bool
  {
    if ($user->hasPermissionTo('usuarios.ver_todo') || $user->id === $model->id) {
      return true;
    }

    return $user->hasPermissionTo('usuarios.ver');
  }

  /**
   * Determine whether the user can create models.
   */
  public function create(UserModel $user): bool
  {
    return $user->hasPermissionTo('usuarios.crear');
  }

  /**
   * Determine whether the user can update the model.
   */
  public function update(UserModel $user, UserModel $model): bool
  {
    if ($user->hasPermissionTo('usuarios.editar')) {
      // No permitir que usuarios no-admin editen admins
      if ($model->hasRole('admin') && !$user->hasRole('admin')) {
        return false;
      }
      return true;
    }

    return $user->id === $model->id; // Usuario puede editar su propio perfil
  }

  /**
   * Determine whether the user can delete the model.
   */
  public function delete(UserModel $user, UserModel $model): bool
  {
    if ($model->hasRole('admin')) {
      return false; // No se puede eliminar admin
    }

    return $user->hasPermissionTo('usuarios.eliminar');
  }

  /**
   * Perform pre-authorization checks.
   */
  public function before(UserModel $user, string $ability): bool|null
  {
    if ($user->hasRole('admin')) {
      return true;
    }

    return null;
  }
}