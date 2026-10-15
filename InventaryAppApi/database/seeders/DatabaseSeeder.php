<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     * Llena la base de datos completa con 10,000 registros por tabla principal
     * para el negocio "Centro de Plástico El Limeño – San Miguel, El Salvador".
     */
    public function run(): void
    {
        $startTime = microtime(true);

        $this->command->info('========================================================================');
        $this->command->info('  INICIANDO CARGA MASIVA DE DATOS PARA CENTRO DE PLÁSTICO EL LIMEÑO     ');
        $this->command->info('  SAN MIGUEL, EL SALVADOR (10,000 REGISTROS POR TABLA PRINCIPAL)        ');
        $this->command->info('========================================================================');

        // Limpieza segura en cascada para evitar colisiones de llaves primarias si se re-ejecuta
        $this->command->warn('Limpiando tablas de datos previas para garantizar integridad...');
        DB::statement('
            TRUNCATE TABLE 
                kardexes, 
                movimientos_inventarios, 
                detalles_ventas, 
                ventas, 
                detalles_compras, 
                compras, 
                producto_sucursal, 
                productos, 
                clientes, 
                proveedors 
            RESTART IDENTITY CASCADE
        ');

        // Ejecutar seeders en orden de dependencias de claves foráneas
        $this->call([
            CentroPlasticoConfigSeeder::class,
            ProveedoresSeeder::class,
            ClientesSeeder::class,
            ProductosSeeder::class,
            ComprasSeeder::class,
            VentasSeeder::class,
            MovimientosInventarioSeeder::class,
        ]);

        // Sincronizar secuencias de claves primarias en PostgreSQL
        $secuencias = [
            'categorias_id_seq' => 'categorias',
            'subcategorias_id_seq' => 'subcategorias',
            'marcas_id_seq' => 'marcas',
            'modelos_id_seq' => 'modelos',
            'unidads_id_seq' => 'unidads',
            'sucursals_id_seq' => 'sucursals',
            'users_id_seq' => 'users',
            'productos_id_seq' => 'productos',
            'producto_sucursal_id_seq' => 'producto_sucursal',
            'clientes_id_seq' => 'clientes',
            'proveedors_id_seq' => 'proveedors',
            'compras_id_seq' => 'compras',
            'detalles_compras_id_seq' => 'detalles_compras',
            'ventas_id_seq' => 'ventas',
            'detalles_ventas_id_seq' => 'detalles_ventas',
            'movimientos_inventarios_id_seq' => 'movimientos_inventarios',
            'kardexes_id_seq' => 'kardexes',
        ];

        foreach ($secuencias as $seq => $tbl) {
            DB::statement("SELECT setval('{$seq}', (SELECT COALESCE(MAX(id), 1) FROM {$tbl}))");
        }

        // Limpiar caché de la aplicación para que métricas del Dashboard se refresquen en vivo
        \Illuminate\Support\Facades\Cache::flush();

        $duration = round(microtime(true) - $startTime, 2);

        $this->command->info('========================================================================');
        $this->command->info("  PROCESO DE SEEDING COMPLETADO EXITOSAMENTE EN {$duration} SEGUNDOS");
        $this->command->info('========================================================================');
    }
}