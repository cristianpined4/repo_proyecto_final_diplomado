-- optimización de consultas supabase (oltp)
-- pruebas con explain analyze y buffers con datos reales

\timing on

-- 1. búsqueda rápida en caja (pos) por sku y sucursal
select 
    p.id,
    p.sku,
    p.nombre,
    p.precio_venta,
    p.costo_promedio,
    ps.stock_actual,
    ps.stock_minimo,
    s.nombre as sucursal
from productos p
join producto_sucursal ps on ps.producto_id = p.id
join sucursals s on s.id = ps.sucursal_id
where p.sku = 'PLAS-000001' and ps.sucursal_id = 1;

-- explain antes del índice:
explain (analyze, buffers, verbose, costs, timing)
select 
    p.id,
    p.sku,
    p.nombre,
    p.precio_venta,
    p.costo_promedio,
    ps.stock_actual,
    ps.stock_minimo,
    s.nombre as sucursal
from productos p
join producto_sucursal ps on ps.producto_id = p.id
join sucursals s on s.id = ps.sucursal_id
where p.sku = 'PLAS-000001' and ps.sucursal_id = 1;

/*
Nested Loop  (cost=0.71..7.38 rows=1 width=615) (actual time=0.036..0.039 rows=1 loops=1)
  Output: p.id, p.sku, p.nombre, p.precio_venta, p.costo_promedio, ps.stock_actual, ps.stock_minimo, s.nombre
  Buffers: shared hit=8
  ->  Nested Loop  (cost=0.57..5.02 rows=1 width=107) (actual time=0.032..0.034 rows=1 loops=1)
        Output: p.id, p.sku, p.nombre, p.precio_venta, p.costo_promedio, ps.stock_actual, ps.stock_minimo, ps.sucursal_id
        Buffers: shared hit=6
        ->  Index Scan using idx_productos_sku on public.productos p  (cost=0.29..2.50 rows=1 width=91) (actual time=0.020..0.021 rows=1 loops=1)
              Output: p.id, p.sku, p.nombre, p.precio_venta, p.costo_promedio
              Index Cond: ((p.sku)::text = 'PLAS-000001'::text)
              Buffers: shared hit=3
        ->  Index Scan using idx_prod_suc_ids on public.producto_sucursal ps  (cost=0.29..2.51 rows=1 width=24) (actual time=0.009..0.010 rows=1 loops=1)
              Output: ps.id, ps.producto_id, ps.sucursal_id, ps.stock_actual, ps.stock_minimo, ps.stock_maximo, ps.created_at, ps.updated_at
              Index Cond: ((ps.producto_id = p.id) AND (ps.sucursal_id = 1))
              Buffers: shared hit=3
  ->  Index Scan using sucursals_pkey on public.sucursals s  (cost=0.14..2.36 rows=1 width=524) (actual time=0.004..0.004 rows=1 loops=1)
        Output: s.id, s.nombre, s.direccion, s.telefono, s.correo, s.activa, s.created_at, s.updated_at, s.logo, s.ciudad, s.estado, s.pais
        Index Cond: (s.id = 1)
        Buffers: shared hit=2
Planning Time: 0.368 ms
Execution Time: 0.079 ms
*/

-- problema: hace index scan normal y tiene que ir al heap a traer stock_actual y stock_minimo
-- solución: índice covering con include para sacar el stock de un solo del índice
create index idx_prod_sucursal_covering 
on producto_sucursal (sucursal_id, producto_id) 
include (stock_actual, stock_minimo);

-- explain después del índice covering:
explain (analyze, buffers, verbose, costs, timing)
select 
    p.id,
    p.sku,
    p.nombre,
    p.precio_venta,
    p.costo_promedio,
    ps.stock_actual,
    ps.stock_minimo,
    s.nombre as sucursal
from productos p
join producto_sucursal ps on ps.producto_id = p.id
join sucursals s on s.id = ps.sucursal_id
where p.sku = 'PLAS-000001' and ps.sucursal_id = 1;

/*
Nested Loop  (cost=0.71..7.38 rows=1 width=615) (actual time=0.075..0.077 rows=1 loops=1)
  Output: p.id, p.sku, p.nombre, p.precio_venta, p.costo_promedio, ps.stock_actual, ps.stock_minimo, s.nombre
  Buffers: shared hit=6 read=2
  ->  Nested Loop  (cost=0.57..5.02 rows=1 width=107) (actual time=0.062..0.063 rows=1 loops=1)
        Output: p.id, p.sku, p.nombre, p.precio_venta, p.costo_promedio, ps.stock_actual, ps.stock_minimo, ps.sucursal_id
        Buffers: shared hit=4 read=2
        ->  Index Scan using idx_productos_sku on public.productos p  (cost=0.29..2.50 rows=1 width=91) (actual time=0.036..0.037 rows=1 loops=1)
              Output: p.id, p.sku, p.nombre, p.precio_venta, p.costo_promedio
              Index Cond: ((p.sku)::text = 'PLAS-000001'::text)
              Buffers: shared hit=3
        ->  Index Only Scan using idx_prod_sucursal_covering on public.producto_sucursal ps  (cost=0.29..2.51 rows=1 width=24) (actual time=0.024..0.024 rows=1 loops=1)
              Output: ps.sucursal_id, ps.producto_id, ps.stock_actual, ps.stock_minimo
              Index Cond: ((ps.sucursal_id = 1) AND (ps.producto_id = p.id))
              Heap Fetches: 0
              Buffers: shared hit=1 read=2
  ->  Index Scan using sucursals_pkey on public.sucursals s  (cost=0.14..2.36 rows=1 width=524) (actual time=0.012..0.012 rows=1 loops=1)
        Output: s.id, s.nombre, s.direccion, s.telefono, s.correo, s.activa, s.created_at, s.updated_at, s.logo, s.ciudad, s.estado, s.pais
        Index Cond: (s.id = 1)
        Buffers: shared hit=2
Planning Time: 0.548 ms
Execution Time: 0.115 ms
*/

-- resultado: index only scan directo, heap fetches en 0 y ya no lee la tabla en disco


-- 2. historial de las últimas 50 ventas pagadas por sucursal
select 
    v.id,
    v.serie,
    v.correlativo,
    v.created_at,
    v.total_venta,
    v.metodo_pago,
    v.status,
    c.nombre as cliente,
    c.dui,
    s.nombre as sucursal
from ventas v
join clientes c on c.id = v.cliente_id
join sucursals s on s.id = v.sucursal_id
where v.sucursal_id = 1 
  and v.status = 'pagada'
order by v.created_at desc
limit 50;

-- explain antes del índice compuesto:
explain (analyze, buffers, verbose, costs, timing)
select 
    v.id,
    v.serie,
    v.correlativo,
    v.created_at,
    v.total_venta,
    v.metodo_pago,
    v.status,
    c.nombre as cliente,
    c.dui,
    s.nombre as sucursal
from ventas v
join clientes c on c.id = v.cliente_id
join sucursals s on s.id = v.sucursal_id
where v.sucursal_id = 1 
  and v.status = 'pagada'
order by v.created_at desc
limit 50;

/*
Limit  (cost=0.71..29.24 rows=50 width=610) (actual time=0.028..0.195 rows=50 loops=1)
  Output: v.id, v.serie, v.correlativo, v.created_at, v.total_venta, v.metodo_pago, v.status, c.nombre, c.dui, s.nombre
  Buffers: shared hit=160
  ->  Nested Loop  (cost=0.71..1750.89 rows=3067 width=610) (actual time=0.027..0.188 rows=50 loops=1)
        Output: v.id, v.serie, v.correlativo, v.created_at, v.total_venta, v.metodo_pago, v.status, c.nombre, c.dui, s.nombre
        Buffers: shared hit=160
        ->  Nested Loop  (cost=0.57..1710.19 rows=3067 width=102) (actual time=0.020..0.164 rows=50 loops=1)
              Output: v.id, v.serie, v.correlativo, v.created_at, v.total_venta, v.metodo_pago, v.status, v.sucursal_id, c.nombre, c.dui
              Inner Unique: true
              Buffers: shared hit=158
              ->  Index Scan Backward using idx_ventas_created_at on public.ventas v  (cost=0.29..419.43 rows=3067 width=69) (actual time=0.013..0.056 rows=50 loops=1)
                    Output: v.id, v.serie, v.correlativo, v.created_at, v.total_venta, v.metodo_pago, v.status, v.cliente_id, v.sucursal_id
                    Filter: ((v.sucursal_id = 1) AND ((v.status)::text = 'pagada'::text))
                    Rows Removed by Filter: 104
                    Buffers: shared hit=8
              ->  Index Scan using clientes_pkey on public.clientes c  (cost=0.29..0.42 rows=1 width=49) (actual time=0.002..0.002 rows=1 loops=50)
                    Output: c.id, c.nombre, c.correo, c.dui, c.telefono, c.direccion, c.status, c.created_at, c.updated_at, c.nit, c.nrc, c.user_id
                    Index Cond: (c.id = v.cliente_id)
                    Buffers: shared hit=150
        ->  Materialize  (cost=0.14..2.36 rows=1 width=524) (actual time=0.000..0.000 rows=1 loops=50)
              Output: s.nombre, s.id
              Buffers: shared hit=2
              ->  Index Scan using sucursals_pkey on public.sucursals s  (cost=0.14..2.36 rows=1 width=524) (actual time=0.005..0.005 rows=1 loops=1)
                    Output: s.nombre, s.id
                    Index Cond: (s.id = 1)
                    Buffers: shared hit=2
Planning Time: 0.311 ms
Execution Time: 0.248 ms
*/

-- problema: recorre el índice de created_at hacia atrás y rows removed da 104 (filas que no son de la sucursal 1 o no están pagadas)
-- solución: índice compuesto por sucursal, status y created_at desc
create index idx_ventas_sucursal_status_fecha 
on ventas (sucursal_id, status, created_at desc);

-- explain después del índice compuesto:
explain (analyze, buffers, verbose, costs, timing)
select 
    v.id,
    v.serie,
    v.correlativo,
    v.created_at,
    v.total_venta,
    v.metodo_pago,
    v.status,
    c.nombre as cliente,
    c.dui,
    s.nombre as sucursal
from ventas v
join clientes c on c.id = v.cliente_id
join sucursals s on s.id = v.sucursal_id
where v.sucursal_id = 1 
  and v.status = 'pagada'
order by v.created_at desc
limit 50;

/*
Limit  (cost=0.71..26.88 rows=50 width=610) (actual time=0.094..0.232 rows=50 loops=1)
  Output: v.id, v.serie, v.correlativo, v.created_at, v.total_venta, v.metodo_pago, v.status, c.nombre, c.dui, s.nombre
  Buffers: shared hit=156 read=2
  ->  Nested Loop  (cost=0.71..1606.14 rows=3067 width=610) (actual time=0.093..0.226 rows=50 loops=1)
        Output: v.id, v.serie, v.correlativo, v.created_at, v.total_venta, v.metodo_pago, v.status, c.nombre, c.dui, s.nombre
        Buffers: shared hit=156 read=2
        ->  Nested Loop  (cost=0.57..1565.44 rows=3067 width=102) (actual time=0.074..0.189 rows=50 loops=1)
              Output: v.id, v.serie, v.correlativo, v.created_at, v.total_venta, v.metodo_pago, v.status, v.sucursal_id, c.nombre, c.dui
              Inner Unique: true
              Buffers: shared hit=154 read=2
              ->  Index Scan using idx_ventas_sucursal_status_fecha on public.ventas v  (cost=0.29..274.68 rows=3067 width=69) (actual time=0.062..0.080 rows=50 loops=1)
                    Output: v.id, v.serie, v.correlativo, v.created_at, v.total_venta, v.metodo_pago, v.status, v.cliente_id, v.sucursal_id
                    Index Cond: ((v.sucursal_id = 1) AND ((v.status)::text = 'pagada'::text))
                    Buffers: shared hit=4 read=2
              ->  Index Scan using clientes_pkey on public.clientes c  (cost=0.29..0.42 rows=1 width=49) (actual time=0.002..0.002 rows=1 loops=50)
                    Output: c.id, c.nombre, c.correo, c.dui, c.telefono, c.direccion, c.status, c.created_at, c.updated_at, c.nit, c.nrc, c.user_id
                    Index Cond: (c.id = v.cliente_id)
                    Buffers: shared hit=150
        ->  Materialize  (cost=0.14..2.36 rows=1 width=524) (actual time=0.000..0.000 rows=1 loops=50)
              Output: s.nombre, s.id
              Buffers: shared hit=2
              ->  Index Scan using sucursals_pkey on public.sucursals s  (cost=0.14..2.36 rows=1 width=524) (actual time=0.011..0.011 rows=1 loops=1)
                    Output: s.nombre, s.id
                    Index Cond: (s.id = 1)
                    Buffers: shared hit=2
Planning Time: 0.387 ms
Execution Time: 0.331 ms
*/

-- resultado: rows removed bajó a 0 y el costo de scan en ventas cayó de 419 a 274
