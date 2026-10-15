<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VentasSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Genera 10,000 ventas históricas (2016 - 2026) con sus detalles para Centro de Plástico El Limeño
     */
    public function run(): void
    {
        $this->command->info('>>> [6/7] Generando 10,000 Ventas históricas (2016 - 2026) con sus detalles...');

        $total = 10000;
        $batchSize = 1000;

        // Vendedores y cajeros para asignación realista de ventas
        $userIds = DB::table('users')->pluck('id')->toArray();
        if (empty($userIds)) $userIds = [1];

        $sucursales = DB::table('sucursals')->pluck('id')->toArray();
        if (empty($sucursales)) $sucursales = [1];

        $totalClientes = DB::table('clientes')->count();
        if ($totalClientes < 1) $totalClientes = 10000;

        $totalProductos = DB::table('productos')->count();
        if ($totalProductos < 1) $totalProductos = 10000;

        // Precios de muestra para cálculos exactos
        $preciosMuestra = DB::table('productos')
            ->where('id', '<=', 1000)
            ->pluck('precio_venta', 'id')
            ->toArray();

        // Rango de fechas: 2016-01-01 hasta 2026-10-11
        $startTs = strtotime('2016-01-01 08:00:00');
        $endTs = strtotime('2026-10-11 18:00:00');
        $tsInterval = ($endTs - $startTs) / $total;

        $ventasBatch = [];
        $detallesBatch = [];
        $ventasInsertadas = 0;

        for ($i = 1; $i <= $total; $i++) {
            $clienteId = (($i * 7) % $totalClientes) + 1;
            $sucursalId = $sucursales[$i % count($sucursales)];
            $userId = $userIds[$i % count($userIds)];

            // Estados válidos según check constraint: 'pagada', 'anulada', 'devolucion', 'pendiente'
            $randStatus = $i % 100;
            if ($randStatus < 5) {
                $status = 'anulada';
            } elseif ($randStatus < 7) {
                $status = 'devolucion';
            } elseif ($randStatus < 8) {
                $status = 'pendiente';
            } else {
                $status = 'pagada';
            }

            // Métodos de pago
            $randPago = $i % 10;
            if ($randPago < 6) {
                $metodoPago = 'efectivo';
            } elseif ($randPago < 8) {
                $metodoPago = 'tarjeta';
            } else {
                $metodoPago = 'transferencia';
            }

            // Series de facturación: FAC01 (Consumidor Final), CCF01 (Crédito Fiscal), TIQ01 (Tiquete)
            $randSerie = $i % 10;
            if ($randSerie < 7) {
                $serie = 'FAC01';
            } elseif ($randSerie < 9) {
                $serie = 'CCF01';
            } else {
                $serie = 'TIQ01';
            }

            $correlativo = sprintf('%08d', $i);

            // Fechas cronológicas distribuidas desde 2016 hasta 2026 con actividad reciente para el Dashboard
            if ($i > 9985) {
                // Últimas 15 ventas: HOY (2026-10-11 entre 08:30 y 18:30)
                $itemTs = strtotime('2026-10-11 08:30:00') + (($i - 9985) * 2300);
            } elseif ($i > 9930) {
                // Ventas en los últimos 7 días (2026-10-04 a 2026-10-10)
                $itemTs = strtotime('2026-10-04 08:30:00') + (($i - 9930) * 9800);
            } elseif ($i > 9850) {
                // Ventas en el último mes (2026-09-12 a 2026-10-03)
                $itemTs = strtotime('2026-09-12 08:30:00') + (($i - 9850) * 22000);
            } else {
                // Histórico cronológico 2016 a agosto 2026
                $itemTs = (int)($startTs + ($i * $tsInterval) + rand(-1800, 1800));
            }

            $createdAt = date('Y-m-d H:i:s', $itemTs);

            // Generar entre 1 y 3 detalles por venta
            $numItems = ($i % 3) + 1;
            $subtotalVenta = 0.0;

            for ($itemIdx = 1; $itemIdx <= $numItems; $itemIdx++) {
                $prodId = ((($i * 17) + $itemIdx) % $totalProductos) + 1;
                $samplePrecio = $preciosMuestra[($prodId % 1000) + 1] ?? 7.25;
                $cantidad = 1 + (($i + $itemIdx) % 18);
                $precioVenta = round($samplePrecio, 2);
                $subtotalItem = round($cantidad * $precioVenta, 2);
                $subtotalVenta += $subtotalItem;

                $detallesBatch[] = [
                    'venta_id' => $i,
                    'producto_id' => $prodId,
                    'cantidad' => $cantidad,
                    'precio_venta' => $precioVenta,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ];
            }

            $montoIva = round($subtotalVenta * 0.13, 2);
            $totalVenta = round($subtotalVenta + $montoIva, 2);

            // Cálculo de monto recibido y vuelto
            if ($metodoPago === 'efectivo') {
                if ($totalVenta <= 5.0) {
                    $montoRecibido = 5.0;
                } elseif ($totalVenta <= 10.0) {
                    $montoRecibido = 10.0;
                } elseif ($totalVenta <= 20.0) {
                    $montoRecibido = 20.0;
                } elseif ($totalVenta <= 50.0) {
                    $montoRecibido = 50.0;
                } elseif ($totalVenta <= 100.0) {
                    $montoRecibido = 100.0;
                } else {
                    $montoRecibido = ceil($totalVenta / 10) * 10;
                }
                $vuelto = round($montoRecibido - $totalVenta, 2);
            } else {
                $montoRecibido = $totalVenta;
                $vuelto = 0.0;
            }

            $ventasBatch[] = [
                'id' => $i,
                'cliente_id' => $clienteId,
                'user_id' => $userId,
                'sucursal_id' => $sucursalId,
                'serie' => $serie,
                'correlativo' => $correlativo,
                'status' => $status,
                'subtotal' => $subtotalVenta,
                'porcentaje_iva_aplicado' => 13.00,
                'monto_iva' => $montoIva,
                'total_venta' => $totalVenta,
                'metodo_pago' => $metodoPago,
                'monto_recibido' => $montoRecibido,
                'vuelto' => $vuelto,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ];

            if (count($ventasBatch) >= $batchSize) {
                DB::table('ventas')->insert($ventasBatch);
                DB::table('detalles_ventas')->insert($detallesBatch);
                $ventasInsertadas += count($ventasBatch);
                $ventasBatch = [];
                $detallesBatch = [];
                $this->command->getOutput()->write("\rVentas insertadas: {$ventasInsertadas} / {$total}");
            }
        }

        if (!empty($ventasBatch)) {
            DB::table('ventas')->insert($ventasBatch);
            DB::table('detalles_ventas')->insert($detallesBatch);
            $ventasInsertadas += count($ventasBatch);
        }

        // Sincronizar secuencias en PostgreSQL
        DB::statement("SELECT setval('ventas_id_seq', (SELECT MAX(id) FROM ventas))");
        DB::statement("SELECT setval('detalles_ventas_id_seq', (SELECT MAX(id) FROM detalles_ventas))");

        $this->command->info("\nVentas completadas exitosamente: {$ventasInsertadas} ventas registradas.");
    }
}
