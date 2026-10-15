<div align="center">

# 🏭 Centro de Plásticos Limeño

## Arquitectura Integral de Datos e Inteligencia de Negocios

[![Laravel](https://img.shields.io/badge/Laravel-11.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com)
[![Vue.js](https://img.shields.io/badge/Vue.js-3.x-4FC08D?style=for-the-badge&logo=vue.js&logoColor=white)](https://vuejs.org)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-8.x-336791?style=for-the-badge&logo=postgresql&logoColor=white)](https://www.postgresql.org)
[![Python](https://img.shields.io/badge/Python-ETL-3776AB?style=for-the-badge&logo=python&logoColor=white)](https://www.python.org)

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

El **Centro de Plásticos Limeño**, ubicado en San Miguel, El Salvador, orienta su actividad comercial a la distribución de productos plásticos y desechables. El proyecto diseña e implementa una **arquitectura integral de datos** que abarca todo el ciclo de la información: desde la captura transaccional hasta la explotación analítica, con el fin de apoyar la toma de decisiones gerenciales.

La solución parte de un sistema web transaccional (OLTP) desarrollado con **Laravel y Vue.js**, que registra compras, ventas e inventarios en una base de datos relacional **PostgreSQL**. Sobre este cimiento, se construye un flujo de **Inteligencia de Negocios** compuesto por un proceso **ETL en Python**, un **Data Warehouse/Data Mart** estructurado bajo un modelo dimensional, y un **dashboard analítico interactivo** en Streamlit o Power BI para identificar los productos más rentables, monitorear la evolución del margen bruto y ajustar estrategias de compra basándose en datos consolidados.

> **Palabras clave:** Inteligencia de Negocios · Arquitectura de datos · Sistema transaccional · Proceso ETL · Modelo dimensional · Rotación de inventario · Rentabilidad comercial · Toma de decisiones

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

Diseñar e implementar una **arquitectura de datos integral** para Centro de Plásticos Limeño que abarque desde la captura de información mediante un sistema operacional web hasta una solución de Inteligencia de Negocios, utilizando procesos ETL, modelado dimensional y un dashboard interactivo para facilitar el análisis del comportamiento de productos, ventas, inventarios y rentabilidad.

### Objetivos Específicos

1. **Sistema OLTP** — Desarrollar e implementar un sistema web funcional (PHP/Laravel + Vue.js) que registre y gestione las operaciones comerciales, respaldado por una BD OLTP en PostgreSQL.
2. **Optimización SQL** — Aplicar estrategias de optimización a consultas representativas en PostgreSQL para asegurar un rendimiento eficiente en la gestión operativa.
3. **Proceso ETL** — Diseñar e implementar scripts ETL en Python para integrar, limpiar y preparar los datos de compras, ventas e inventario.
4. **Data Warehouse** — Diseñar e implementar un Data Warehouse/Data Mart mediante un modelo dimensional que separe la carga analítica del entorno transaccional.
5. **KPIs** — Definir y calcular indicadores de ventas, ingresos, costos, rotación de inventario y margen bruto.
6. **Dashboard analítico** — Desarrollar un dashboard en Streamlit (o Power BI) que visualice los KPIs a través de diferentes dimensiones para apoyar la toma de decisiones.

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
│              Dashboard Streamlit / Power BI                     │
│              KPIs · Tendencias · Análisis de rentabilidad       │
└─────────────────────────────────────────────────────────────────┘
```

---

## 🛠️ Marco Tecnológico

| Capa                   | Tecnología               | Rol                                   |
| ---------------------- | ------------------------ | ------------------------------------- |
| **Frontend**           | Vue.js 3 + Quasar        | Interfaz de usuario, POS, facturación |
| **Backend**            | PHP / Laravel 11         | Lógica de negocio, API REST           |
| **Base de datos OLTP** | PostgreSQL               | Almacén transaccional normalizado     |
| **ETL**                | Python                   | Extracción, transformación y carga    |
| **Data Warehouse**     | PostgreSQL (dimensional) | Modelo estrella/copo de nieve         |
| **Dashboard BI**       | Streamlit / Power BI     | Visualización de KPIs e indicadores   |

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

## 📈 Componente 4 — Dashboard Analítico (BI)

### KPIs Principales

| Indicador                     | Descripción                                                                    |
| ----------------------------- | ------------------------------------------------------------------------------ |
| **Margen Bruto**              | Ganancia real tras descontar costos variables por producto/período             |
| **Rotación de Inventario**    | Dinámica de movimiento por categoría (desechables vs. artículos para el hogar) |
| **Concentración de Ingresos** | Qué categorías aportan el mayor flujo de efectivo                              |
| **Evolución de Costos**       | Impacto del precio del petróleo y fletes en el costo de adquisición            |
| **Volumen de Ventas**         | Unidades vendidas por producto, categoría y período                            |

### Dimensiones de Análisis

- 📅 **Temporal**: día, mes, trimestre, año
- 🏷️ **Producto**: artículo individual, subcategoría, categoría
- 🤝 **Proveedor**: por origen y tipo de proveedor
- 📦 **Tipo de inventario**: rotación alta, baja, estacional

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

---

## 🔑 Credenciales por Defecto

| Usuario           | Contraseña | Rol           |
| ----------------- | ---------- | ------------- |
| `admin@admin.com` | `password` | Administrador |

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

---

## 📄 Documentación del Proyecto

| Archivo                                                                | Descripción                                          |
| ---------------------------------------------------------------------- | ---------------------------------------------------- |
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
- ✅ Desarrollo de dashboards BI (Streamlit / Power BI)
- ✅ Definición y cálculo de KPIs de rentabilidad e inventario
- ✅ Desarrollo de sistemas web (Laravel + Vue.js)
- ✅ Control de acceso basado en roles y auditoría de datos

---

<div align="center">

_Desarrollado con ❤️ por el equipo PB20002 · VV18009 · PB19022 — UES 2026_

</div>
