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
| FASE 7: Plataforma de Dashboards BI (Streamlit) | COMPLETADO Y VERIFICADO | 8 vistas ejecutivas interactivas en `Dashboards/` con filtros multidimensionales, CMI, control de stock y balance de IVA. |
| Restricción: No modificar Frontend/Backend | COMPLETADO Y VERIFICADO | Cero archivos modificados en `InventaryApp/` e `InventaryAppApi/`. |

---

## 9. Documentación Técnica Detallada y Catálogo de Indicadores

Para una explicación exhaustiva con la taxonomía del Módulo IV Unidad 7 (Parmenter, Kaplan & Norton, Doran SMART, análisis de aditividad y efectos perversos), fórmulas matemáticas y desglose línea por línea del código, consultar:
- [DataWareHouse/DOCUMENTACION_TECNICA_DETALLADA.md](file:///c:/Users/user/Desktop/repo_proyecto_final_diplomado/DataWareHouse/DOCUMENTACION_TECNICA_DETALLADA.md)

---

## 10. Plataforma de Inteligencia de Negocios y Dashboards (Streamlit)

### 10.1 Arquitectura de la Plataforma (`Dashboards/`)
Se desarrolló una solución de Business Intelligence de nivel ejecutivo alojada en `Dashboards/`, desacoplada del aplicativo transaccional y conectada directamente al Data Warehouse en PostgreSQL.

- **Punto de Entrada (`Dashboards/app.py`)**:
  - Arquitectura multipágina basada en un menú lateral persistente (`st.sidebar.radio`) conectado a `st.session_state["nav_view"]`.
  - Eliminación de parpadeos y renderizado inconsistente mediante gestión de estado centralizada.
  - Barra superior ejecutiva con estado de sincronización de datos y resumen de indicadores globales.

- **Capa de Conexión y Rendimiento (`Dashboards/db.py`)**:
  - Conexión vía SQLAlchemy al contenedor del Data Warehouse (`localhost:5433/inventory_dw`).
  - Implementación de caché analítico (`@st.cache_data(ttl=600)`) con invalidación manual.
  - Funciones de consulta optimizadas para cálculo de métricas financieras, series temporales a nivel diario y mensual, rentabilidad por SKU, alertas de inventario y balances fiscales.

- **Sistema de Diseño e Identidad Visual (`Dashboards/styles.py`)**:
  - **Identidad Corporativa**: Estética ejecutiva sobria en modo oscuro/slate (`#0B0F19`, `#111827`, `#00D2FF`, `#10B981`, `#EF4444`). Se eliminaron todas las etiquetas y menciones automáticas tipo *"AI Suite"* o *"Generado por IA"*, adaptando el producto como software empresarial de Centro de Plásticos El Limeño.
  - **Tipografía e Iconografía**: Integración de la fuente *Plus Jakarta Sans* y la librería de glifos *Google Material Symbols*.
  - **Componentes Reutilizables**:
    - `render_banner()`: Encabezados ejecutivos con píldora de estado en vivo.
    - `render_kpi_card()`: Tarjetas analíticas con bandas cromáticas, indicadores de variación (delta) y etiquetas.
    - `render_diagnostic()`: Cajas de síntesis estratégica con iconos de contexto.
    - `render_indicator_meta_card()`: Tarjetas para metadatos de gobernanza con diseño responsivo que previene truncamiento con puntos suspensivos (`...`).
  - **Procesamiento de Texto**: Función `format_bold_markdown()` para transformar sintaxis markdown (`**texto**`) en etiquetas HTML reales (`<strong>`), eliminando asteriscos visibles en pantalla.
  - **Localización Completa al Español**:
    - Mapeo institucional `MESES_ES_CORTO` (`Ene`, `Feb`, ..., `Dic`) y `MESES_ES_COMPLETO`.
    - Función de formato `format_mes_anio()`.
    - Configuración en gráficos de Plotly (`xaxis=dict(type="category")`) para evitar que motores de renderizado D3 apliquen formatos en inglés (`Jan`, `Apr`, etc.).
  - **Reglas CSS Anti-Truncamiento**:
    - Anulación global de elipsis en métricas de Streamlit (`div[data-testid="stMetricValue"]` y `div[data-testid="stMetricLabel"]`), asegurando lectura íntegra en pantallas de cualquier resolución.

- **Motor de Filtros Multidimensionales (`Dashboards/filters.py`)**:
  - **Filtro de Puntos de Venta**: Multiselección para Usulután, Matriz Centro y Metrocentro.
  - **Filtro Temporal Dinámico**:
    - Presets rápidos: Histórico Completo, Ejercicios Anuales (2024, 2025, 2026), Ventanas Móviles (Últimos 30, 90 y 180 días).
    - Rango personalizado con selectores de fecha (`date_input`).
  - **Filtros por Dimensión**: Categorías de catálogo, Años fiscales, Meses (en español: Enero a Diciembre) y Proveedores estratégicos.
  - Contador de transacciones activas según los filtros aplicados y botón de reseteo rápido.

---

### 10.2 Vistas Analíticas Implementadas (`Dashboards/views/`)

1. **`0_Inicio.py` — Resumen Operacional y Ejecutivo**:
   - 8 tarjetas KPI globales: Facturación Bruta, Margen Ponderado, Utilidad Neta, Transacciones, Unidades Despachadas, IVA Débito, Stock en Riesgo y Capital en Bodega.
   - Gráfico interactivo de tendencia temporal: Conmutación automática entre vista diaria ($\le 45$ días) y mensual, con fechas y meses localizados al español.
   - Donut de distribución de ingresos (Costo de Mercancía COGS vs. Margen Neto).
   - Comparativas horizontales de ingresos y ticket promedio por punto de venta.

2. **`1_Resumen_Ejecutivo_CMI.py` — Cuadro de Mando Integral (Balanced Scorecard)**:
   - Alineación según la metodología de Robert Kaplan y David Norton.
   - Evaluación en 4 perspectivas: Financiera, Clientes, Procesos Internos, y Aprendizaje y Crecimiento.
   - Definición de objetivos SMART (Doran), semaforización de metas institucionales y matriz de relaciones causa-efecto.

3. **`2_Rendimiento_Sucursales.py` — Rendimiento y Comparativa de Puntos de Venta**:
   - Matriz de dispersión/burbujas: Ticket promedio vs. volumen transaccional vs. volumen de ingresos.
   - Gráfico de barras apiladas: Desglose de base imponible entre costo de mercancía y utilidad neta por sucursal.
   - Análisis de volumen físico despachado por cada tienda.

4. **`3_Rentabilidad_Productos.py` — Rentabilidad por Categoría y Análisis ABC**:
   - Matriz de dispersión: Margen comercial (%) frente a ventas netas ($).
   - Curva de Pareto y Clasificación ABC (Regla 80/20): Gráfico combinado de barras y línea acumulada porcentual.
   - Rankings de productos más y menos rentables para apoyo a decisiones de surtido.

5. **`4_Inventario_y_Alertas.py` — Monitoreo Preventivo de Stock y DSI**:
   - Detección temprana de quiebres de góndola bajo el umbral preventivo de 15 unidades físicas.
   - Matriz de artículos críticos por sucursal y categoría.
   - Indicadores de Rotación de Inventario y Días de Inventario en Bodega (DSI).

6. **`5_Compras_y_Proveedores.py` — Gestión de Compras y Proveedores**:
   - Evaluación de concentración de compras por fabricante y distribuidor.
   - Gráfico de barras horizontal con el Top 15 de proveedores por monto facturado.
   - Donut de dependencia: Cuota de los 10 principales proveedores frente al resto de la cartera.

7. **`6_Balance_IVA_Fiscal.py` — Balance y Liquidación Mensual de IVA**:
   - Auditoría fiscal de IVA Débito (13% en ventas) vs. IVA Crédito (13% en compras formales).
   - Gráfico comparativo mensual con nombres de meses en español y balance neto a pagar / remanente a favor.
   - Libro tabular de cuadratura tributaria mensual.

8. **`7_Catalogo_Indicadores.py` — Glosario y Diccionario de Métricas**:
   - Compendio de gobernanza con 7 Fichas Técnicas normalizadas (`MET-FIN-01` a `MET-ABAS-07`).
   - Formulación matemática rigurosa en notación LaTeX (sin SQL crudo incrustado).
   - Metadatos institucionales (Áreas responsables, metas, frecuencias) presentados en tarjetas `render_indicator_meta_card` sin truncamiento de texto.
   - Matriz maestra de control de métricas y Procedimiento Operativo Estandarizado (POE) para cierres contables y auditoría interna.

---

### 10.3 Cumplimiento de Estándares Técnicos y Formato Numérico / Monetario
- **Formateo Numérico y Monetario Riguroso**:
  - **Cantidades y Conteos Enteros**: Formato estandarizado `x,xxx` sin decimales y con separador de miles por comas (ej. `9,201` transacciones, `174,835` unidades físicas, `10,000` SKUs, `1,660` alertas) en todas las tarjetas KPI, tooltips de gráficos y tablas interactivas (`st.dataframe`).
  - **Valores Monetarios**: Formato estandarizado `$x,xxx.xx` con símbolo de dólar `$`, separador de miles por comas y exactamente dos decimales (ej. `$3,274,079.50`, `$1,633,508.87`, `$355.84`, `$5.00`) en métricas ejecutivas, etiquetas de barras, tooltips y columnas tabuladas.
  - **Tasas Porcentuales**: Formato estandarizado `xx.xx%` (ej. `23.17%`, `26.80%`).
  - **Funciones Centralizadas en `Dashboards/styles.py`**: `format_currency()`, `format_integer()`, `format_percent()`.
- **Sintaxis Streamlit**: Se reemplazó el parámetro obsoleto `use_container_width=True` por `width="stretch"` en todas las tablas y gráficos interactivos.
- **Rendimiento**: Ejecución fluida sin bloqueos gracias al desacoplamiento entre capa de cálculo (`Dashboards/db.py`) y capa de presentación (`Dashboards/views/`).
- **Aislamiento**: Cero modificaciones en las carpetas operacionales del aplicativo web original (`InventaryApp/` e `InventaryAppApi/`).
