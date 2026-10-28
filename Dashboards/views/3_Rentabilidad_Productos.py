"""
Dashboard 3: Rentabilidad por Producto, Categoría y Curva de Pareto (ABC)
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
    COLOR_ACCENT
)
from db import get_rentabilidad_productos, get_pareto_productos
from filters import render_global_filters

apply_global_styles()

render_banner(
    title="Rentabilidad de Catálogo y Clasificación ABC (Pareto 80/20)",
    subtitle="Análisis comercial de margen de contribución, concentración de facturación y matriz de rendimiento por línea de producto.",
    icon="category",
    badge_text="Control de Portafolio"
)

with st.spinner("Procesando catálogo y matriz de contribución..."):
    df_prod = get_rentabilidad_productos()
    df_pareto = get_pareto_productos()

if df_prod.empty:
    st.warning("No hay datos de catálogo en el Data Warehouse.")
    st.stop()

# Filtros Globales Interactivos
categorias_disp = sorted([c for c in df_prod["categoria"].dropna().unique() if c != ""])
filtros = render_global_filters(
    categorias_disp=categorias_disp,
    show_dates=False,
    show_categories=True,
    show_branches=False,
    show_stock_filter=False,
    show_margin_filter=True,
    show_abc_filter=True
)

# Busqueda adicional
st.sidebar.markdown(
    """
    <div style="display:flex; align-items:center; gap:6px; margin: 10px 0 5px 0;">
        <span class="material-symbols-outlined" style="color:#00D2FF; font-size:18px;">search</span>
        <strong style="color:#F8FAFC; font-size:12px; text-transform:uppercase;">Búsqueda Directa</strong>
    </div>
    """,
    unsafe_allow_html=True
)
sku_search = st.sidebar.text_input("Buscar por SKU o Nombre:", "", placeholder="Ej: PLAS-001 o Bote...").strip().lower()

df_filtered = df_prod.copy()
if filtros["categorias"]:
    df_filtered = df_filtered[df_filtered["categoria"].isin(filtros["categorias"])]
if sku_search:
    df_filtered = df_filtered[
        df_filtered["sku"].str.lower().str.contains(sku_search) | 
        df_filtered["producto"].str.lower().str.contains(sku_search)
    ]

# Filtro de rango de margen
if filtros["margen_rango"] == "Alto Rendimiento (> 25% Margen)":
    df_filtered = df_filtered[df_filtered["margen_porcentaje"] > 25]
elif filtros["margen_rango"] == "Rendimiento Estándar (20% a 25% Margen)":
    df_filtered = df_filtered[(df_filtered["margen_porcentaje"] >= 20) & (df_filtered["margen_porcentaje"] <= 25)]
elif filtros["margen_rango"] == "Margen Moderado (15% a 20% Margen)":
    df_filtered = df_filtered[(df_filtered["margen_porcentaje"] >= 15) & (df_filtered["margen_porcentaje"] < 20)]
elif filtros["margen_rango"] == "Margen Ajustado (< 15% Margen)":
    df_filtered = df_filtered[df_filtered["margen_porcentaje"] < 15]

# Metricas del segmento filtrado
total_skus = len(df_filtered)
total_ingresos_fil = df_filtered["ingresos_netos"].sum()
total_utilidad_fil = df_filtered["utilidad_bruta"].sum()
margen_pond_fil = (total_utilidad_fil / total_ingresos_fil * 100) if total_ingresos_fil > 0 else 0

st.markdown("#### Resumen del Segmento de Catálogo")

c1, c2, c3, c4 = st.columns(4)
with c1:
    render_kpi_card(
        title="Artículos Activos Filtrados",
        value=f"{total_skus:,}",
        subtitle="SKUs con movimiento de venta",
        delta="Cobertura de Catálogo",
        delta_type="neutral",
        icon_name="inventory",
        tag_text="Portafolio",
        tag_class="tag-cyan",
        stripe_class="stripe-cyan"
    )
with c2:
    render_kpi_card(
        title="Ingreso Neto Generado",
        value=f"${total_ingresos_fil:,.2f}",
        subtitle="Base imponible sin impuestos",
        delta="Ventas del Grupo",
        delta_type="positive",
        icon_name="payments",
        tag_text="Facturación",
        tag_class="tag-purple",
        stripe_class="stripe-purple"
    )
with c3:
    render_kpi_card(
        title="Masa de Ganancia Bruta",
        value=f"${total_utilidad_fil:,.2f}",
        subtitle="Margen directo consolidado",
        delta="Ganancia Efectiva",
        delta_type="positive",
        icon_name="savings",
        tag_text="Utilidad",
        tag_class="tag-green",
        stripe_class="stripe-green"
    )
with c4:
    render_kpi_card(
        title="Margen Ponderado del Grupo",
        value=f"{margen_pond_fil:.2f}%",
        subtitle="Rendimiento del segmento",
        delta="Rentabilidad Media",
        delta_type="positive" if margen_pond_fil >= 20 else "negative",
        icon_name="percent",
        tag_text="Margen",
        tag_class="tag-amber",
        stripe_class="stripe-amber"
    )

st.markdown("---")

# Resumen Operacional de Portafolio
render_diagnostic(
    title="Diagnóstico de Concentración de Ingresos",
    text=(
        f"El análisis de Pareto confirma que una selección reducida de productos clave concentra el **80% de los ingresos totales**, "
        f"mientras que los artículos de Zona C representan la cola larga del catálogo. "
        f"**Estrategia Recomendada**: Priorizar el inventario de seguridad y reabastecimiento continuo en artículos Zona A, evitando sobrecargar espacio físico con productos de bajo margen y rotación lenta."
    ),
    icon="analytics"
)

# ==============================================================================
# PESTAÑAS: PARETO, MATRIZ Y RANKING
# ==============================================================================
tab_p1, tab_p2, tab_p3 = st.tabs([
    "Curva de Pareto y Clasificación ABC (80/20)",
    "Matriz Margen vs. Ingresos Netos",
    "Ranking de Rentabilidad Máxima y Mínima"
])

with tab_p1:
    col_par1, col_par2 = st.columns([3, 2])
    
    with col_par1:
        df_p_sample = df_pareto.copy()
        if filtros["clasificacion_abc"]:
            selected_letters = [c[0] for c in filtros["clasificacion_abc"]]
            df_p_sample = df_p_sample[df_p_sample["clasificacion_abc"].str[0].isin(selected_letters)]
        df_p_sample = df_p_sample.head(250).copy()
        df_p_sample["indice_sku"] = range(1, len(df_p_sample) + 1)
        
        fig_pareto = go.Figure()
        fig_pareto.add_trace(go.Bar(
            x=df_p_sample["indice_sku"],
            y=df_p_sample["ingresos_netos"],
            name="Ingresos Netos ($)",
            marker_color="rgba(0, 210, 255, 0.6)",
            yaxis="y"
        ))
        fig_pareto.add_trace(go.Scatter(
            x=df_p_sample["indice_sku"],
            y=df_p_sample["porcentaje_acumulado"],
            name="% Ingresos Acumulado",
            mode="lines",
            line=dict(color=COLOR_SUCCESS, width=3),
            yaxis="y2"
        ))
        fig_pareto.add_hline(y=80, line_dash="dash", line_color=COLOR_DANGER, yref="y2", annotation_text="Límite 80% Pareto")
        
        fig_pareto.update_layout(
            title="Curva de Pareto de Productos (Ingresos vs. % Acumulado)",
            xaxis_title="Ranking de SKUs por Volumen de Venta",
            yaxis=dict(title="Ingresos Netos ($USD)"),
            yaxis2=dict(
                title="% Acumulado de Facturación",
                overlaying="y",
                side="right",
                range=[0, 105],
                tickvals=[0, 20, 40, 60, 80, 100]
            ),
            legend=dict(x=0.05, y=0.95)
        )
        apply_chart_theme(fig_pareto)
        st.plotly_chart(fig_pareto, width="stretch")

    with col_par2:
        st.markdown("##### Concentración de Ingresos por Segmento ABC")
        df_abc_counts = df_pareto.groupby("clasificacion_abc").agg({
            "sku": "count",
            "ingresos_netos": "sum"
        }).reset_index()
        
        fig_abc = px.pie(
            df_abc_counts,
            names="clasificacion_abc",
            values="ingresos_netos",
            title="Distribución de Ingresos por Zona ABC",
            color_discrete_sequence=[COLOR_SUCCESS, COLOR_WARNING, COLOR_DANGER],
            hole=0.45
        )
        apply_chart_theme(fig_abc)
        st.plotly_chart(fig_abc, width="stretch")

with tab_p2:
    st.markdown("##### Matriz de Rentabilidad: Margen (%) vs. Ingresos Netos ($)")
    st.caption("El tamaño de cada burbuja representa las unidades físicas vendidas para evitar la distorsión de artículos de alto margen pero venta nula.")
    
    fig_scatter = px.scatter(
        df_filtered.head(500),
        x="ingresos_netos",
        y="margen_porcentaje",
        size="unidades_vendidas",
        color="categoria",
        hover_name="producto",
        hover_data={"sku": True, "utilidad_bruta": ":$,.2f", "unidades_vendidas": True},
        title="Dispersión de Portafolio: Rentabilidad vs. Facturación",
        labels={
            "ingresos_netos": "Ingresos Netos ($USD)",
            "margen_porcentaje": "Margen Neto (%)",
            "categoria": "Categoría"
        }
    )
    apply_chart_theme(fig_scatter)
    st.plotly_chart(fig_scatter, width="stretch")

with tab_p3:
    col_top, col_bot = st.columns(2)
    
    with col_top:
        st.markdown("##### 10 Productos con Mayor Ganancia Efectiva ($)")
        top_10 = df_filtered.sort_values(by="utilidad_bruta", ascending=False).head(10).copy()
        fig_top = px.bar(
            top_10,
            x="utilidad_bruta",
            y="producto",
            orientation="h",
            text="utilidad_bruta",
            color="margen_porcentaje",
            color_continuous_scale="Greens",
            title="Líderes en Masa de Utilidad ($)",
            labels={"utilidad_bruta": "Utilidad Neta ($)", "producto": "Producto"}
        )
        fig_top.update_traces(texttemplate="$%{text:,.2f}", textposition="outside")
        fig_top.update_layout(yaxis=dict(autorange="reversed"))
        apply_chart_theme(fig_top)
        st.plotly_chart(fig_top, width="stretch")

    with col_bot:
        st.markdown("##### 10 Productos con Menor Aporte de Ganancia ($)")
        bot_10 = df_filtered.sort_values(by="utilidad_bruta", ascending=True).head(10).copy()
        fig_bot = px.bar(
            bot_10,
            x="utilidad_bruta",
            y="producto",
            orientation="h",
            text="utilidad_bruta",
            color="margen_porcentaje",
            color_continuous_scale="Reds",
            title="Artículos de Menor Rendimiento ($)",
            labels={"utilidad_bruta": "Utilidad Neta ($)", "producto": "Producto"}
        )
        fig_bot.update_traces(texttemplate="$%{text:,.2f}", textposition="outside")
        fig_bot.update_layout(yaxis=dict(autorange="reversed"))
        apply_chart_theme(fig_bot)
        st.plotly_chart(fig_bot, width="stretch")

st.markdown("---")

# ==============================================================================
# TABLA COMPLETA EN ESPAÑOL
# ==============================================================================
st.markdown("#### Detalle Tabulado de Rentabilidad por Artículo")

df_table = df_filtered.copy()
df_table["ingresos_netos"] = df_table["ingresos_netos"].apply(lambda x: f"${x:,.2f}")
df_table["costo_total"] = df_table["costo_total"].apply(lambda x: f"${x:,.2f}")
df_table["utilidad_bruta"] = df_table["utilidad_bruta"].apply(lambda x: f"${x:,.2f}")
df_table["margen_porcentaje"] = df_table["margen_porcentaje"].apply(lambda x: f"{x:.2f}%")

df_table = df_table.rename(columns={
    "sku": "Código SKU",
    "producto": "Descripción del Producto",
    "categoria": "Categoría",
    "unidades_vendidas": "Unidades Vendidas",
    "ingresos_netos": "Ingreso Neto ($)",
    "costo_total": "Costo Mercancía ($)",
    "utilidad_bruta": "Utilidad Neta ($)",
    "margen_porcentaje": "Margen (%)"
})

st.dataframe(
    df_table[["Código SKU", "Descripción del Producto", "Categoría", "Unidades Vendidas", "Ingreso Neto ($)", "Costo Mercancía ($)", "Utilidad Neta ($)", "Margen (%)"]],
    width="stretch",
    hide_index=True
)

csv_prod = df_filtered.to_csv(index=False).encode('utf-8')
st.download_button(
    label="Descargar Catálogo de Rentabilidad Filtrado (CSV)",
    data=csv_prod,
    file_name="catalogo_rentabilidad_productos.csv",
    mime="text/csv",
    icon=":material/download:"
)
