# Memoria Técnica del Proyecto: Data Warehouse y ETL InventoryApp

## 1. Resumen Ejecutivo y Requisitos

- **Proyecto**: Solución de Inteligencia de Negocios y Almacenamiento de Datos para InventoryApp / Plásticos Limeño.
- **Contexto Académico**: Universidad de El Salvador (UES) — FMO 2026. Curso de Especialización "Administración de Bases de Datos e Inteligencia de Negocios".
- **Regla Crítica del Entorno**: Prohibición absoluta de modificar el aplicativo existente (Frontend en Quasar/Vue y Backend API en Laravel). El desarrollo está 100% aislado dentro de `datawarehouse/`, `ProcesoETL/` y `AI_MEMORY.md`.

---

## 2. Hallazgos del Esquema Operacional (OLTP)

- **Motor Operacional**: PostgreSQL en la nube (referenciado vía variable `DB_URL` en `InventaryAppApi/.env`).
- **Tablas Inspeccionadas y Volumetría Real**:
  - `productos`: 10,000 registros (SKU, nombre, costo_promedio, precio_venta, categoria_id, marca_id, modelo_id, subcategoria_id, unidad_id, stock, status).
  - `categorias`: 9 registros.
  - `subcategorias`: 36 registros.
  - `marcas`: 11 registros.
  - `modelos`: 35 registros.
  - `unidads`: 8 registros.
  - `clientes`: 10,000 registros (nombre, apellido, dui, nit, nrc, telefono, direccion).
  - `proveedors`: 10,000 registros (nombre, nit, nrc, telefono, direccion).
  - `sucursals`: 3 sucursales operativas.
  - `users`: 10 usuarios del sistema.
  - `ventas`: 10,001 transacciones (cliente_id, sucursal_id, user_id, total_venta, subtotal, monto_iva, status, serie, correlativo).
  - `detalles_ventas`: 20,001 líneas de venta (venta_id, producto_id, cantidad, precio_venta).
  - `compras`: 10,001 registros (proveedor_id, sucursal_id, user_id, total_compra, subtotal, monto_iva, status).
  - `detalles_compras`: 20,001 líneas de compra (compra_id, producto_id, cantidad, precio_compra).
  - `kardexes`: 10,002 movimientos de inventario.
  - `producto_sucursal`: 30,000 asignaciones de stock por sucursal.

---

## 3. Diseño del Data Warehouse / Data Mart

- **Paradigma**: Esquema Estrella (Star Schema) basado en lineamientos de la cátedra (guía FarmaOriente y módulo de modelado dimensional).
- **Separación de Esquemas en BD Analítica**:
  - `stg`: Zona de staging (tablas de aterrizaje sin restricciones complejas para carga reproducible).
  - `dw`: Modelo dimensional analítico definitivo.
- **Dimensiones (`dw.dim_*`)**:
  - `dim_tiempo`: Clave subrogada `fecha_sk` (formato AAAAMMDD), fecha, año, semestre, trimestre, mes, nombre_mes, año_mes, semana, día, nombre_día, es_fin_de_semana.
  - `dim_sucursal`: Clave subrogada `sucursal_sk`, clave de negocio `sucursal_bk`, nombre, dirección, teléfono.
  - `dim_producto`: Clave subrogada `producto_sk`, clave de negocio `producto_bk`, sku, nombre, categoría, subcategoría, marca, modelo, unidad_medida, costo_promedio, precio_venta, aplica_iva, tipo, estado.
  - `dim_cliente`: Clave subrogada `cliente_sk`, clave de negocio `cliente_bk`, nombre_completo, dui, nit, nrc, teléfono, email, dirección.
  - `dim_proveedor`: Clave subrogada `proveedor_sk`, clave de negocio `proveedor_bk`, nombre, nit, nrc, teléfono, email, dirección.
  - `dim_usuario`: Clave subrogada `usuario_sk`, clave de negocio `usuario_bk`, nombre_completo, email.
- **Tablas de Hechos (`dw.fact_*`)**:
  - `fact_ventas`:
    - **Grano**: Una fila por cada línea de detalle de venta con estado 'pagada'.
    - **Dimensiones**: `fecha_sk`, `sucursal_sk`, `producto_sk`, `cliente_sk`, `usuario_sk`.
    - **Dimensión Degenerada**: `venta_id_bk`, `serie_correlativo`.
    - **Medidas**: `cantidad`, `precio_unitario`, `subtotal_linea`, `monto_iva_linea`, `total_linea`, `costo_unitario`, `costo_total`, `utilidad_bruta`.
  - `fact_compras`:
    - **Grano**: Una fila por cada línea de detalle de compra con estado 'pagado'.
    - **Dimensiones**: `fecha_sk`, `sucursal_sk`, `producto_sk`, `proveedor_sk`, `usuario_sk`.
    - **Dimensión Degenerada**: `compra_id_bk`, `numero_factura`.
    - **Medidas**: `cantidad`, `precio_compra`, `subtotal_linea`, `monto_iva_linea`, `total_linea`.
  - `fact_inventario_snapshot`:
    - **Grano**: Una fila por producto y por sucursal en el momento de captura.
    - **Dimensiones**: `fecha_sk`, `sucursal_sk`, `producto_sk`.
    - **Medidas**: `stock_actual`, `costo_promedio`, `precio_venta`, `valor_inventario_costo`, `valor_inventario_venta`, `stock_bajo_alerta`.

---

## 4. Vistas e Indicadores Analíticos

1. `vw_kpis_generales`: Resumen ejecutivo (Ventas Totales, Costo Total, Margen Bruto, % Margen Global, Ticket Promedio, Total Transacciones).
2. `vw_ventas_por_sucursal`: Ventas, margen y volumen transaccional desglosado por sucursal.
3. `vw_rentabilidad_por_producto`: SKU, producto, categoría, unidades vendidas, ingresos, costos, utilidad y margen %.
4. `vw_rotacion_inventario`: Stock actual, rotación y costo de ventas (COGS).
5. `vw_balance_iva_fiscal`: Débito fiscal (ventas) vs Crédito fiscal (compras) y balance a pagar/favor.
6. `vw_compras_por_proveedor`: Gasto total y volumen por proveedor.
7. `vw_alertas_stock_critico`: Alerta temprana de productos con stock < 15.

---

## 5. Infraestructura Docker

- **Contenedor**: PostgreSQL 16 Alpine oficial.
- **Archivo**: `datawarehouse/docker-compose.yml`.
- **Puerto expuesto**: `5433:5432` (aislado del puerto estándar local).
- **Volumen persistente**: `dw_postgres_data`.
- **Inicialización automática**: Montaje de scripts SQL en `/docker-entrypoint-initdb.d/`.

---

## 6. Proceso ETL en Python

- **Ubicación**: `ProcesoETL/`.
- **Comando principal**: `python ProcesoETL/main.py`.
- **Conexión Origen**: Lectura segura y sin modificaciones de `InventaryAppApi/.env` (`DB_URL`).
- **Conexión Destino**: Parámetros de conexión al DW PostgreSQL local vía variables de entorno (`localhost:5433/inventory_dw`).
- **Idempotencia**: Carga a esquema `stg.*` previa, upsert en dimensiones (`ON CONFLICT (id_bk) DO UPDATE`), y carga transaccional por lotes en hechos con claves de unicidad compuestas.

---

## 7. Pruebas Realizadas y Resultados Reales

### Pruebas Unitarias Automatizadas (pytest)
- **Comando**: `python -m pytest ProcesoETL/test_etl.py -v`
- **Resultado**: 5 de 5 pruebas aprobadas exitosamente (`5 passed in 6.39s`).
  - `test_staging_tables_loaded`: Verificación de las 15 tablas en esquema `stg`.
  - `test_dimensions_loaded`: Verificación de volumen y claves en `dw.dim_sucursal`, `dw.dim_producto`, `dw.dim_cliente`, `dw.dim_proveedor`, `dw.dim_usuario`, `dw.dim_tiempo`.
  - `test_facts_integrity`: Integridad referencial FKs contra dimensiones.
  - `test_stock_alert_rule`: Regla de negocio de alerta de stock crítico (<15).
  - `test_idempotency_dim_product`: Ausencia de duplicación al ejecutar múltiples upserts.

### Volumetría Real Cargada en el Data Warehouse
- **Dimensiones**:
  - `dw.dim_sucursal`: 3 registros.
  - `dw.dim_producto`: 10,000 registros.
  - `dw.dim_cliente`: 10,000 registros.
  - `dw.dim_proveedor`: 10,000 registros.
  - `dw.dim_usuario`: 10 registros.
  - `dw.dim_tiempo`: 5,845 registros (calendario 2015-2030 + fallback key 0).
- **Tablas de Hechos**:
  - `dw.fact_ventas`: 18,401 registros (líneas de detalle de 9,201 ventas pagadas).
  - `dw.fact_compras`: 19,430 registros (líneas de detalle de 9,716 compras pagadas).
  - `dw.fact_inventario_snapshot`: 30,000 registros (10,000 productos × 3 sucursales).

### Métricas de Negocio Verificadas en Vistas Analíticas
- **KPIs Generales (`dw.vw_kpis_generales`)**:
  - Total transacciones pagadas: 9,201
  - Total unidades vendidas: 174,835
  - Subtotal neto ventas: $2,897,415.49
  - Débito fiscal IVA ventas: $376,664.01
  - Total ingresos brutos: $3,274,079.50
  - Costo de mercancía vendida (COGS): $2,226,136.48
  - Utilidad bruta total: $671,279.01
  - Margen bruto global: 23.17%
  - Ticket promedio: $355.84
- **Rendimiento por Sucursal (`dw.vw_ventas_por_sucursal`)**:
  - Sucursal Usulután: 3,067 ventas | $1,633,508.87 total | 23.12% margen | Ticket prom. $532.61
  - Sucursal San Miguel - Metrocentro: 3,066 ventas | $1,080,756.65 total | 21.36% margen | Ticket prom. $352.50
  - Sucursal Matriz San Miguel - Centro: 3,068 ventas | $559,813.99 total | 26.80% margen | Ticket prom. $182.47
- **Alertas de Stock Crítico (`dw.vw_alertas_stock_critico`)**:
  - 1,660 combinaciones producto-sucursal con stock actual inferior a 15 unidades identificadas para reabastecimiento.

---

## 8. Estado de Cumplimiento de Fases y Requisitos

| Requisito / Fase | Estado | Evidencia |
|---|---|---|
| FASE 1: Inspección (PDFs, evaluación modelo, OLTP) | COMPLETADO Y VERIFICADO | Identificación de 15 tablas operacionales y reglas de negocio. |
| FASE 2: Diseño Dimensional (Esquema Estrella) | COMPLETADO Y VERIFICADO | Grano definido, dimensiones, hechos y vistas KPIs. |
| FASE 3: Data Warehouse (Docker Compose, SQL) | COMPLETADO Y VERIFICADO | Contenedor `dw_postgres` activo en puerto 5433 con persistencia y 4 scripts DDL ejecutados. |
| FASE 4: Proceso ETL en Python | COMPLETADO Y VERIFICADO | Pipeline modular `ProcesoETL/` (extract, transform, load, config). |
| FASE 5: Integración y Carga de Datos | COMPLETADO Y VERIFICADO | Extracción de 15 tablas OLTP, transformación y carga completa al DW. |
| FASE 6: Validación Final | COMPLETADO Y VERIFICADO | 5 pruebas pytest pasadas, vistas analíticas validadas con queries directas. |
| Restricción: No modificar Frontend/Backend | COMPLETADO Y VERIFICADO | Cero archivos modificados en `InventaryApp/` e `InventaryAppApi/`. |

---

## 9. Documentación Técnica Detallada y Catálogo de Indicadores

Para una explicación exhaustiva con la taxonomía del Módulo IV Unidad 7 (Parmenter, Kaplan & Norton, Doran SMART, análisis de aditividad y efectos perversos), fórmulas matemáticas y desglose línea por línea del código, consultar:
- [DataWareHouse/DOCUMENTACION_TECNICA_DETALLADA.md](file:///c:/Users/user/Desktop/repo_proyecto_final_diplomado/DataWareHouse/DOCUMENTACION_TECNICA_DETALLADA.md)
