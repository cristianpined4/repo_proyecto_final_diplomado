<div align="center">

# 🏭 Centro de Plásticos Limeño

## Arquitectura Integral de Datos e Inteligencia de Negocios

[![Laravel](https://img.shields.io/badge/Laravel-11.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com)
[![Vue.js](https://img.shields.io/badge/Vue.js-3.x-4FC08D?style=for-the-badge&logo=vue.js&logoColor=white)](https://vuejs.org)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-8.x-336791?style=for-the-badge&logo=postgresql&logoColor=white)](https://www.postgresql.org)
[![Python](https://img.shields.io/badge/Python-ETL-3776AB?style=for-the-badge&logo=python&logoColor=white)](https://www.python.org)
[![Streamlit](https://img.shields.io/badge/Streamlit-1.36+-FF4B4B?style=for-the-badge&logo=streamlit&logoColor=white)](https://streamlit.io)

**Proyecto Final — Pre-Especialización en Bases de Datos e Inteligencia de Negocios**
**Universidad de El Salvador (UES)**

</div>

---

## 👥 Integrantes del Equipo

| Nombre                         | Carnet  |
| ------------------------------ | ------- |
| Cristian Alberto Pineda Blanco | PB20002 |
| Andrés Isaí Vásquez Vásquez    | VV18009 |
| Luis Andrés Parada Barrero     | PB19022 |

---

## 📋 Resumen

El **Centro de Plásticos Limeño**, ubicado en San Miguel, El Salvador, orienta su actividad comercial a la distribución mayorista y minorista de productos plásticos, desechables y artículos para el hogar. El proyecto diseña e implementa una **arquitectura integral de datos** que abarca todo el ciclo de la información: desde la captura transaccional hasta la explotación analítica, con el fin de apoyar la toma de decisiones gerenciales.

La solución parte de un sistema web transaccional (OLTP) desarrollado con **Laravel y Vue.js**, que registra compras, ventas e inventarios en una base de datos relacional **PostgreSQL**. Sobre este cimiento, se construye un flujo de **Inteligencia de Negocios** compuesto por un proceso **ETL en Python**, un **Data Warehouse/Data Mart** estructurado bajo un modelo dimensional en PostgreSQL/Docker, y una **plataforma analítica interactiva en Streamlit** orquestada con `st.navigation` y 8 módulos especializados para auditar el margen bruto ponderado, monitorear alertas de inventario crítico (< 15 piezas), analizar el rendimiento por sucursal y conciliar el balance fiscal de IVA (13%).

> **Palabras clave:** Inteligencia de Negocios · Arquitectura de datos · Sistema transaccional · Proceso ETL · Modelo dimensional · Rotación de inventario · Rentabilidad comercial · Toma de decisiones · Streamlit

---

## 🎯 Planteamiento del Problema

El Centro de Plásticos Limeño mantiene **tres años de historial operativo** en su sistema OLTP, pero la dependencia exclusiva de ese entorno presenta limitaciones críticas para el análisis:

- El **costo de adquisición** fluctúa constantemente (precio del petróleo, fletes), impactando el margen bruto a lo largo del tiempo.
- El catálogo mezcla artículos de **alto volumen / bajo margen** (bolsas, desechables) con productos de **menor movimiento / alta rentabilidad** (artículos para el hogar, cajas plásticas, macetas).
- Existe **inventario estacional** (papel celofán, listones) con períodos de rotación nula.
- Realizar análisis complejos directamente sobre la base de datos operacional **degrada el rendimiento** del sistema de facturación.

**El problema central:** la necesidad de separar los entornos OLTP y OLAP, transformando la información operacional en una estructura analítica que responda consultas gerenciales sin afectar las operaciones cotidianas.

---

## 🎓 Objetivos del Proyecto

### Objetivo General

Diseñar e implementar una **arquitectura de datos integral** para Centro de Plásticos Limeño que abarque desde la captura de información mediante un sistema operacional web hasta una solución de Inteligencia de Negocios, utilizando procesos ETL, modelado dimensional y una plataforma de dashboards interactivos en Streamlit para facilitar el análisis del comportamiento de productos, ventas, inventarios, cumplimiento tributario y rentabilidad comercial.

### Objetivos Específicos

1. **Sistema OLTP** — Desarrollar e implementar un sistema web funcional (PHP/Laravel + Vue.js) que registre y gestione las operaciones comerciales, respaldado por una BD OLTP en PostgreSQL.
2. **Optimización SQL** — Aplicar estrategias de optimización a consultas representativas en PostgreSQL para asegurar un rendimiento eficiente en la gestión operativa.
3. **Proceso ETL** — Diseñar e implementar scripts ETL en Python para integrar, limpiar y preparar los datos de compras, ventas e inventario.
4. **Data Warehouse** — Diseñar e implementar un Data Warehouse/Data Mart mediante un modelo dimensional que separe la carga analítica del entorno transaccional.
5. **KPIs y Métricas Formales** — Definir y calcular indicadores normalizados de facturación neta, margen ponderado, rotación de existencias, ticket promedio y balance de IVA fiscal.
6. **Plataforma Analítica BI** — Desarrollar una suite ejecutiva en Streamlit estructurada con navegación por áreas de negocio (`st.navigation`), filtros multinivel interactivos y 8 módulos analíticos especializados.

---

## 🏗️ Arquitectura del Sistema

La solución abarca el **ciclo completo de los datos**:

```
┌─────────────────────────────────────────────────────────────────┐
│                     CAPA DE CAPTURA (OLTP)                      │
│                                                                 │
│   Vue.js (Frontend)  ◄──►  Laravel API  ◄──►  PostgreSQL       │
│   Ventas · Compras · Inventario · Facturación electrónica       │
└──────────────────────────────┬──────────────────────────────────┘
                               │
                    Proceso ETL (Python)
                    Extracción · Limpieza · Transformación
                               │
                               ▼
┌─────────────────────────────────────────────────────────────────┐
│                  CAPA ANALÍTICA (OLAP / DW)                     │
│                                                                 │
│          Data Warehouse / Data Mart (Modelo Dimensional)        │
│          Hechos: Ventas · Compras · Inventario                  │
│          Dimensiones: Producto · Categoría · Fecha · Proveedor  │
└──────────────────────────────┬──────────────────────────────────┘
                               │
                               ▼
┌─────────────────────────────────────────────────────────────────┐
│                  CAPA DE VISUALIZACIÓN (BI)                     │
│                                                                 │
│          Plataforma Analítica Streamlit (Carpeta Dashboards/)   │
│          8 Módulos · st.navigation · Plotly · Filtros Globales  │
└─────────────────────────────────────────────────────────────────┘
```

---

## 🛠️ Marco Tecnológico

| Capa                   | Tecnología                  | Rol                                                         |
| ---------------------- | --------------------------- | ----------------------------------------------------------- |
| **Frontend**           | Vue.js 3 + Quasar           | Interfaz de usuario, POS, facturación y gestión operativa   |
| **Backend**            | PHP / Laravel 11            | Lógica de negocio, API REST transaccional                   |
| **Base de datos OLTP** | PostgreSQL                  | Almacén transaccional normalizado (compras, ventas, kardex) |
| **ETL**                | Python                      | Extracción, transformación y carga idempotente              |
| **Data Warehouse**     | PostgreSQL (Docker)         | Esquema estrella analítico (`dw.*`)                         |
| **Dashboard BI**       | Streamlit + Plotly          | Plataforma analítica ejecutiva (8 módulos especializados)   |

---

## 📊 Componente 1 — Sistema OLTP (InventaryApp)

### Descripción

Sistema web transaccional que centraliza la recopilación, validación y almacenamiento de las operaciones comerciales de la empresa.

### Módulos Implementados

| Módulo                     | Descripción                                                                  |
| -------------------------- | ---------------------------------------------------------------------------- |
| **Ventas (POS)**           | Punto de venta con cálculo de IVA, métodos de pago y tickets térmicos        |
| **Compras**                | Registro de compras a proveedores con actualización automática de inventario |
| **Inventario / Kardex**    | Control de stock por sucursal con trazabilidad completa de movimientos       |
| **Productos**              | Catálogo con categorías, subcategorías, marcas, modelos e imágenes           |
| **Clientes / Proveedores** | Gestión con validación de NIT/NRC/correo                                     |
| **Usuarios y Roles**       | Control de acceso (Administrador, Gerente, Vendedor, Bodeguero)              |
| **Reportes**               | Análisis básico de ventas, compras e inventario                              |
| **Configuración**          | IVA, empresa, moneda, series y correlativos parametrizables                  |

### Stack del Sistema OLTP

```
InventaryApp/
├── InventaryApp/          # Frontend — Quasar (Vue 3) + Electron
│   └── src/
│       ├── pages/         # Vistas: Ventas, Compras, Kardex, Productos...
│       ├── store/         # Estado global con Pinia
│       └── boot/          # Configuración de Axios, i18n
└── InventaryAppApi/       # Backend — Laravel 11
    └── app/
        ├── Http/Controllers/  # Controladores API REST
        ├── Models/            # Modelos Eloquent (18 entidades)
        ├── Services/          # Lógica de negocio
        └── Policies/          # Autorización por roles
```

---

## 🔄 Componente 2 — Proceso ETL (Python)

El flujo ETL conecta el entorno operativo (OLTP) con el analítico (Data Warehouse):

### Fases del ETL

```
EXTRACCIÓN                TRANSFORMACIÓN               CARGA
─────────────────         ──────────────────────       ─────────────────
PostgreSQL OLTP    ──►    Unificación de formatos  ──► Data Warehouse
Tablas operativas         Cálculo de costos             (Modelo Dimensional)
3 años de historial       derivados
                          Limpieza de inconsistencias
                          Normalización de catálogos
```

- **Ejecución**: en horarios de baja actividad comercial para no interrumpir la facturación.
- **Librería principal**: Python con conectores PostgreSQL y librerías de procesamiento de datos.

---

## 🏛️ Componente 3 — Data Warehouse (Modelo Dimensional)

### Modelo Estrella

```
                    ┌──────────────┐
                    │  dim_tiempo  │
                    │  año/mes/día │
                    └──────┬───────┘
                           │
┌──────────────┐    ┌──────┴───────┐    ┌──────────────────┐
│  dim_producto│────│  fact_ventas │────│  dim_categoria   │
│  nombre      │    │  unidades    │    │  nombre          │
│  categoría   │    │  ingresos    │    │  subcategoría    │
│  proveedor   │    │  costo       │    └──────────────────┘
└──────────────┘    │  margen_bruto│
                    │  rotación    │
                    └──────┬───────┘
                           │
                    ┌──────┴───────┐
                    │ dim_proveedor│
                    │ nombre       │
                    │ origen       │
                    └──────────────┘
```

### Tablas de Hechos

- `fact_ventas` — Unidades vendidas, ingresos, costo, margen bruto
- `fact_compras` — Unidades adquiridas, costos por proveedor
- `fact_inventario` — Niveles de stock, rotación

### Dimensiones

- `dim_tiempo` — Año, trimestre, mes, día
- `dim_producto` — Nombre, categoría, subcategoría, unidad de medida
- `dim_proveedor` — Nombre, origen, tipo
- `dim_categoria` — Agrupación de productos por tipo

---

## 📈 Componente 4 — Plataforma Analítica Empresarial BI (Streamlit)

La carpeta [`Dashboards/`](./Dashboards) contiene la solución integral de Business Intelligence y Analítica Empresarial desarrollada en **Streamlit** y **Plotly** para **Centro de Plásticos El Limeño**. 

### Arquitectura de Aislamiento y Desacoplamiento

- **Cero impacto al entorno transaccional**: La plataforma se conecta exclusivamente al Data Warehouse PostgreSQL (`dw.*`), garantizando que la explotación analítica nunca degrade las cajas y puntos de venta del sistema OLTP.
- **Consultas optimizadas y almacenamiento en caché**: Motor analítico estructurado en `Dashboards/db.py` con decoradores `@st.cache_data` para acelerar los tiempos de respuesta y minimizar la latencia de red.
- **Orquestación modular nativa**: Navegación lateral estructurada mediante `st.navigation`, agrupando los módulos en categorías lógicas del negocio sin menús transitorios ni parpadeos.

### Módulos Analíticos Implementados

```
Dashboards/
├── app.py                             # Orquestador principal de navegación (st.navigation)
├── db.py                              # Conector SQLAlchemy al Data Warehouse y consultas agregadas
├── filters.py                         # Motor de filtros reactivos globales sincronizados en sesión
├── styles.py                          # Sistema de diseño ejecutivo, tema oscuro y Material Symbols
├── .streamlit/config.toml             # Configuración de servidor, cliente y tema corporativo
└── views/                             # 8 Módulos de inteligencia de negocios
    ├── 0_Inicio.py                    # Panel Principal y Resumen Operativo
    ├── 1_Resumen_Ejecutivo_CMI.py     # Rendimiento Estratégico y Control Multidimensional
    ├── 2_Rendimiento_Sucursales.py    # Desempeño Comercial por Punto de Venta
    ├── 3_Rentabilidad_Productos.py    # Rentabilidad, Análisis ABC y Pareto 80/20
    ├── 4_Inventario_y_Alertas.py      # Control de Stock Crítico y Cobertura
    ├── 5_Compras_y_Proveedores.py     # Abastecimiento y Concentración de Proveedores
    ├── 6_Balance_IVA_Fiscal.py        # Cuadratura Tributaria y Liquidación de IVA
    └── 7_Catalogo_Indicadores.py      # Manual Institucional de Métricas y Gobernanza
```

#### 1. Macro-Sección: Visión Estratégica
* **Panel Principal** (`views/0_Inicio.py`): Vista ejecutiva de KPIs consolidados (Facturación neta de $3.27M, Margen Ponderado de 23.17%, Ticket Medio de $355.85, 9,201 transacciones verificadas), donut de participación por sucursal, tendencia temporal dinámica con auto-resolución diaria/mensual, monitor de bodega y diagnósticos comerciales.
* **Rendimiento y Estrategia** (`views/1_Resumen_Ejecutivo_CMI.py`): Cuadro de mando integral organizado en 4 dimensiones de control (Financiera, Comercial/Clientes, Procesos Internos, Catálogo y Almacén), cadena causal de alertas preventivas e histórico anual.

#### 2. Macro-Sección: Operaciones Comerciales
* **Sucursales y Canales** (`views/2_Rendimiento_Sucursales.py`): Análisis comparativo Head-to-Head entre las 3 sucursales físicas:
  * **Usulután**: Líder en volumen de facturación ($1,633,508.87 en 3,067 ventas) con modelo semi-mayorista (Ticket: $532.61, Margen: 23.12%).
  * **San Miguel - Metrocentro**: Canal de tráfico continuo ($1,080,756.65 en 3,066 ventas, Ticket: $352.50, Margen: 21.36%).
  * **San Miguel - Matriz Centro**: Canal minorista de máxima rentabilidad ($559,813.99 en 3,068 ventas, Ticket: $182.47, Margen récord: 26.80%).
  * Matriz de dispersión interactiva Ticket Medio vs. Margen Bruto.
* **Rentabilidad y Catálogo ABC** (`views/3_Rentabilidad_Productos.py`): Curva de Pareto 80/20 sobre facturación acumulada, clasificación ABC de SKUs, matriz de cuadrantes Margen vs. Volumen para detectar la trampa de artículos inmovilizados, y rankings Top/Bottom.
* **Control de Stock y Alertas** (`views/4_Inventario_y_Alertas.py`): Monitor preventivo de 16 artículos críticos con existencias físicas menores al umbral de seguridad (< 15 unidades), cálculo de días de inventario (DSI) y generador exportable de órdenes de reposición para bodega.

#### 3. Macro-Sección: Cadena de Suministro y Finanzas
* **Abastecimiento y Proveedores** (`views/5_Compras_y_Proveedores.py`): Control de gasto en adquisiciones ($2.52M acumulados en 8 fabricantes), índice de concentración en proveedores líderes (Plásticos de El Salvador con 46.85%), e IVA Crédito fiscal soportado ($289.89K).
* **Cuadratura y Balance Fiscal** (`views/6_Balance_IVA_Fiscal.py`): Auditoría tributaria mensual de IVA Débito (ventas $376.65K) vs. IVA Crédito (compras $289.89K), arrojando una obligación neta histórica de liquidación fiscal de $86,761.79 ante el Ministerio de Hacienda (13%).
* **Manual de Métricas y Gobernanza Financiera** (`views/7_Catalogo_Indicadores.py`): Catálogo normativo con 7 fichas técnicas (`MET-FIN-01` a `MET-ABAS-07`), formulación matemática institucional en notación algebraica pura (sin expresiones SQL), reglas de contrapeso para auditoría interna y Procedimiento Operativo Estandarizado (POE) para cierres contables.

### Motor de Filtros Globales y Reactividad

El módulo `filters.py` dota a la plataforma de una barra de control lateral reactiva y sincronizada:
- **Filtro Temporal Multinivel**: Selección de ejercicio anual (2016 a 2026), selector de fecha exacta por rango (`date_input`), semana del año (1 a 52), día de la semana (Lunes a Domingo) y mes del año.
- **Filtros Comerciales**: Selección de sucursales físicas y categorías de productos.
- **Persistencia en Sesión**: Almacenamiento dinámico en `st.session_state` con botón de reseteo integral a valores predeterminados.

---

## 🗄️ Base de Datos OLTP

### Entidades Principales

```sql
-- Catálogos
categorias, subcategorias, marcas, modelos, unidads
productos, clientes, proveedors, sucursals, users

-- Transacciones
ventas, detalles_ventas
compras, detalles_compras

-- Inventario
producto_sucursal  -- Stock aislado por sucursal
kardex             -- Trazabilidad de todos los movimientos
movimientos_inventarios

-- Sistema
settings           -- Configuración parametrizada
audit_logs         -- Auditoría del sistema
```

El script SQL base se encuentra en [`tarealimeño.sql`](./tarealimeño.sql).

---

## 🚀 Instalación del Sistema OLTP

### Prerrequisitos

- **PHP** >= 8.2 + **Composer** >= 2.x
- **Node.js** >= 18.x + **npm** >= 6.13
- **PostgreSQL** 14+ (o MySQL 8.x)
- **Python** >= 3.10 (para el ETL)

### 1. Clonar el Repositorio

```bash
git clone https://github.com/cristianpined4/repo_proyecto_final_diplomado.git
cd repo_proyecto_final_diplomado
```

### 2. Backend (Laravel API)

```bash
cd InventaryAppApi
composer install
cp .env.example .env
php artisan key:generate
```

Configurar la conexión en `.env`:

```env
DB_CONNECTION=pgsql          # PostgreSQL como BD principal
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=plasticos_limeno
DB_USERNAME=postgres
DB_PASSWORD=tu_contraseña
```

```bash
php artisan migrate --seed   # Migraciones + datos de prueba
php artisan storage:link     # Enlace para imágenes de productos
php artisan serve            # API en http://localhost:8000
```

### 3. Frontend (Quasar + Vue 3)

```bash
cd ../InventaryApp
npm install
npm run dev                  # App en http://localhost:9000
```

Para compilar la versión de escritorio (Electron):

```bash
npm run build
```

### 4. ETL (Python)

```bash
cd etl/
pip install -r requirements.txt
python etl_main.py           # Ejecutar el proceso ETL
```

### 5. Plataforma de Dashboards BI (Streamlit)

Asegúrese de contar con el Data Warehouse en ejecución (PostgreSQL en Docker en el puerto 5433 o configurado en `.env`).

```bash
cd Dashboards/
pip install -r requirements.txt
python -m streamlit run app.py
```

La plataforma estará disponible en el navegador en `http://localhost:8501`.

---

## 🔑 Credenciales por Defecto

| Usuario | Contraseña | Rol           |
| ------- | ---------- | ------------- |
| `admin` | `admin`    | Administrador |

> ⚠️ **Cambiar las credenciales antes de poner en producción.**

---

## 📐 Principios de Diseño

### Sistema OLTP

- **Paginación y búsqueda 100% server-side** — Optimizado para +100,000 registros
- **Inventario por sucursal** — No existe stock global; todo pertenece a una sucursal
- **Trazabilidad total** — Todo movimiento genera registro en el kardex
- **IVA y parámetros desde BD** — Sin valores hardcodeados en el código
- **Índices compuestos** — Para búsquedas de alta frecuencia en productos, clientes y proveedores

### Data Warehouse

- **Separación OLTP/OLAP** — La carga analítica no impacta el sistema transaccional
- **Modelo dimensional (estrella)** — Consultas analíticas rápidas sobre histórico
- **ETL automatizable** — Actualización en horarios de baja actividad
- **Historial de 3 años** — Base de datos con registros históricos completos

### Plataforma Analítica (BI)

- **Aislamiento absoluto** — Lectura exclusiva contra `dw.*` vía SQLAlchemy
- **No aditividad** — Recálculo estricto de márgenes y razones sobre sumatorias atómicas
- **Navegación nativa** — `st.navigation` organizada en 3 macro-secciones y 8 módulos ejecutivos
- **Filtros globales persistentes** — Sincronización en tiempo real vía `st.session_state`

---

## 📄 Documentación del Proyecto

| Archivo                                                                | Descripción                                          |
| ---------------------------------------------------------------------- | ---------------------------------------------------- |
| [`Dashboards/README.md`](./Dashboards/README.md)                       | Manual técnico y operativo de la plataforma de Dashboards BI en Streamlit |
| [`Lineamientos_Proyecto_Final.pdf`](./Lineamientos_Proyecto_Final.pdf) | Requerimientos y lineamientos oficiales del proyecto |
| [`tarealimeño.sql`](./tarealimeño.sql)                                 | Script SQL base de la base de datos operacional      |
| [`pdf_text.txt`](./pdf_text.txt)                                       | Extracción de texto del documento de lineamientos    |

---

## 🎓 Contexto Académico

**Programa:** Pre-Especialización en Bases de Datos e Inteligencia de Negocios  
**Institución:** Universidad de El Salvador (UES)  
**Empresa objetivo:** Centro de Plásticos Limeño — San Miguel, El Salvador  
**Tipo de proyecto:** Proyecto Final Integrador  

### Áreas de Conocimiento Aplicadas

- ✅ Diseño y modelado de bases de datos relacionales (PostgreSQL)
- ✅ Optimización de consultas SQL e índices (OLTP)
- ✅ Arquitectura de datos y separación OLTP/OLAP
- ✅ Proceso ETL con Python para integración de datos históricos
- ✅ Modelado dimensional (esquema estrella/copo de nieve)
- ✅ Desarrollo de dashboards BI avanzados (Streamlit + Plotly)
- ✅ Definición y cálculo de KPIs de rentabilidad, inventario y fiscalidad
- ✅ Desarrollo de sistemas web (Laravel + Vue.js)
- ✅ Control de acceso basado en roles y auditoría de datos

---

<div align="center">

_Desarrollado con ❤️ por el equipo PB20002 · VV18009 · PB19022 — UES 2026_

</div>
