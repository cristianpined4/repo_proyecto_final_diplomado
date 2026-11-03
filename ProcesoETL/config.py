
"""
Configuracion y gestion de conexiones para el proceso ETL.

ORIGEN:
    PostgreSQL 17 - inventory_app - Puerto 5432

DESTINO:
    PostgreSQL 16 - inventory_dw - Puerto 5433

Las conexiones se configuran mediante archivos .env.
No modifica contraseñas ni estructuras de bases de datos.
"""

import os
import logging
from pathlib import Path

from dotenv import dotenv_values
from sqlalchemy import create_engine, text
from sqlalchemy.engine import URL, make_url


# ============================================================
# 1. CONFIGURACION GENERAL
# ============================================================

logger = logging.getLogger("ETL_Config")

BASE_DIR = Path(__file__).resolve().parent
PROJECT_ROOT = BASE_DIR.parent

LOCAL_ENV_PATH = BASE_DIR / ".env"
API_ENV_PATH = PROJECT_ROOT / "InventaryAppApi" / ".env"


# ============================================================
# 2. LECTURA DE VARIABLES DE ENTORNO
# ============================================================

def read_env_file(path):
    """
    Lee un archivo .env sin modificarlo.
    """
    if path.exists():
        return dotenv_values(path, encoding="utf-8")

    return {}


def get_setting(name, env_values, default=None):
    """
    Obtiene una variable de entorno.

    Prioridad:
    1. Variable del sistema.
    2. Archivo .env.
    3. Valor predeterminado.
    """
    value = os.getenv(name)

    if value is not None and value != "":
        return value

    value = env_values.get(name)

    if value is not None and value != "":
        return value

    return default


# ============================================================
# 3. CONEXION A BASE OPERACIONAL (OLTP)
# ============================================================

def get_oltp_url():
    """
    Obtiene la URL de conexion a PostgreSQL operacional.

    Prioridad:
    1. Variable OLTP_DATABASE_URL del sistema.
    2. ProcesoETL/.env
    3. InventaryAppApi/.env

    Fuerza el controlador psycopg2.
    """

    local_env = read_env_file(LOCAL_ENV_PATH)

    url = get_setting(
        "OLTP_DATABASE_URL",
        local_env
    )

    if url:
        return make_url(url.strip()).set(
            drivername="postgresql+psycopg2"
        )

    # Configuracion alternativa desde Laravel
    if API_ENV_PATH.exists():

        api_env = read_env_file(API_ENV_PATH)

        db_url = api_env.get("DB_URL")

        if db_url:
            return make_url(db_url.strip()).set(
                drivername="postgresql+psycopg2"
            )

        host = api_env.get("DB_HOST") or "127.0.0.1"
        port = api_env.get("DB_PORT") or "5432"
        database = api_env.get("DB_DATABASE") or "inventory_app"
        username = api_env.get("DB_USERNAME") or "postgres"
        password = api_env.get("DB_PASSWORD") or ""

        return URL.create(
            drivername="postgresql+psycopg2",
            username=username,
            password=password,
            host=host,
            port=int(port),
            database=database
        )

    raise ValueError(
        "No se encontro la configuracion de PostgreSQL OLTP. "
        "Verifica ProcesoETL/.env o InventaryAppApi/.env."
    )


# ============================================================
# 4. CONEXION AL DATA WAREHOUSE (DW)
# ============================================================

def get_dw_url():
    """
    Obtiene la conexion al Data Warehouse PostgreSQL.

    Lee los parametros desde ProcesoETL/.env.

    Utiliza SQLAlchemy URL.create para manejar correctamente
    las contraseñas con caracteres especiales.
    """

    local_env = read_env_file(LOCAL_ENV_PATH)

    host = get_setting(
        "DW_HOST", local_env, "127.0.0.1"
    )

    port = get_setting(
        "DW_PORT", local_env, "5433"
    )

    database = get_setting(
        "DW_DATABASE", local_env, "inventory_dw"
    )

    username = get_setting(
        "DW_USER", local_env, "dw_admin"
    )

    password = get_setting(
        "DW_PASSWORD", local_env
    )

    if not password:
        raise ValueError(
            "Falta configurar DW_PASSWORD en ProcesoETL/.env."
        )

    return URL.create(
        drivername="postgresql+psycopg2",
        username=username,
        password=password,
        host=host,
        port=int(port),
        database=database
    )


# ============================================================
# 5. MOTORES DE CONEXION SQLALCHEMY
# ============================================================

def get_oltp_engine():
    """
    Crea el motor SQLAlchemy del sistema operacional.

    El proceso ETL utilizara esta conexion para extraer datos.
    """

    return create_engine(
        get_oltp_url(),
        pool_pre_ping=True,
        pool_recycle=1800
    )


def get_dw_engine():
    """
    Crea el motor SQLAlchemy del Data Warehouse.

    El proceso ETL utilizara esta conexion para cargar
    staging, dimensiones y tablas de hechos.
    """

    return create_engine(
        get_dw_url(),
        pool_pre_ping=True,
        pool_recycle=1800
    )


# ============================================================
# 6. VERIFICACION DE CONECTIVIDAD
# ============================================================

def test_connections():
    """
    Comprueba la conectividad a ambas bases de datos.

    Realiza consultas SELECT sin modificar datos.

    Devuelve:
        oltp: True / False
        dw: True / False
        error_oltp: descripcion del error
        error_dw: descripcion del error
    """

    results = {
        "oltp": False,
        "dw": False,
        "error_oltp": None,
        "error_dw": None
    }

    # --------------------------------------------------------
    # VERIFICAR BASE OPERACIONAL
    # --------------------------------------------------------

    engine_oltp = None

    try:
        engine_oltp = get_oltp_engine()

        with engine_oltp.connect() as conn:

            database = conn.execute(
                text("SELECT current_database()")
            ).scalar()

            results["oltp"] = True

            logger.info(
                "Conexion OLTP exitosa: %s",
                database
            )

    except Exception as e:

        results["error_oltp"] = str(e).splitlines()[0]

        logger.error(
            "Error de conexion OLTP: %s",
            type(e).__name__
        )

    finally:
        if engine_oltp is not None:
            engine_oltp.dispose()

    # --------------------------------------------------------
    # VERIFICAR DATA WAREHOUSE
    # --------------------------------------------------------

    engine_dw = None

    try:
        engine_dw = get_dw_engine()

        with engine_dw.connect() as conn:

            database = conn.execute(
                text("SELECT current_database()")
            ).scalar()

            results["dw"] = True

            logger.info(
                "Conexion DW exitosa: %s",
                database
            )

    except Exception as e:

        results["error_dw"] = str(e).splitlines()[0]

        logger.error(
            "Error de conexion DW: %s",
            type(e).__name__
        )

    finally:
        if engine_dw is not None:
            engine_dw.dispose()

    return results


# ============================================================
# 7. PRUEBA DIRECTA
# ============================================================

if __name__ == "__main__":

    logging.basicConfig(
        level=logging.INFO,
        format="%(levelname)s: %(message)s"
    )

    print("=" * 55)
    print("VERIFICACION DE CONEXIONES DEL PROCESO ETL")
    print("=" * 55)

    status = test_connections()

    print(
        "Base operacional (inventory_app):",
        "CONECTADA" if status["oltp"] else "ERROR"
    )

    print(
        "Data Warehouse (inventory_dw):",
        "CONECTADA" if status["dw"] else "ERROR"
    )

    if status["oltp"] and status["dw"]:

        print("=" * 55)
        print("RESULTADO: AMBAS CONEXIONES FUNCIONAN")
        print("=" * 55)

    else:

        print("=" * 55)
        print("RESULTADO: REVISAR CONFIGURACION")
        print("=" * 55)
