-- dataset de prueba y queries de validación
-- Centro de Plásticos El Limeño

/*
Pasos para regenerar la data:

1. Con Laravel (10,000 registros por tabla):
   cd InventaryAppApi
   php artisan migrate:fresh --seed

2. Cargar hacia el Data Warehouse:
   cd DataWareHouse && docker compose up -d
   cd ../ProcesoETL
   python main.py
   pytest test_etl.py -v

3. O correr este script directo en PostgreSQL para pruebas rápidas
*/

begin;

-- limpiar tablas
truncate table 
    kardexes, 
    movimientos_inventarios, 
    detalles_ventas, 
    ventas, 
    detalles_compras, 
    compras, 
    producto_sucursal, 
    productos, 
    modelos, 
    marcas, 
    subcategorias, 
    categorias, 
    unidads, 
    clientes, 
    proveedors, 
    sucursals, 
    settings
restart identity cascade;

-- config
insert into settings (key, value, description, created_at, updated_at) values
('empresa_nombre', 'Centro de Plástico El Limeño', 'Razón social', now(), now()),
('empresa_nit', '1217-010190-101-5', 'NIT de la empresa', now(), now()),
('empresa_nrc', '294851-9', 'NRC de contribuyente IVA', now(), now()),
('empresa_direccion', 'Av. Roosevelt Sur #402, San Miguel, El Salvador', 'Dirección principal', now(), now()),
('tasa_iva', '13.00', 'Tasa de IVA vigente en El Salvador', now(), now()),
('alerta_stock_minimo', '15', 'Umbral para disparar alerta de stock crítico', now(), now())
on conflict (key) do update set value = excluded.value;

-- sucursales
insert into sucursals (id, nombre, direccion, telefono, activa, created_at, updated_at) values
(1, 'Matriz San Miguel - Centro', '4.ª Calle Oriente y 2.ª Av. Sur #301, San Miguel', '2661-1200', true, now(), now()),
(2, 'Sucursal San Miguel - Metrocentro', 'Centro Comercial Metrocentro San Miguel, Local B-14', '2660-8800', true, now(), now()),
(3, 'Sucursal Usulután', 'Barrio El Calvario, Calle Grimaldi #18, Usulután', '2662-4500', true, now(), now())
on conflict (id) do update set nombre = excluded.nombre;

-- unidades de medida
insert into unidads (id, nombre, abreviatura, created_at, updated_at) values
(1, 'Unidad', 'UND', now(), now()),
(2, 'Docena', 'DOC', now(), now()),
(3, 'Ciento', 'CTO', now(), now()),
(4, 'Millar', 'MIL', now(), now()),
(5, 'Paquete', 'PQT', now(), now()),
(6, 'Fardo', 'FDO', now(), now()),
(7, 'Caja', 'CJA', now(), now()),
(8, 'Quintal', 'QQ', now(), now())
on conflict (id) do nothing;

-- categorías
insert into categorias (id, nombre, descripcion, created_at, updated_at) values
(1, 'Bolsas y Empaques', 'Bolsas de rollo, gabacha, basura y empaques', now(), now()),
(2, 'Desechables para Alimentos', 'Vasos, platos, cubiertos, térmicos y bandejas', now(), now()),
(3, 'Contenedores y Herméticos', 'Huacales, botes herméticos y cajas organizadoras', now(), now()),
(4, 'Artículos para el Hogar', 'Sillas plásticas, mesas, tinas y cubetas', now(), now()),
(5, 'Artículos de Limpieza', 'Escobas plásticas, trapeadores y recogedores', now(), now()),
(6, 'Línea Infantil y Escolar', 'Pachones, loncheras y recipientes escolares', now(), now()),
(7, 'Envases y Botellas', 'Galoneras, bidones y botellas de polietileno', now(), now()),
(8, 'Ferretería y Jardinería', 'Macetas plásticas, mangueras y gavetas', now(), now()),
(9, 'Temporada y Eventos', 'Rollos de celofán, manteles plásticos y adornos', now(), now())
on conflict (id) do nothing;

-- subcategorías
insert into subcategorias (id, nombre, categoria_id, created_at, updated_at) values
(1, 'Bolsas de Gabacha', 1, now(), now()),
(2, 'Bolsas en Rollo', 1, now(), now()),
(3, 'Bolsas para Basura Industrial', 1, now(), now()),
(4, 'Vasos Plásticos Desechables', 2, now(), now()),
(5, 'Platos y Bandejas Térmicas', 2, now(), now()),
(6, 'Cubiertos y Pajillas', 2, now(), now()),
(7, 'Contenedores Herméticos', 3, now(), now()),
(8, 'Botes y Barriles con Tapa', 3, now(), now()),
(9, 'Sillas y Bancos Plásticos', 4, now(), now()),
(10, 'Cubetas y Huacales Multiuso', 4, now(), now()),
(11, 'Cepillería y Escobas', 5, now(), now()),
(12, 'Galoneras y Bidones Plásticos', 7, now(), now())
on conflict (id) do nothing;

-- marcas
insert into marcas (id, nombre, created_at, updated_at) values
(1, 'Plásticos El Limeño', now(), now()),
(2, 'Rey Plast', now(), now()),
(3, 'Industrias Termoencogibles', now(), now()),
(4, 'Rubbermaid Comercial', now(), now()),
(5, 'Plásticos Atlas', now(), now()),
(6, 'Rimax', now(), now()),
(7, 'Genérico / Importación', now(), now())
on conflict (id) do nothing;

-- modelos
insert into modelos (id, nombre, marca_id, created_at, updated_at) values
(1, 'Línea Estándar Comercial', 1, now(), now()),
(2, 'Línea Ultra Resistente Reforzada', 1, now(), now()),
(3, 'Clásico Hermético', 2, now(), now()),
(4, 'Heavy Duty Industrial', 4, now(), now()),
(5, 'Línea Hogar Confort', 6, now(), now())
on conflict (id) do nothing;

-- catálogo de productos
insert into productos (id, sku, nombre, costo_promedio, precio_venta, categoria_id, subcategoria_id, marca_id, modelo_id, unidad_id, stock, status, aplica_iva, created_at, updated_at) values
(1, 'BOL-GAB-001', 'Bolsa de Gabacha Blanca Mediana 10x15 (Paquete 100 und)', 1.1500, 1.8500, 1, 1, 1, 1, 5, 250, true, true, now(), now()),
(2, 'BOL-GAB-002', 'Bolsa de Gabacha Grande Negra 12x20 (Paquete 100 und)', 1.6000, 2.5000, 1, 1, 1, 1, 5, 180, true, true, now(), now()),
(3, 'BOL-BAS-003', 'Bolsa para Basura Jumbo 55 Galones Calibre Grueso (Fardo 50 und)', 5.2500, 7.9500, 1, 3, 3, 2, 6, 85, true, true, now(), now()),
(4, 'VAS-DES-004', 'Vaso Plástico Transparente Desechable 16 oz (Fardo 1000 und)', 14.5000, 21.0000, 2, 4, 1, 1, 6, 40, true, true, now(), now()),
(5, 'PLA-DES-005', 'Plato Desechable Hondo #8 Blanco Térmico (Ciento 100 und)', 2.1000, 3.2500, 2, 5, 3, 1, 3, 120, true, true, now(), now()),
(6, 'CUB-DES-006', 'Tenedor Plástico Blanco Reforzado Desechable (Caja 1000 und)', 6.8000, 10.5000, 2, 6, 1, 1, 7, 65, true, true, now(), now()),
(7, 'CON-HER-007', 'Contenedor Hermético Rectangular 4.5 Litros con Seguro Click', 2.4500, 4.2500, 3, 7, 2, 3, 1, 12, true, true, now(), now()),
(8, 'BOT-BAR-008', 'Barril Plástico Azul con Tapa Hermética y Cincho Metálico 55 Gal', 18.0000, 28.5000, 3, 8, 4, 4, 1, 8, true, true, now(), now()),
(9, 'SIL-PLA-009', 'Silla Plástica Apilable Clásica sin Brazos Color Blanco', 6.2000, 9.9500, 4, 9, 6, 5, 1, 95, true, true, now(), now()),
(10, 'CUB-HOG-010', 'Cubeta Plástica Graduada de 18 Litros con Asa Metálica Ergonómica', 2.1000, 3.5000, 4, 10, 2, 1, 1, 14, true, true, now(), now()),
(11, 'ESC-LIM-011', 'Escoba de Vinilo Alta Densidad con Cabo Metálico Plastificado', 1.3500, 2.2500, 5, 11, 1, 1, 1, 110, true, true, now(), now()),
(12, 'BID-ENV-012', 'Bidón Galonera Plástica Blanca de Polietileno Grado Alimenticio 1 Gal', 0.8500, 1.4500, 7, 12, 1, 1, 1, 9, true, true, now(), now())
on conflict (id) do update set nombre = excluded.nombre, stock = excluded.stock;

-- stock por sucursal (dejamos varios < 15 adrede para probar alertas)
insert into producto_sucursal (producto_id, sucursal_id, stock_actual, stock_minimo, stock_maximo, created_at, updated_at) values
-- sucursal 1: matriz san miguel
(1, 1, 150, 20, 500, now(), now()),
(2, 1, 100, 20, 400, now(), now()),
(3, 1, 50, 15, 200, now(), now()),
(4, 1, 25, 10, 100, now(), now()),
(5, 1, 80, 20, 300, now(), now()),
(6, 1, 40, 15, 150, now(), now()),
(7, 1, 6, 15, 50, now(), now()),   -- alerta (< 15)
(8, 1, 4, 10, 30, now(), now()),   -- alerta (< 15)
(9, 1, 50, 15, 200, now(), now()),
(10, 1, 8, 15, 60, now(), now()),  -- alerta (< 15)
(11, 1, 70, 20, 250, now(), now()),
(12, 1, 5, 15, 50, now(), now()),  -- alerta (< 15)

-- sucursal 2: metrocentro
(1, 2, 60, 15, 200, now(), now()),
(2, 2, 50, 15, 200, now(), now()),
(3, 2, 20, 10, 100, now(), now()),
(4, 2, 10, 15, 80, now(), now()),  -- alerta (< 15)
(5, 2, 25, 15, 100, now(), now()),
(6, 2, 15, 10, 80, now(), now()),
(7, 2, 4, 10, 40, now(), now()),   -- alerta (< 15)
(8, 2, 2, 10, 20, now(), now()),   -- alerta (< 15)
(9, 2, 30, 15, 100, now(), now()),
(10, 2, 3, 10, 50, now(), now()),  -- alerta (< 15)
(11, 2, 25, 15, 100, now(), now()),
(12, 2, 2, 10, 30, now(), now()),  -- alerta (< 15)

-- sucursal 3: usulután
(1, 3, 40, 15, 150, now(), now()),
(2, 3, 30, 15, 150, now(), now()),
(3, 3, 15, 10, 80, now(), now()),
(4, 3, 5, 10, 50, now(), now()),   -- alerta (< 15)
(5, 3, 15, 10, 80, now(), now()),
(6, 3, 10, 10, 60, now(), now()),  -- alerta (< 15)
(7, 3, 2, 10, 30, now(), now()),   -- alerta (< 15)
(8, 3, 2, 10, 20, now(), now()),   -- alerta (< 15)
(9, 3, 15, 10, 60, now(), now()),
(10, 3, 3, 10, 40, now(), now()),  -- alerta (< 15)
(11, 3, 15, 10, 60, now(), now()),
(12, 3, 2, 10, 30, now(), now())   -- alerta (< 15)
on conflict (producto_id, sucursal_id) do update set stock_actual = excluded.stock_actual;

-- proveedores
insert into proveedors (id, nombre, nit, correo, telefono, direccion, estado, created_at, updated_at) values
(1, 'Distribuidora Industrial de Resinas S.A. de C.V.', '0614-250385-102-1', 'ventas@indresinas.com.sv', '2250-9900', 'Zona Industrial Plan de La Laguna, Antiguo Cuscatlán', true, now(), now()),
(2, 'Termoencogibles de El Salvador S.A. de C.V.', '0614-121078-001-4', 'pedidos@termoencogibles.com', '2212-7000', 'Km 11 Carretera al Puerto de La Libertad, Santa Tecla', true, now(), now()),
(3, 'Importaciones y Plásticos del Pacífico S.A.', '1217-150892-101-9', 'gerencia@plasticospacifico.com', '2661-8850', 'Barrio San Felipe, 8.ª Calle Poniente, San Miguel', true, now(), now()),
(4, 'Manufacturas Plásticas Rey Plast El Salvador', '0614-301198-103-6', 'contacto@reyplast.com.sv', '2278-4500', 'Bulevar del Ejército Nacional Km 5.5, Soyapango', true, now(), now())
on conflict (id) do nothing;

-- clientes
insert into clientes (id, nombre, correo, dui, telefono, direccion, status, created_at, updated_at) values
(1, 'Consumidor Final', 'ventas@plasticoslimeno.com', '00000000-0', '2661-0000', 'Venta en mostrador, San Miguel', true, now(), now()),
(2, 'María Teresa Gómez de Henríquez', 'teresa.gomez@gmail.com', '02345678-9', '7845-1200', 'Colonia Ciudad Jardín, Av. Los Naranjos #14, San Miguel', true, now(), now()),
(3, 'José Carlos Hernández Salmerón', 'jhernandez_agro@hotmail.com', '03891245-1', '7120-8945', 'Barrio El Centro, 2.ª Av. Sur, Usulután', true, now(), now()),
(4, 'Restaurante y Pupusería El Buen Sabor S.A.', 'compras@elbuensabor.sv', '0614-050499-104-2', '2660-1515', 'Av. Roosevelt Norte #1205, San Miguel', true, now(), now())
on conflict (id) do nothing;

-- compras de prueba
insert into compras (id, proveedor_id, sucursal_id, user_id, fecha_compra, total_compra, subtotal, monto_iva, status, created_at, updated_at) values
(1, 1, 1, 1, current_date - interval '15 days', 1898.40, 1680.00, 218.40, 'pagado', now() - interval '15 days', now() - interval '15 days'),
(2, 2, 2, 1, current_date - interval '10 days', 949.20, 840.00, 109.20, 'pagado', now() - interval '10 days', now() - interval '10 days')
on conflict (id) do nothing;

insert into detalles_compras (id, compra_id, producto_id, cantidad, precio_compra, created_at, updated_at) values
(1, 1, 1, 500, 1.1500, now() - interval '15 days', now() - interval '15 days'),
(2, 1, 4, 50, 14.5000, now() - interval '15 days', now() - interval '15 days'),
(3, 1, 9, 60, 6.2000, now() - interval '15 days', now() - interval '15 days'),
(4, 2, 2, 300, 1.6000, now() - interval '10 days', now() - interval '10 days'),
(5, 2, 5, 100, 2.1000, now() - interval '10 days', now() - interval '10 days'),
(6, 2, 11, 110, 1.3500, now() - interval '10 days', now() - interval '10 days')
on conflict (id) do nothing;

-- ventas de prueba
insert into ventas (id, cliente_id, sucursal_id, user_id, fecha_venta, subtotal, monto_iva, total_venta, status, serie, correlativo, created_at, updated_at) values
(1, 4, 1, 1, current_date - interval '5 days', 215.00, 27.95, 242.95, 'pagada', 'FAC-01', '00001001', now() - interval '5 days', now() - interval '5 days'),
(2, 2, 2, 1, current_date - interval '3 days', 58.70, 7.63, 66.33, 'pagada', 'FAC-02', '00000450', now() - interval '3 days', now() - interval '3 days'),
(3, 3, 3, 1, current_date - interval '1 days', 145.20, 18.88, 164.08, 'pagada', 'FAC-03', '00000820', now() - interval '1 days', now() - interval '1 days')
on conflict (id) do nothing;

insert into detalles_ventas (id, venta_id, producto_id, cantidad, precio_venta, created_at, updated_at) values
(1, 1, 1, 50, 1.8500, now() - interval '5 days', now() - interval '5 days'),
(2, 1, 4, 5, 21.0000, now() - interval '5 days', now() - interval '5 days'),
(3, 1, 6, 2, 10.5000, now() - interval '5 days', now() - interval '5 days'),
(4, 2, 5, 6, 3.2500, now() - interval '3 days', now() - interval '3 days'),
(5, 2, 7, 4, 4.2500, now() - interval '3 days', now() - interval '3 days'),
(6, 2, 11, 10, 2.2500, now() - interval '3 days', now() - interval '3 days'),
(7, 3, 2, 30, 2.5000, now() - interval '1 days', now() - interval '1 days'),
(8, 3, 9, 6, 9.9500, now() - interval '1 days', now() - interval '1 days'),
(9, 3, 10, 3, 3.5000, now() - interval '1 days', now() - interval '1 days')
on conflict (id) do nothing;

-- movimientos de kardex
insert into kardexes (id, producto_id, sucursal_id, user_id, tipo_movimiento, cantidad_antes, cantidad_movimiento, cantidad_despues, costo_movimiento, costo_despues, detalle, created_at, updated_at) values
(1, 1, 1, 1, 'compra', 0, 500, 500, 1.1500, 1.1500, 'Ingreso por compra inicial #1', now() - interval '15 days', now() - interval '15 days'),
(2, 1, 1, 1, 'venta', 500, -50, 450, 1.1500, 1.1500, 'Salida por venta #1', now() - interval '5 days', now() - interval '5 days'),
(3, 7, 1, 1, 'ajuste_salida', 8, -2, 6, 2.4500, 2.4500, 'Ajuste por producto dañado en bodega', now() - interval '2 days', now() - interval '2 days')
on conflict (id) do nothing;

-- sincronizar secuencias para no tener conflicto con autoincrementables
select setval('categorias_id_seq', (select coalesce(max(id), 1) from categorias));
select setval('subcategorias_id_seq', (select coalesce(max(id), 1) from subcategorias));
select setval('marcas_id_seq', (select coalesce(max(id), 1) from marcas));
select setval('modelos_id_seq', (select coalesce(max(id), 1) from modelos));
select setval('unidads_id_seq', (select coalesce(max(id), 1) from unidads));
select setval('sucursals_id_seq', (select coalesce(max(id), 1) from sucursals));
select setval('productos_id_seq', (select coalesce(max(id), 1) from productos));
select setval('clientes_id_seq', (select coalesce(max(id), 1) from clientes));
select setval('proveedors_id_seq', (select coalesce(max(id), 1) from proveedors));
select setval('compras_id_seq', (select coalesce(max(id), 1) from compras));
select setval('detalles_compras_id_seq', (select coalesce(max(id), 1) from detalles_compras));
select setval('ventas_id_seq', (select coalesce(max(id), 1) from ventas));
select setval('detalles_ventas_id_seq', (select coalesce(max(id), 1) from detalles_ventas));
select setval('kardexes_id_seq', (select coalesce(max(id), 1) from kardexes));

commit;

-- validaciones rápidas:

-- 1. conteo de filas
select 'sucursals' as tabla, count(*) as total from sucursals
union all select 'categorias', count(*) from categorias
union all select 'productos', count(*) from productos
union all select 'producto_sucursal', count(*) from producto_sucursal
union all select 'clientes', count(*) from clientes
union all select 'proveedors', count(*) from proveedors
union all select 'ventas', count(*) from ventas
union all select 'compras', count(*) from compras
union all select 'kardexes', count(*) from kardexes;

-- 2. productos con stock bajo (< 15)
select 
    s.nombre as sucursal,
    p.sku,
    p.nombre as producto,
    ps.stock_actual,
    p.precio_venta
from producto_sucursal ps
join productos p on ps.producto_id = p.id
join sucursals s on ps.sucursal_id = s.id
where ps.stock_actual < 15
order by s.id, ps.stock_actual asc;

-- 3. cuadre de ventas y total calculado
select 
    v.id as venta_id,
    v.serie || '-' || v.correlativo as factura,
    c.nombre as cliente,
    v.subtotal,
    v.monto_iva as iva_13,
    v.total_venta,
    round(sum(dv.cantidad * dv.precio_venta)::numeric, 2) as total_calculado
from ventas v
join detalles_ventas dv on v.id = dv.venta_id
join clientes c on v.cliente_id = c.id
group by v.id, v.serie, v.correlativo, c.nombre, v.subtotal, v.monto_iva, v.total_venta
order by v.id;
