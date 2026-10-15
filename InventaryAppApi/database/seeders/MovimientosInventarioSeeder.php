<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MovimientosInventarioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Genera 10,000 movimientos de inventario y 10,000 registros de kardex histórico (2016 - 2026)
     */
    public function run(): void
    {
        $this->command->info('>>> [7/7] Generando 10,000 Movimientos de Inventario y Kardex histórico (2016 - 2026)...');

        $total = 10000;
        $batchSize = 1000;

        $userIds = DB::table('users')->pluck('id')->toArray();
        if (empty($userIds)) $userIds = [1];

        $sucursales = DB::table('sucursals')->pluck('id')->toArray();
        if (empty($sucursales)) $sucursales = [1];

        $totalProductos = DB::table('productos')->count();
        if ($totalProductos < 1) $totalProductos = 10000;

        // Costos de muestra de productos
        $costosMuestra = DB::table('productos')
            ->where('id', '<=', 1000)
            ->pluck('costo_promedio', 'id')
            ->toArray();

        // Razones según tipo de movimiento (respetando check constraint: 'entrada' o 'salida')
        $razonesEntrada = [
            'Compra de mercadería ingresada a bodega',
            'Devolución de cliente por exceso de pedido',
            'Ajuste de inventario físico mensual (sobrante)',
            'Recepción de traslado de mercadería entre sucursales',
            'Reingreso de producto por cancelación de pedido',
            'Ajuste positivo por conteo físico anual',
        ];

        $razonesSalida = [
            'Venta directa despachada en sucursal',
            'Merma por producto plástico golpeado o quebrado',
            'Traslado de mercadería a otra sucursal',
            'Ajuste de inventario físico mensual (faltante)',
            'Salida de muestra comercial para cliente mayorista',
            'Baja de inventario por daño en manipulación',
        ];

        // Rango de fechas: 2016-01-01 hasta 2026-10-11
        $startTs = strtotime('2016-01-01 08:00:00');
        $endTs = strtotime('2026-10-11 18:00:00');
        $tsInterval = ($endTs - $startTs) / $total;

        $movimientosBatch = [];
        $kardexBatch = [];
        $movimientosInsertados = 0;

        for ($i = 1; $i <= $total; $i++) {
            $productoId = (($i * 19) % $totalProductos) + 1;
            $sucursalId = $sucursales[$i % count($sucursales)];
            $userId = $userIds[$i % count($userIds)];

            // Tipo de movimiento: 55% entrada, 45% salida
            $isEntrada = ($i % 10 < 6);
            $tipo = $isEntrada ? 'entrada' : 'salida';
            $razon = $isEntrada 
                ? $razonesEntrada[($i) % count($razonesEntrada)]
                : $razonesSalida[($i) % count($razonesSalida)];

            $costoUnitario = round($costosMuestra[($productoId % 1000) + 1] ?? 4.50, 2);
            $cantidadMovimiento = 5 + (($i * 3) % 45);

            // Simulación de cantidades coherentes de Kardex
            $cantidadAntes = 50 + (($i * 7) % 250);
            $cantidadDespues = $isEntrada 
                ? ($cantidadAntes + $cantidadMovimiento) 
                : max(0, $cantidadAntes - $cantidadMovimiento);

            // Fechas cronológicas distribuidas desde 2016 hasta 2026
            $itemTs = (int)($startTs + ($i * $tsInterval) + rand(-1800, 1800));
            if ($itemTs > $endTs) $itemTs = $endTs;
            $createdAt = date('Y-m-d H:i:s', $itemTs);

            $movimientosBatch[] = [
                'id' => $i,
                'producto_id' => $productoId,
                'sucursal_id' => $sucursalId,
                'user_id' => $userId,
                'tipo' => $tipo,
                'cantidad' => $cantidadMovimiento,
                'costo_unitario' => $costoUnitario,
                'razon' => $razon,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ];

            $kardexBatch[] = [
                'id' => $i,
                'producto_id' => $productoId,
                'sucursal_id' => $sucursalId,
                'user_id' => $userId,
                'cantidad_antes' => $cantidadAntes,
                'costo_antes' => $costoUnitario,
                'cantidad_movimiento' => $cantidadMovimiento,
                'costo_movimiento' => $costoUnitario,
                'cantidad_despues' => $cantidadDespues,
                'costo_despues' => $costoUnitario,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ];

            if (count($movimientosBatch) >= $batchSize) {
                DB::table('movimientos_inventarios')->insert($movimientosBatch);
                DB::table('kardexes')->insert($kardexBatch);
                $movimientosInsertados += count($movimientosBatch);
                $movimientosBatch = [];
                $kardexBatch = [];
                $this->command->getOutput()->write("\rMovimientos y Kardex insertados: {$movimientosInsertados} / {$total}");
            }
        }

        if (!empty($movimientosBatch)) {
            DB::table('movimientos_inventarios')->insert($movimientosBatch);
            DB::table('kardexes')->insert($kardexBatch);
            $movimientosInsertados += count($movimientosBatch);
        }

        // Sincronizar secuencias en PostgreSQL
        DB::statement("SELECT setval('movimientos_inventarios_id_seq', (SELECT MAX(id) FROM movimientos_inventarios))");
        DB::statement("SELECT setval('kardexes_id_seq', (SELECT MAX(id) FROM kardexes))");

        $this->command->info("\nMovimientos de inventario y Kardex completados exitosamente: {$movimientosInsertados} registros.");
    }
}
