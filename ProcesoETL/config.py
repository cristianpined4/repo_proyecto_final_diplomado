"""
Configuracion y gestion de conexiones para el proceso ETL.
Permite conectar con el origen OLTP y el destino DW de forma segura y parametrizada.
"""

import os
from pathlib import Path
from dotenv import dotenv_values
from sqlalchemy import create_engine, text

BASE_DIR = Path(__file__).resolve().parent
PROJECT_ROOT = BASE_DIR.parent
API_ENV_PATH = PROJECT_ROOT / "InventaryAppApi" / ".env"
LOCAL_ENV_PATH = BASE_DIR / ".env"


def get_oltp_url() -> str:
    """
    Obtiene la URL de conexion a la base operacional OLTP de manera segura.
    Prioridad:
    1. Variable de entorno OLTP_DATABASE_URL si existe en el entorno.
    2. Variable DB_URL en InventaryAppApi/.env (sin modificar el archivo).
    """
    # 1. Variable de entorno directa
    env_url = os.getenv("OLTP_DATABASE_URL")
    if env_url:
        url = env_url.strip()
    elif API_ENV_PATH.exists():
        # 2. Lectura en modo seguro desde el .env del aplicativo existente
        api_env = dotenv_values(API_ENV_PATH)
        url = api_env.get("DB_URL", "")
        if not url:
            # Fallback si estuviera desglosada
            host = api_env.get("DB_HOST", "127.0.0.1")
            port = api_env.get("DB_PORT", "5432")
            user = api_env.get("DB_USERNAME", "postgres")
            password = api_env.get("DB_PASSWORD", "")
            db = api_env.get("DB_DATABASE", "postgres")
            url = f"postgresql://{user}:{password}@{host}:{port}/{db}"
    else:
        raise FileNotFoundError(f"No se encontro el archivo .env de la API en {API_ENV_PATH}")

    if not url:
        raise ValueError("No se pudo obtener la cadena de conexion operacional (DB_URL).")

    # Normalizar esquema a postgresql+psycopg2
    if url.startswith("postgres://"):
        url = url.replace("postgres://", "postgresql+psycopg2://", 1)
    elif url.startswith("postgresql://") and not url.startswith("postgresql+psycopg2://"):
        url = url.replace("postgresql://", "postgresql+psycopg2://", 1)

    return url


def get_dw_url() -> str:
    """
    Obtiene la URL de conexion a la base de datos analitica (Data Warehouse).
    Lee desde ProcesoETL/.env o variables de entorno del sistema con valores por defecto seguros.
    """
    local_env = {}
    if LOCAL_ENV_PATH.exists():
        local_env = dotenv_values(LOCAL_ENV_PATH)

    host = os.getenv("DW_HOST", local_env.get("DW_HOST", "localhost"))
    port = os.getenv("DW_PORT", local_env.get("DW_PORT", "5433"))
    database = os.getenv("DW_DATABASE", local_env.get("DW_DATABASE", "inventory_dw"))
    user = os.getenv("DW_USER", local_env.get("DW_USER", "dw_admin"))
    password = os.getenv("DW_PASSWORD", local_env.get("DW_PASSWORD", "dw_secure_password_2026"))

    return f"postgresql+psycopg2://{user}:{password}@{host}:{port}/{database}"


def get_oltp_engine():
    """Retorna el motor SQLAlchemy para el origen operacional (lectura)."""
    return create_engine(get_oltp_url(), pool_pre_ping=True)


def get_dw_engine():
    """Retorna el motor SQLAlchemy para el destino analitico (escritura/lectura)."""
    return create_engine(get_dw_url(), pool_pre_ping=True)


def test_connections() -> dict:
    """
    Verifica la conectividad a ambas bases de datos sin exponer secretos.
    """
    results = {"oltp": False, "dw": False, "error_oltp": None, "error_dw": None}
    
    try:
        engine_oltp = get_oltp_engine()
        with engine_oltp.connect() as conn:
            conn.execute(text("SELECT 1"))
            results["oltp"] = True
    except Exception as e:
        results["error_oltp"] = str(e)

    try:
        engine_dw = get_dw_engine()
        with engine_dw.connect() as conn:
            conn.execute(text("SELECT 1"))
            results["dw"] = True
    except Exception as e:
        results["error_dw"] = str(e)

    return results
