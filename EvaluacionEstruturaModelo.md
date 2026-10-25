# Evaluación Detallada: Sistema ERP vs. Lineamientos Oficiales
## Curso de Especialización — UES FMO 2026
### "Administración de Bases de Datos e Inteligencia de Negocios"

---

> [!IMPORTANT]
> Este análisis cruza **cada requisito oficial del PDF** con lo que el sistema ERP ya implementa, identificando qué cubre, qué falta y qué debe construirse.

---

## Arquitectura que Exigen los Lineamientos

```
SISTEMA DE CAPTURA → OLTP → ETL (Python) → DATA WAREHOUSE/DATA MART → STREAMLIT/POWER BI → DECISIONES
```

El PDF es muy explícito: **no se aceptará** un dashboard que consulte directamente tablas transaccionales. Se requiere separación OLTP/OLAP.

---

## 1. Lo Que el Sistema YA TIENE (Detallado)

### 1.1 Sistema de Captura y Gestión (Requisito 4.1) ✅

El sistema cumple **completamente** este requisito. Tiene una aplicación funcional desarrollada en **Laravel 12 + Quasar (Vue 3)** que cubre:

| Módulo | Vistas Existentes | Operaciones |
|---|---|---|
| **Productos** | `MainProductos.vue` | CRUD completo, SKU, costo promedio, precio, IVA, stock |
| **Categorías / Subcategorías** | `MainCategorias.vue` | CRUD con modal anidado de subcategorías |
| **Marcas y Modelos** | `MainCatalogos.vue` | CRUD completo |
| **Unidades de Medida** | `MainUnidades.vue` | CRUD completo |
| **Clientes** | `MainClientes.vue` | CRUD con DUI, NIT, NRC |
| **Proveedores** | `MainProveedores.vue` | CRUD con NIT, NRC, datos fiscales |
| **Ventas (POS)** | `MainVentas.vue` | Registro con validación de stock, IVA, subtotal, total |
| **Compras** | `MainCompras.vue` | Registro por proveedor, actualiza inventario y kardex |
| **Inventario / Kardex** | `MainKardex.vue` | Entradas, salidas, ajustes, transferencias |
| **Historial Ventas** | `MainHistoriaVentas.vue` | Anulaciones, devoluciones |
| **Historial Compras** | `MainHistoriaCompra.vue` | Historial completo por sucursal |
| **Sucursales** | `MainSucursales.vue` | CRUD multisucursal |
| **Usuarios y Roles** | `MainUsuarios.vue`, `MainRoles.vue` | Control de acceso con permisos granulares |
| **Configuración** | `MainSettings.vue` | IVA, empresa, moneda, series, correlativos |

**Tecnología justificable:** Laravel/PHP es uno de los ejemplos literales del PDF.

---

### 1.2 Reportes Analíticos ya implementados

El controlador [`ReportesController.php`](file:///c:/Users/user/Desktop/InventaryApp/InventaryAppApi/app/Http/Controllers/ReportesController.php) y la vista [`MainReportes.vue`](file:///c:/Users/user/Desktop/InventaryApp/InventaryApp/src/pages/MainReportes.vue) implementan **10 reportes** directamente relevantes al tema:

#### 📊 Reporte 1 — Resumen KPIs (Dashboard de Métricas)
```php
// DashboardController.php + ReportesController@dashboardMetrics
ventas_hoy         → suma de total_venta del día (status='pagada')
ventas_mes         → suma del mes actual
ventas_anio        → suma del año fiscal
utilidad_mes       → SUM(cantidad * (precio_venta - costo_promedio))  ← MARGEN BRUTO
total_productos    → conteo total de productos activos
stock_bajo         → productos con stock < 15
top_clientes       → top 5 clientes por volumen de compra
top_productos      → top 5 productos más vendidos por unidades
```

#### 📊 Reporte 2 — Ventas por Sucursal
```sql
SELECT sucursal, COUNT(ventas.id) AS transacciones,
       SUM(subtotal), SUM(monto_iva), SUM(total_venta)
FROM ventas JOIN sucursals ...
WHERE status='pagada' AND BETWEEN dates
GROUP BY sucursal
-- + cálculo de ticket promedio en frontend
```
Exportable a **CSV** nativo.

#### 📊 Reporte 3 — Ventas por Vendedor (Performance)
```sql
SELECT CONCAT(name,' ',lastname) AS vendedor,
       COUNT(ventas.id) AS transacciones,
       SUM(total_venta)
FROM ventas JOIN users ...
```

#### 📊 Reporte 4 — **Rentabilidad y Margen por Producto** ← EL MÁS CRÍTICO PARA EL TEMA
```sql
SELECT productos.sku, productos.nombre,
       SUM(cantidad) AS unidades_vendidas,
       SUM(cantidad * precio_venta) AS ingresos,
       SUM(cantidad * costo_promedio) AS costo_total,
       (ingresos - costo_total) AS utilidad,
       ROUND((utilidad / ingresos) * 100, 2) AS margen_porcentaje
FROM detalles_ventas
JOIN ventas ON ventas.status = 'pagada'
JOIN productos ...
GROUP BY producto
ORDER BY utilidad DESC
```
Este reporte calcula exactamente los **indicadores de ventas, costos y margen bruto** que exige el tema de especialización.

#### 📊 Reporte 5 — Rotación de Inventario
```sql
SELECT sku, nombre, stock_actual, costo_promedio,
       SUM(cantidad) AS unidades_vendidas,
       SUM(cantidad * costo_promedio) AS COGS,
       ROUND(unidades_vendidas / stock, 2) AS ratio_rotacion
FROM productos LEFT JOIN detalles_ventas ...
```

#### 📊 Reporte 6 — Compras por Proveedor
```sql
SELECT proveedor, nit, COUNT(compras.id) AS facturas,
       SUM(subtotal), SUM(total_compra)
FROM compras JOIN proveedors ...
WHERE status='pagado'
```

#### 📊 Reporte 7 — Alertas de Stock Mínimo
```sql
SELECT sku, nombre, categoria, stock,
       costo_promedio, precio_venta
FROM productos WHERE stock < 15 AND status = true
ORDER BY stock ASC
```

#### 📊 Reporte 8 — Balance de IVA (Libro Fiscal)
```sql
-- IVA Débito (Ventas)
SELECT SUM(subtotal), SUM(monto_iva), SUM(total_venta) FROM ventas

-- IVA Crédito (Compras)  
SELECT SUM(subtotal), SUM(monto_iva), SUM(total_compra) FROM compras

-- Balance: iva_debito - iva_credito = IVA a pagar o saldo a favor
```

#### 📊 Reporte 9 — Ventas por Categoría
```sql
SELECT categoria, COUNT(DISTINCT ventas.id) AS transacciones,
       SUM(cantidad) AS productos_vendidos,
       SUM(cantidad * precio_venta) AS total_venta
FROM detalles_ventas JOIN productos JOIN categorias ...
```

#### 📊 Reporte 10 — Libro Kardex Consolidado (Auditoría)
```sql
SELECT fecha, sku, nombre, cantidad_antes, cantidad_movimiento,
       costo_movimiento, cantidad_despues, costo_despues
FROM kardexes JOIN productos
ORDER BY fecha DESC
```

---

### 1.3 Modelo de Base de Datos (MySQL / OLTP)

El sistema cuenta con **36 migraciones** que definen un modelo relacional completo:

| Tabla | Propósito |
|---|---|
| `productos` | SKU, nombre, costo_promedio, precio_venta, stock, aplica_iva, categoria_id, subcategoria_id, marca_id, modelo_id, unidad_id |
| `ventas` | serie, correlativo, cliente_id, user_id, sucursal_id, subtotal, monto_iva, total_venta, status |
| `detalles_ventas` | venta_id, producto_id, cantidad, precio_venta (FK → ventas) |
| `compras` | proveedor_id, sucursal_id, subtotal, monto_iva, total_compra, status |
| `detalles_compras` | compra_id, producto_id, cantidad, precio_compra |
| `kardexes` | producto_id, sucursal_id, cantidad_antes, cantidad_movimiento, costo_movimiento, cantidad_despues, costo_despues |
| `producto_sucursal` | product_id, sucursal_id, stock (inventario aislado por sucursal) |
| `categorias` / `subcategorias` | jerarquía de categorías |
| `marcas` / `modelos` / `unidads` | catálogos maestros |
| `clientes` / `proveedors` | con DUI, NIT, NRC |
| `sucursals` | multisucursal |
| `users` + sistema de roles/permisos (Spatie) | control de acceso granular |
| `settings` | configuración parametrizada (IVA, empresa, moneda) |
| `audit_logs` | trazabilidad completa de acciones |

**Índices de rendimiento:** migración `add_performance_indexes.php` con índices en columnas críticas de consultas.

---

### 1.4 Roles y Control de Acceso

Sistema de permisos granulares implementado (Spatie Laravel Permission):
- **Administrador** — acceso total
- **Gerente** — acceso multisucursal
- **Vendedor** — POS y clientes
- **Bodeguero** — inventario y compras
- Roles personalizados creados desde la UI con permisos configurables

---

## 2. Lo Que FALTA (Brechas Críticas con el PDF)

> [!CAUTION]
> Los siguientes puntos son **OBLIGATORIOS** según los lineamientos y actualmente NO están implementados.

### ❌ BRECHA 1 — Base de Datos Operacional en PostgreSQL (Requisito 4.2)

**El PDF exige PostgreSQL como base de datos operacional.** El sistema actual usa **MySQL**.

**Impacto:** Alto. El PDF lo menciona como requisito obligatorio.

**Solución:** Cambiar el driver de Laravel de MySQL a PostgreSQL, o migrar los datos existentes a una instancia PostgreSQL. El esquema es compatible.

---

### ❌ BRECHA 2 — Separación OLTP/OLAP — Data Warehouse o Data Mart (Requisito 4.5 y 4.6)

Este es el **requisito más crítico**. El PDF es explícito:

> *"El dashboard no deberá limitarse a consultar directamente las tablas transaccionales."*
> *"No se aceptará un dashboard que funcione solamente como consulta directa de las tablas transaccionales sin separación OLTP/OLAP."*

Los reportes actuales (`ReportesController.php`) consultan **directamente** las tablas transaccionales. Esto **NO cumple** el requisito de OLAP.

**Lo que se necesita construir:**
- **Esquema estrella** con:
  - `fact_ventas` — hechos de ventas (medidas: total, costo, margen)
  - `fact_compras` — hechos de compras
  - `fact_inventario` — snapshot de inventario
  - `dim_producto` — dimensión producto (SKU, nombre, categoría, marca)
  - `dim_tiempo` — dimensión temporal (día, mes, trimestre, año)
  - `dim_sucursal` — dimensión sucursal
  - `dim_cliente` — dimensión cliente
  - `dim_proveedor` — dimensión proveedor

---

### ❌ BRECHA 3 — Proceso ETL con Python (Requisito 4.7)

**El PDF exige un proceso ETL desarrollado en Python**, no integrado en el sistema web.

```
Extracción: PostgreSQL (OLTP) → Python → Data Warehouse (OLAP)
```

Librerías sugeridas en el PDF: `pandas`, `SQLAlchemy`, `psycopg`

**Lo que se necesita:**
```python
# Ejemplo de estructura ETL requerida
import pandas as pd
from sqlalchemy import create_engine

# 1. EXTRACT — desde BD operacional
engine_oltp = create_engine("postgresql://...")
df_ventas = pd.read_sql("SELECT * FROM ventas WHERE ...", engine_oltp)

# 2. TRANSFORM — limpiar, calcular métricas, mapear dimensiones
df_ventas['margen_bruto'] = df_ventas['total_venta'] - df_ventas['costo_total']

# 3. LOAD — al Data Warehouse
engine_dw = create_engine("postgresql://dw_...")
df_ventas.to_sql('fact_ventas', engine_dw, if_exists='append')
```

---

### ❌ BRECHA 4 — Dashboard en Streamlit o Power BI (Requisito 4.9)

**El PDF exige Streamlit o Power BI** como capa analítica. Los reportes actuales en Quasar/Vue no cumplen este requisito.

**El dashboard de Streamlit debe incluir:**
- Resumen ejecutivo con KPIs principales (ventas, costos, margen, rotación)
- Filtros por dimensiones (producto, categoría, sucursal, período)
- Visualizaciones: gráficos de barras, líneas de tendencia, pie charts, heatmaps
- Análisis de rentabilidad por producto
- Análisis ABC de inventario
- Interpretación de tendencias y recomendaciones para la toma de decisiones

---

### ❌ BRECHA 5 — KPIs Formalmente Documentados (Requisito 4.8)

El PDF pide **entre 5 y 10 preguntas de negocio** y **al menos 5 KPIs formales** documentados con:
- Nombre del KPI
- Propósito
- Fórmula de cálculo
- Fuente de datos
- Interpretación para la toma de decisiones

**KPIs sugeridos para Plásticos Limeño:**

| KPI | Fórmula | Fuente |
|---|---|---|
| **Margen Bruto por Producto** | `(Ingresos - COGS) / Ingresos * 100` | fact_ventas + dim_producto |
| **Rotación de Inventario** | `COGS / Inventario Promedio` | fact_inventario |
| **DSI (Days Sales Inventory)** | `365 / Rotación de Inventario` | fact_inventario |
| **Ticket Promedio** | `Total Ventas / Nº Transacciones` | fact_ventas |
| **% Cumplimiento de Compras** | `Compras realizadas / Compras planificadas` | fact_compras |
| **Valor del Inventario en Riesgo** | `SUM(stock_critico * costo_promedio)` | fact_inventario |
| **Concentración de Ventas (ABC)** | Pareto: % productos = 80% ingresos | fact_ventas |

---

### ⚠️ BRECHA 6 — Componente NoSQL (Requisito 4.4)

MongoDB o Neo4j **si el problema lo justifica**. El PDF dice que no debe incorporarse artificialmente.

**Posible uso justificado para Plásticos Limeño:**
- **MongoDB:** Almacenar logs de actividad del sistema, catálogos de productos con atributos variables (polietileno, PVC, nylon — cada uno con propiedades distintas)
- **Neo4j:** No aplica claramente al problema de inventarios

---

### ⚠️ BRECHA 7 — Análisis de Rendimiento de Consultas (Requisito 4.3)

Documentar con `EXPLAIN ANALYZE` en PostgreSQL al menos 2 consultas antes y después de optimización.

El sistema ya tiene índices creados (`add_performance_indexes.php`) pero falta la **documentación formal** del análisis de rendimiento con el plan de ejecución.

---

## 3. Tabla de Cumplimiento por Requisito

| # | Requisito del PDF | Estado | Descripción |
|---|---|---|---|
| 4.1 | Sistema funcional de captura y gestión | ✅ **COMPLETO** | ERP completo con Laravel + Quasar |
| 4.2 | PostgreSQL como base operacional | ❌ **FALTA** | Sistema usa MySQL, debe migrarse |
| 4.3 | Análisis de rendimiento (EXPLAIN) | ⚠️ **PARCIAL** | Índices existen, falta documentación formal |
| 4.4 | Componente NoSQL (MongoDB/Neo4j) | ⚠️ **OPCIONAL** | Debe justificarse si aplica |
| 4.5 | Separación OLTP / OLAP | ❌ **FALTA** | Reportes consultan tablas transaccionales |
| 4.6 | Data Warehouse / Data Mart (esquema estrella) | ❌ **FALTA** | No existe modelo dimensional |
| 4.7 | Proceso ETL con Python | ❌ **FALTA** | No existe script ETL |
| 4.8 | KPIs documentados (mín. 5) y preguntas de negocio | ⚠️ **PARCIAL** | KPIs calculados en código pero no documentados formalmente |
| 4.9 | Dashboard en Streamlit o Power BI | ❌ **FALTA** | Los reportes están en Quasar/Vue, no en Streamlit |

---

## 4. Arquitectura Completa para el Proyecto

```
┌─────────────────────────────────────────────────────────────────┐
│  CAPA 1: SISTEMA OPERACIONAL (OLTP)  ← YA EXISTE ✅             │
│                                                                  │
│  Laravel 12 + Quasar (Vue 3) + MySQL/PostgreSQL                 │
│  Módulos: Ventas, Compras, Inventario, Kardex,                  │
│           Productos, Clientes, Proveedores, Sucursales           │
└──────────────────────────┬──────────────────────────────────────┘
                           │
                    (extracción diaria)
                           │
┌──────────────────────────▼──────────────────────────────────────┐
│  CAPA 2: ETL CON PYTHON  ← DEBE CONSTRUIRSE ❌                   │
│                                                                  │
│  script_etl.py (pandas + SQLAlchemy + psycopg)                  │
│  1. Extract: leer ventas, compras, kardex, productos            │
│  2. Transform: calcular margen, rotación, agregar por tiempo    │
│  3. Load: poblar tablas del Data Mart                           │
└──────────────────────────┬──────────────────────────────────────┘
                           │
┌──────────────────────────▼──────────────────────────────────────┐
│  CAPA 3: DATA MART (OLAP)  ← DEBE CONSTRUIRSE ❌                 │
│                                                                  │
│  PostgreSQL — Esquema Estrella                                   │
│  ┌──────────────┐    ┌──────────────┐    ┌──────────────┐      │
│  │ dim_producto │    │  dim_tiempo  │    │ dim_sucursal │      │
│  └──────┬───────┘    └──────┬───────┘    └──────┬───────┘      │
│         └──────────────┬────┘────────────────────┘             │
│                 ┌──────▼──────┐                                 │
│                 │ fact_ventas │  (total, costo, margen, cant)   │
│                 └─────────────┘                                 │
│                 ┌──────────────┐                                │
│                 │ fact_compras │  (total, costo, proveedor)     │
│                 └─────────────┘                                 │
│                 ┌──────────────────┐                            │
│                 │ fact_inventario  │  (stock, valor, rotación)  │
│                 └──────────────────┘                            │
└──────────────────────────┬──────────────────────────────────────┘
                           │
┌──────────────────────────▼──────────────────────────────────────┐
│  CAPA 4: DASHBOARD ANALÍTICO  ← DEBE CONSTRUIRSE ❌              │
│                                                                  │
│  Streamlit (Python)                                              │
│  • KPI Cards: Ventas, Margen Bruto, Rotación, Stock Crítico     │
│  • Gráficas: tendencias de ventas, rentabilidad por producto    │
│  • Análisis ABC de inventario                                   │
│  • Filtros: por período, categoría, sucursal, producto          │
│  • Recomendaciones basadas en datos                             │
└─────────────────────────────────────────────────────────────────┘
```

---

## 5. Plan de Trabajo Sugerido

### Fase 1 — Migración a PostgreSQL (1-2 días)
- Cambiar `.env` de MySQL a PostgreSQL en Laravel
- Ejecutar migraciones en PostgreSQL
- Verificar compatibilidad de tipos de datos
- Documentar modelo conceptual, lógico y físico

### Fase 2 — Diseño del Data Mart (2-3 días)
- Definir proceso de negocio: **ventas y rentabilidad de productos plásticos**
- Diseñar esquema estrella con tablas de hechos y dimensiones
- Crear scripts SQL de creación del Data Mart
- Documentar granularidad y justificación

### Fase 3 — Proceso ETL Python (3-4 días)
- Script `etl_plasticos_limeno.py` con pandas + SQLAlchemy
- Extracción desde PostgreSQL OLTP
- Transformación: calcular métricas derivadas
- Carga incremental al Data Mart
- Registro de errores y evidencias de ejecución

### Fase 4 — Dashboard Streamlit (3-4 días)
- Página de resumen ejecutivo con KPIs principales
- Gráficas de rentabilidad por producto
- Análisis de rotación e inventario
- Filtros interactivos por dimensiones
- Interpretación y recomendaciones

### Fase 5 — Análisis de Rendimiento (1 día)
- Seleccionar 2 consultas complejas (ej: rentabilidad por producto, rotación)
- Ejecutar `EXPLAIN ANALYZE` antes y después de índices
- Documentar comparativa de costos

### Fase 6 — Documentación Académica
- Seguir la estructura del PDF (21 secciones)
- Documentar KPIs formalmente
- Redactar interpretaciones y recomendaciones para Plásticos Limeño

---

## 6. Veredicto Final

> [!NOTE]
> El sistema ERP existente es una **base EXCELENTE** para el proyecto. Cubre completamente la **Capa 1 (OLTP)** que el PDF exige y tiene los datos y la lógica de negocio correctamente implementados.

**Lo que el sistema YA APORTA al proyecto:**
- ✅ Sistema de captura funcional y completo
- ✅ Datos operacionales de alta calidad (ventas, costos, inventario)
- ✅ Lógica de negocio correcta (IVA, kardex, multisucursal)
- ✅ Consultas analíticas ya validadas (margen bruto, rotación)
- ✅ Control de acceso y auditoría

**Lo que DEBE CONSTRUIRSE para cumplir el 100%:**
- ❌ Migración a PostgreSQL (o crear instancia separada)
- ❌ Esquema estrella (Data Mart)
- ❌ Script ETL en Python
- ❌ Dashboard en Streamlit
- ❌ Documentación formal de KPIs y preguntas de negocio

**Estimación de trabajo adicional:** 10-15 días de desarrollo para completar todas las brechas.
