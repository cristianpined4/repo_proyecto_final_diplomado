-- ============================================================================
-- SCRIPT 04: INDICES DE RENDIMIENTO Y VISTAS ANALITICAS (KPIS / METRICAS)
-- Data Warehouse / Data Mart - InventoryApp
-- ============================================================================

-- 1. Indices B-Tree para optimizacion de joins y consultas multidimensionales
CREATE INDEX IF NOT EXISTS idx_fact_ventas_fecha ON dw.fact_ventas (fecha_sk);
CREATE INDEX IF NOT EXISTS idx_fact_ventas_sucursal ON dw.fact_ventas (sucursal_sk);
CREATE INDEX IF NOT EXISTS idx_fact_ventas_producto ON dw.fact_ventas (producto_sk);
CREATE INDEX IF NOT EXISTS idx_fact_ventas_cliente ON dw.fact_ventas (cliente_sk);
CREATE INDEX IF NOT EXISTS idx_fact_ventas_usuario ON dw.fact_ventas (usuario_sk);

CREATE INDEX IF NOT EXISTS idx_fact_compras_fecha ON dw.fact_compras (fecha_sk);
CREATE INDEX IF NOT EXISTS idx_fact_compras_sucursal ON dw.fact_compras (sucursal_sk);
CREATE INDEX IF NOT EXISTS idx_fact_compras_producto ON dw.fact_compras (producto_sk);
CREATE INDEX IF NOT EXISTS idx_fact_compras_proveedor ON dw.fact_compras (proveedor_sk);

CREATE INDEX IF NOT EXISTS idx_fact_inventario_fecha ON dw.fact_inventario_snapshot (fecha_sk);
CREATE INDEX IF NOT EXISTS idx_fact_inventario_sucursal ON dw.fact_inventario_snapshot (sucursal_sk);
CREATE INDEX IF NOT EXISTS idx_fact_inventario_producto ON dw.fact_inventario_snapshot (producto_sk);

CREATE INDEX IF NOT EXISTS idx_dim_producto_sku ON dw.dim_producto (sku);
CREATE INDEX IF NOT EXISTS idx_dim_producto_categoria ON dw.dim_producto (categoria);

-- 2. Vistas Analiticas para Inteligencia de Negocios

-- KPI 1: Resumen General Ejecutivo (Total Ventas, Costos, Utilidad y Margen)
CREATE OR REPLACE VIEW dw.vw_kpis_generales AS
SELECT
    COUNT(DISTINCT v.venta_id_bk) AS total_transacciones,
    COALESCE(SUM(v.cantidad), 0) AS total_unidades_vendidas,
    ROUND(COALESCE(SUM(v.subtotal_linea), 0), 2) AS subtotal_ventas,
    ROUND(COALESCE(SUM(v.monto_iva_linea), 0), 2) AS total_iva_debito,
    ROUND(COALESCE(SUM(v.total_linea), 0), 2) AS total_ingresos_brutos,
    ROUND(COALESCE(SUM(v.costo_total), 0), 2) AS costo_mercancia_vendida_cogs,
    ROUND(COALESCE(SUM(v.utilidad_bruta), 0), 2) AS utilidad_bruta_total,
    ROUND(
        CASE 
            WHEN SUM(v.subtotal_linea) > 0 
            THEN (SUM(v.utilidad_bruta) / SUM(v.subtotal_linea)) * 100 
            ELSE 0 
        END, 2
    ) AS margen_bruto_porcentaje,
    ROUND(
        CASE 
            WHEN COUNT(DISTINCT v.venta_id_bk) > 0 
            THEN SUM(v.total_linea) / COUNT(DISTINCT v.venta_id_bk) 
            ELSE 0 
        END, 2
    ) AS ticket_promedio
FROM dw.fact_ventas v;
COMMENT ON VIEW dw.vw_kpis_generales IS 'KPI Resumen Ejecutivo: ventas globales, margen bruto y ticket promedio';

-- KPI 2: Ventas y Rentabilidad por Sucursal
CREATE OR REPLACE VIEW dw.vw_ventas_por_sucursal AS
SELECT
    s.sucursal_bk AS id_sucursal,
    s.nombre AS sucursal,
    COUNT(DISTINCT v.venta_id_bk) AS transacciones,
    COALESCE(SUM(v.cantidad), 0) AS unidades_vendidas,
    ROUND(COALESCE(SUM(v.subtotal_linea), 0), 2) AS subtotal_ventas,
    ROUND(COALESCE(SUM(v.total_linea), 0), 2) AS total_ventas,
    ROUND(COALESCE(SUM(v.costo_total), 0), 2) AS costo_total,
    ROUND(COALESCE(SUM(v.utilidad_bruta), 0), 2) AS utilidad_bruta,
    ROUND(
        CASE 
            WHEN SUM(v.subtotal_linea) > 0 
            THEN (SUM(v.utilidad_bruta) / SUM(v.subtotal_linea)) * 100 
            ELSE 0 
        END, 2
    ) AS margen_porcentaje,
    ROUND(
        CASE 
            WHEN COUNT(DISTINCT v.venta_id_bk) > 0 
            THEN SUM(v.total_linea) / COUNT(DISTINCT v.venta_id_bk) 
            ELSE 0 
        END, 2
    ) AS ticket_promedio
FROM dw.dim_sucursal s
LEFT JOIN dw.fact_ventas v ON s.sucursal_sk = v.sucursal_sk
WHERE s.sucursal_sk > 0
GROUP BY s.sucursal_bk, s.nombre
ORDER BY total_ventas DESC;
COMMENT ON VIEW dw.vw_ventas_por_sucursal IS 'Desempeno de ventas, costo y margen por cada sucursal';

-- KPI 3: Rentabilidad por Producto y Categoria
CREATE OR REPLACE VIEW dw.vw_rentabilidad_por_producto AS
SELECT
    p.producto_bk AS id_producto,
    p.sku,
    p.nombre AS producto,
    p.categoria,
    p.marca,
    COALESCE(SUM(v.cantidad), 0) AS unidades_vendidas,
    ROUND(COALESCE(SUM(v.subtotal_linea), 0), 2) AS ingresos_netos,
    ROUND(COALESCE(SUM(v.costo_total), 0), 2) AS costo_total,
    ROUND(COALESCE(SUM(v.utilidad_bruta), 0), 2) AS utilidad_bruta,
    ROUND(
        CASE 
            WHEN SUM(v.subtotal_linea) > 0 
            THEN (SUM(v.utilidad_bruta) / SUM(v.subtotal_linea)) * 100 
            ELSE 0 
        END, 2
    ) AS margen_porcentaje
FROM dw.dim_producto p
JOIN dw.fact_ventas v ON p.producto_sk = v.producto_sk
GROUP BY p.producto_bk, p.sku, p.nombre, p.categoria, p.marca
ORDER BY utilidad_bruta DESC;
COMMENT ON VIEW dw.vw_rentabilidad_por_producto IS 'Ranking de rentabilidad, margen y volumen por producto';

-- KPI 4: Rotacion y Eficiencia de Inventario
CREATE OR REPLACE VIEW dw.vw_rotacion_inventario AS
WITH ventas_agg AS (
    SELECT
        producto_sk,
        SUM(cantidad) AS unidades_vendidas,
        SUM(costo_total) AS cogs_total
    FROM dw.fact_ventas
    GROUP BY producto_sk
),
inv_agg AS (
    SELECT
        producto_sk,
        SUM(stock_actual) AS stock_total,
        SUM(valor_inventario_costo) AS valor_inventario_costo_total
    FROM dw.fact_inventario_snapshot
    WHERE fecha_sk = (SELECT MAX(fecha_sk) FROM dw.fact_inventario_snapshot)
    GROUP BY producto_sk
)
SELECT
    p.producto_bk AS id_producto,
    p.sku,
    p.nombre AS producto,
    p.categoria,
    COALESCE(i.stock_total, 0) AS stock_actual,
    ROUND(COALESCE(i.valor_inventario_costo_total, 0), 2) AS valor_inventario_costo,
    COALESCE(v.unidades_vendidas, 0) AS unidades_vendidas,
    ROUND(COALESCE(v.cogs_total, 0), 2) AS cogs,
    ROUND(
        CASE 
            WHEN COALESCE(i.valor_inventario_costo_total, 0) > 0 
            THEN COALESCE(v.cogs_total, 0) / i.valor_inventario_costo_total 
            ELSE 0 
        END, 2
    ) AS ratio_rotacion_inventario
FROM dw.dim_producto p
LEFT JOIN inv_agg i ON p.producto_sk = i.producto_sk
LEFT JOIN ventas_agg v ON p.producto_sk = v.producto_sk
WHERE p.producto_sk > 0
ORDER BY ratio_rotacion_inventario DESC;
COMMENT ON VIEW dw.vw_rotacion_inventario IS 'Rotacion de inventario relacionando costo de venta vs valor en existencia';

-- KPI 5: Balance de IVA Fiscal (Libro IVA Ventas vs Compras)
CREATE OR REPLACE VIEW dw.vw_balance_iva_fiscal AS
WITH iva_ventas AS (
    SELECT
        t.anio,
        t.mes,
        t.nombre_mes,
        t.anio_mes,
        SUM(v.subtotal_linea) AS base_imponible_ventas,
        SUM(v.monto_iva_linea) AS iva_debito_fiscal
    FROM dw.fact_ventas v
    JOIN dw.dim_tiempo t ON v.fecha_sk = t.fecha_sk
    GROUP BY t.anio, t.mes, t.nombre_mes, t.anio_mes
),
iva_compras AS (
    SELECT
        t.anio,
        t.mes,
        t.nombre_mes,
        t.anio_mes,
        SUM(c.subtotal_linea) AS base_imponible_compras,
        SUM(c.monto_iva_linea) AS iva_credito_fiscal
    FROM dw.fact_compras c
    JOIN dw.dim_tiempo t ON c.fecha_sk = t.fecha_sk
    GROUP BY t.anio, t.mes, t.nombre_mes, t.anio_mes
)
SELECT
    COALESCE(v.anio, c.anio) AS anio,
    COALESCE(v.mes, c.mes) AS mes,
    COALESCE(v.nombre_mes, c.nombre_mes) AS nombre_mes,
    COALESCE(v.anio_mes, c.anio_mes) AS anio_mes,
    ROUND(COALESCE(v.base_imponible_ventas, 0), 2) AS base_ventas,
    ROUND(COALESCE(v.iva_debito_fiscal, 0), 2) AS iva_debito_ventas,
    ROUND(COALESCE(c.base_imponible_compras, 0), 2) AS base_compras,
    ROUND(COALESCE(c.iva_credito_fiscal, 0), 2) AS iva_credito_compras,
    ROUND(COALESCE(v.iva_debito_fiscal, 0) - COALESCE(c.iva_credito_fiscal, 0), 2) AS balance_iva_a_pagar
FROM iva_ventas v
FULL OUTER JOIN iva_compras c ON v.anio_mes = c.anio_mes
ORDER BY anio DESC, mes DESC;
COMMENT ON VIEW dw.vw_balance_iva_fiscal IS 'Balance fiscal mensual de debito fiscal vs credito fiscal';

-- KPI 6: Compras por Proveedor
CREATE OR REPLACE VIEW dw.vw_compras_por_proveedor AS
SELECT
    pr.proveedor_bk AS id_proveedor,
    pr.nombre AS proveedor,
    pr.nit,
    pr.nrc,
    COUNT(DISTINCT c.compra_id_bk) AS facturas_emitidas,
    COALESCE(SUM(c.cantidad), 0) AS unidades_adquiridas,
    ROUND(COALESCE(SUM(c.subtotal_linea), 0), 2) AS subtotal_compras,
    ROUND(COALESCE(SUM(c.monto_iva_linea), 0), 2) AS iva_credito,
    ROUND(COALESCE(SUM(c.total_linea), 0), 2) AS total_compras
FROM dw.dim_proveedor pr
JOIN dw.fact_compras c ON pr.proveedor_sk = c.proveedor_sk
WHERE pr.proveedor_sk > 0
GROUP BY pr.proveedor_bk, pr.nombre, pr.nit, pr.nrc
ORDER BY total_compras DESC;
COMMENT ON VIEW dw.vw_compras_por_proveedor IS 'Analisis de compras, volumen y gasto por proveedor';

-- KPI 7: Alertas de Stock Critico en Riesgo (< 15 unidades)
CREATE OR REPLACE VIEW dw.vw_alertas_stock_critico AS
SELECT
    s.nombre AS sucursal,
    p.sku,
    p.nombre AS producto,
    p.categoria,
    inv.stock_actual,
    p.costo_promedio,
    p.precio_venta,
    ROUND(inv.stock_actual * p.costo_promedio, 2) AS valor_inventario_costo
FROM dw.fact_inventario_snapshot inv
JOIN dw.dim_producto p ON inv.producto_sk = p.producto_sk
JOIN dw.dim_sucursal s ON inv.sucursal_sk = s.sucursal_sk
WHERE inv.fecha_sk = (SELECT MAX(fecha_sk) FROM dw.fact_inventario_snapshot)
  AND inv.stock_bajo_alerta = true
ORDER BY inv.stock_actual ASC;
COMMENT ON VIEW dw.vw_alertas_stock_critico IS 'Productos con stock en nivel de alerta (< 15 unidades) para reposicion';
