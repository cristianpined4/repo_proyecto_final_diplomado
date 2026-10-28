"""
Dashboard 2: Rendimiento Comercial y Rentabilidad por Sucursal
Centro de Plásticos El Limeño - Plataforma de Gestión y Analítica
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
    COLOR_SECONDARY
)
from db import get_ventas_por_sucursal
from filters import render_global_filters

apply_global_styles()

render_banner(
    title="Desempeño Comercial y Rentabilidad por Punto de Venta",
    subtitle="Análisis dimensional del volumen de transacciones, tickets promedio, masa de utilidad y márgenes de ganancia por tienda física.",
    icon="store",
    badge_text="Red Multisucursal"
)

with st.spinner("Cargando métricas de puntos de venta..."):
    df_suc = get_ventas_por_sucursal()

if df_suc.empty:
    st.warning("No hay información de sucursales en el Data Warehouse.")
    st.stop()

# Filtro de Sucursal
sucursales_disp = df_suc["sucursal"].dropna().unique().tolist()
filtros = render_global_filters(
    sucursales_disp=sucursales_disp,
    show_dates=False,
    show_categories=False,
    show_branches=True
)

df_view = df_suc.copy()
if filtros["sucursales"]:
    df_view = df_view[df_view["sucursal"].isin(filtros["sucursales"])]

# ==============================================================================
# TARJETAS DE SUCURSALES (HEAD TO HEAD)
# ==============================================================================
st.markdown("#### Comparativa de Puntos de Venta Físicos")

cols = st.columns(len(df_view))
stripes = ["stripe-cyan", "stripe-purple", "stripe-green"]
tags = ["tag-cyan", "tag-purple", "tag-green"]

for idx, (_, row) in enumerate(df_view.iterrows()):
    with cols[idx]:
        suc_clean = row["sucursal"].replace("Sucursal ", "")
        render_kpi_card(
            title=suc_clean,
            value=f"${row['total_ventas']:,.2f}",
            subtitle=f"Utilidad: ${row['utilidad_bruta']:,.2f} | Margen: {row['margen_porcentaje']:.2f}%",
            delta=f"Ticket: ${row['ticket_promedio']:,.2f}",
            delta_type="positive" if row['margen_porcentaje'] > 22 else "neutral",
            icon_name="storefront",
            tag_text=f"{int(row['transacciones']):,} Ventas",
            tag_class=tags[idx % len(tags)],
            stripe_class=stripes[idx % len(stripes)]
        )

st.markdown("---")

# ==============================================================================
# RESUMEN OPERACIONAL DE SUCURSALES
# ==============================================================================
render_diagnostic(
    title="Diagnóstico de Canales de Venta",
    text=(
        "**Sucursal Usulután** captura el 49.9% de los ingresos totales impulsada por pedidos de gran volumen y un ticket medio récord de **$532.61**. "
        "En contraste, **San Miguel Centro** presenta el ticket más bajo ($182.47) debido al flujo minorista frecuente, pero alcanza el **mayor margen neto de toda la empresa (26.80%)**, "
        "convirtiéndola en la tienda más eficiente en conversión de ganancia por dólar facturado."
    ),
    icon="analytics"
)

# ==============================================================================
# GRAFICOS ANALITICOS
# ==============================================================================
col_g1, col_g2 = st.columns([3, 2])

with col_g1:
    fig_bubble = px.scatter(
        df_view,
        x="ticket_promedio",
        y="margen_porcentaje",
        size="total_ventas",
        color="sucursal",
        hover_name="sucursal",
        text="sucursal",
        size_max=48,
        title="Matriz de Posicionamiento: Ticket Promedio ($) vs. Margen Bruto (%)",
        labels={
            "ticket_promedio": "Ticket Promedio por Venta ($USD)",
            "margen_porcentaje": "Margen Bruto de Ganancia (%)",
            "total_ventas": "Total Facturación ($)"
        }
    )
    fig_bubble.update_traces(textposition="top center")
    apply_chart_theme(fig_bubble)
    st.plotly_chart(fig_bubble, width="stretch")

with col_g2:
    fig_pie = px.pie(
        df_view,
        names="sucursal",
        values="total_ventas",
        title="Participación en la Facturación Total (Cuota de Red)",
        color_discrete_sequence=[COLOR_PRIMARY, COLOR_ACCENT, COLOR_SUCCESS],
        hole=0.48
    )
    fig_pie.update_traces(textposition="inside", textinfo="percent+label")
    apply_chart_theme(fig_pie)
    st.plotly_chart(fig_pie, width="stretch")

# ==============================================================================
# COMPARATIVA FINANCIERA DETALLADA
# ==============================================================================
st.markdown("#### Estructura de Costos y Masa de Utilidad por Tienda")

col_bar1, col_bar2 = st.columns(2)

with col_bar1:
    fig_stack = go.Figure()
    fig_stack.add_trace(go.Bar(
        name="Costo de Mercancía (COGS)",
        x=df_view["sucursal"],
        y=df_view["costo_total"],
        marker_color=COLOR_WARNING
    ))
    fig_stack.add_trace(go.Bar(
        name="Utilidad Neta (Ganancia)",
        x=df_view["sucursal"],
        y=df_view["utilidad_bruta"],
        marker_color=COLOR_SUCCESS
    ))
    fig_stack.update_layout(
        barmode="stack",
        title="Composición de la Base Imponible: Costo vs. Ganancia",
        xaxis_title="Punto de Venta",
        yaxis_title="Dólares ($USD)"
    )
    apply_chart_theme(fig_stack)
    st.plotly_chart(fig_stack, width="stretch")

with col_bar2:
    fig_vol = go.Figure()
    fig_vol.add_trace(go.Bar(
        name="Unidades Despachadas",
        x=df_view["sucursal"],
        y=df_view["unidades_vendidas"],
        marker_color=COLOR_PRIMARY
    ))
    fig_vol.update_layout(
        title="Volumen Físico Despachado por Punto de Venta",
        xaxis_title="Punto de Venta",
        yaxis_title="Cantidad de Artículos"
    )
    apply_chart_theme(fig_vol)
    st.plotly_chart(fig_vol, width="stretch")

# ==============================================================================
# TABLA EJECUTIVA EN ESPAÑOL Y DESCARGA
# ==============================================================================
st.markdown("#### Resumen Tabulado de Rendimiento Comercial")

df_formatted = df_view.copy()
df_formatted["subtotal_ventas"] = df_formatted["subtotal_ventas"].apply(lambda x: f"${x:,.2f}")
df_formatted["total_ventas"] = df_formatted["total_ventas"].apply(lambda x: f"${x:,.2f}")
df_formatted["costo_total"] = df_formatted["costo_total"].apply(lambda x: f"${x:,.2f}")
df_formatted["utilidad_bruta"] = df_formatted["utilidad_bruta"].apply(lambda x: f"${x:,.2f}")
df_formatted["margen_porcentaje"] = df_formatted["margen_porcentaje"].apply(lambda x: f"{x:.2f}%")
df_formatted["ticket_promedio"] = df_formatted["ticket_promedio"].apply(lambda x: f"${x:,.2f}")

df_formatted = df_formatted.rename(columns={
    "id_sucursal": "ID",
    "sucursal": "Punto de Venta",
    "transacciones": "Ventas Registradas",
    "unidades_vendidas": "Unidades Físicas",
    "subtotal_ventas": "Subtotal Neto ($)",
    "total_ventas": "Total Facturado ($)",
    "costo_total": "Costo Adquisición ($)",
    "utilidad_bruta": "Utilidad Neta ($)",
    "margen_porcentaje": "Margen Neto (%)",
    "ticket_promedio": "Ticket Promedio ($)"
})

st.dataframe(
    df_formatted[["Punto de Venta", "Ventas Registradas", "Unidades Físicas", "Total Facturado ($)", "Costo Adquisición ($)", "Utilidad Neta ($)", "Margen Neto (%)", "Ticket Promedio ($)"]],
    width="stretch",
    hide_index=True
)

csv_data = df_view.to_csv(index=False).encode('utf-8')
st.download_button(
    label="Descargar Informe de Sucursales en CSV",
    data=csv_data,
    file_name="informe_rendimiento_sucursales.csv",
    mime="text/csv",
    icon=":material/download:"
)
