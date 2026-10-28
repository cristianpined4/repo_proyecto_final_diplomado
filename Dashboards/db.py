"""
Capa de Conexion y Acceso a Datos para Dashboards de Streamlit
Data Warehouse PostgreSQL (inventory_dw)
Proyecto: InventoryApp / Plásticos Limeño (UES FMO 2026)
"""

import os
from pathlib import Path
import pandas as pd
from dotenv import dotenv_values
from sqlalchemy import create_engine, text
import streamlit as st

# Rutas de configuracion
BASE_DIR = Path(__file__).resolve().parent
PROJECT_ROOT = BASE_DIR.parent
ETL_ENV_PATH = PROJECT_ROOT / "ProcesoETL" / ".env"
API_ENV_PATH = PROJECT_ROOT / "InventaryAppApi" / ".env"


def get_dw_url() -> str:
    """
    Obtiene la URL de conexion a la base de datos analitica (Data Warehouse).
    Prioridad:
    1. Variables de entorno DW_* del sistema.
    2. ProcesoETL/.env si existe.
    3. Valores predeterminados de la infraestructura Docker (puerto 5433).
    """
    local_env = {}
    if ETL_ENV_PATH.exists():
        local_env = dotenv_values(ETL_ENV_PATH)

    host = os.getenv("DW_HOST", local_env.get("DW_HOST", "localhost"))
    port = os.getenv("DW_PORT", local_env.get("DW_PORT", "5433"))
    database = os.getenv("DW_DATABASE", local_env.get("DW_DATABASE", "inventory_dw"))
    user = os.getenv("DW_USER", local_env.get("DW_USER", "dw_admin"))
    password = os.getenv("DW_PASSWORD", local_env.get("DW_PASSWORD", "dw_secure_password_2026"))

    return f"postgresql+psycopg2://{user}:{password}@{host}:{port}/{database}"


@st.cache_resource
def get_dw_engine():
    """Retorna un motor SQLAlchemy reutilizable y con cache para Streamlit."""
    url = get_dw_url()
    return create_engine(url, pool_pre_ping=True, pool_size=5, max_overflow=10)


def sanitize_text(val):
    """Limpia caracteres con codificacion defectuosa comunes en cadenas Windows."""
    if not isinstance(val, str):
        return val
    
    # Mapeo de corrupciones comunes
    replacements = {
        "Usulutn": "Usulután",
        "Usulut\ufffdn": "Usulután",
        "Juguetera": "Juguetería",
        "Jugueter\ufffda": "Juguetería",
        "Lnea": "Línea",
        "L\ufffda": "Línea",
        "Agrcola": "Agrícola",
        "Agr\ufffdcola": "Agrícola",
        "Mobiliario Plstico": "Mobiliario Plástico",
        "Mobiliario Pl\ufffdstico": "Mobiliario Plástico",
        "Plstico": "Plástico",
        "Pl\ufffdstico": "Plástico",
        "Trmicos": "Térmicos",
        "T\ufffdrmicos": "Térmicos",
        "Berros": "Berríos",
        "Berr\ufffdos": "Berríos",
        "Vsquez": "Vásquez",
        "V\ufffdsquez": "Vásquez",
        "Dubn": "Dubón",
        "Dub\ufffdn": "Dubón",
        "Doa": "Doña",
        "Do\ufffda": "Doña",
        "Frutera": "Frutería",
        "Fruter\ufffda": "Frutería",
        "Verdulera": "Verdulería",
        "Verduler\ufffda": "Verdulería",
        "Meja": "Mejía",
        "Mej\ufffda": "Mejía",
        "Garca": "García",
        "Garc\ufffda": "García",
        "Martnez": "Martínez",
        "Mart\ufffdnez": "Martínez",
        "Hernndez": "Hernández",
        "Hern\ufffdndez": "Hernández",
        "Lpez": "López",
        "L\ufffdez": "López",
        "Rodrguez": "Rodríguez",
        "Rodr\ufffdguez": "Rodríguez",
        "Prez": "Pérez",
        "P\ufffdez": "Pérez",
        "Snchez": "Sánchez",
        "S\ufffdnchez": "Sánchez",
        "Daz": "Díaz",
        "D\ufffdaz": "Díaz",
        "Pea": "Peña",
        "Pe\ufffda": "Peña",
    }
    
    for corrupted, fixed in replacements.items():
        if corrupted in val:
            val = val.replace(corrupted, fixed)
            
    # Reemplazo generico de caracter de sustitucion si quedo alguno
    if "\ufffd" in val:
        val = val.replace("\ufffd", "")
        
    return val


def clean_dataframe(df: pd.DataFrame) -> pd.DataFrame:
    """Aplica saneamiento de texto a todas las columnas de texto del DataFrame."""
    if df.empty:
        return df
    
    df_clean = df.copy()
    for col in df_clean.select_dtypes(include=["object"]).columns:
        df_clean[col] = df_clean[col].apply(sanitize_text)
    return df_clean


@st.cache_data(ttl=300)
def check_connection() -> bool:
    """Verifica si el Data Warehouse esta accesible."""
    try:
        engine = get_dw_engine()
        with engine.connect() as conn:
            conn.execute(text("SELECT 1"))
        return True
    except Exception:
        return False


# ==============================================================================
# FUNCIONES DE CONSULTA A CAPA SEMANTICA (VISTAS ANALITICAS)
# ==============================================================================

@st.cache_data(ttl=300)
def get_kpis_generales() -> pd.DataFrame:
    """Consulta los KPIs ejecutivos consolidados (dw.vw_kpis_generales)."""
    engine = get_dw_engine()
    df = pd.read_sql("SELECT * FROM dw.vw_kpis_generales", engine)
    return clean_dataframe(df)


@st.cache_data(ttl=300)
def get_ventas_por_sucursal() -> pd.DataFrame:
    """Consulta el rendimiento comercial por sucursal (dw.vw_ventas_por_sucursal)."""
    engine = get_dw_engine()
    df = pd.read_sql("SELECT * FROM dw.vw_ventas_por_sucursal", engine)
    return clean_dataframe(df)


@st.cache_data(ttl=300)
def get_rentabilidad_productos() -> pd.DataFrame:
    """Consulta la rentabilidad por producto y categoria (dw.vw_rentabilidad_por_producto)."""
    engine = get_dw_engine()
    df = pd.read_sql("SELECT * FROM dw.vw_rentabilidad_por_producto", engine)
    return clean_dataframe(df)


@st.cache_data(ttl=300)
def get_rotacion_inventario() -> pd.DataFrame:
    """Consulta el ratio de rotacion de inventarios (dw.vw_rotacion_inventario)."""
    engine = get_dw_engine()
    df = pd.read_sql("SELECT * FROM dw.vw_rotacion_inventario", engine)
    return clean_dataframe(df)


@st.cache_data(ttl=300)
def get_balance_iva_fiscal() -> pd.DataFrame:
    """Consulta la cuadratura fiscal mensual de IVA (dw.vw_balance_iva_fiscal)."""
    engine = get_dw_engine()
    df = pd.read_sql("SELECT * FROM dw.vw_balance_iva_fiscal ORDER BY anio ASC, mes ASC", engine)
    return clean_dataframe(df)


@st.cache_data(ttl=300)
def get_compras_por_proveedor() -> pd.DataFrame:
    """Consulta el volumen y gasto por proveedor (dw.vw_compras_por_proveedor)."""
    engine = get_dw_engine()
    df = pd.read_sql("SELECT * FROM dw.vw_compras_por_proveedor", engine)
    return clean_dataframe(df)


@st.cache_data(ttl=300)
def get_alertas_stock_critico() -> pd.DataFrame:
    """Consulta productos con stock critico (< 15 unidades) (dw.vw_alertas_stock_critico)."""
    engine = get_dw_engine()
    df = pd.read_sql("SELECT * FROM dw.vw_alertas_stock_critico ORDER BY stock_actual ASC", engine)
    return clean_dataframe(df)


@st.cache_data(ttl=300)
def get_tendencia_ventas_mensual() -> pd.DataFrame:
    """Consulta la serie de tiempo mensual de ventas, costos y utilidades."""
    query = """
    SELECT 
        t.anio,
        t.mes,
        t.anio_mes,
        t.nombre_mes,
        COUNT(DISTINCT v.venta_id_bk) AS transacciones,
        SUM(v.cantidad) AS unidades_vendidas,
        ROUND(SUM(v.subtotal_linea), 2) AS subtotal_neto,
        ROUND(SUM(v.monto_iva_linea), 2) AS debito_iva,
        ROUND(SUM(v.total_linea), 2) AS total_ingresos,
        ROUND(SUM(v.costo_total), 2) AS cogs,
        ROUND(SUM(v.utilidad_bruta), 2) AS utilidad_bruta,
        ROUND(
            CASE 
                WHEN SUM(v.subtotal_linea) > 0 
                THEN (SUM(v.utilidad_bruta) / SUM(v.subtotal_linea)) * 100 
                ELSE 0 
            END, 2
        ) AS margen_porcentaje,
        ROUND(
            CASE 
                WHEN COUNT(DISTINCT v.venta_id_bk) > 0 
                THEN SUM(v.total_linea) / COUNT(DISTINCT v.venta_id_bk) 
                ELSE 0 
            END, 2
        ) AS ticket_promedio
    FROM dw.fact_ventas v
    JOIN dw.dim_tiempo t ON v.fecha_sk = t.fecha_sk
    GROUP BY t.anio, t.mes, t.anio_mes, t.nombre_mes
    ORDER BY t.anio ASC, t.mes ASC;
    """
    engine = get_dw_engine()
    df = pd.read_sql(query, engine)
    return clean_dataframe(df)


@st.cache_data(ttl=300)
def get_top_clientes(limit: int = 10) -> pd.DataFrame:
    """Consulta los clientes de mayor facturacion."""
    query = f"""
    SELECT 
        c.cliente_bk AS id_cliente,
        c.nombre_completo AS cliente,
        c.nit,
        c.telefono,
        c.direccion,
        COUNT(DISTINCT v.venta_id_bk) AS total_transacciones,
        ROUND(SUM(v.total_linea), 2) AS total_comprado,
        ROUND(SUM(v.total_linea) / NULLIF(COUNT(DISTINCT v.venta_id_bk), 0), 2) AS ticket_promedio_cliente
    FROM dw.fact_ventas v
    JOIN dw.dim_cliente c ON v.cliente_sk = c.cliente_sk
    WHERE c.cliente_sk > 0
    GROUP BY c.cliente_bk, c.nombre_completo, c.nit, c.telefono, c.direccion
    ORDER BY total_comprado DESC
    LIMIT {limit};
    """
    engine = get_dw_engine()
    df = pd.read_sql(query, engine)
    return clean_dataframe(df)


@st.cache_data(ttl=300)
def get_pareto_productos() -> pd.DataFrame:
    """Calcula la curva de Pareto (Clasificacion ABC 80/20) para productos."""
    query = """
    SELECT 
        p.sku,
        p.nombre AS producto,
        p.categoria,
        ROUND(SUM(v.subtotal_linea), 2) AS ingresos_netos,
        ROUND(SUM(v.utilidad_bruta), 2) AS utilidad_bruta,
        SUM(v.cantidad) AS unidades_vendidas
    FROM dw.fact_ventas v
    JOIN dw.dim_producto p ON v.producto_sk = p.producto_sk
    GROUP BY p.sku, p.nombre, p.categoria
    ORDER BY ingresos_netos DESC;
    """
    engine = get_dw_engine()
    df = pd.read_sql(query, engine)
    df = clean_dataframe(df)
    
    if not df.empty:
        total_ingresos = df["ingresos_netos"].sum()
        df["ingresos_acumulados"] = df["ingresos_netos"].cumsum()
        df["porcentaje_acumulado"] = (df["ingresos_acumulados"] / total_ingresos) * 100
        
        # Clasificacion ABC
        def asignar_abc(pct):
            if pct <= 80.0:
                return "A (Alto Impacto - 80%)"
            elif pct <= 95.0:
                return "B (Impacto Medio - 15%)"
            else:
                return "C (Bajo Impacto - 5%)"
                
        df["clasificacion_abc"] = df["porcentaje_acumulado"].apply(asignar_abc)
        
    return df


@st.cache_data(ttl=300)
def get_metricas_inventario_resumen() -> dict:
    """Obtiene el resumen consolidado de inventarios y valorizaciones."""
    query = """
    SELECT 
        COUNT(DISTINCT producto_sk) AS total_productos_stock,
        SUM(stock_actual) AS unidades_totales_stock,
        ROUND(SUM(valor_inventario_costo), 2) AS valor_total_costo,
        ROUND(SUM(valor_inventario_venta), 2) AS valor_total_venta,
        SUM(CASE WHEN stock_bajo_alerta = TRUE THEN 1 ELSE 0 END) AS productos_en_alerta,
        ROUND(
            (SUM(CASE WHEN stock_bajo_alerta = TRUE THEN 1.0 ELSE 0.0 END) / NULLIF(COUNT(*), 0)) * 100, 2
        ) AS porcentaje_en_alerta
    FROM dw.fact_inventario_snapshot
    WHERE fecha_sk = (SELECT MAX(fecha_sk) FROM dw.fact_inventario_snapshot);
    """
    engine = get_dw_engine()
    df = pd.read_sql(query, engine)
    if not df.empty:
        return df.iloc[0].to_dict()
    return {}


@st.cache_data(ttl=300)
def get_ventas_diarias() -> pd.DataFrame:
    """Consulta la serie temporal detallada por día, semana, mes, año y sucursal."""
    query = """
    SELECT 
        t.fecha,
        t.anio,
        t.mes,
        t.nombre_mes,
        t.semana_anio,
        t.dia,
        t.dia_semana,
        t.nombre_dia,
        t.es_fin_de_semana,
        s.nombre AS sucursal,
        COUNT(DISTINCT v.venta_id_bk) AS transacciones,
        SUM(v.cantidad) AS unidades_vendidas,
        ROUND(SUM(v.subtotal_linea), 2) AS subtotal_neto,
        ROUND(SUM(v.monto_iva_linea), 2) AS debito_iva,
        ROUND(SUM(v.total_linea), 2) AS total_ingresos,
        ROUND(SUM(v.costo_total), 2) AS cogs,
        ROUND(SUM(v.utilidad_bruta), 2) AS utilidad_bruta
    FROM dw.fact_ventas v
    JOIN dw.dim_tiempo t ON v.fecha_sk = t.fecha_sk
    JOIN dw.dim_sucursal s ON v.sucursal_sk = s.sucursal_sk
    GROUP BY 
        t.fecha, t.anio, t.mes, t.nombre_mes, t.semana_anio, 
        t.dia, t.dia_semana, t.nombre_dia, t.es_fin_de_semana, s.nombre
    ORDER BY t.fecha ASC;
    """
    engine = get_dw_engine()
    df = pd.read_sql(query, engine)
    df["fecha"] = pd.to_datetime(df["fecha"]).dt.date
    return clean_dataframe(df)

