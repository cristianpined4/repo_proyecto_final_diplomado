<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\Productos;
use App\Models\Ventas;
use App\Models\Compras;
use App\Models\User as UserModel;
use App\Policies\ProductoPolicy;
use App\Policies\VentaPolicy;
use App\Policies\CompraPolicy;
use App\Policies\UserPolicy;

class AuthServiceProvider extends ServiceProvider
{
  /**
   * The model to policy mappings for the application.
   *
   * @var array<class-string, class-string>
   */
  protected $policies = [
    Productos::class => ProductoPolicy::class,
    Ventas::class => VentaPolicy::class,
    Compras::class => CompraPolicy::class,
    UserModel::class => UserPolicy::class,
    // Agregar más según se creen (SucursalPolicy, etc.)
  ];

  /**
   * Register any authentication / authorization services.
   */
  public function boot(): void
  {
    $this->registerPolicies();

    // Gates adicionales si se necesitan (por ejemplo para reportes o dashboard)
    Gate::define('viewDashboard', function ($user) {
      return $user->hasPermissionTo('dashboard.ver');
    });

    Gate::define('viewReports', function ($user) {
      return $user->hasPermissionTo('reportes.ver');
    });
  }
}