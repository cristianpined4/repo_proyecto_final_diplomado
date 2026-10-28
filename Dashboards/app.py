"""
Sistema de Inteligencia de Negocios & Analítica Empresarial
Centro de Plásticos El Limeño
Orquestador Principal de Navegación (Streamlit Navigation Engine)
"""

import streamlit as st
from pathlib import Path
import sys

# Asegurar que el directorio de Dashboards este en el PATH de ejecucion
BASE_DIR = Path(__file__).resolve().parent
if str(BASE_DIR) not in sys.path:
    sys.path.insert(0, str(BASE_DIR))

import importlib
import styles
importlib.reload(styles)

from styles import apply_global_styles
from db import check_connection

# Configuracion de la pagina principal
st.set_page_config(
    page_title="Centro de Plásticos El Limeño | Sistema de BI",
    page_icon=":material/insights:",
    layout="wide",
    initial_sidebar_state="expanded"
)

# Inyectar estilos globales y Google Material Symbols
apply_global_styles()

# Rutas de las paginas analiticas (en 'views' para evitar parpadeo de autodescubrimiento)
PAGES_DIR = BASE_DIR / "views"

# Definicion formal de paginas con nombres en español e iconos Material
p_inicio = st.Page(
    str(PAGES_DIR / "0_Inicio.py"),
    title="Panel Principal",
    icon=":material/dashboard:",
    default=True
)

p_estrategia = st.Page(
    str(PAGES_DIR / "1_Resumen_Ejecutivo_CMI.py"),
    title="Rendimiento y Estrategia",
    icon=":material/monitoring:"
)

p_sucursales = st.Page(
    str(PAGES_DIR / "2_Rendimiento_Sucursales.py"),
    title="Sucursales y Canales",
    icon=":material/store:"
)

p_productos = st.Page(
    str(PAGES_DIR / "3_Rentabilidad_Productos.py"),
    title="Rentabilidad y Catálogo ABC",
    icon=":material/category:"
)

p_inventario = st.Page(
    str(PAGES_DIR / "4_Inventario_y_Alertas.py"),
    title="Control de Stock y Alertas",
    icon=":material/inventory_2:"
)

p_compras = st.Page(
    str(PAGES_DIR / "5_Compras_y_Proveedores.py"),
    title="Abastecimiento y Proveedores",
    icon=":material/local_shipping:"
)

p_fiscal = st.Page(
    str(PAGES_DIR / "6_Balance_IVA_Fiscal.py"),
    title="Cuadratura y Balance Fiscal",
    icon=":material/account_balance:"
)

p_catalogo = st.Page(
    str(PAGES_DIR / "7_Catalogo_Indicadores.py"),
    title="Diccionario de Métricas",
    icon=":material/menu_book:"
)

# Secciones del Menu de Navegacion
navegacion = st.navigation(
    {
        "Visión Estratégica": [p_inicio, p_estrategia],
        "Operaciones Comerciales": [p_sucursales, p_productos, p_inventario],
        "Cadena de Suministro y Finanzas": [p_compras, p_fiscal, p_catalogo]
    },
    position="sidebar",
    expanded=True
)

# Barra Lateral: Branding Corporativo y Estado del Sistema
with st.sidebar:
    st.markdown(
        """
        <div style="display:flex; align-items:center; gap:10px; margin-bottom:12px; padding-bottom:10px; border-bottom:1px solid rgba(255,255,255,0.08);">
            <div style="background: linear-gradient(135deg, #00D2FF, #7C3AED); width:36px; height:36px; border-radius:8px; display:flex; align-items:center; justify-content:center; color:#FFF;">
                <span class="material-symbols-outlined" style="font-size:22px;">precision_manufacturing</span>
            </div>
            <div>
                <div style="font-weight:800; color:#F8FAFC; font-size:14px; letter-spacing:-0.02em; line-height:1.2;">Plásticos El Limeño</div>
                <div style="font-size:10px; color:#00D2FF; text-transform:uppercase; letter-spacing:0.06em; font-weight:600;">Sistema de Gestión & BI</div>
            </div>
        </div>
        """,
        unsafe_allow_html=True
    )
    
    # Estado de Conexion al Data Warehouse en Vivo
    if check_connection():
        st.markdown(
            """
            <div style="margin-bottom: 15px;">
                <span class="status-pill status-active">
                    <span class="pulse-dot"></span>
                    Data Warehouse Conectado (5433)
                </span>
            </div>
            """,
            unsafe_allow_html=True
        )
    else:
        st.markdown(
            """
            <div style="margin-bottom: 15px;">
                <span class="status-pill status-critical">
                    DW Desconectado
                </span>
            </div>
            """,
            unsafe_allow_html=True
        )

# Ejecutar la pagina seleccionada
navegacion.run()
