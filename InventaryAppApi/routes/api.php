<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ComprasController;
use App\Http\Controllers\ProductosController;
use App\Http\Controllers\CategoriasController;
use App\Http\Controllers\InventarioController;
use App\Http\Controllers\ClientesController;
use App\Http\Controllers\VentasController;
use App\Http\Controllers\ReportesController;

/*
|--------------------------------------------------------------------------
| Ruta de Inicialización (solo funciona si no hay usuarios)
|--------------------------------------------------------------------------
*/
Route::get('/init', function () {
    // Limpiar caché de Spatie para evitar conflictos
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

    \Illuminate\Support\Facades\DB::beginTransaction();
    try {
        // 1. Crear roles con guard 'web' (compatible con Sanctum SPA)
        $roles = ['admin', 'gerente', 'vendedor', 'almacenero', 'supervisor', 'cajero', 'compras', 'auditor'];
        foreach ($roles as $roleName) {
            \Spatie\Permission\Models\Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
            ]);
        }

        // 2. Crear permisos granulares
        $permisos = [
            'usuarios.ver', 'usuarios.crear', 'usuarios.editar', 'usuarios.eliminar',
            'productos.ver', 'productos.crear', 'productos.editar', 'productos.eliminar',
            'categorias.ver', 'categorias.crear', 'categorias.editar', 'categorias.eliminar',
            'clientes.ver', 'clientes.crear', 'clientes.editar', 'clientes.eliminar', 'clientes.ver_todo',
            'ventas.ver', 'ventas.crear', 'ventas.anular', 'ventas.devolver', 'ventas.historial', 'ventas.ver_todo',
            'compras.ver', 'compras.crear', 'compras.cancelar', 'compras.historial', 'compras.ver_todo',
            'inventario.ver', 'inventario.mover', 'inventario.kardex', 'inventario.ver_todo',
            'proveedores.ver', 'proveedores.crear', 'proveedores.editar', 'proveedores.eliminar',
            'configuracion.ver', 'configuracion.editar',
            'reportes.ver',
            'sucursales.ver', 'sucursales.crear', 'sucursales.editar', 'sucursales.eliminar',
            'dashboard.ver',
            'roles.ver', 'roles.crear', 'roles.editar', 'roles.eliminar'
        ];

        foreach ($permisos as $permiso) {
            \Spatie\Permission\Models\Permission::firstOrCreate([
                'name' => $permiso,
                'guard_name' => 'web',
            ]);
        }

        // 3. Asignar permisos por rol
        $adminRole = \Spatie\Permission\Models\Role::findByName('admin', 'web');
        $adminRole->syncPermissions($permisos);

        $gerenteRole = \Spatie\Permission\Models\Role::findByName('gerente', 'web');
        $gerenteRole->syncPermissions([
            'productos.ver', 'productos.crear', 'productos.editar',
            'categorias.ver', 'categorias.crear', 'categorias.editar',
            'clientes.ver', 'clientes.crear', 'clientes.editar',
            'ventas.ver', 'ventas.crear', 'ventas.anular', 'ventas.devolver', 'ventas.historial',
            'compras.ver', 'compras.crear', 'compras.cancelar', 'compras.historial',
            'inventario.ver', 'inventario.mover', 'inventario.kardex',
            'proveedores.ver', 'proveedores.crear', 'proveedores.editar',
            'reportes.ver', 'dashboard.ver', 'sucursales.ver'
        ]);

        $supervisorRole = \Spatie\Permission\Models\Role::findByName('supervisor', 'web');
        $supervisorRole->syncPermissions([
            'productos.ver', 'productos.crear', 'productos.editar',
            'clientes.ver', 'clientes.crear', 'clientes.editar',
            'ventas.ver', 'ventas.crear', 'ventas.anular', 'ventas.devolver', 'ventas.historial',
            'compras.ver', 'compras.historial',
            'inventario.ver', 'inventario.kardex',
            'dashboard.ver', 'sucursales.ver'
        ]);

        $cajeroRole = \Spatie\Permission\Models\Role::findByName('cajero', 'web');
        $cajeroRole->syncPermissions([
            'clientes.ver', 'clientes.crear', 'clientes.editar',
            'ventas.ver', 'ventas.crear', 'ventas.historial',
            'dashboard.ver'
        ]);

        $comprasRole = \Spatie\Permission\Models\Role::findByName('compras', 'web');
        $comprasRole->syncPermissions([
            'productos.ver',
            'compras.ver', 'compras.crear', 'compras.historial',
            'proveedores.ver', 'proveedores.crear', 'proveedores.editar',
            'inventario.ver', 'inventario.kardex',
            'dashboard.ver'
        ]);

        $auditorRole = \Spatie\Permission\Models\Role::findByName('auditor', 'web');
        $auditorRole->syncPermissions([
            'productos.ver', 'clientes.ver', 'ventas.historial', 'compras.historial',
            'inventario.ver', 'inventario.kardex', 'reportes.ver', 'dashboard.ver'
        ]);

        $vendedorRole = \Spatie\Permission\Models\Role::findByName('vendedor', 'web');
        $vendedorRole->syncPermissions([
            'productos.ver', 'clientes.ver', 'clientes.crear', 'clientes.editar',
            'ventas.ver', 'ventas.crear', 'ventas.historial',
            'dashboard.ver',
        ]);

        $almaceneroRole = \Spatie\Permission\Models\Role::findByName('almacenero', 'web');
        $almaceneroRole->syncPermissions([
            'productos.ver', 'productos.crear', 'productos.editar',
            'categorias.ver',
            'inventario.ver', 'inventario.mover', 'inventario.kardex',
            'dashboard.ver',
        ]);

        // 4. Crear usuario admin si no existe
        $admin = \App\Models\User::where('username', 'admin')->first();
        $created = false;
        if (!$admin) {
            $admin = \App\Models\User::create([
                'name' => 'Admin',
                'lastname' => 'Admin',
                'username' => 'admin',
                'password' => \Illuminate\Support\Facades\Hash::make('admin'),
            ]);
            $created = true;
        }

        // Asegurar que el admin tiene el rol (siempre)
        if (!$admin->hasRole('admin')) {
            $admin->assignRole('admin');
        }

        // Crear categorías básicas
        \App\Models\Categoria::firstOrCreate(
            ['name' => 'General'],
            ['status' => 1]
        );

        // Crear unidades de medida básicas
        \App\Models\Unidad::firstOrCreate(
            ['nombre' => 'Unidad'],
            [
                'abreviatura' => 'UND',
                'estado' => 1,
            ]
        );

        // Crear marcas básicas
        \App\Models\Marca::firstOrCreate(
            ['nombre' => 'Genérica'],
            ['estado' => 1]
        );

        // 5. Crear sucursal por defecto
        $sucursalMatriz = \App\Models\Sucursal::firstOrCreate(
            ['nombre' => 'Casa Matriz'],
            [
                'direccion' => 'Dirección Principal',
                'telefono' => '2200-0000',
                'correo' => 'contacto@miempresa.com',
                'activa' => true,
                'logo' => null,
                'ciudad' => 'San Salvador',
                'estado' => 'San Salvador',
                'pais' => 'El Salvador',
            ]
        );

        // Asociar admin a sucursal matriz si no tiene una
        if (!$admin->sucursal_id) {
            $admin->sucursal_id = $sucursalMatriz->id;
            $admin->save();
        }

        // 6. Crear / actualizar configuraciones por defecto
        $defaultSettings = [
            ['key' => 'empresa_nombre', 'value' => 'Mi Empresa de Repuestos', 'type' => 'string', 'group' => 'general'],
            ['key' => 'empresa_correo', 'value' => 'contacto@miempresa.com', 'type' => 'string', 'group' => 'general'],
            ['key' => 'empresa_telefono', 'value' => '2200-0000', 'type' => 'string', 'group' => 'general'],
            ['key' => 'empresa_direccion', 'value' => 'Dirección Principal', 'type' => 'string', 'group' => 'general'],
            ['key' => 'empresa_moneda', 'value' => 'USD', 'type' => 'string', 'group' => 'general'],
            ['key' => 'iva_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'fiscal'],
            ['key' => 'iva_percentage', 'value' => '13', 'type' => 'decimal', 'group' => 'fiscal'],
            ['key' => 'inventory_valuation_method', 'value' => 'promedio', 'type' => 'string', 'group' => 'inventario'],
            ['key' => 'venta_serie', 'value' => 'F001', 'type' => 'string', 'group' => 'ventas'],
            ['key' => 'venta_ultimo_correlativo', 'value' => '0', 'type' => 'integer', 'group' => 'ventas'],
            ['key' => 'color_primary', 'value' => '#1976D2', 'type' => 'string', 'group' => 'branding'],
            ['key' => 'color_secondary', 'value' => '#26A69A', 'type' => 'string', 'group' => 'branding'],
            ['key' => 'color_accent', 'value' => '#9C27B0', 'type' => 'string', 'group' => 'branding'],
        ];

        foreach ($defaultSettings as $setting) {
            \Illuminate\Support\Facades\DB::table('settings')->updateOrInsert(
                ['key' => $setting['key']],
                [
                    'value' => $setting['value'],
                    'type' => $setting['type'],
                    'group' => $setting['group'],
                    'updated_at' => now(),
                ]
            );
        }

        \Illuminate\Support\Facades\DB::commit();

        // Limpiar caché de Spatie después de crear todo
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $rolesCount = \Spatie\Permission\Models\Role::count();
        $permisosCount = \Spatie\Permission\Models\Permission::count();
        $settingsCount = \Illuminate\Support\Facades\DB::table('settings')->count();

        return response()->json([
            'success' => true,
            'message' => $created
                ? 'Sistema inicializado exitosamente. Usuario: admin / Contraseña: admin'
                : 'Configuraciones iniciales, roles, permisos y catálogos base actualizados correctamente.',
            'roles_creados' => $rolesCount,
            'permisos_creados' => $permisosCount,
            'settings_total' => $settingsCount,
            'sucursal' => $sucursalMatriz->nombre,
            'user' => $admin->load('roles', 'permissions'),
        ], 200);
    } catch (\Throwable $e) {
        \Illuminate\Support\Facades\DB::rollBack();
        return response()->json([
            'message' => 'Error al inicializar: ' . $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ], 500);
    }
});

/*
|--------------------------------------------------------------------------
| Autenticación — Rate Limited
|--------------------------------------------------------------------------
*/
Route::post('/login', [UsersController::class, 'login'])
    ->middleware('throttle:30,1'); // Máximo 30 intentos por minuto

Route::get('/settings/public', [\App\Http\Controllers\SettingsController::class, 'publicSettings']);

/*
|--------------------------------------------------------------------------
| Rutas Protegidas (Sanctum SPA cookie auth)
|--------------------------------------------------------------------------
*/
Route::group(['middleware' => 'auth:sanctum'], function () {
    Route::post('/logout', [UsersController::class, 'logout']);
    Route::get('/me', [UsersController::class, 'me']);
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/dashboard/productos-agotandose', [DashboardController::class, 'lowStock']);
    // Categorías - Lectura libre, Escritura restringida
    Route::get('/categorias', [CategoriasController::class, 'index']);
    Route::get('/categorias/{id}', [CategoriasController::class, 'show']);
    Route::post('/categorias', [CategoriasController::class, 'store'])->middleware('permission:categorias.crear');
    Route::put('/categorias/{id}', [CategoriasController::class, 'update'])->middleware('permission:categorias.editar');
    Route::delete('/categorias/{id}', [CategoriasController::class, 'destroy'])->middleware('permission:categorias.eliminar');

    // Productos - Lectura libre, Escritura restringida
    Route::get('/productos/search', [ProductosController::class, 'search']);
    Route::get('/productos/catalogos', [ProductosController::class, 'catalogos']);
    Route::get('/productos', [ProductosController::class, 'index']);
    Route::get('/productos/{id}', [ProductosController::class, 'show']);
    Route::post('/productos', [ProductosController::class, 'store'])->middleware('permission:productos.crear');
    Route::put('/productos/{id}', [ProductosController::class, 'update'])->middleware('permission:productos.editar');
    Route::delete('/productos/{id}', [ProductosController::class, 'destroy'])->middleware('permission:productos.eliminar');
    Route::post('/productos/{id}/upload-image', [ProductosController::class, 'uploadImage'])->middleware('permission:productos.editar');
    Route::post('/productos/{id}/delete-image', [ProductosController::class, 'deleteImage'])->middleware('permission:productos.editar');

    // Clientes - Lectura libre, Escritura vendedores, cajeros y admins
    Route::get('/clientes/search', [ClientesController::class, 'search']);
    Route::get('/clientes', [ClientesController::class, 'index']);
    Route::get('/clientes/{id}', [ClientesController::class, 'show']);
    Route::post('/clientes', [ClientesController::class, 'store'])->middleware('permission:clientes.crear');
    Route::put('/clientes/{id}', [ClientesController::class, 'update'])->middleware('permission:clientes.editar');
    Route::delete('/clientes/{id}', [ClientesController::class, 'destroy'])->middleware('permission:clientes.eliminar');

    Route::group(['middleware' => ['permission:compras.ver|compras.crear']], function () {
        Route::get('/compras/historial', [ComprasController::class, 'showAllCompras']);
        Route::apiResource('/compras', ComprasController::class);
        Route::post('/compras/cancelar/{id}', [ComprasController::class, 'cancelarCompra']);
    });

    // Ventas - Lectura libre, Registro restringido a vendedores/cajeros/admins, Anulación a supervisores/admins
    Route::get('/ventas/historial', [VentasController::class, 'showAllVentas']);
    Route::get('/ventas', [VentasController::class, 'index']);
    Route::get('/ventas/{id}', [VentasController::class, 'show']);
    Route::post('/ventas', [VentasController::class, 'store'])->middleware('permission:ventas.crear');
    Route::post('/ventas/anular/{id}', [VentasController::class, 'anular'])->middleware('permission:ventas.anular');
    Route::post('/ventas/devolver/{id}', [VentasController::class, 'devolucion'])->middleware('permission:ventas.devolver');

    Route::group(['middleware' => ['permission:inventario.kardex']], function () {
        Route::get('/kardex/razones', [InventarioController::class, 'searchRazones']);
        Route::get('/kardex', [InventarioController::class, 'mostrarKardexTodos']);
        Route::post('/kardex', [InventarioController::class, 'registrarMovimiento']);
    });

    Route::group(['middleware' => ['permission:usuarios.ver|configuracion.ver|roles.ver']], function () {
        Route::apiResource('/usuarios', UsersController::class);
        Route::get('/settings', [\App\Http\Controllers\SettingsController::class, 'index']);
        Route::post('/settings/bulk', [\App\Http\Controllers\SettingsController::class, 'updateBulk']);
        Route::post('/settings/logo', [\App\Http\Controllers\SettingsController::class, 'uploadGlobalLogo']);
        
        // Creador de permisos parametrizado
        Route::get('/roles/permissions', [\App\Http\Controllers\RolesController::class, 'permissions']);
        Route::apiResource('/roles', \App\Http\Controllers\RolesController::class);
    });

    // Perfil de usuario (cualquier usuario autenticado)
    Route::post('/profile', [UsersController::class, 'updateProfile']);

    // Catálogos - Lectura para todos, Escritura restringida
    Route::get('/marcas', [\App\Http\Controllers\MarcaController::class, 'index']);
    Route::get('/marcas/{id}', [\App\Http\Controllers\MarcaController::class, 'show']);
    Route::post('/marcas', [\App\Http\Controllers\MarcaController::class, 'store'])->middleware('permission:categorias.crear');
    Route::put('/marcas/{id}', [\App\Http\Controllers\MarcaController::class, 'update'])->middleware('permission:categorias.editar');
    Route::delete('/marcas/{id}', [\App\Http\Controllers\MarcaController::class, 'destroy'])->middleware('permission:categorias.eliminar');

    Route::get('/modelos', [\App\Http\Controllers\ModeloController::class, 'index']);
    Route::get('/modelos/{id}', [\App\Http\Controllers\ModeloController::class, 'show']);
    Route::post('/modelos', [\App\Http\Controllers\ModeloController::class, 'store'])->middleware('permission:categorias.crear');
    Route::put('/modelos/{id}', [\App\Http\Controllers\ModeloController::class, 'update'])->middleware('permission:categorias.editar');
    Route::delete('/modelos/{id}', [\App\Http\Controllers\ModeloController::class, 'destroy'])->middleware('permission:categorias.eliminar');

    // Subcategorías - Lectura libre, Escritura almacenero/admin
    Route::get('/subcategorias', [\App\Http\Controllers\SubcategoriaController::class, 'index']);
    Route::get('/subcategorias/{id}', [\App\Http\Controllers\SubcategoriaController::class, 'show']);
    Route::post('/subcategorias', [\App\Http\Controllers\SubcategoriaController::class, 'store'])->middleware('permission:categorias.crear');
    Route::put('/subcategorias/{id}', [\App\Http\Controllers\SubcategoriaController::class, 'update'])->middleware('permission:categorias.editar');
    Route::delete('/subcategorias/{id}', [\App\Http\Controllers\SubcategoriaController::class, 'destroy'])->middleware('permission:categorias.eliminar');

    // Unidades - Lectura libre, Escritura almacenero/admin
    Route::get('/unidades', [\App\Http\Controllers\UnidadController::class, 'index']);
    Route::get('/unidades/{id}', [\App\Http\Controllers\UnidadController::class, 'show']);
    Route::post('/unidades', [\App\Http\Controllers\UnidadController::class, 'store'])->middleware('permission:categorias.crear');
    Route::put('/unidades/{id}', [\App\Http\Controllers\UnidadController::class, 'update'])->middleware('permission:categorias.editar');
    Route::delete('/unidades/{id}', [\App\Http\Controllers\UnidadController::class, 'destroy'])->middleware('permission:categorias.eliminar');

    // Sucursales - Lectura para todos, Escritura solo admin
    Route::get('/sucursales', [\App\Http\Controllers\SucursalController::class, 'index']);
    Route::get('/sucursales/{id}', [\App\Http\Controllers\SucursalController::class, 'show']);
    Route::post('/sucursales', [\App\Http\Controllers\SucursalController::class, 'store'])->middleware('permission:sucursales.crear');
    Route::put('/sucursales/{id}', [\App\Http\Controllers\SucursalController::class, 'update'])->middleware('permission:sucursales.editar');
    Route::delete('/sucursales/{id}', [\App\Http\Controllers\SucursalController::class, 'destroy'])->middleware('permission:sucursales.eliminar');
    Route::post('/sucursales/{id}/upload-logo', [\App\Http\Controllers\SucursalController::class, 'uploadLogo'])->middleware('permission:sucursales.editar');

    // Proveedores - Lectura para todos, Escritura admin/gerente/compras
    Route::get('/proveedores/search', [\App\Http\Controllers\ProveedorController::class, 'search']);
    Route::get('/proveedores', [\App\Http\Controllers\ProveedorController::class, 'index']);
    Route::get('/proveedores/{id}', [\App\Http\Controllers\ProveedorController::class, 'show']);
    Route::post('/proveedores', [\App\Http\Controllers\ProveedorController::class, 'store'])->middleware('permission:proveedores.crear');
    Route::put('/proveedores/{id}', [\App\Http\Controllers\ProveedorController::class, 'update'])->middleware('permission:proveedores.editar');
    Route::delete('/proveedores/{id}', [\App\Http\Controllers\ProveedorController::class, 'destroy'])->middleware('permission:proveedores.eliminar');

    // Reportes (admin, gerente y auditor tienen acceso)
    Route::group(['middleware' => ['permission:reportes.ver']], function () {
        Route::get('/reportes/dashboard', [ReportesController::class, 'dashboardMetrics']);
        Route::get('/reportes/ventas-sucursal', [ReportesController::class, 'ventasPorSucursal']);
        Route::get('/reportes/ventas-vendedor', [ReportesController::class, 'ventasPorVendedor']);
        Route::get('/reportes/rentabilidad', [ReportesController::class, 'rentabilidadProductos']);
        Route::get('/reportes/rotacion', [ReportesController::class, 'rotacionInventario']);
        Route::get('/reportes/compras-proveedor', [ReportesController::class, 'comprasPorProveedor']);
        Route::get('/reportes/stock-bajo', [ReportesController::class, 'alertasStockMinimo']);
        Route::get('/reportes/iva', [ReportesController::class, 'impuestosIva']);
        Route::get('/reportes/ventas-categoria', [ReportesController::class, 'ventasPorCategoria']);
        Route::get('/reportes/kardex-consolidado', [ReportesController::class, 'kardexConsolidado']);
    });

    // Impresión de ticket térmico
    Route::get('/ventas/{id}/ticket', [VentasController::class, 'getTicket']);
});