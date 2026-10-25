-- ============================================================================
-- SCRIPT 02: CREACION DE TABLAS DIMENSIONALES (MODELO ESTRELLA)
-- Data Warehouse / Data Mart - InventoryApp
-- ============================================================================

-- 1. Dimension Tiempo (Generacion por serie de calendario)
CREATE TABLE IF NOT EXISTS dw.dim_tiempo (
    fecha_sk integer PRIMARY KEY, -- Formato AAAAMMDD
    fecha date NOT NULL UNIQUE,
    anio smallint NOT NULL,
    semestre smallint NOT NULL,
    trimestre smallint NOT NULL,
    mes smallint NOT NULL,
    nombre_mes varchar(20) NOT NULL,
    anio_mes char(7) NOT NULL,    -- 'AAAA-MM'
    semana_anio smallint NOT NULL,
    dia smallint NOT NULL,
    dia_semana smallint NOT NULL,
    nombre_dia varchar(20) NOT NULL,
    es_fin_de_semana boolean NOT NULL
);
COMMENT ON TABLE dw.dim_tiempo IS 'Dimension Tiempo generada para agregaciones de calendario analiticas';

-- Poblado automatico de dim_tiempo (2015 a 2030)
INSERT INTO dw.dim_tiempo (
    fecha_sk, fecha, anio, semestre, trimestre, mes, nombre_mes, anio_mes,
    semana_anio, dia, dia_semana, nombre_dia, es_fin_de_semana
)
VALUES (0, '1900-01-01', 1900, 1, 1, 1, 'Desconocido', '1900-01', 1, 1, 1, 'Desconocido', false)
ON CONFLICT (fecha_sk) DO NOTHING;

INSERT INTO dw.dim_tiempo (
    fecha_sk, fecha, anio, semestre, trimestre, mes, nombre_mes, anio_mes,
    semana_anio, dia, dia_semana, nombre_dia, es_fin_de_semana
)
SELECT
    to_char(d, 'YYYYMMDD')::integer AS fecha_sk,
    d::date AS fecha,
    extract(year FROM d)::smallint AS anio,
    CASE WHEN extract(month FROM d) <= 6 THEN 1 ELSE 2 END::smallint AS semestre,
    extract(quarter FROM d)::smallint AS trimestre,
    extract(month FROM d)::smallint AS mes,
    (ARRAY['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'])[extract(month FROM d)] AS nombre_mes,
    to_char(d, 'YYYY-MM') AS anio_mes,
    extract(week FROM d)::smallint AS semana_anio,
    extract(day FROM d)::smallint AS dia,
    extract(isodow FROM d)::smallint AS dia_semana,
    (ARRAY['Lunes','Martes','Miercoles','Jueves','Viernes','Sabado','Domingo'])[extract(isodow FROM d)] AS nombre_dia,
    CASE WHEN extract(isodow FROM d) IN (6, 7) THEN true ELSE false END AS es_fin_de_semana
FROM generate_series('2015-01-01'::date, '2030-12-31'::date, interval '1 day') AS d
ON CONFLICT (fecha_sk) DO NOTHING;

-- 2. Dimension Sucursal
CREATE TABLE IF NOT EXISTS dw.dim_sucursal (
    sucursal_sk SERIAL PRIMARY KEY,
    sucursal_bk integer NOT NULL UNIQUE,
    nombre varchar(150) NOT NULL,
    direccion text,
    telefono varchar(50)
);
COMMENT ON TABLE dw.dim_sucursal IS 'Dimension Sucursal con clave subrogada y clave de negocio';

-- 3. Dimension Producto
CREATE TABLE IF NOT EXISTS dw.dim_producto (
    producto_sk SERIAL PRIMARY KEY,
    producto_bk integer NOT NULL UNIQUE,
    sku varchar(100) NOT NULL UNIQUE,
    nombre varchar(255) NOT NULL,
    categoria varchar(150) DEFAULT 'Sin Categoria',
    subcategoria varchar(150) DEFAULT 'Sin Subcategoria',
    marca varchar(150) DEFAULT 'Sin Marca',
    modelo varchar(150) DEFAULT 'Sin Modelo',
    unidad_medida varchar(50) DEFAULT 'Unidad',
    costo_promedio numeric(12, 4) DEFAULT 0,
    precio_venta numeric(12, 4) DEFAULT 0,
    aplica_iva boolean DEFAULT true,
    tipo varchar(50) DEFAULT 'no perecedero',
    estado varchar(20) DEFAULT 'Activo'
);
COMMENT ON TABLE dw.dim_producto IS 'Dimension Producto desnormalizada con jerarquias de categoria, marca y unidad';

-- 4. Dimension Cliente
CREATE TABLE IF NOT EXISTS dw.dim_cliente (
    cliente_sk SERIAL PRIMARY KEY,
    cliente_bk integer NOT NULL UNIQUE,
    nombre_completo varchar(255) NOT NULL,
    dui varchar(50),
    nit varchar(50),
    nrc varchar(50),
    telefono varchar(50),
    email varchar(150),
    direccion text
);
COMMENT ON TABLE dw.dim_cliente IS 'Dimension Cliente para analisis demografico y por tipo de contribuyente';

-- 5. Dimension Proveedor
CREATE TABLE IF NOT EXISTS dw.dim_proveedor (
    proveedor_sk SERIAL PRIMARY KEY,
    proveedor_bk integer NOT NULL UNIQUE,
    nombre varchar(255) NOT NULL,
    nit varchar(50),
    nrc varchar(50),
    telefono varchar(50),
    email varchar(150),
    direccion text
);
COMMENT ON TABLE dw.dim_proveedor IS 'Dimension Proveedor para analisis de cadena de suministro y compras';

-- 6. Dimension Usuario (Vendedor / Gestor)
CREATE TABLE IF NOT EXISTS dw.dim_usuario (
    usuario_sk SERIAL PRIMARY KEY,
    usuario_bk integer NOT NULL UNIQUE,
    nombre_completo varchar(255) NOT NULL,
    email varchar(150)
);
COMMENT ON TABLE dw.dim_usuario IS 'Dimension Usuario para analisis de rendimiento del personal de ventas';

-- Registro generico / "No identificado" para cada dimension
INSERT INTO dw.dim_sucursal (sucursal_sk, sucursal_bk, nombre, direccion, telefono)
VALUES (0, 0, 'Sucursal no identificada', 'N/D', 'N/D')
ON CONFLICT (sucursal_bk) DO NOTHING;

INSERT INTO dw.dim_producto (producto_sk, producto_bk, sku, nombre, categoria, subcategoria, marca, modelo, unidad_medida)
VALUES (0, 0, 'SKU-0000', 'Producto no identificado', 'General', 'General', 'General', 'General', 'Unidad')
ON CONFLICT (producto_bk) DO NOTHING;

INSERT INTO dw.dim_cliente (cliente_sk, cliente_bk, nombre_completo)
VALUES (0, 0, 'Cliente Consumidor Final')
ON CONFLICT (cliente_bk) DO NOTHING;

INSERT INTO dw.dim_proveedor (proveedor_sk, proveedor_bk, nombre)
VALUES (0, 0, 'Proveedor no identificado')
ON CONFLICT (proveedor_bk) DO NOTHING;

INSERT INTO dw.dim_usuario (usuario_sk, usuario_bk, nombre_completo, email)
VALUES (0, 0, 'Usuario Sistema', 'sistema@inventaryapp.local')
ON CONFLICT (usuario_bk) DO NOTHING;
