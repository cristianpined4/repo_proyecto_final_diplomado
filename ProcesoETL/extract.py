"""
Modulo de Extraccion (Extract) - Proceso ETL
Extrae datos del PostgreSQL operacional de InventoryApp utilizando SQLAlchemy y pandas.
"""

import logging
import pandas as pd
from sqlalchemy import text
from ProcesoETL.config import get_oltp_engine

logger = logging.getLogger("ETL_Extract")


def extract_table(table_name: str, query: str = None) -> pd.DataFrame:
    """Extrae una tabla completa o resultado de consulta desde el OLTP."""
    engine = get_oltp_engine()
    sql = query if query else f"SELECT * FROM {table_name}"
    try:
        with engine.connect() as conn:
            df = pd.read_sql(text(sql), conn)
        logger.info("Tabla extraida exitosamente: %-20s | Registros: %d", table_name, len(df))
        return df
    except Exception as e:
        logger.error("Error al extraer la tabla %s: %s", table_name, str(e))
        raise


def extract_all_data() -> dict:
    """
    Realiza la extraccion completa de todas las tablas operacionales necesarias
    para poblar el Staging y el Data Mart.
    """
    logger.info("Iniciando fase de extraccion desde la base operacional...")
    
    data = {}
    tables = [
        "sucursals",
        "categorias",
        "subcategorias",
        "marcas",
        "modelos",
        "unidads",
        "productos",
        "clientes",
        "proveedors",
        "users",
        "ventas",
        "detalles_ventas",
        "compras",
        "detalles_compras",
        "producto_sucursal",
    ]
    
    for t in tables:
        data[t] = extract_table(t)

    logger.info("Fase de extraccion finalizada con exito (%d tablas procesadas).", len(tables))
    return data
