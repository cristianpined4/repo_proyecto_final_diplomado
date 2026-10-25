-- ============================================================================
-- SCRIPT 01: CREACION DE ESQUEMAS Y TABLAS DE STAGING (ZONA DE ATERRIZAJE)
-- Data Warehouse / Data Mart - InventoryApp
-- ============================================================================

-- 1. Creacion de esquemas analiticos
CREATE SCHEMA IF NOT EXISTS stg;
COMMENT ON SCHEMA stg IS 'Zona de Staging: recepcion temporal y sin restricciones de datos operacionales extraidos';

CREATE SCHEMA IF NOT EXISTS dw;
COMMENT ON SCHEMA dw IS 'Esquema Dimensional: Almacen de datos analitico (dimensiones, hechos y vistas)';

-- 2. Tablas de Staging (sin claves foraneas estrictas para permitir extraccion resiliente)
DROP TABLE IF EXISTS stg.stg_detalles_ventas CASCADE;
DROP TABLE IF EXISTS stg.stg_ventas CASCADE;
DROP TABLE IF EXISTS stg.stg_detalles_compras CASCADE;
DROP TABLE IF EXISTS stg.stg_compras CASCADE;
DROP TABLE IF EXISTS stg.stg_producto_sucursal CASCADE;
DROP TABLE IF EXISTS stg.stg_productos CASCADE;
DROP TABLE IF EXISTS stg.stg_categorias CASCADE;
DROP TABLE IF EXISTS stg.stg_subcategorias CASCADE;
DROP TABLE IF EXISTS stg.stg_marcas CASCADE;
DROP TABLE IF EXISTS stg.stg_modelos CASCADE;
DROP TABLE IF EXISTS stg.stg_unidades CASCADE;
DROP TABLE IF EXISTS stg.stg_clientes CASCADE;
DROP TABLE IF EXISTS stg.stg_proveedores CASCADE;
DROP TABLE IF EXISTS stg.stg_sucursales CASCADE;
DROP TABLE IF EXISTS stg.stg_usuarios CASCADE;

CREATE TABLE stg.stg_sucursales (
    id integer,
    nombre text,
    direccion text,
    telefono text,
    created_at timestamp,
    updated_at timestamp
);

CREATE TABLE stg.stg_categorias (
    id integer,
    nombre text,
    descripcion text,
    created_at timestamp,
    updated_at timestamp
);

CREATE TABLE stg.stg_subcategorias (
    id integer,
    nombre text,
    categoria_id integer,
    created_at timestamp,
    updated_at timestamp
);

CREATE TABLE stg.stg_marcas (
    id integer,
    nombre text,
    created_at timestamp,
    updated_at timestamp
);

CREATE TABLE stg.stg_modelos (
    id integer,
    nombre text,
    marca_id integer,
    created_at timestamp,
    updated_at timestamp
);

CREATE TABLE stg.stg_unidades (
    id integer,
    nombre text,
    abreviatura text,
    created_at timestamp,
    updated_at timestamp
);

CREATE TABLE stg.stg_clientes (
    id integer,
    nombre text,
    apellido text,
    dui text,
    nit text,
    nrc text,
    telefono text,
    email text,
    direccion text,
    created_at timestamp,
    updated_at timestamp
);

CREATE TABLE stg.stg_proveedores (
    id integer,
    nombre text,
    nit text,
    nrc text,
    telefono text,
    email text,
    direccion text,
    created_at timestamp,
    updated_at timestamp
);

CREATE TABLE stg.stg_usuarios (
    id integer,
    name text,
    lastname text,
    email text,
    created_at timestamp,
    updated_at timestamp
);

CREATE TABLE stg.stg_productos (
    id integer,
    nombre text,
    sku text,
    stock integer,
    costo_promedio numeric(12, 4),
    precio_venta numeric(12, 4),
    categoria_id integer,
    subcategoria_id integer,
    marca_id integer,
    modelo_id integer,
    unidad_id integer,
    status boolean,
    tipo text,
    aplica_iva boolean,
    codigo_interno text,
    fecha_vencimiento date,
    created_at timestamp,
    updated_at timestamp
);

CREATE TABLE stg.stg_ventas (
    id integer,
    cliente_id integer,
    user_id integer,
    sucursal_id integer,
    subtotal numeric(12, 4),
    monto_iva numeric(12, 4),
    total_venta numeric(12, 4),
    status text,
    serie text,
    correlativo text,
    metodo_pago text,
    created_at timestamp,
    updated_at timestamp
);

CREATE TABLE stg.stg_detalles_ventas (
    id integer,
    venta_id integer,
    producto_id integer,
    cantidad integer,
    precio_venta numeric(12, 4),
    created_at timestamp,
    updated_at timestamp
);

CREATE TABLE stg.stg_compras (
    id integer,
    proveedor_id integer,
    user_id integer,
    sucursal_id integer,
    subtotal numeric(12, 4),
    monto_iva numeric(12, 4),
    total_compra numeric(12, 4),
    status text,
    nombre_proveedor text,
    numero_factura text,
    created_at timestamp,
    updated_at timestamp
);

CREATE TABLE stg.stg_detalles_compras (
    id integer,
    compra_id integer,
    producto_id integer,
    cantidad integer,
    precio_compra numeric(12, 4),
    created_at timestamp,
    updated_at timestamp
);

CREATE TABLE stg.stg_producto_sucursal (
    id integer,
    producto_id integer,
    sucursal_id integer,
    stock integer,
    created_at timestamp,
    updated_at timestamp
);
