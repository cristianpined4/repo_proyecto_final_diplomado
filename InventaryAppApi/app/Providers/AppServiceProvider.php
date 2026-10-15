<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Solo MySQL necesita deshabilitar ONLY_FULL_GROUP_BY
        if (DB::getDriverName() === 'mysql') {
            DB::statement("SET SESSION sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''))");
        }

        // Registrar Observers de Auditoría
        \App\Models\Productos::observe(\App\Observers\AuditObserver::class);
        \App\Models\Ventas::observe(\App\Observers\AuditObserver::class);
        \App\Models\Compras::observe(\App\Observers\AuditObserver::class);
        \App\Models\Clientes::observe(\App\Observers\AuditObserver::class);
        \App\Models\User::observe(\App\Observers\AuditObserver::class);
        \App\Models\Setting::observe(\App\Observers\AuditObserver::class);
    }
}
