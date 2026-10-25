-- ============================================================================
-- SCRIPT 03: CREACION DE TABLAS DE HECHOS (FACT TABLES)
-- Data Warehouse / Data Mart - InventoryApp
-- ============================================================================

-- 1. Tabla de Hechos: Ventas
-- Grano: Una fila por linea de detalle de venta completada ('pagada')
CREATE TABLE IF NOT EXISTS dw.fact_ventas (
    venta_hecho_sk BIGSERIAL PRIMARY KEY,
    fecha_sk integer NOT NULL REFERENCES dw.dim_tiempo(fecha_sk),
    sucursal_sk integer NOT NULL REFERENCES dw.dim_sucursal(sucursal_sk),
    producto_sk integer NOT NULL REFERENCES dw.dim_producto(producto_sk),
    cliente_sk integer NOT NULL REFERENCES dw.dim_cliente(cliente_sk),
    usuario_sk integer NOT NULL REFERENCES dw.dim_usuario(usuario_sk),
    venta_id_bk integer NOT NULL,
    detalle_venta_id_bk integer NOT NULL UNIQUE,
    serie_correlativo varchar(50),
    metodo_pago varchar(50),
    cantidad integer NOT NULL CHECK (cantidad > 0),
    precio_unitario numeric(12, 4) NOT NULL CHECK (precio_unitario >= 0),
    subtotal_linea numeric(12, 4) NOT NULL,
    monto_iva_linea numeric(12, 4) NOT NULL DEFAULT 0,
    total_linea numeric(12, 4) NOT NULL,
    costo_unitario numeric(12, 4) NOT NULL DEFAULT 0,
    costo_total numeric(12, 4) NOT NULL DEFAULT 0,
    utilidad_bruta numeric(12, 4) NOT NULL,
    created_at timestamp DEFAULT CURRENT_TIMESTAMP
);
COMMENT ON TABLE dw.fact_ventas IS 'Grano: Una fila por cada linea de producto en una venta con estado PAGADA';

-- 2. Tabla de Hechos: Compras
-- Grano: Una fila por linea de detalle de compra completada ('pagado')
CREATE TABLE IF NOT EXISTS dw.fact_compras (
    compra_hecho_sk BIGSERIAL PRIMARY KEY,
    fecha_sk integer NOT NULL REFERENCES dw.dim_tiempo(fecha_sk),
    sucursal_sk integer NOT NULL REFERENCES dw.dim_sucursal(sucursal_sk),
    producto_sk integer NOT NULL REFERENCES dw.dim_producto(producto_sk),
    proveedor_sk integer NOT NULL REFERENCES dw.dim_proveedor(proveedor_sk),
    usuario_sk integer NOT NULL REFERENCES dw.dim_usuario(usuario_sk),
    compra_id_bk integer NOT NULL,
    detalle_compra_id_bk integer NOT NULL UNIQUE,
    numero_factura varchar(100),
    cantidad integer NOT NULL CHECK (cantidad > 0),
    precio_compra numeric(12, 4) NOT NULL CHECK (precio_compra >= 0),
    subtotal_linea numeric(12, 4) NOT NULL,
    monto_iva_linea numeric(12, 4) NOT NULL DEFAULT 0,
    total_linea numeric(12, 4) NOT NULL,
    created_at timestamp DEFAULT CURRENT_TIMESTAMP
);
COMMENT ON TABLE dw.fact_compras IS 'Grano: Una fila por cada linea de producto en una compra con estado PAGADO';

-- 3. Tabla de Hechos: Snapshot de Inventario
-- Grano: Una fila por producto y sucursal en cada fecha de captura
CREATE TABLE IF NOT EXISTS dw.fact_inventario_snapshot (
    inventario_snapshot_sk BIGSERIAL PRIMARY KEY,
    fecha_sk integer NOT NULL REFERENCES dw.dim_tiempo(fecha_sk),
    sucursal_sk integer NOT NULL REFERENCES dw.dim_sucursal(sucursal_sk),
    producto_sk integer NOT NULL REFERENCES dw.dim_producto(producto_sk),
    stock_actual integer NOT NULL,
    costo_promedio numeric(12, 4) NOT NULL DEFAULT 0,
    precio_venta numeric(12, 4) NOT NULL DEFAULT 0,
    valor_inventario_costo numeric(14, 4) NOT NULL DEFAULT 0,
    valor_inventario_venta numeric(14, 4) NOT NULL DEFAULT 0,
    stock_bajo_alerta boolean NOT NULL DEFAULT false,
    created_at timestamp DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_inventario_snapshot_dia UNIQUE (fecha_sk, sucursal_sk, producto_sk)
);
COMMENT ON TABLE dw.fact_inventario_snapshot IS 'Grano: Snapshot diario de existencias y valorizacion de inventario por producto y sucursal';
