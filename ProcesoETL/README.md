# Proceso ETL en Python - InventoryApp to Data Warehouse

Componente de integración, extracción, transformación y carga desarrollado en **Python** para transferir datos desde la base operacional hacia el Data Warehouse analítico en PostgreSQL.

---

## 1. Arquitectura del ETL

```
┌─────────────────────────────────┐
│  PostgreSQL Operacional (OLTP)  │  (Extrae: ventas, compras, productos, kardex, etc.)
└────────────────┬────────────────┘
                 │
                 ▼
┌─────────────────────────────────┐
│     Proceso ETL con Python      │
│  • extract.py                   │  (Extracción con SQLAlchemy y pandas)
│  • transform.py                 │  (Limpieza, reglas de negocio, cálculo de margen y KPIs)
│  • load.py                      │  (Carga a staging y DW con transacciones e idempotencia)
└────────────────┬────────────────┘
                 │
                 ▼
┌─────────────────────────────────┐
│     Data Warehouse (OLAP)       │
│  • Esquema stg (Staging)        │  (Aterrizaje seguro y reproducible)
│  • Esquema dw (Star Schema)     │  (Dimensiones y Hechos con claves _sk)
│  • Vistas Analíticas            │  (Vistas de KPIs para BI y Dashboard)
└─────────────────────────────────┘
```

---

## 2. Requisitos y Dependencias

Instalar las dependencias listadas en `requirements.txt`:
```bash
pip install -r ProcesoETL/requirements.txt
```

---

## 3. Configuración de Conexiones

### 3.1 Origen (OLTP)
El proceso lee dinámicamente la variable `DB_URL` desde `InventaryAppApi/.env` sin alterar el archivo original del aplicativo.
También puede sobreescribirse mediante la variable de entorno:
```bash
export OLTP_DATABASE_URL="postgresql://usuario:password@host:5432/database"
```

### 3.2 Destino (Data Warehouse)
Configurado a través de `ProcesoETL/.env`:
```ini
DW_HOST=localhost
DW_PORT=5433
DW_DATABASE=inventory_dw
DW_USER=dw_admin
DW_PASSWORD=dw_secure_password_2026
```

---

## 4. Ejecución del Proceso ETL

Para ejecutar el proceso completo:
```bash
python -m ProcesoETL.main
# O directamente:
python ProcesoETL/main.py
```

### Funcionalidades implementadas:
1. **Verificación de conectividad**: Valida origen y destino antes de iniciar.
2. **Extracción**: Lectura de 15 tablas operacionales reales.
3. **Staging**: Carga de aterrizaje en `stg.*` para trazabilidad y depuración.
4. **Dimensiones**: Limpieza y desnormalización de catálogos (`dw.dim_*`) con upsert idempotente.
5. **Mapeo de Claves**: Obtención dinámica de claves subrogadas (`_sk`).
6. **Hechos**:
   - `fact_ventas`: Grano por línea de venta, solo transacciones `pagada`, cálculo de IVA, costo y utilidad bruta.
   - `fact_compras`: Grano por línea de compra, solo transacciones `pagado`.
   - `fact_inventario_snapshot`: Snapshot diario de existencias, costos, valorización y detección de alertas de stock crítico (< 15).
7. **Control de duplicados**: Cargas repetidas no duplican registros gracias al uso de `ON CONFLICT DO UPDATE`.

---

## 5. Pruebas Unitarias y de Integración

Ejecutar la suite de pruebas automatizada con pytest:
```bash
pytest ProcesoETL/test_etl.py -v
```
