-- optimización de consultas dw (inventory_dw / esquema dw)
-- pruebas con explain analyze sobre el modelo estrella

\timing on

-- 1. cubo de ventas, costos y margen por categoría y sucursal
select 
    dp.categoria,
    dp.marca,
    dt.anio,
    dt.nombre_mes,
    count(distinct fv.venta_id_bk) as total_transacciones,
    sum(fv.cantidad) as total_unidades,
    round(sum(fv.subtotal_linea), 2) as venta_neta_total,
    round(sum(fv.costo_total), 2) as costo_mercancia_vendida,
    round(sum(fv.utilidad_bruta), 2) as utilidad_bruta_acumulada,
    round((sum(fv.utilidad_bruta) / nullif(sum(fv.subtotal_linea), 0) * 100)::numeric, 2) as margen_bruto_pct
from dw.fact_ventas fv
join dw.dim_producto dp on fv.producto_sk = dp.producto_sk
join dw.dim_tiempo dt on fv.fecha_sk = dt.fecha_sk
join dw.dim_sucursal ds on fv.sucursal_sk = ds.sucursal_sk
where dt.anio = 2026 and ds.sucursal_bk = 1
group by dp.categoria, dp.marca, dt.anio, dt.nombre_mes, dt.mes
order by venta_neta_total desc;

-- explain antes del índice:
explain (analyze, buffers, verbose, costs, timing)
select 
    dp.categoria,
    dp.marca,
    dt.anio,
    dt.nombre_mes,
    count(distinct fv.venta_id_bk) as total_transacciones,
    sum(fv.cantidad) as total_unidades,
    round(sum(fv.subtotal_linea), 2) as venta_neta_total,
    round(sum(fv.costo_total), 2) as costo_mercancia_vendida,
    round(sum(fv.utilidad_bruta), 2) as utilidad_bruta_acumulada,
    round((sum(fv.utilidad_bruta) / nullif(sum(fv.subtotal_linea), 0) * 100)::numeric, 2) as margen_bruto_pct
from dw.fact_ventas fv
join dw.dim_producto dp on fv.producto_sk = dp.producto_sk
join dw.dim_tiempo dt on fv.fecha_sk = dt.fecha_sk
join dw.dim_sucursal ds on fv.sucursal_sk = ds.sucursal_sk
where dt.anio = 2026 and ds.sucursal_bk = 1
group by dp.categoria, dp.marca, dt.anio, dt.nombre_mes, dt.mes
order by venta_neta_total desc;

/*
Sort  (cost=991.30..991.32 rows=7 width=191) (actual time=27.285..27.319 rows=222 loops=1)
  Output: dp.categoria, dp.marca, dt.anio, dt.nombre_mes, (count(DISTINCT fv.venta_id_bk)), (sum(fv.cantidad)), (round(sum(fv.subtotal_linea), 2)), (round(sum(fv.costo_total), 2)), (round(sum(fv.utilidad_bruta), 2)), (round(((sum(fv.utilidad_bruta) / NULLIF(sum(fv.subtotal_linea), '0'::numeric)) * '100'::numeric), 2)), dt.mes
  Sort Key: (round(sum(fv.subtotal_linea), 2)) DESC
  Sort Method: quicksort  Memory: 51kB
  Buffers: shared hit=10593
  ->  GroupAggregate  (cost=990.79..991.21 rows=7 width=191) (actual time=25.961..26.888 rows=222 loops=1)
        Output: dp.categoria, dp.marca, dt.nombre_mes, dt.mes, count(DISTINCT fv.venta_id_bk), sum(fv.cantidad), round(sum(fv.subtotal_linea), 2), round(sum(fv.costo_total), 2), round(sum(fv.utilidad_bruta), 2), round(((sum(fv.utilidad_bruta) / NULLIF(sum(fv.subtotal_linea), '0'::numeric)) * '100'::numeric), 2), dt.mes
        Group Key: dp.categoria, dp.marca, dt.nombre_mes, dt.mes
        Buffers: shared hit=10590
        ->  Sort  (cost=990.79..990.80 rows=7 width=73) (actual time=25.887..25.923 rows=222 loops=1)
              Output: dp.categoria, dp.marca, dt.nombre_mes, dt.mes, dt.anio, fv.venta_id_bk, fv.cantidad, fv.subtotal_linea, fv.costo_total, fv.utilidad_bruta
              Sort Key: dp.categoria, dp.marca, dt.nombre_mes, dt.mes, fv.venta_id_bk
              Sort Method: quicksort  Memory: 48kB
              Buffers: shared hit=10590
              ->  Nested Loop  (cost=8.74..990.69 rows=7 width=73) (actual time=17.558..24.998 rows=222 loops=1)
                    Output: dp.categoria, dp.marca, dt.nombre_mes, dt.mes, dt.anio, fv.venta_id_bk, fv.cantidad, fv.subtotal_linea, fv.costo_total, fv.utilidad_bruta
                    Inner Unique: true
                    Buffers: shared hit=10581
                    ->  Nested Loop  (cost=8.46..987.58 rows=7 width=41) (actual time=17.498..21.916 rows=222 loops=1)
                          Output: fv.venta_id_bk, fv.cantidad, fv.subtotal_linea, fv.costo_total, fv.utilidad_bruta, fv.producto_sk, dt.anio, dt.nombre_mes, dt.mes
                          Inner Unique: true
                          Buffers: shared hit=9915
                          ->  Hash Join  (cost=8.17..950.77 rows=115 width=34) (actual time=0.091..12.698 rows=3068 loops=1)
                                Output: fv.venta_id_bk, fv.cantidad, fv.subtotal_linea, fv.costo_total, fv.utilidad_bruta, fv.producto_sk, fv.fecha_sk
                                Inner Unique: true
                                Hash Cond: (fv.sucursal_sk = ds.sucursal_sk)
                                Buffers: shared hit=711
                                ->  Seq Scan on dw.fact_ventas fv  (cost=0.00..893.01 rows=18401 width=38) (actual time=0.014..6.979 rows=18401 loops=1)
                                      Output: fv.venta_hecho_sk, fv.fecha_sk, fv.sucursal_sk, fv.producto_sk, fv.cliente_sk, fv.usuario_sk, fv.venta_id_bk, fv.detalle_venta_id_bk, fv.serie_correlativo, fv.metodo_pago, fv.cantidad, fv.precio_unitario, fv.subtotal_linea, fv.monto_iva_linea, fv.total_linea, fv.costo_unitario, fv.costo_total, fv.utilidad_bruta, fv.created_at
                                      Buffers: shared hit=709
                                ->  Hash  (cost=8.16..8.16 rows=1 width=4) (actual time=0.040..0.041 rows=1 loops=1)
                                      Output: ds.sucursal_sk
                                      Buckets: 1024  Batches: 1  Memory Usage: 9kB
                                      Buffers: shared hit=2
                                      ->  Index Scan using dim_sucursal_sucursal_bk_key on dw.dim_sucursal ds  (cost=0.14..8.16 rows=1 width=4) (actual time=0.025..0.026 rows=1 loops=1)
                                            Output: ds.sucursal_sk
                                            Index Cond: (ds.sucursal_bk = 1)
                                            Buffers: shared hit=2
                          ->  Index Scan using dim_tiempo_pkey on dw.dim_tiempo dt  (cost=0.28..0.32 rows=1 width=15) (actual time=0.002..0.002 rows=0 loops=3068)
                                Output: dt.fecha_sk, dt.fecha, dt.anio, dt.semestre, dt.trimestre, dt.mes, dt.nombre_mes, dt.anio_mes, dt.semana_anio, dt.dia, dt.dia_semana, dt.nombre_dia, dt.es_fin_de_semana
                                Index Cond: (dt.fecha_sk = fv.fecha_sk)
                                Filter: (dt.anio = 2026)
                                Rows Removed by Filter: 1
                                Buffers: shared hit=9204
                    ->  Index Scan using dim_producto_pkey on dw.dim_producto dp  (cost=0.29..0.44 rows=1 width=40) (actual time=0.013..0.013 rows=1 loops=222)
                          Output: dp.producto_sk, dp.producto_bk, dp.sku, dp.nombre, dp.categoria, dp.subcategoria, dp.marca, dp.modelo, dp.unidad_medida, dp.costo_promedio, dp.precio_venta, dp.aplica_iva, dp.tipo, dp.estado
                          Index Cond: (dp.producto_sk = fv.producto_sk)
                          Buffers: shared hit=666
Planning Time: 11.241 ms
Execution Time: 28.101 ms
*/

-- problema: seq scan completo en fact_ventas (se lee 709 buffers y 18k filas) para filtrar sucursal y fecha
-- solución: índice compuesto por las llaves foráneas de mayor uso
create index idx_fact_ventas_reporting 
on dw.fact_ventas (fecha_sk, sucursal_sk, producto_sk);

-- explain después del índice:
explain (analyze, buffers, verbose, costs, timing)
select 
    dp.categoria,
    dp.marca,
    dt.anio,
    dt.nombre_mes,
    count(distinct fv.venta_id_bk) as total_transacciones,
    sum(fv.cantidad) as total_unidades,
    round(sum(fv.subtotal_linea), 2) as venta_neta_total,
    round(sum(fv.costo_total), 2) as costo_mercancia_vendida,
    round(sum(fv.utilidad_bruta), 2) as utilidad_bruta_acumulada,
    round((sum(fv.utilidad_bruta) / nullif(sum(fv.subtotal_linea), 0) * 100)::numeric, 2) as margen_bruto_pct
from dw.fact_ventas fv
join dw.dim_producto dp on fv.producto_sk = dp.producto_sk
join dw.dim_tiempo dt on fv.fecha_sk = dt.fecha_sk
join dw.dim_sucursal ds on fv.sucursal_sk = ds.sucursal_sk
where dt.anio = 2026 and ds.sucursal_bk = 1
group by dp.categoria, dp.marca, dt.anio, dt.nombre_mes, dt.mes
order by venta_neta_total desc;

/*
Sort  (cost=991.30..991.32 rows=7 width=191) (actual time=17.014..17.035 rows=222 loops=1)
  Output: dp.categoria, dp.marca, dt.anio, dt.nombre_mes, (count(DISTINCT fv.venta_id_bk)), (sum(fv.cantidad)), (round(sum(fv.subtotal_linea), 2)), (round(sum(fv.costo_total), 2)), (round(sum(fv.utilidad_bruta), 2)), (round(((sum(fv.utilidad_bruta) / NULLIF(sum(fv.subtotal_linea), '0'::numeric)) * '100'::numeric), 2)), dt.mes
  Sort Key: (round(sum(fv.subtotal_linea), 2)) DESC
  Sort Method: quicksort  Memory: 51kB
  Buffers: shared hit=10581
  ->  GroupAggregate  (cost=990.79..991.21 rows=7 width=191) (actual time=15.914..16.758 rows=222 loops=1)
        ...
Planning Time: 2.316 ms
Execution Time: 17.424 ms
*/

-- resultado: ejecución bajó de 28.1 ms a 17.4 ms y la planeación cayó de 11.2 ms a 2.3 ms


-- 2. alertas de stock crítico en fact_inventario_snapshot
select 
    ds.nombre as sucursal,
    dp.sku,
    dp.nombre as producto,
    dp.categoria,
    dp.marca,
    fis.stock_actual,
    fis.costo_promedio,
    fis.precio_venta,
    fis.valor_inventario_costo
from dw.fact_inventario_snapshot fis
join dw.dim_producto dp on fis.producto_sk = dp.producto_sk
join dw.dim_sucursal ds on fis.sucursal_sk = ds.sucursal_sk
join dw.dim_tiempo dt on fis.fecha_sk = dt.fecha_sk
where fis.stock_bajo_alerta = true
order by fis.stock_actual asc
limit 100;

-- explain antes del índice parcial:
explain (analyze, buffers, verbose, costs, timing)
select 
    ds.nombre as sucursal,
    dp.sku,
    dp.nombre as producto,
    dp.categoria,
    dp.marca,
    fis.stock_actual,
    fis.costo_promedio,
    fis.precio_venta,
    fis.valor_inventario_costo
from dw.fact_inventario_snapshot fis
join dw.dim_producto dp on fis.producto_sk = dp.producto_sk
join dw.dim_sucursal ds on fis.sucursal_sk = ds.sucursal_sk
join dw.dim_tiempo dt on fis.fecha_sk = dt.fecha_sk
where fis.stock_bajo_alerta = true
order by fis.stock_actual asc
limit 100;

/*
Limit  (cost=2347.52..2347.77 rows=100 width=446) (actual time=190.377..190.409 rows=100 loops=1)
  Output: ds.nombre, dp.sku, dp.nombre, dp.categoria, dp.marca, fis.stock_actual, fis.costo_promedio, fis.precio_venta, fis.valor_inventario_costo
  Buffers: shared hit=1282
  ->  Sort  (cost=2347.52..2355.98 rows=3384 width=446) (actual time=190.375..190.392 rows=100 loops=1)
        Output: ds.nombre, dp.sku, dp.nombre, dp.categoria, dp.marca, fis.stock_actual, fis.costo_promedio, fis.precio_venta, fis.valor_inventario_costo
        Sort Key: fis.stock_actual
        Sort Method: top-N heapsort  Memory: 67kB
        Buffers: shared hit=1282
        ->  Nested Loop  (cost=832.92..2218.19 rows=3384 width=446) (actual time=54.500..186.093 rows=3320 loops=1)
              Output: ds.nombre, dp.sku, dp.nombre, dp.categoria, dp.marca, fis.stock_actual, fis.costo_promedio, fis.precio_venta, fis.valor_inventario_costo
              Inner Unique: true
              Buffers: shared hit=1282
              ->  Hash Join  (cost=832.62..2132.62 rows=3384 width=450) (actual time=54.286..181.108 rows=3320 loops=1)
                    Output: fis.stock_actual, fis.costo_promedio, fis.precio_venta, fis.valor_inventario_costo, fis.fecha_sk, dp.sku, dp.nombre, dp.categoria, dp.marca, ds.nombre
                    Inner Unique: true
                    Hash Cond: (fis.sucursal_sk = ds.sucursal_sk)
                    Buffers: shared hit=1277
                    ->  Hash Join  (cost=819.02..2109.91 rows=3384 width=136) (actual time=54.213..178.877 rows=3320 loops=1)
                          Output: fis.stock_actual, fis.costo_promedio, fis.precio_venta, fis.valor_inventario_costo, fis.sucursal_sk, fis.fecha_sk, dp.sku, dp.nombre, dp.categoria, dp.marca
                          Inner Unique: true
                          Hash Cond: (fis.producto_sk = dp.producto_sk)
                          Buffers: shared hit=1276
                          ->  Seq Scan on dw.fact_inventario_snapshot fis  (cost=0.00..1282.00 rows=3384 width=34) (actual time=0.187..120.453 rows=3320 loops=1)
                                Output: fis.inventario_snapshot_sk, fis.fecha_sk, fis.sucursal_sk, fis.producto_sk, fis.stock_actual, fis.costo_promedio, fis.precio_venta, fis.valor_inventario_costo, fis.valor_inventario_venta, fis.stock_bajo_alerta, fis.created_at
                                Filter: fis.stock_bajo_alerta
                                Rows Removed by Filter: 56680
                                Buffers: shared hit=682
                          ->  Hash  (cost=694.01..694.01 rows=10001 width=110) (actual time=53.849..53.850 rows=10001 loops=1)
                                Output: dp.sku, dp.nombre, dp.categoria, dp.marca, dp.producto_sk
                                Buckets: 16384  Batches: 1  Memory Usage: 1555kB
                                Buffers: shared hit=594
                                ->  Seq Scan on dw.dim_producto dp  (cost=0.00..694.01 rows=10001 width=110) (actual time=0.073..41.403 rows=10001 loops=1)
                                      Output: dp.sku, dp.nombre, dp.categoria, dp.marca, dp.producto_sk
                                      Buffers: shared hit=594
                    ->  Hash  (cost=11.60..11.60 rows=160 width=322) (actual time=0.043..0.043 rows=4 loops=1)
                          Output: ds.nombre, ds.sucursal_sk
                          Buckets: 1024  Batches: 1  Memory Usage: 9kB
                          Buffers: shared hit=1
                          ->  Seq Scan on dw.dim_sucursal ds  (cost=0.00..11.60 rows=160 width=322) (actual time=0.020..0.022 rows=4 loops=1)
                                Output: ds.nombre, ds.sucursal_sk
                                Buffers: shared hit=1
              ->  Memoize  (cost=0.29..0.34 rows=1 width=4) (actual time=0.001..0.001 rows=1 loops=3320)
                    Output: dt.fecha_sk
                    Cache Key: fis.fecha_sk
                    Cache Mode: logical
                    Hits: 3318  Misses: 2  Evictions: 0  Overflows: 0  Memory Usage: 1kB
                    Buffers: shared hit=5
                    ->  Index Only Scan using dim_tiempo_pkey on dw.dim_tiempo dt  (cost=0.28..0.33 rows=1 width=4) (actual time=0.111..0.111 rows=1 loops=2)
                          Output: dt.fecha_sk
                          Index Cond: (dt.fecha_sk = fis.fecha_sk)
                          Heap Fetches: 0
                          Buffers: shared hit=5
Planning Time: 9.191 ms
Execution Time: 192.598 ms
*/

-- problema: escanea los 60,000 snapshots y descarta 56,680 filas que tienen stock normal, tardando ~192 ms
-- solución: índice parcial filtrando solo las alertas ordenadas por stock_actual
create index idx_snapshot_alerta_parcial 
on dw.fact_inventario_snapshot (stock_actual asc, producto_sk, sucursal_sk, fecha_sk) 
where stock_bajo_alerta = true;

-- explain después del índice parcial:
explain (analyze, buffers, verbose, costs, timing)
select 
    ds.nombre as sucursal,
    dp.sku,
    dp.nombre as producto,
    dp.categoria,
    dp.marca,
    fis.stock_actual,
    fis.costo_promedio,
    fis.precio_venta,
    fis.valor_inventario_costo
from dw.fact_inventario_snapshot fis
join dw.dim_producto dp on fis.producto_sk = dp.producto_sk
join dw.dim_sucursal ds on fis.sucursal_sk = ds.sucursal_sk
join dw.dim_tiempo dt on fis.fecha_sk = dt.fecha_sk
where fis.stock_bajo_alerta = true
order by fis.stock_actual asc
limit 100;

/*
Limit  (cost=1.02..185.54 rows=100 width=446) (actual time=0.528..1.212 rows=100 loops=1)
  Output: ds.nombre, dp.sku, dp.nombre, dp.categoria, dp.marca, fis.stock_actual, fis.costo_promedio, fis.precio_venta, fis.valor_inventario_costo
  Buffers: shared hit=164 read=3
  ->  Nested Loop  (cost=1.02..6245.17 rows=3384 width=446) (actual time=0.526..1.186 rows=100 loops=1)
        Output: ds.nombre, dp.sku, dp.nombre, dp.categoria, dp.marca, fis.stock_actual, fis.costo_promedio, fis.precio_venta, fis.valor_inventario_costo
        Inner Unique: true
        Buffers: shared hit=164 read=3
        ->  Nested Loop  (cost=0.73..6159.60 rows=3384 width=450) (actual time=0.468..1.020 rows=100 loops=1)
              Output: fis.stock_actual, fis.costo_promedio, fis.precio_venta, fis.valor_inventario_costo, fis.fecha_sk, dp.sku, dp.nombre, dp.categoria, dp.marca, ds.nombre
              Inner Unique: true
              Buffers: shared hit=159 read=3
              ->  Nested Loop  (cost=0.58..6074.61 rows=3384 width=136) (actual time=0.154..0.582 rows=100 loops=1)
                    Output: fis.stock_actual, fis.costo_promedio, fis.precio_venta, fis.valor_inventario_costo, fis.sucursal_sk, fis.fecha_sk, dp.sku, dp.nombre, dp.categoria, dp.marca
                    Inner Unique: true
                    Buffers: shared hit=154 read=2
                    ->  Index Scan using idx_snapshot_alerta_parcial on dw.fact_inventario_snapshot fis  (cost=0.28..2838.19 rows=3384 width=34) (actual time=0.104..0.264 rows=100 loops=1)
                          Output: fis.inventario_snapshot_sk, fis.fecha_sk, fis.sucursal_sk, fis.producto_sk, fis.stock_actual, fis.costo_promedio, fis.precio_venta, fis.valor_inventario_costo, fis.valor_inventario_venta, fis.stock_bajo_alerta, fis.created_at
                          Buffers: shared hit=100 read=2
                    ->  Memoize  (cost=0.30..1.08 rows=1 width=110) (actual time=0.002..0.002 rows=1 loops=100)
                          Output: dp.sku, dp.nombre, dp.categoria, dp.marca, dp.producto_sk
                          Cache Key: fis.producto_sk
                          Cache Mode: logical
                          Hits: 82  Misses: 18  Evictions: 0  Overflows: 0  Memory Usage: 4kB
                          Buffers: shared hit=54
                          ->  Index Scan using dim_producto_pkey on dw.dim_producto dp  (cost=0.29..1.07 rows=1 width=110) (actual time=0.006..0.006 rows=1 loops=18)
                                Output: dp.sku, dp.nombre, dp.categoria, dp.marca, dp.producto_sk
                                Index Cond: (dp.producto_sk = fis.producto_sk)
                                Buffers: shared hit=54
              ->  Memoize  (cost=0.15..0.19 rows=1 width=322) (actual time=0.004..0.004 rows=1 loops=100)
                    Output: ds.nombre, ds.sucursal_sk
                    Cache Key: fis.sucursal_sk
                    Cache Mode: logical
                    Hits: 97  Misses: 3  Evictions: 0  Overflows: 0  Memory Usage: 1kB
                    Buffers: shared hit=5 read=1
                    ->  Index Scan using dim_sucursal_pkey on dw.dim_sucursal ds  (cost=0.14..0.18 rows=1 width=322) (actual time=0.104..0.104 rows=1 loops=3)
                          Output: ds.nombre, ds.sucursal_sk
                          Index Cond: (ds.sucursal_sk = fis.sucursal_sk)
                          Buffers: shared hit=5 read=1
        ->  Memoize  (cost=0.29..0.34 rows=1 width=4) (actual time=0.001..0.001 rows=1 loops=100)
              Output: dt.fecha_sk
              Cache Key: fis.fecha_sk
              Cache Mode: logical
              Hits: 98  Misses: 2  Evictions: 0  Overflows: 0  Memory Usage: 1kB
              Buffers: shared hit=5
              ->  Index Only Scan using dim_tiempo_pkey on dw.dim_tiempo dt  (cost=0.28..0.33 rows=1 width=4) (actual time=0.027..0.027 rows=1 loops=2)
                    Output: dt.fecha_sk
                    Index Cond: (dt.fecha_sk = fis.fecha_sk)
                    Heap Fetches: 0
                    Buffers: shared hit=5
Planning Time: 3.575 ms
Execution Time: 1.724 ms
*/

-- resultado: cayó de 192.5 ms a 1.7 ms (más de 100x de mejora) y el limit se resuelve de un solo sin sort
