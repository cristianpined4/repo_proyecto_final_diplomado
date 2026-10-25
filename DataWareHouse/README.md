# Infraestructura Data Warehouse / Data Mart - InventoryApp

Infraestructura analítica desarrollada para el proyecto final de la cátedra **Administración de Bases de Datos e Inteligencia de Negocios** (UES - FMO 2026).

---

## 1. Arquitectura y Componentes

- **Motor**: PostgreSQL 16 (Alpine).
- **Esquema de Datos**:
  - `stg`: Zona de staging (tablas de aterrizaje reproducibles sin restricciones foráneas).
  - `dw`: Modelo dimensional en **Esquema Estrella** (dimensiones con claves subrogadas `_sk` y de negocio `_bk`, tablas de hechos con grano definido y medidas aditivas).
- **Vistas Analíticas**: Resumen de KPIs ejecutivos, ventas por sucursal, rentabilidad por producto, rotación de inventario, balance IVA fiscal, compras por proveedor y alertas de stock mínimo.

---

## 2. Requisitos Previos

- Docker y Docker Compose instalados en el sistema (ejecutable mediante `wsl docker` o `docker`).
- Puerto `5433` disponible en el host local (para evitar colisión con otras instancias de PostgreSQL en el puerto 5432).

---

## 3. Instrucciones de Uso

### 3.1 Configuración de Variables
El archivo `.env` ya se encuentra parametrizado con las credenciales por defecto:
```bash
POSTGRES_DB=inventory_dw
POSTGRES_USER=dw_admin
POSTGRES_PASSWORD=dw_secure_password_2026
POSTGRES_PORT=5433
```

### 3.2 Iniciar el Contenedor
Ejecutar desde la raíz del proyecto o desde la carpeta `DataWareHouse/`:

```bash
# Desde WSL
wsl docker compose -f DataWareHouse/docker-compose.yml up -d

# O si se ejecuta directamente con el CLI de Docker
docker compose -f DataWareHouse/docker-compose.yml up -d
```

### 3.3 Verificar el Estado del Servicio
```bash
wsl docker compose -f DataWareHouse/docker-compose.yml ps
```
El contenedor `dw_postgres` debe figurar en estado `healthy` y escuchando en `0.0.0.0:5433->5432/tcp`.

### 3.4 Conexión a la Base de Datos Analítica
Desde cualquier cliente SQL (DBeaver, pgAdmin, psql):
- **Host**: `localhost` o `127.0.0.1`
- **Puerto**: `5433`
- **Base de Datos**: `inventory_dw`
- **Usuario**: `dw_admin`
- **Contraseña**: `dw_secure_password_2026`

Desde la línea de comandos con psql dentro del contenedor:
```bash
wsl docker exec -it dw_postgres psql -U dw_admin -d inventory_dw
```

### 3.5 Detener el Contenedor
```bash
wsl docker compose -f DataWareHouse/docker-compose.yml down
```
*(Los datos se conservan gracias al volumen persistente `dw_postgres_data`).*

---

## 4. Scripts de Inicialización

Los scripts ubicados en `DataWareHouse/sql/` se ejecutan automáticamente en orden al iniciar por primera vez el contenedor:
1. `01_create_schemas_and_staging.sql`: Creación de esquemas `stg`, `dw` y tablas de aterrizaje.
2. `02_create_dimensions.sql`: Dimensiones (`dim_tiempo`, `dim_sucursal`, `dim_producto`, `dim_cliente`, `dim_proveedor`, `dim_usuario`) y calendario automático.
3. `03_create_facts.sql`: Tablas de hechos (`fact_ventas`, `fact_compras`, `fact_inventario_snapshot`).
4. `04_create_indexes_and_views.sql`: Índices B-Tree de rendimiento y vistas analíticas de negocio.
