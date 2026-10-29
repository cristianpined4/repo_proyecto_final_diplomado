"""
Dashboard 6: Cuadratura Fiscal Tributaria (Balance de IVA 13%)
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
    COLOR_ACCENT,
    MESES_ES_CORTO
)
from db import get_balance_iva_fiscal
from filters import render_global_filters

apply_global_styles()

render_banner(
    title="Cuadratura Tributaria y Balance de IVA Fiscal (13%)",
    subtitle="Auditoría fiscal continua de IVA Débito (ventas) vs. IVA Crédito (compras) para declaraciones ante el Ministerio de Hacienda.",
    icon="account_balance",
    badge_text="Cumplimiento Tributario"
)

with st.spinner("Sincronizando libros fiscales de IVA..."):
    df_iva = get_balance_iva_fiscal()

if df_iva.empty:
    st.warning("No hay registros fiscales de IVA en el Data Warehouse.")
    st.stop()

# Filtros Globales de Ejercicio Fiscal
anios_disp = sorted(df_iva["anio"].unique(), reverse=True)
filtros = render_global_filters(
    anios_disp=anios_disp,
    show_dates=True,
    show_categories=False,
    show_branches=False,
    show_stock_filter=False,
    show_margin_filter=False
)

df_iva_plot = df_iva.copy()
if filtros["anios"]:
    df_iva_plot = df_iva_plot[df_iva_plot["anio"].isin(filtros["anios"])]
if filtros["meses_filtro"]:
    df_iva_plot = df_iva_plot[df_iva_plot["mes"].isin(filtros["meses_filtro"])]

# Ordenar cronológicamente y generar etiqueta mensual en español
df_iva_plot = df_iva_plot.sort_values(by=["anio", "mes"], ascending=True)
df_iva_plot["periodo_es"] = df_iva_plot.apply(
    lambda r: f"{MESES_ES_CORTO.get(int(r['mes']), '')} {int(r['anio'])}", axis=1
)

# Totales recalculados
total_debito = df_iva_plot["iva_debito_ventas"].sum()
total_credito = df_iva_plot["iva_credito_compras"].sum()
total_balance = df_iva_plot["balance_iva_a_pagar"].sum()
meses_evaluados = len(df_iva_plot)
is_favorable = total_balance < 0

st.markdown("#### Resumen Acumulado del Ejercicio Fiscal")

c1, c2, c3, c4 = st.columns(4)

with c1:
    render_kpi_card(
        title="IVA Débito Fiscal (Ventas)",
        value=f"${total_debito:,.2f}",
        subtitle="13% sobre ventas netas",
        delta="Recaudado de Clientes",
        delta_type="neutral",
        icon_name="receipt",
        tag_text="Débito Fiscal",
        tag_class="tag-cyan",
        stripe_class="stripe-cyan"
    )

with c2:
    render_kpi_card(
        title="IVA Crédito Fiscal (Compras)",
        value=f"${total_credito:,.2f}",
        subtitle="13% sobre compras netas",
        delta="Deducible de Compras",
        delta_type="positive",
        icon_name="payments",
        tag_text="Crédito Fiscal",
        tag_class="tag-green",
        stripe_class="stripe-green"
    )

with c3:
    render_kpi_card(
        title="Balance Fiscal Neto",
        value=f"${abs(total_balance):,.2f}",
        subtitle="Remanente acumulado a favor" if is_favorable else "Impuesto neto a pagar",
        delta="Saldo a Favor de la Empresa" if is_favorable else "Por Enterar a Hacienda",
        delta_type="positive" if is_favorable else "negative",
        icon_name="balance",
        tag_text="Remanente" if is_favorable else "Por Pagar",
        tag_class="tag-purple" if is_favorable else "tag-red",
        stripe_class="stripe-purple" if is_favorable else "stripe-red"
    )

with c4:
    render_kpi_card(
        title="Períodos Auditados",
        value=f"{meses_evaluados} Meses",
        subtitle=f"{df_iva_plot.iloc[0]['periodo_es']} a {df_iva_plot.iloc[-1]['periodo_es']}" if not df_iva_plot.empty else "Sin registros",
        delta="Trazabilidad 100%",
        delta_type="neutral",
        icon_name="history",
        tag_text="Histórico",
        tag_class="tag-amber",
        stripe_class="stripe-amber"
    )

st.markdown("---")

# Diagnóstico Fiscal
render_diagnostic(
    title="Diagnóstico Automatizado de Obligación Tributaria",
    text=(
        f"Durante el período histórico evaluado ({meses_evaluados} meses), las adquisiciones de inventario generaron un crédito fiscal de **${total_credito:,.2f}** "
        f"frente a un débito recaudado de **${total_debito:,.2f}**. "
        f"**Estado de Cumplimiento**: El balance neto acumulado es de **${abs(total_balance):,.2f} {'a favor de la empresa (Remanente transferible)' if is_favorable else 'a pagar'}**, "
        f"respaldando la conciliación mensual de los libros de IVA compras y ventas."
    ),
    icon="verified"
)

# ==============================================================================
# GRAFICOS ANALITICOS DE IVA
# ==============================================================================
st.markdown("#### Evolución Mensual: Débito vs. Crédito Fiscal")

col_g1, col_g2 = st.columns([3, 2])

with col_g1:
    fig_iva = go.Figure()
    fig_iva.add_trace(go.Bar(
        x=df_iva_plot["periodo_es"],
        y=df_iva_plot["iva_debito_ventas"],
        name="IVA Débito (Ventas)",
        marker_color=COLOR_PRIMARY
    ))
    fig_iva.add_trace(go.Bar(
        x=df_iva_plot["periodo_es"],
        y=df_iva_plot["iva_credito_compras"],
        name="IVA Crédito (Compras)",
        marker_color=COLOR_SUCCESS
    ))
    fig_iva.update_layout(
        title="Comparativo Mensual: IVA Débito vs. Crédito Fiscal ($)",
        barmode="group",
        xaxis_title="Mes / Período Fiscal",
        yaxis_title="Dólares ($USD)",
        xaxis=dict(type="category", tickangle=-45)
    )
    apply_chart_theme(fig_iva)
    st.plotly_chart(fig_iva, width="stretch")

with col_g2:
    colores_balance = [COLOR_SUCCESS if b < 0 else COLOR_DANGER for b in df_iva_plot["balance_iva_a_pagar"]]
    fig_neto = go.Figure()
    fig_neto.add_trace(go.Bar(
        x=df_iva_plot["periodo_es"],
        y=df_iva_plot["balance_iva_a_pagar"],
        marker_color=colores_balance,
        name="Balance Neto"
    ))
    fig_neto.update_layout(
        title="Balance Neto Mensual (Verde: Saldo a Favor | Rojo: Por Enterar)",
        xaxis_title="Mes / Período Fiscal",
        yaxis_title="Balance Neto ($USD)",
        xaxis=dict(type="category", tickangle=-45)
    )
    apply_chart_theme(fig_neto)
    st.plotly_chart(fig_neto, width="stretch")

st.markdown("---")

# ==============================================================================
# TABLA COMPLETA EN ESPAÑOL
# ==============================================================================
st.markdown("#### Libro de Cuadratura Fiscal Mensual")

df_iva_table = df_iva_plot.copy()
df_iva_table["base_ventas"] = df_iva_table["base_ventas"].apply(lambda x: f"${x:,.2f}")
df_iva_table["iva_debito_ventas"] = df_iva_table["iva_debito_ventas"].apply(lambda x: f"${x:,.2f}")
df_iva_table["base_compras"] = df_iva_table["base_compras"].apply(lambda x: f"${x:,.2f}")
df_iva_table["iva_credito_compras"] = df_iva_table["iva_credito_compras"].apply(lambda x: f"${x:,.2f}")
df_iva_table["balance_iva_a_pagar"] = df_iva_table["balance_iva_a_pagar"].apply(
    lambda x: f"-${abs(x):,.2f} (A Favor)" if x < 0 else f"+${x:,.2f} (Por Enterar)"
)

df_iva_table = df_iva_table.rename(columns={
    "periodo_es": "Período",
    "nombre_mes": "Mes",
    "base_ventas": "Base Ventas ($)",
    "iva_debito_ventas": "IVA Débito Ventas ($)",
    "base_compras": "Base Compras ($)",
    "iva_credito_compras": "IVA Crédito Compras ($)",
    "balance_iva_a_pagar": "Balance Fiscal Neto ($)"
})

st.dataframe(
    df_iva_table[["Período", "Mes", "Base Ventas ($)", "IVA Débito Ventas ($)", "Base Compras ($)", "IVA Crédito Compras ($)", "Balance Fiscal Neto ($)"]],
    width="stretch",
    hide_index=True
)

csv_iva = df_iva.to_csv(index=False).encode('utf-8')
st.download_button(
    label="Descargar Libro de IVA Fiscal en CSV",
    data=csv_iva,
    file_name="libro_balance_iva_fiscal.csv",
    mime="text/csv",
    icon=":material/download:"
)
