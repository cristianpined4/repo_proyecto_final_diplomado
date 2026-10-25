"""
Modulo de Carga (Load) - Proceso ETL
Carga los datos en Staging y en el modelo dimensional del Data Warehouse
con soporte de transacciones, control de duplicados (upserts) e idempotencia.
"""

import logging
import pandas as pd
from sqlalchemy import text
from ProcesoETL.config import get_dw_engine

logger = logging.getLogger("ETL_Load")


def load_staging_table(df: pd.DataFrame, table_name: str, engine) -> int:
    """Carga una tabla en el esquema stg como copia fiel y reproducible del origen."""
    if df.empty:
        return 0

    stg_table = f"stg_{table_name}"
    # Normalizacion de nombres de staging
    name_map = {
        "stg_sucursals": "stg_sucursales",
        "stg_unidads": "stg_unidades",
        "stg_users": "stg_usuarios",
        "stg_proveedors": "stg_proveedores",
    }
    stg_table = name_map.get(stg_table, stg_table)

    # Convertir estructuras complejas (listas/diccionarios JSON) a texto plano
    clean_df = df.copy()
    for col in clean_df.columns:
        if clean_df[col].dtype == object:
            clean_df[col] = clean_df[col].apply(lambda x: str(x) if isinstance(x, (dict, list)) else x)

    with engine.begin() as conn:
        clean_df.to_sql(stg_table, con=conn, schema="stg", if_exists="replace", index=False, chunksize=2000)

    logger.info("Staging cargado: stg.%-22s | Filas: %d", stg_table, len(clean_df))
    return len(clean_df)


def load_staging_all(data: dict) -> dict:
    """Carga todas las tablas operacionales en la zona de staging."""
    logger.info("Iniciando carga en la zona de Staging (stg)...")
    engine = get_dw_engine()
    counts = {}
    for table_name, df in data.items():
        try:
            counts[table_name] = load_staging_table(df, table_name, engine)
        except Exception as e:
            logger.warning("No se pudo cargar stg_%s: %s", table_name, str(e))
    return counts


def upsert_dimension(df: pd.DataFrame, table_name: str, bk_col: str, engine) -> int:
    """
    Inserta o actualiza una dimension usando ON CONFLICT en la clave de negocio (bk).
    Garantiza idempotencia total sin registros duplicados.
    """
    if df.empty:
        return 0

    cols = list(df.columns)
    col_str = ", ".join(cols)
    param_str = ", ".join([f":{c}" for c in cols])
    update_str = ", ".join([f"{c} = EXCLUDED.{c}" for c in cols if c != bk_col])

    sql = f"""
    INSERT INTO dw.{table_name} ({col_str})
    VALUES ({param_str})
    ON CONFLICT ({bk_col}) DO UPDATE
    SET {update_str};
    """

    records = df.to_dict(orient="records")
    with engine.begin() as conn:
        conn.execute(text(sql), records)

    logger.info("Dimension actualizada: dw.%-18s | Registros: %d", table_name, len(records))
    return len(records)


def get_dimension_mappings(engine) -> dict:
    """
    Obtiene los diccionarios de mapeo desde las claves de negocio (_bk)
    hacia las claves subrogadas (_sk) generadas por el Data Warehouse.
    """
    mappings = {}
    with engine.connect() as conn:
        # Sucursales
        res = conn.execute(text("SELECT sucursal_bk, sucursal_sk FROM dw.dim_sucursal")).fetchall()
        mappings["sucursal"] = {row[0]: row[1] for row in res}

        # Productos
        res = conn.execute(text("SELECT producto_bk, producto_sk FROM dw.dim_producto")).fetchall()
        mappings["producto"] = {row[0]: row[1] for row in res}

        # Clientes
        res = conn.execute(text("SELECT cliente_bk, cliente_sk FROM dw.dim_cliente")).fetchall()
        mappings["cliente"] = {row[0]: row[1] for row in res}

        # Proveedores
        res = conn.execute(text("SELECT proveedor_bk, proveedor_sk FROM dw.dim_proveedor")).fetchall()
        mappings["proveedor"] = {row[0]: row[1] for row in res}

        # Usuarios
        res = conn.execute(text("SELECT usuario_bk, usuario_sk FROM dw.dim_usuario")).fetchall()
        mappings["usuario"] = {row[0]: row[1] for row in res}

    return mappings


def upsert_fact_table(df: pd.DataFrame, table_name: str, conflict_cols: list, engine, chunksize: int = 2000) -> int:
    """
    Inserta o actualiza una tabla de hechos usando ON CONFLICT en sus claves de control.
    """
    if df.empty:
        return 0

    cols = list(df.columns)
    col_str = ", ".join(cols)
    param_str = ", ".join([f":{c}" for c in cols])
    conflict_str = ", ".join(conflict_cols)
    update_str = ", ".join([f"{c} = EXCLUDED.{c}" for c in cols if c not in conflict_cols])

    sql = f"""
    INSERT INTO dw.{table_name} ({col_str})
    VALUES ({param_str})
    ON CONFLICT ({conflict_str}) DO UPDATE
    SET {update_str};
    """

    records = df.to_dict(orient="records")
    total = len(records)
    with engine.begin() as conn:
        for i in range(0, total, chunksize):
            chunk = records[i:i + chunksize]
            conn.execute(text(sql), chunk)

    logger.info("Tabla de hechos cargada: dw.%-20s | Filas: %d", table_name, total)
    return total
