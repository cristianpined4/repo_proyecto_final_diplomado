<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use App\Models\Sucursal;
use App\Models\Categoria;
use App\Models\Subcategoria;
use App\Models\Marca;
use App\Models\Modelo;
use App\Models\Unidad;

class CentroPlasticoConfigSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Configura el sistema para Centro de Plástico El Limeño – San Miguel, El Salvador
     */
    public function run(): void
    {
        $this->command->info('>>> [1/7] Configurando Sistema: Centro de Plástico El Limeño – San Miguel, El Salvador...');

        // 1. Limpiar caché de permisos Spatie
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // 2. Roles del sistema
        $roles = ['admin', 'gerente', 'vendedor', 'almacenero', 'supervisor', 'cajero', 'compras', 'auditor'];
        foreach ($roles as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        }

        // 3. Permisos granulares
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
            Permission::firstOrCreate(['name' => $permiso, 'guard_name' => 'web']);
        }

        // Asignar todos los permisos al rol admin
        $adminRole = Role::findByName('admin', 'web');
        $adminRole->syncPermissions($permisos);

        // 4. Configurar información institucional en tabla `settings`
        $configuraciones = [
            ['key' => 'empresa_nombre', 'value' => 'Centro de Plástico El Limeño', 'type' => 'string', 'group' => 'general'],
            ['key' => 'empresa_correo', 'value' => 'contacto@plasticosellimeno.com', 'type' => 'string', 'group' => 'general'],
            ['key' => 'empresa_telefono', 'value' => '2660-1234', 'type' => 'string', 'group' => 'general'],
            ['key' => 'empresa_direccion', 'value' => 'Av. Roosevelt Sur #402, San Miguel, El Salvador', 'type' => 'string', 'group' => 'general'],
            ['key' => 'empresa_moneda', 'value' => 'USD', 'type' => 'string', 'group' => 'general'],
            ['key' => 'iva_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'fiscal'],
            ['key' => 'iva_percentage', 'value' => '13', 'type' => 'decimal', 'group' => 'fiscal'],
            ['key' => 'inventory_valuation_method', 'value' => 'fifo', 'type' => 'string', 'group' => 'inventario'],
            ['key' => 'venta_serie', 'value' => 'FAC01', 'type' => 'string', 'group' => 'ventas'],
            ['key' => 'venta_ultimo_correlativo', 'value' => '10000', 'type' => 'integer', 'group' => 'ventas'],
            ['key' => 'color_primary', 'value' => '#1976D2', 'type' => 'string', 'group' => 'branding'],
            ['key' => 'color_secondary', 'value' => '#26A69A', 'type' => 'string', 'group' => 'branding'],
            ['key' => 'color_accent', 'value' => '#9C27B0', 'type' => 'string', 'group' => 'branding'],
        ];

        foreach ($configuraciones as $conf) {
            DB::table('settings')->updateOrInsert(
                ['key' => $conf['key']],
                [
                    'value' => $conf['value'],
                    'type' => $conf['type'],
                    'group' => $conf['group'],
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        // 5. Configurar Sucursales en San Miguel y zona oriental
        $sucursales = [
            [
                'id' => 1,
                'nombre' => 'Sucursal Matriz San Miguel - Centro',
                'direccion' => 'Av. Roosevelt Sur #402, Centro',
                'telefono' => '2660-1234',
                'correo' => 'matriz@plasticosellimeno.com',
                'activa' => true,
                'ciudad' => 'San Miguel',
                'estado' => 'San Miguel',
                'pais' => 'El Salvador',
            ],
            [
                'id' => 2,
                'nombre' => 'Sucursal San Miguel - Metrocentro',
                'direccion' => 'Colonia Ciudad Jardín, 8va Calle Poniente',
                'telefono' => '2661-5678',
                'correo' => 'metrocentro@plasticosellimeno.com',
                'activa' => true,
                'ciudad' => 'San Miguel',
                'estado' => 'San Miguel',
                'pais' => 'El Salvador',
            ],
            [
                'id' => 3,
                'nombre' => 'Sucursal Usulután',
                'direccion' => 'Barrio El Calvario, Calle Grimaldi',
                'telefono' => '2624-9012',
                'correo' => 'usulutan@plasticosellimeno.com',
                'activa' => true,
                'ciudad' => 'Usulután',
                'estado' => 'Usulután',
                'pais' => 'El Salvador',
            ],
        ];

        foreach ($sucursales as $sData) {
            Sucursal::updateOrCreate(['id' => $sData['id']], $sData);
        }

        // 6. Usuarios del sistema con nombres salvadoreños y roles asignados
        $usuarios = [
            [
                'username' => 'admin',
                'name' => 'Cristian',
                'lastname' => 'Pineda',
                'password' => 'admin',
                'email' => 'admin@plasticosellimeno.com',
                'phone' => '7123-4567',
                'sucursal_id' => 1,
                'role' => 'admin',
            ],
            [
                'username' => 'carlos.quintanilla',
                'name' => 'Carlos Alberto',
                'lastname' => 'Quintanilla',
                'password' => 'password123',
                'email' => 'carlos.quintanilla@plasticosellimeno.com',
                'phone' => '7234-5678',
                'sucursal_id' => 1,
                'role' => 'gerente',
            ],
            [
                'username' => 'jose.martinez',
                'name' => 'José Antonio',
                'lastname' => 'Martínez',
                'password' => 'password123',
                'email' => 'jose.martinez@plasticosellimeno.com',
                'phone' => '7345-6789',
                'sucursal_id' => 1,
                'role' => 'vendedor',
            ],
            [
                'username' => 'maria.batres',
                'name' => 'María Elena',
                'lastname' => 'Batres',
                'password' => 'password123',
                'email' => 'maria.batres@plasticosellimeno.com',
                'phone' => '7456-7890',
                'sucursal_id' => 2,
                'role' => 'vendedor',
            ],
            [
                'username' => 'francisco.granados',
                'name' => 'Francisco Javier',
                'lastname' => 'Granados',
                'password' => 'password123',
                'email' => 'francisco.granados@plasticosellimeno.com',
                'phone' => '7567-8901',
                'sucursal_id' => 3,
                'role' => 'vendedor',
            ],
            [
                'username' => 'sandra.lazo',
                'name' => 'Sandra Elizabeth',
                'lastname' => 'Lazo',
                'password' => 'password123',
                'email' => 'sandra.lazo@plasticosellimeno.com',
                'phone' => '7678-9012',
                'sucursal_id' => 1,
                'role' => 'vendedor',
            ],
            [
                'username' => 'wendy.romero',
                'name' => 'Wendy Marisol',
                'lastname' => 'Romero',
                'password' => 'password123',
                'email' => 'wendy.romero@plasticosellimeno.com',
                'phone' => '7789-0123',
                'sucursal_id' => 1,
                'role' => 'cajero',
            ],
            [
                'username' => 'kevin.villalobos',
                'name' => 'Kevin Alexander',
                'lastname' => 'Villalobos',
                'password' => 'password123',
                'email' => 'kevin.villalobos@plasticosellimeno.com',
                'phone' => '7890-1234',
                'sucursal_id' => 2,
                'role' => 'cajero',
            ],
            [
                'username' => 'manuel.portillo',
                'name' => 'Manuel de Jesús',
                'lastname' => 'Portillo',
                'password' => 'password123',
                'email' => 'manuel.portillo@plasticosellimeno.com',
                'phone' => '7901-2345',
                'sucursal_id' => 1,
                'role' => 'almacenero',
            ],
            [
                'username' => 'ana.salmeron',
                'name' => 'Ana Patricia',
                'lastname' => 'Salmerón',
                'password' => 'password123',
                'email' => 'ana.salmeron@plasticosellimeno.com',
                'phone' => '7012-3456',
                'sucursal_id' => 1,
                'role' => 'compras',
            ],
        ];

        foreach ($usuarios as $uData) {
            $user = User::updateOrCreate(
                ['username' => $uData['username']],
                [
                    'name' => $uData['name'],
                    'lastname' => $uData['lastname'],
                    'password' => Hash::make($uData['password']),
                    'email' => $uData['email'],
                    'phone' => $uData['phone'],
                    'sucursal_id' => $uData['sucursal_id'],
                    'status' => true,
                ]
            );

            if (!$user->hasRole($uData['role'])) {
                $user->syncRoles([$uData['role']]);
            }
        }

        // 7. Catálogo de Unidades de Medida
        $unidades = [
            ['id' => 1, 'nombre' => 'Unidad', 'abreviatura' => 'UND', 'estado' => true],
            ['id' => 2, 'nombre' => 'Paquete', 'abreviatura' => 'PAQ', 'estado' => true],
            ['id' => 3, 'nombre' => 'Fardo', 'abreviatura' => 'FAR', 'estado' => true],
            ['id' => 4, 'nombre' => 'Millar', 'abreviatura' => 'MIL', 'estado' => true],
            ['id' => 5, 'nombre' => 'Docena', 'abreviatura' => 'DOC', 'estado' => true],
            ['id' => 6, 'nombre' => 'Ciento', 'abreviatura' => 'CTO', 'estado' => true],
            ['id' => 7, 'nombre' => 'Caja', 'abreviatura' => 'CAJ', 'estado' => true],
            ['id' => 8, 'nombre' => 'Rollo', 'abreviatura' => 'ROL', 'estado' => true],
        ];

        foreach ($unidades as $u) {
            Unidad::updateOrCreate(['id' => $u['id']], $u);
        }

        // 8. Catálogo de Marcas y Modelos
        $marcasModelos = [
            'Plastigama' => ['Línea Clásica', 'Línea Reforzada', 'Línea Económica', 'Línea Industrial'],
            'Rimax' => ['Confort Ergonómica', 'Fiesta Apilable', 'Elegance', 'Jardín y Terraza'],
            'Reyplast' => ['Organizador Hogar', 'Contenedor Hermético', 'Multiusos', 'Línea Infantil'],
            'Termopack' => ['Térmico Estándar', 'Clamshell Térmico', 'Biodegradable', 'Exportación'],
            'Carvajal Empaques' => ['Bolsa Gabacha HD', 'Bolsa Gabacha BD', 'Strech Film Industrial', 'Film Alimentos'],
            'Plásticos Modernos' => ['Paila Plástica Pesada', 'Huacal Multiusos', 'Bote Tapa Rosca'],
            'Durespo' => ['Bidón Extra Reforzado', 'Tanque Agrícola', 'Malla Sombra UV'],
            'Cuñados Plastic' => ['Vaso Desechable Cristal', 'Plato Fiesta', 'Cubiertos Extra Fuertes'],
            'El Limeño Industrial' => ['Fabricación Propia', 'Línea Distribución', 'Especialidad San Miguel'],
            'Tupperware' => ['Modular Clásico', 'Frescura Total', 'Eco Vaso'],
        ];

        $marcaId = 1;
        foreach ($marcasModelos as $marcaNombre => $modelosList) {
            $marca = Marca::updateOrCreate(
                ['id' => $marcaId],
                ['nombre' => $marcaNombre, 'estado' => true]
            );

            foreach ($modelosList as $modNombre) {
                Modelo::firstOrCreate(
                    ['marca_id' => $marca->id, 'nombre' => $modNombre],
                    ['estado' => true]
                );
            }
            $marcaId++;
        }

        // 9. Categorías y Subcategorías del rubro de plásticos
        $categoriasSubcategorias = [
            'Bolsas y Empaques' => [
                'Bolsas Gabacha Blanca y Color',
                'Bolsas para Basura Jumbo y Hogar',
                'Bolsas Ziploc y Herméticas',
                'Bolsas en Rollo para Mercado',
                'Películas Plásticas y Strech Film'
            ],
            'Desechables y Fiestas' => [
                'Vasos Plásticos Desechables',
                'Platos Térmicos y Tendidos',
                'Cubiertos Plásticos (Cuchara, Tenedor, Cuchillo)',
                'Pajillas con y sin Envoltura',
                'Contenedores Térmicos Clamshell'
            ],
            'Contenedores y Almacenamiento' => [
                'Pailas y Huacales Plásticos',
                'Botes y Piccheles con Tapa',
                'Jarras Graduadas de Plástico',
                'Tuppers y Fiambreras Herméticas',
                'Cajas y Organizadores Multiuso'
            ],
            'Hogar y Limpieza' => [
                'Escobas y Cepillos Plásticos',
                'Basureros con Pedal y Vaivén',
                'Cubetas con Exprimidor y Baldes',
                'Cestas Plásticas para Ropa',
                'Ganchos y Pinzas para Ropa'
            ],
            'Mobiliario Plástico' => [
                'Sillas Plásticas sin Brazos (Cafetería)',
                'Sillas Plásticas con Brazos',
                'Mesas Plásticas Cuadradas y Redondas',
                'Bancos Altos y Bajos',
                'Banquitos Infantiles'
            ],
            'Línea Industrial y Agrícola' => [
                'Bidones Plásticos 55 Galones',
                'Tanques de Polietileno',
                'Malla Sombra Agrícola',
                'Plástico Negro Agrícola Ensilaje',
                'Cajas Cosecheras Plásticas'
            ],
            'Recipientes Térmicos y Bebidas' => [
                'Termos para Agua Rimax y Coleman',
                'Bules y Pachones Escolares',
                'Dispensadores de Bebidas con Grifo'
            ],
            'Juguetería y Novedades' => [
                'Pelotas Plásticas Surtidas',
                'Baldes y Juguetes de Playa',
                'Macetas y Jardineras Plásticas'
            ],
        ];

        $catId = 1;
        foreach ($categoriasSubcategorias as $catNombre => $subList) {
            $categoria = Categoria::updateOrCreate(
                ['id' => $catId],
                ['name' => $catNombre, 'status' => true]
            );

            foreach ($subList as $subNombre) {
                Subcategoria::firstOrCreate(
                    ['categoria_id' => $categoria->id, 'nombre' => $subNombre],
                    ['estado' => true]
                );
            }
            $catId++;
        }

        // Sincronizar secuencias en PostgreSQL para evitar colisiones con auto-increment
        DB::statement("SELECT setval('categorias_id_seq', (SELECT COALESCE(MAX(id), 1) FROM categorias))");
        DB::statement("SELECT setval('subcategorias_id_seq', (SELECT COALESCE(MAX(id), 1) FROM subcategorias))");
        DB::statement("SELECT setval('marcas_id_seq', (SELECT COALESCE(MAX(id), 1) FROM marcas))");
        DB::statement("SELECT setval('modelos_id_seq', (SELECT COALESCE(MAX(id), 1) FROM modelos))");
        DB::statement("SELECT setval('unidads_id_seq', (SELECT COALESCE(MAX(id), 1) FROM unidads))");
        DB::statement("SELECT setval('sucursals_id_seq', (SELECT COALESCE(MAX(id), 1) FROM sucursals))");
        DB::statement("SELECT setval('users_id_seq', (SELECT COALESCE(MAX(id), 1) FROM users))");

        $this->command->info('Configuración institucional, sucursales, roles, catálogos y usuarios listos.');
    }
}
