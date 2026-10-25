"""
Orquestador Principal del Proceso ETL - InventoryApp
Ejecuta de manera secuencial y atomica la extraccion, transformacion y carga
hacia el Data Warehouse analitico.
"""

import sys
import time
import logging
from pathlib import Path
from datetime import datetime

# Garantizar que el modulo ProcesoETL sea localizable al ejecutar directamente
BASE_DIR = Path(__file__).resolve().parent.parent
if str(BASE_DIR) not in sys.path:
    sys.path.insert(0, str(BASE_DIR))

from ProcesoETL.config import test_connections, get_dw_engine
from ProcesoETL.extract import extract_all_data
from ProcesoETL.transform import (
    transform_dim_sucursal,
    transform_dim_producto,
    transform_dim_cliente,
    transform_dim_proveedor,
    transform_dim_usuario,
    transform_fact_ventas,
    transform_fact_compras,
    transform_fact_inventario_snapshot,
)
from ProcesoETL.load import (
    load_staging_all,
    upsert_dimension,
    get_dimension_mappings,
    upsert_fact_table,
)

# Configuracion de Logging
logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s [%(levelname)s] %(name)s: %(message)s",
    datefmt="%Y-%m-%d %H:%M:%S",
)
logger = logging.getLogger("ETL_Pipeline")


def run_etl():
    """Ejecuta el ciclo de vida completo del proceso ETL."""
    start_time = time.time()
    logger.info("==================================================================")
    logger.info("INICIANDO PROCESO ETL: INVENTORYAPP -> DATA WAREHOUSE")
    logger.info("Fecha y Hora de Inicio: %s", datetime.now().strftime("%Y-%m-%d %H:%M:%S"))
    logger.info("==================================================================")

    # 1. Verificacion de conectividad
    logger.info("[FASE 0] Verificando conectividad a bases de datos...")
    conn_status = test_connections()
    if not conn_status["oltp"]:
        logger.error("Error al conectar con la base de datos operacional: %s", conn_status["error_oltp"])
        sys.exit(1)
    if not conn_status["dw"]:
        logger.error("Error al conectar con el Data Warehouse: %s", conn_status["error_dw"])
        sys.exit(1)
    logger.info("Conexiones verificadas exitosamente (OLTP y DW listos).")

    # 2. Extraccion (Extract)
    logger.info("[FASE 1] Extrayendo datos desde PostgreSQL operacional...")
    data = extract_all_data()

    # 3. Carga en Staging (Staging Load)
    logger.info("[FASE 2] Poblando zona de Staging (stg)...")
    stg_counts = load_staging_all(data)

    # 4. Transformacion y Carga de Dimensiones
    logger.info("[FASE 3] Transformando y cargando dimensiones analiticas...")
    engine_dw = get_dw_engine()
    dim_counts = {}

    df_dim_suc = transform_dim_sucursal(data["sucursals"])
    dim_counts["dim_sucursal"] = upsert_dimension(df_dim_suc, "dim_sucursal", "sucursal_bk", engine_dw)

    df_dim_prod = transform_dim_producto(
        data["productos"],
        data["categorias"],
        data["subcategorias"],
        data["marcas"],
        data["modelos"],
        data["unidads"],
    )
    dim_counts["dim_producto"] = upsert_dimension(df_dim_prod, "dim_producto", "producto_bk", engine_dw)

    df_dim_cli = transform_dim_cliente(data["clientes"])
    dim_counts["dim_cliente"] = upsert_dimension(df_dim_cli, "dim_cliente", "cliente_bk", engine_dw)

    df_dim_prov = transform_dim_proveedor(data["proveedors"])
    dim_counts["dim_proveedor"] = upsert_dimension(df_dim_prov, "dim_proveedor", "proveedor_bk", engine_dw)

    df_dim_usr = transform_dim_usuario(data["users"])
    dim_counts["dim_usuario"] = upsert_dimension(df_dim_usr, "dim_usuario", "usuario_bk", engine_dw)

    # 5. Mapeo de claves subrogadas para poblar hechos
    logger.info("[FASE 4] Obteniendo catalogo de claves subrogadas (_sk)...")
    dim_maps = get_dimension_mappings(engine_dw)

    # 6. Transformacion y Carga de Tablas de Hechos
    logger.info("[FASE 5] Transformando y cargando tablas de hechos...")
    fact_counts = {}

    # Hecho: Ventas
    df_fact_ventas = transform_fact_ventas(data["ventas"], data["detalles_ventas"], data["productos"], dim_maps)
    fact_counts["fact_ventas"] = upsert_fact_table(
        df_fact_ventas, "fact_ventas", ["detalle_venta_id_bk"], engine_dw
    )

    # Hecho: Compras
    df_fact_compras = transform_fact_compras(data["compras"], data["detalles_compras"], dim_maps)
    fact_counts["fact_compras"] = upsert_fact_table(
        df_fact_compras, "fact_compras", ["detalle_compra_id_bk"], engine_dw
    )

    # Hecho: Snapshot de Inventario
    df_fact_inv = transform_fact_inventario_snapshot(data["producto_sucursal"], data["productos"], dim_maps)
    fact_counts["fact_inventario_snapshot"] = upsert_fact_table(
        df_fact_inv, "fact_inventario_snapshot", ["fecha_sk", "sucursal_sk", "producto_sk"], engine_dw
    )

    # Resumen de Ejecucion
    duration = time.time() - start_time
    logger.info("==================================================================")
    logger.info("RESUMEN DE EJECUCION DEL ETL")
    logger.info("==================================================================")
    logger.info("Dimensiones Procesadas:")
    for k, v in dim_counts.items():
        logger.info("  * dw.%-22s: %d registros", k, v)
    logger.info("Tablas de Hechos Procesadas:")
    for k, v in fact_counts.items():
        logger.info("  * dw.%-22s: %d registros", k, v)
    logger.info("Tiempo Total de Ejecucion: %.2f segundos", duration)
    logger.info("ESTADO: ETL COMPLETADO EXITOSAMENTE")
    logger.info("==================================================================")


if __name__ == "__main__":
    run_etl()
