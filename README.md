<div align="center">

# 🏭 Centro de Plásticos El Limeño

## Arquitectura Integral de Datos e Inteligencia de Negocios

[![Laravel](https://img.shields.io/badge/Laravel-11.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com)
[![Vue.js](https://img.shields.io/badge/Vue.js-3.x-4FC08D?style=for-the-badge&logo=vue.js&logoColor=white)](https://vuejs.org)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-16-336791?style=for-the-badge&logo=postgresql&logoColor=white)](https://www.postgresql.org)
[![Docker](https://img.shields.io/badge/Docker-Data_Warehouse-2496ED?style=for-the-badge&logo=docker&logoColor=white)](https://www.docker.com)
[![Python](https://img.shields.io/badge/Python-ETL_3.10-3776AB?style=for-the-badge&logo=python&logoColor=white)](https://www.python.org)
[![Streamlit](https://img.shields.io/badge/Streamlit-1.36+-FF4B4B?style=for-the-badge&logo=streamlit&logoColor=white)](https://streamlit.io)

**Proyecto Final — Curso de Especialización en Administración de Bases de Datos e Inteligencia de Negocios**  
**Universidad de El Salvador (UES) — Facultad Multidisciplinaria Oriental**  
**Departamento de Ingeniería y Arquitectura — Sección de Ingeniería de Sistemas Informáticos**

</div>

---

## 👥 Integrantes del Equipo y Asesoría

| Nombre | Carnet | Rol Principal |
| :--- | :---: | :--- |
| **Cristian Alberto Pineda Blanco** | PB20002 | Arquitectura de Datos, Optimización SQL y Backend Laravel |
| **Andrés Isaí Vásquez Vásquez** | VV18009 | Modelado Dimensional, Pipeline ETL y Pruebas Unitarias |
| **Luis Andrés Parada Barrero** | PB19022 | Plataforma de Business Intelligence en Streamlit y Frontend |

**Docente Asesor y Coordinador:** Ing. Hector Javier Paiz Ramos  
**Año Académico:** 2026 — San Miguel, El Salvador

---

## 📋 Resumen del Proyecto

El **Centro de Plásticos El Limeño**, con sede principal en la ciudad de San Miguel y sucursales en Metrocentro y Usulután, es una empresa dedicada a la comercialización mayorista y minorista de artículos plásticos, enseres para el hogar, bolsas y recipientes desechables.

El proyecto diseña e implementa una **solución integral de ingeniería de datos y analítica de negocios** que abarca el ciclo completo de vida de la información:

1. **Captura Operacional (OLTP):** Sistema web transaccional desarrollado con **Laravel 11** y **Vue.js 3**, respaldado por una base de datos relacional normalizada en **PostgreSQL** (Tercera Forma Normal) que gestiona compras, ventas, inventario por sucursal y kardex permanente.
2. **Optimización Física SQL:** Implementación de índices de cobertura (`INCLUDE`) e índices compuestos alineados a planes de ejecución evaluados con `EXPLAIN (ANALYZE, BUFFERS)`, eliminando lecturas a disco físico en el punto de venta (POS).
3. **Integración y Transformación (ETL):** Pipeline automatizado y reproducible en **Python 3.10** (SQLAlchemy, pandas) que extrae registros operacionales, normaliza la información en un área de transición (`stg.*`) y consolida las dimensiones y hechos en menos de 15 segundos, verificado con pruebas unitarias en `pytest`.
4. **Almacén de Datos (Data Warehouse):** Contenedor **Docker** con PostgreSQL 16 Alpine estructurado bajo un **modelo dimensional de constelación** (`dw.*`), compuesto por 3 tablas de hechos (`fact_ventas`, `fact_compras`, `fact_inventario_snapshot` con 60,000 registros históricos) y 6 dimensiones conformadas.
5. **Plataforma de Inteligencia de Negocios (BI):** Aplicación analítica directiva desarrollada en **Streamlit** (carpeta `Dashboards/`) estructurada mediante `st.navigation`, filtros globales persistentes en `st.session_state` y 8 módulos interactivos con gráficos Plotly para el control de rentabilidad comercial, stock crítico, rotación y balance de IVA fiscal (13%).

> **Palabras clave:** Inteligencia de Negocios, Data Warehouse, Arquitectura de Datos, Modelo Dimensional, Proceso ETL, Optimización SQL, Streamlit, PostgreSQL, Laravel, Vue.js.

---

## 🏗️ Arquitectura Global del Sistema

La solución desacopla estrictamente los entornos operacionales (OLTP) de los analíticos (OLAP) para garantizar que las consultas de alta agregación nunca degraden las cajas registradoras del negocio:

```
┌────────────────────────────────────────────────────────────────────────┐
│                   1. CAPA TRANSACCIONAL (OLTP)                         │
│                                                                        │
│   Frontend (Vue.js 3 / Vite) ◄──► API REST (Laravel 11 / PHP 8.2)     │
│   Cajas POS · Compras · Existencias por Sucursal · Kardex Ponderado    │
│   Persistencia: PostgreSQL 16 (Normalización 3NF / Supabase Cloud)     │
└───────────────────────────────────┬────────────────────────────────────┘
                                    │
                         Pipeline ETL (Python 3.10)
                 Extracción Batch · Limpieza · Deduplicación
                                    │
                                    ▼
┌────────────────────────────────────────────────────────────────────────┐
│                 2. ZONA DE TRANSICIÓN (STAGING: stg.*)                 │
│                                                                        │
│   15 Tablas de Aterrizaje sin restricciones foráneas estrictas         │
│   Auditoría de consistencia de tipos y saneamiento de nulos            │
└───────────────────────────────────┬────────────────────────────────────┘
                                    │
                         Transformación Dimensional
                 Generación de Claves Subrogadas (SK / BK)
                                    │
                                    ▼
┌────────────────────────────────────────────────────────────────────────┐
│                3. ALMACÉN DE DATOS (DATA WAREHOUSE: dw.*)              │
│                                                                        │
│   PostgreSQL 16 en Docker (Puerto 5433 / BD: inventory_dw)             │
│   Hechos: fact_ventas (18.4k), fact_compras, fact_inventario_snapshot  │
│   Dimensiones: tiempo, sucursal, producto, cliente, proveedor, usuario │
│   Optimización: Índices Parciales y Compuestos Dimensionales           │
└───────────────────────────────────┬────────────────────────────────────┘
                                    │
                      Lectura Exclusiva (SQLAlchemy)
                                    │
                                    ▼
┌────────────────────────────────────────────────────────────────────────┐
│              4. CAPA DE EXPLOTACIÓN Y DECISIÓN (BI)                    │
│                                                                        │
│   Plataforma Analítica Streamlit (Carpeta Dashboards/)                 │
│   8 Módulos Ejecutivos · st.navigation · Plotly · Control Multidim.    │
└────────────────────────────────────────────────────────────────────────┘
```

---

## 📂 Estructura del Repositorio

El repositorio se encuentra organizado de forma modular, separando cada componente de la arquitectura:

```
repo_proyecto_final_diplomado/
├── Dashboards/                         # Suite Analítica Empresarial en Streamlit
│   ├── app.py                          # Orquestador de navegación multinivel (st.navigation)
│   ├── db.py                           # Capa de acceso a datos contra dw.* vía SQLAlchemy
│   ├── filters.py                      # Motor de filtros reactivos sincronizados en sesión
│   ├── styles.py                       # Sistema visual y diseño corporativo
│   ├── requirements.txt                # Dependencias de Python para la plataforma analítica
│   └── views/                          # 8 Módulos de inteligencia de negocios
│       ├── 0_Inicio.py                 # Resumen ejecutivo y KPIs globales
│       ├── 1_Resumen_Ejecutivo_CMI.py  # Cuadro de Mando Integral en 4 dimensiones
│       ├── 2_Rendimiento_Sucursales.py # Comparativa Head-to-Head entre sucursales
│       ├── 3_Rentabilidad_Productos.py # Curva de Pareto 80/20 y análisis ABC de SKUs
│       ├── 4_Inventario_y_Alertas.py   # Monitor de existencias críticas (< 15 piezas)
│       ├── 5_Compras_y_Proveedores.py  # Gasto en adquisiciones y concentración
│       ├── 6_Balance_IVA_Fiscal.py     # Cuadratura tributaria mensual de IVA (13%)
│       └── 7_Catalogo_Indicadores.py   # Manual normativo de métricas institucionales
├── DataWareHouse/                      # Definición e infraestructura del Almacén Analítico
│   ├── docker-compose.yml              # Orquestación de PostgreSQL 16 en puerto 5433
│   └── sql/                            # Scripts DDL de inicialización automática
│       ├── 01_create_schemas_and_staging.sql
│       ├── 02_create_dimensions.sql
│       ├── 03_create_facts.sql
│       └── 04_create_indexes_and_views.sql
├── InventaryApp/                       # Frontend Web Operacional (Punto de Venta / POS)
│   ├── src/                            # Componentes Vue 3, vistas, Pinia y rutas
│   ├── package.json                    # Dependencias de Node.js / Vite
│   └── vite.config.js                  # Configuración del bundler de desarrollo
├── InventaryAppApi/                    # Backend Web Operacional (API REST en Laravel 11)
│   ├── app/                            # Controladores, modelos Eloquent y servicios
│   ├── database/migrations/            # Esquema relacional operacional en PostgreSQL
│   ├── database/seeders/               # Generadores de catálogo y transacciones base
│   └── composer.json                   # Dependencias de PHP y Laravel
├── ProcesoETL/                         # Pipeline de Extracción, Transformación y Carga
│   ├── main.py                         # Script principal de ejecución del pipeline
│   ├── test_etl.py                     # Suite de pruebas unitarias automatizadas con pytest
│   └── requirements.txt                # Dependencias de Python para el proceso ETL
├── dataset_test.sql                    # Dataset representativo DML reproducible para PostgreSQL
├── diagrama_modelo_operacional.png     # Diagrama Entidad-Relación 3NF del sistema transaccional
├── diagrama_modelo_dimensional.png     # Diagrama de Constelación del Data Warehouse
├── Evidencias_optimizacion_consultas_sistema_operacional.sql  # Trazas EXPLAIN ANALYZE OLTP
├── Evidencias_optimizacion_consultas_datawarehouse.sql        # Trazas EXPLAIN ANALYZE OLAP
└── README.md                           # Documentación principal del repositorio
```

---

## 🛠️ Marco Tecnológico Integrado

| Área / Capa | Tecnología | Versión | Rol en el Proyecto |
| :--- | :--- | :---: | :--- |
| **Backend OLTP** | PHP / Laravel | 11.x | Lógica transaccional, autenticación RBAC, API REST |
| **Frontend OLTP** | Vue.js / Vite | 3.x | Punto de Venta (POS), catálogo de sucursales y cajas |
| **BD Operacional** | PostgreSQL | 16.x | Modelo normalizado en 3NF con soporte transaccional ACID |
| **Data Warehouse** | PostgreSQL en Docker | 16-alpine | Esquema dimensional (`stg.*` y `dw.*`) en puerto 5433 |
| **Pipeline ETL** | Python / SQLAlchemy / pandas | 3.10+ | Extracción batch, saneamiento y carga dimensional |
| **Testing ETL** | pytest | 8.x | Validación de integridad referencial y cuadratura contable |
| **Plataforma BI** | Streamlit / Plotly | 1.36+ | Cuadros de mando ejecutivos con navegación interactiva |

---

## 🏛️ Modelos de Datos del Sistema

### 1. Modelo Operacional (OLTP - 3NF)
El modelo relacional transaccional se compone de 17 entidades organizadas en dominios de negocio claros, garantizando integridad referencial mediante claves foráneas y restricciones de unicidad:
- **Catálogo Central:** `categorias`, `subcategorias`, `marcas`, `modelos`, `unidads`, `productos`.
- **Inventario Distribuido:** `sucursals`, `producto_sucursal` (existencias aisladas por sucursal), `kardexes` y `movimientos_inventarios`.
- **Comercialización y Facturación:** `clientes`, `ventas`, `detalles_ventas` (con discriminación de IVA 13%, serie y correlativo).
- **Abastecimiento:** `proveedors`, `compras`, `detalles_compras`.
- **Administración y Control:** `users`, `roles`, `permissions`, `settings`.

*Consulte el archivo gráfico `diagrama_modelo_operacional.png` para visualizar la estructura relacional completa con claves primarias y foráneas.*

### 2. Modelo Dimensional (Data Warehouse - Esquema Constelación)
El almacén de datos implementa un esquema multidimensional que permite analizar ventas, adquisiciones y la evolución temporal del stock:
- **Tablas de Hechos (Facts):**
  - `dw.fact_ventas`: Granularidad atómica por ítem vendido (18,401 tuplas con precio, costo, IVA, subtotal y margen bruto).
  - `dw.fact_compras`: Detalle de adquisiciones con desglose de crédito fiscal por proveedor.
  - `dw.fact_inventario_snapshot`: Instantáneas periódicas de existencias (60,000 registros con valorización de inventario al costo y precio de venta, con bandera booleana `stock_bajo_alerta`).
- **Dimensiones Conformadas (Conformed Dimensions):**
  - `dw.dim_tiempo`: Jerarquía completa de fechas (año, semestre, trimestre, mes, semana, día, día de la semana, bandera de fin de semana).
  - `dw.dim_sucursal`: Sucursales físicas del negocio con datos de contacto y ubicación.
  - `dw.dim_producto`: Atributos descriptivos unificados (categoría, subcategoría, marca, modelo, unidad de medida, costo y precio).
  - `dw.dim_cliente`: Información tributaria (DUI, NIT, NRC) y categorización de clientes.
  - `dw.dim_proveedor`: Datos fiscales y comerciales de fabricantes y distribuidores.
  - `dw.dim_usuario`: Cajeros y operadores responsables de los registros.

*Consulte el archivo gráfico `diagrama_modelo_dimensional.png` para visualizar la constelación analítica con sus correspondientes relaciones de cardinalidad.*

---

## ⚡ Análisis y Evidencias de Optimización SQL

Se realizaron evaluaciones de desempeño mediante trazas reales de `EXPLAIN (ANALYZE, BUFFERS)` en ambos motores de bases de datos:

### 1. Optimización en el Sistema Operacional (OLTP)
* **Búsqueda de Producto y Validación de Stock en Caja (POS):**
  - *Problema:* El escaneo por SKU requería un `Index Scan` en la tabla `producto_sucursal`, forzando accesos a la tabla física en disco (`Heap Table Access`) para leer el stock actual.
  - *Solución:* Creación de un **Índice de Cobertura** (`Covering Index`) con cláusula `INCLUDE`:
    ```sql
    CREATE INDEX idx_prod_sucursal_covering 
    ON producto_sucursal (sucursal_id, producto_id) 
    INCLUDE (stock_actual, stock_minimo);
    ```
  - *Resultado:* El plan cambió a `Index Only Scan` con **Heap Fetches: 0**, extrayendo el stock directamente desde la memoria RAM sin tocar el disco.
* **Histórico de Ventas Pagadas por Sucursal:**
  - *Problema:* Lectura inversa de fechas descartaba 104 registros de otras sucursales (`Rows Removed by Filter: 104`), con un costo de escaneo de 419.
  - *Solución:* **Índice Compuesto Ordenado** `(sucursal_id, status, created_at DESC)`.
  - *Resultado:* Descarte de tuplas reducido a 0 absoluto y costo de escaneo disminuido a 274 (-34.5%).

### 2. Optimización en el Data Warehouse (OLAP)
* **Cubo Agregado de Ventas y Margen Bruto (18,401 tuplas de hechos):**
  - *Problema:* `Seq Scan` sobre la tabla de hechos con lectura de 709 bloques de memoria y 28.1 ms de tiempo de ejecución.
  - *Solución:* **Índice Compuesto Dimensional** en las llaves foráneas de hechos:
    ```sql
    CREATE INDEX idx_fact_ventas_reporting 
    ON dw.fact_ventas (fecha_sk, sucursal_sk, producto_sk);
    ```
  - *Resultado:* Tiempo de ejecución reducido a 17.4 ms (-38%) y tiempo de planeación reducido de 11.2 ms a 2.3 ms (-79.4%).
* **Monitoreo de Alertas de Stock Crítico en Snapshots (60,000 tuplas):**
  - *Problema:* `Seq Scan` descartando 56,680 tuplas con stock normal, requiriendo ordenamiento en memoria (`top-N heapsort`) y consumiendo 192.6 ms.
  - *Solución:* **Índice Parcial** B-Tree pre-ordenado:
    ```sql
    CREATE INDEX idx_snapshot_alerta_parcial 
    ON dw.fact_inventario_snapshot (stock_actual ASC, producto_sk, sucursal_sk, fecha_sk) 
    WHERE stock_bajo_alerta = true;
    ```
  - *Resultado:* Tiempo de respuesta reducido a **1.72 ms** (aceleración superior a **111x** sin requerir paso de ordenamiento).

*Las trazas completas y reproducibles se encuentran en los archivos `Evidencias_optimizacion_consultas_sistema_operacional.sql` y `Evidencias_optimizacion_consultas_datawarehouse.sql`.*

---

## 📈 Plataforma de Business Intelligence (Streamlit)

La suite analítica ubicada en la carpeta `Dashboards/` implementa una interfaz ejecutiva con tema corporativo oscuro, métricas reactivas y navegación orquestada con `st.navigation`:

```
Dashboards/
├── app.py                             # Orquestador general y menú de navegación
├── db.py                              # Conector SQLAlchemy con caché analítica (@st.cache_data)
├── filters.py                         # Motor de filtros multidimensionales en st.session_state
├── styles.py                          # Sistema de diseño con tokens visuales y estilos CSS
└── views/                             # Módulos analíticos especializados
    ├── 0_Inicio.py                    # Vista global de facturación ($3.27M), margen y ticket promedio
    ├── 1_Resumen_Ejecutivo_CMI.py     # Cuadro de Mando Integral en 4 dimensiones estratégicas
    ├── 2_Rendimiento_Sucursales.py    # Comparativa de canales (Usulután, Metrocentro y Matriz Centro)
    ├── 3_Rentabilidad_Productos.py    # Análisis ABC de SKUs y curva de Pareto 80/20
    ├── 4_Inventario_y_Alertas.py      # Monitor de existencias bajo stock mínimo y cobertura
    ├── 5_Compras_y_Proveedores.py     # Auditoría de compras ($2.52M) y concentración de proveedores
    ├── 6_Balance_IVA_Fiscal.py        # Liquidación fiscal: IVA Débito ($376.6K) vs. IVA Crédito ($289.8K)
    └── 7_Catalogo_Indicadores.py      # Fichas técnicas normativas institucionales de KPIs
```

---

## 🚀 Guía de Instalación y Ejecución

A continuación se detallan los pasos para poner en marcha cada uno de los componentes de la arquitectura.

### 1. Requisitos Previos del Sistema
- **Git** instalado en el sistema.
- **Docker Desktop** activo con soporte para contenedores Linux.
- **PHP 8.2+** con extensiones activas: `pdo_pgsql`, `pgsql`, `mbstring`, `fileinfo`.
- **Composer 2.x**.
- **Node.js 18+** y gestor `npm`.
- **Python 3.10+** con gestor de paquetes `pip`.

---

### 2. Despliegue del Data Warehouse (Docker)
El contenedor de base de datos analítica debe inicializarse en primer lugar:

```bash
cd DataWareHouse
docker compose up -d
```

*Verificación de estado:*
```bash
docker ps --filter "name=inventory_dw_pg"
```
*Parámetros de conexión:* Host: `localhost` | Puerto: `5433` | BD: `inventory_dw` | Usuario: `dw_admin` | Clave: `dw_secure_password_2026`.

---

### 3. Despliegue del Backend Operacional (Laravel API)
En una nueva terminal, configure y levante la API transaccional:

```bash
cd InventaryAppApi
composer install
cp .env.example .env
php artisan key:generate
```

Configure la conexión a PostgreSQL en su archivo `.env` y ejecute las migraciones y datos iniciales:

```bash
php artisan migrate:fresh --seed
php artisan serve
```
*La API estará escuchando en `http://127.0.0.1:8000`.*  
*(Opcional: Si prefiere poblar la base directamente con SQL, ejecute el script `dataset_test.sql` en su cliente de PostgreSQL).*

---

### 4. Despliegue del Frontend Web (Vue.js 3 / Vite)
En una nueva terminal, instale dependencias y ejecute el servidor de desarrollo:

```bash
cd InventaryApp
npm install
npm run dev
```
*La aplicación web estará accesible en el navegador en `http://localhost:5173`.*

---

### 5. Ejecución del Proceso ETL y Pruebas Unitarias (Python)
Para extraer los datos del sistema operacional y poblar el Data Warehouse dimensional:

```bash
cd ProcesoETL
python -m venv venv

# Activar el entorno virtual:
# En Windows:
venv\Scripts\activate
# En Linux / macOS:
source venv/bin/activate

pip install -r requirements.txt
python main.py
```

*Para ejecutar la suite de pruebas unitarias de validación contable y dimensional:*
```bash
pytest test_etl.py -v
```

---

### 6. Ejecución de la Plataforma Analítica (Streamlit)
En una nueva terminal, instale las librerías analíticas e inicie los dashboards:

```bash
cd Dashboards
pip install -r requirements.txt
python -m streamlit run app.py
```

*La plataforma se abrirá automáticamente en su navegador en `http://localhost:8501`.*

---

## 🔑 Credenciales de Acceso por Defecto

| Componente | Usuario / Identificador | Contraseña | Rol / Permiso |
| :--- | :--- | :--- | :--- |
| **Sistema Web OLTP** | `admin@plasticoslimeno.com` (o `admin`) | `password` (o `admin`) | Administrador General del Sistema |
| **Data Warehouse (Docker)** | `dw_admin` | `dw_secure_password_2026` | Administrador de Esquemas `stg` y `dw` |
| **Base de Datos Operacional** | `postgres` | Configurada en `.env` | Acceso a nivel de base de datos |

---

<div align="center">

**Universidad de El Salvador — Facultad Multidisciplinaria Oriental**  
*Curso de Especialización en Administración de Bases de Datos e Inteligencia de Negocios 2026*  
*Desarrollado por Cristian Alberto Pineda Blanco, Andrés Isaí Vásquez Vásquez y Luis Andrés Parada Barrero*

</div>
