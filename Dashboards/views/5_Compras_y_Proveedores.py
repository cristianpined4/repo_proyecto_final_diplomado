"""
Dashboard 5: Gestión de Compras y Concentración de Proveedores
Centro de Plásticos El Limeño - Plataforma Analítica Empresarial
"""

import streamlit as st
import pandas as pd
import plotly.express as px
import plotly.graph_objects as go
import sys
from pathlib import Path

DASH_ROOT = str(Path(__file__).resolve().parent.parent)
if DASH_ROOT not in sys.path:
    sys.path.insert(0, DASH_ROOT)

import importlib
import styles
importlib.reload(styles)

from styles import (
    apply_global_styles,
    apply_chart_theme,
    render_banner,
    render_kpi_card,
    render_diagnostic,
    COLOR_PRIMARY,
    COLOR_SUCCESS,
    COLOR_WARNING,
    COLOR_DANGER,
    COLOR_ACCENT
)
from db import get_compras_por_proveedor

apply_global_styles()

render_banner(
    title="Abastecimiento y Concentración de la Red de Proveedores",
    subtitle="Monitoreo del gasto de compras, generación de crédito fiscal IVA (13%) y análisis de concentración en fabricantes de materias plásticas.",
    icon="local_shipping",
    badge_text="Cadena de Suministro"
)

with st.spinner("Cargando historial de compras y proveedores..."):
    df_prov = get_compras_por_proveedor()

if df_prov.empty:
    st.warning("No hay registros de compras en el Data Warehouse.")
    st.stop()

# Metricas Globales
total_compras = df_prov["total_compras"].sum()
total_subtotal_compras = df_prov["subtotal_compras"].sum()
total_iva_credito = df_prov["iva_credito"].sum()
total_facturas = df_prov["facturas_emitidas"].sum()
total_unidades = df_prov["unidades_adquiridas"].sum()
total_proveedores = len(df_prov)

st.markdown("#### Métricas de Adquisiciones y Crédito Fiscal")

c1, c2, c3, c4 = st.columns(4)

with c1:
    render_kpi_card(
        title="Gasto Total en Compras",
        value=f"${total_compras:,.2f}",
        subtitle=f"Subtotal: ${total_subtotal_compras:,.2f}",
        delta="Inversión en Mercadería",
        delta_type="neutral",
        icon_name="shopping_bag",
        tag_text="Presupuesto",
        tag_class="tag-purple",
        stripe_class="stripe-purple"
    )

with c2:
    render_kpi_card(
        title="Crédito Fiscal IVA (13%)",
        value=f"${total_iva_credito:,.2f}",
        subtitle="Crédito a favor contra ventas",
        delta="Deducción Tributaria",
        delta_type="positive",
        icon_name="account_balance_wallet",
        tag_text="Fiscal",
        tag_class="tag-green",
        stripe_class="stripe-green"
    )

with c3:
    render_kpi_card(
        title="Facturas de Compra Pagadas",
        value=f"{int(total_facturas):,}",
        subtitle=f"{int(total_unidades):,} piezas adquiridas",
        delta="Volumen Documentario",
        delta_type="neutral",
        icon_name="receipt",
        tag_text="Operativo",
        tag_class="tag-cyan",
        stripe_class="stripe-cyan"
    )

with c4:
    render_kpi_card(
        title="Fabricantes y Proveedores",
        value=f"{total_proveedores:,}",
        subtitle="Red de distribución activa",
        delta="Cartera Homologada",
        delta_type="positive",
        icon_name="factory",
        tag_text="Red",
        tag_class="tag-amber",
        stripe_class="stripe-amber"
    )

st.markdown("---")

# Diagnóstico Operativo
top_10_monto = df_prov.sort_values(by="total_compras", ascending=False).head(10)["total_compras"].sum()
pct_top10 = (top_10_monto / total_compras) * 100

render_diagnostic(
    title="Diagnóstico Estratégico de Concentración de Proveedores",
    text=(
        f"La cartera de compras presenta una concentración del **{pct_top10:.1f}% del presupuesto en los 10 principales fabricantes** (${top_10_monto:,.2f}). "
        f"**Oportunidad de Negociación**: Centralizar las órdenes de compra consolidadas para exigir descuentos por volumen del 3% al 5% "
        f"y extensión de plazos comerciales a 45 días sin afectar la tasa de entrega a tienda."
    ),
    icon="insights"
)

# ==============================================================================
# GRAFICOS DE CONCENTRACION
# ==============================================================================
st.markdown("#### Distribución y Principales Proveedores")

col_p1, col_p2 = st.columns([3, 2])

with col_p1:
    top_15_prov = df_prov.sort_values(by="total_compras", ascending=False).head(15).copy()
    fig_top_p = px.bar(
        top_15_prov,
        x="total_compras",
        y="proveedor",
        orientation="h",
        text="total_compras",
        color="total_compras",
        color_continuous_scale="Purples",
        title="Top 15 Proveedores por Facturación Acumulada ($)",
        labels={"total_compras": "Total Compras ($)", "proveedor": "Proveedor"}
    )
    fig_top_p.update_traces(texttemplate="$%{text:,.2f}", textposition="outside")
    fig_top_p.update_layout(yaxis=dict(autorange="reversed"), xaxis=dict(tickprefix="$"))
    apply_chart_theme(fig_top_p)
    st.plotly_chart(fig_top_p, width="stretch")

with col_p2:
    resto_monto = total_compras - top_10_monto
    fig_donut_p = px.pie(
        names=["Top 10 Proveedores", "Resto de la Cartera"],
        values=[top_10_monto, resto_monto],
        title="Concentración: Principales 10 vs. Resto de Cartera",
        color_discrete_sequence=[COLOR_ACCENT, "#334155"],
        hole=0.5
    )
    fig_donut_p.update_traces(textposition="inside", textinfo="percent+label")
    apply_chart_theme(fig_donut_p)
    st.plotly_chart(fig_donut_p, width="stretch")

st.markdown("---")

# ==============================================================================
# TABLA COMPLETA EN ESPAÑOL
# ==============================================================================
st.markdown("#### Directorio Completo de Compras por Proveedor")

# Buscador en la barra lateral
st.sidebar.markdown(
    """
    <div style="display:flex; align-items:center; gap:6px; margin: 10px 0 5px 0;">
        <span class="material-symbols-outlined" style="color:#00D2FF; font-size:18px;">search</span>
        <strong style="color:#F8FAFC; font-size:12px; text-transform:uppercase;">Filtrar Proveedores</strong>
    </div>
    """,
    unsafe_allow_html=True
)
busqueda_prov = st.sidebar.text_input("Filtrar por Nombre o NIT:", "", placeholder="Ej: Plásticos o NIT...").strip().lower()

df_prov_show = df_prov.copy()
if busqueda_prov:
    df_prov_show = df_prov_show[
        df_prov_show["proveedor"].str.lower().str.contains(busqueda_prov) |
        df_prov_show["nit"].str.lower().str.contains(busqueda_prov)
    ]

df_prov_fmt = df_prov_show.copy()
df_prov_fmt["facturas_emitidas"] = df_prov_fmt["facturas_emitidas"].apply(lambda x: f"{int(x):,}")
df_prov_fmt["unidades_adquiridas"] = df_prov_fmt["unidades_adquiridas"].apply(lambda x: f"{int(x):,}")
df_prov_fmt["subtotal_compras"] = df_prov_fmt["subtotal_compras"].apply(lambda x: f"${x:,.2f}")
df_prov_fmt["iva_credito"] = df_prov_fmt["iva_credito"].apply(lambda x: f"${x:,.2f}")
df_prov_fmt["total_compras"] = df_prov_fmt["total_compras"].apply(lambda x: f"${x:,.2f}")

df_prov_fmt = df_prov_fmt.rename(columns={
    "id_proveedor": "Código",
    "proveedor": "Nombre / Razón Social",
    "nit": "NIT",
    "nrc": "NRC",
    "facturas_emitidas": "Facturas",
    "unidades_adquiridas": "Piezas Compradas",
    "subtotal_compras": "Subtotal ($)",
    "iva_credito": "IVA Crédito Fiscal ($)",
    "total_compras": "Total Pagado ($)"
})

st.dataframe(
    df_prov_fmt[["Código", "Nombre / Razón Social", "NIT", "Facturas", "Piezas Compradas", "Subtotal ($)", "IVA Crédito Fiscal ($)", "Total Pagado ($)"]],
    width="stretch",
    hide_index=True
)

csv_prov = df_prov_show.to_csv(index=False).encode('utf-8')
st.download_button(
    label="Descargar Reporte de Compras en CSV",
    data=csv_prov,
    file_name="reporte_compras_proveedores.csv",
    mime="text/csv",
    icon=":material/download:"
)
