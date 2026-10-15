<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ComprasSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Genera 10,000 compras con soporte exacto de valuación FIFO (PEPS)
     * para Centro de Plástico El Limeño – San Miguel, El Salvador
     */
    public function run(): void
    {
        $this->command->info('>>> [5/7] Generando 10,000 Compras históricas (2016 - 2026) con lotes FIFO...');

        $total = 10000;
        $batchSize = 1000;

        $userIds = DB::table('users')->pluck('id')->toArray();
        if (empty($userIds)) $userIds = [1];

        $sucursales = DB::table('sucursals')->pluck('id')->toArray();
        if (empty($sucursales)) $sucursales = [1];

        $totalProveedores = DB::table('proveedors')->count();
        if ($totalProveedores < 1) $totalProveedores = 10000;

        $totalProductos = DB::table('productos')->count();
        if ($totalProductos < 1) $totalProductos = 10000;

        // Costos base de productos
        $costosMuestra = DB::table('productos')
            ->where('id', '<=', 1000)
            ->pluck('costo_promedio', 'id')
            ->toArray();

        // Mapeo de stock por producto y sucursal para respaldar los lotes activos de FIFO
        $stockPorProductoSucursal = [];
        $filasStock = DB::table('producto_sucursal')->select('producto_id', 'sucursal_id', 'stock_actual')->get();
        foreach ($filasStock as $fs) {
            $stockPorProductoSucursal[$fs->producto_id][$fs->sucursal_id] = $fs->stock_actual;
        }

        // Rango de fechas: 2016-01-01 hasta 2026-10-11
        $startTs = strtotime('2016-01-01 08:00:00');
        $endTs = strtotime('2026-09-01 18:00:00');
        $tsInterval = ($endTs - $startTs) / 9850;

        $comprasBatch = [];
        $detallesBatch = [];
        $comprasInsertadas = 0;

        for ($i = 1; $i <= $total; $i++) {
            $proveedorId = (($i * 3) % $totalProveedores) + 1;
            $sucursalId = $sucursales[$i % count($sucursales)];
            $userId = $userIds[$i % count($userIds)];

            // Estados válidos: 'pagado' (96%), 'cancelada' (2.5%), 'pendiente' (1.5%)
            $randStatus = $i % 70;
            if ($randStatus === 0) {
                $status = 'cancelada';
            } elseif ($randStatus === 1) {
                $status = 'pendiente';
            } else {
                $status = 'pagado';
            }

            // Distribución temporal realista garantizando actividad reciente para el Dashboard
            if ($i > 9985) {
                // Últimas 15 compras: HOY (2026-10-11 entre 08:00 y 17:30)
                $itemTs = strtotime('2026-10-11 08:00:00') + (($i - 9985) * 2100);
            } elseif ($i > 9930) {
                // Compras en los últimos 7 días (2026-10-04 a 2026-10-10)
                $itemTs = strtotime('2026-10-04 08:00:00') + (($i - 9930) * 9800);
            } elseif ($i > 9850) {
                // Compras en el último mes (2026-09-12 a 2026-10-03)
                $itemTs = strtotime('2026-09-12 08:00:00') + (($i - 9850) * 22000);
            } else {
                // Histórico cronológico 2016 a agosto 2026
                $itemTs = (int)($startTs + ($i * $tsInterval) + rand(-1800, 1800));
            }

            $createdAt = date('Y-m-d H:i:s', $itemTs);
            $year = date('Y', $itemTs);

            $numDocTipo = ($i % 2 === 0) ? 'CCF' : 'FAC';
            $numeroFactura = sprintf('%s-%s-%06d', $numDocTipo, $year, $i);

            // Generar entre 1 y 3 ítems por compra
            $numItems = ($i % 3) + 1;
            $subtotalCompra = 0.0;

            for ($itemIdx = 1; $itemIdx <= $numItems; $itemIdx++) {
                $prodId = ((($i * 13) + $itemIdx) % $totalProductos) + 1;
                $sampleCosto = $costosMuestra[($prodId % 1000) + 1] ?? 5.50;
                $precioCompra = round($sampleCosto, 2);

                // LÓGICA FIFO PARA LOTES:
                // Las compras históricas antiguas (i <= 5000, años 2016-2023) ya fueron consumidas por ventas (cantidad_restante = 0).
                // Las compras más recientes (i > 5000, 2024-2026) corresponden a los lotes activos con existencia disponible.
                if ($status === 'pagado') {
                    if ($i > 5000) {
                        $stockSuc = $stockPorProductoSucursal[$prodId][$sucursalId] ?? 15;
                        $cantidadRestante = $stockSuc;
                        $cantidad = max($stockSuc, 15 + (($i + $itemIdx) % 80));
                    } else {
                        // Lote agotado por ventas pasadas
                        $cantidadRestante = 0;
                        $cantidad = 20 + (($i + $itemIdx) % 100);
                    }
                } else {
                    $cantidadRestante = 0;
                    $cantidad = 10 + (($i + $itemIdx) % 50);
                }

                $subtotalItem = round($cantidad * $precioCompra, 2);
                $subtotalCompra += $subtotalItem;

                $detallesBatch[] = [
                    'compra_id' => $i,
                    'producto_id' => $prodId,
                    'cantidad' => $cantidad,
                    'precio_compra' => $precioCompra,
                    'cantidad_restante' => $cantidadRestante,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ];
            }

            $montoIva = round($subtotalCompra * 0.13, 2);
            $totalCompra = round($subtotalCompra + $montoIva, 2);

            $comprasBatch[] = [
                'id' => $i,
                'proveedor_id' => $proveedorId,
                'user_id' => $userId,
                'sucursal_id' => $sucursalId,
                'numero_factura' => $numeroFactura,
                'status' => $status,
                'subtotal' => $subtotalCompra,
                'porcentaje_iva_aplicado' => 13.00,
                'monto_iva' => $montoIva,
                'total_compra' => $totalCompra,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ];

            if (count($comprasBatch) >= $batchSize) {
                DB::table('compras')->insert($comprasBatch);
                DB::table('detalles_compras')->insert($detallesBatch);
                $comprasInsertadas += count($comprasBatch);
                $comprasBatch = [];
                $detallesBatch = [];
                $this->command->getOutput()->write("\rCompras insertadas: {$comprasInsertadas} / {$total}");
            }
        }

        if (!empty($comprasBatch)) {
            DB::table('compras')->insert($comprasBatch);
            DB::table('detalles_compras')->insert($detallesBatch);
            $comprasInsertadas += count($comprasBatch);
        }

        // Sincronizar secuencias en PostgreSQL
        DB::statement("SELECT setval('compras_id_seq', (SELECT MAX(id) FROM compras))");
        DB::statement("SELECT setval('detalles_compras_id_seq', (SELECT MAX(id) FROM detalles_compras))");

        $this->command->info("\nCompras completadas exitosamente: {$comprasInsertadas} compras registradas bajo modelo FIFO.");
    }
}
