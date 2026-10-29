"""
Dashboard 4: Control de Inventario y Monitor de Alertas de Stock Crítico
Centro de Plásticos El Limeño - Plataforma de Control Operativo
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
from db import (
    get_alertas_stock_critico,
    get_rotacion_inventario,
    get_metricas_inventario_resumen
)
from filters import render_global_filters

apply_global_styles()

render_banner(
    title="Control de Inventario y Monitor Preventivo de Stock Crítico",
    subtitle="Sistema de alerta temprana para prevenir quiebres de góndola (< 15 unidades) y optimización de rotación de capital inmovilizado en bodega.",
    icon="inventory_2",
    badge_text="Monitoreo Preventivo Continuo"
)

with st.spinner("Escaneando niveles de inventario en bodega..."):
    df_alertas = get_alertas_stock_critico()
    df_rotacion = get_rotacion_inventario()
    dict_resumen = get_metricas_inventario_resumen()

if df_alertas.empty and df_rotacion.empty:
    st.warning("No hay registros de inventario en el Data Warehouse.")
    st.stop()

# Filtros Globales
sucursales_disp = sorted(df_alertas["sucursal"].dropna().unique().tolist())
filtros = render_global_filters(
    sucursales_disp=sucursales_disp,
    show_dates=False,
    show_categories=False,
    show_branches=True
)

st.sidebar.markdown(
    """
    <div style="display:flex; align-items:center; gap:6px; margin: 10px 0 5px 0;">
        <span class="material-symbols-outlined" style="color:#EF4444; font-size:18px;">crisis_alert</span>
        <strong style="color:#F8FAFC; font-size:12px; text-transform:uppercase;">Nivel de Severidad</strong>
    </div>
    """,
    unsafe_allow_html=True
)
nivel_riesgo = st.sidebar.selectbox(
    "Filtrar por Rango de Existencias:",
    ["Todas las Alertas (< 15)", "Quiebre Inminente (0 a 4 unids)", "Alerta Moderada (5 a 14 unids)"]
)

df_al_fil = df_alertas.copy()
if filtros["sucursales"]:
    df_al_fil = df_al_fil[df_al_fil["sucursal"].isin(filtros["sucursales"])]

if nivel_riesgo == "Quiebre Inminente (0 a 4 unids)":
    df_al_fil = df_al_fil[df_al_fil["stock_actual"] <= 4]
elif nivel_riesgo == "Alerta Moderada (5 a 14 unids)":
    df_al_fil = df_al_fil[(df_al_fil["stock_actual"] >= 5) & (df_al_fil["stock_actual"] < 15)]

# Metricas Clave
total_alertas_fil = len(df_al_fil)
quiebres_cero = len(df_al_fil[df_al_fil["stock_actual"] == 0])
valor_en_riesgo = df_al_fil["valor_stock_costo"].sum() if "valor_stock_costo" in df_al_fil.columns else 0
rot_promedio = df_rotacion["ratio_rotacion_inventario"].mean() if not df_rotacion.empty else 0

st.markdown("#### Indicadores Operacionales de Almacén")

k1, k2, k3, k4 = st.columns(4)

with k1:
    render_kpi_card(
        title="Alertas Activas (< 15)",
        value=f"{total_alertas_fil:,}",
        subtitle="Registros que requieren reposición",
        delta="Nivel Crítico",
        delta_type="negative",
        icon_name="warning",
        tag_text="Alerta Inmediata",
        tag_class="tag-red",
        stripe_class="stripe-red"
    )

with k2:
    render_kpi_card(
        title="Quiebres Totales (Stock 0)",
        value=f"{quiebres_cero:,}",
        subtitle="Góndola vacía en tienda",
        delta="Pérdida Inmediata de Venta",
        delta_type="negative",
        icon_name="remove_shopping_cart",
        tag_text="Quiebre Activo",
        tag_class="tag-red",
        stripe_class="stripe-red"
    )

with k3:
    render_kpi_card(
        title="Valor Stock en Alerta (Costo)",
        value=f"${valor_en_riesgo:,.2f}",
        subtitle="Inversión remanente en alerta",
        delta="Capital en Riesgo",
        delta_type="neutral",
        icon_name="savings",
        tag_text="Valorización",
        tag_class="tag-amber",
        stripe_class="stripe-amber"
    )

with k4:
    render_kpi_card(
        title="Rotación Media de Bodega",
        value=f"{rot_promedio:.2f}x",
        subtitle="COGS / Inventario al Costo",
        delta="Velocidad de Movimiento",
        delta_type="positive" if rot_promedio > 1 else "neutral",
        icon_name="sync",
        tag_text="Rotación",
        tag_class="tag-cyan",
        stripe_class="stripe-cyan"
    )

st.markdown("---")

# Diagnóstico Operativo
render_diagnostic(
    title="Recomendación Operativa de Reabastecimiento",
    text=(
        f"Se identificaron **{total_alertas_fil:,} asignaciones de producto-tienda en estado crítico** ({quiebres_cero:,} en quiebre total con cero existencias). "
        f"**Acción Automatizada**: El sistema ha generado abajo una propuesta de orden de reposición con una meta base de seguridad de 35 unidades por producto "
        f"para garantizar la continuidad de venta de la próxima semana."
    ),
    icon="crisis_alert"
)

# ==============================================================================
# PESTAÑAS: ALERTAS CRITICAS Y ROTACION
# ==============================================================================
tab_inv1, tab_inv2 = st.tabs([
    "Alertas de Quiebre y Generador de Reposición",
    "Rotación de Existencias y Días de Cobertura (DSI)"
])

with tab_inv1:
    col_a1, col_a2 = st.columns([3, 2])
    
    with col_a1:
        df_al_suc = df_al_fil.groupby(["sucursal", "categoria"]).size().reset_index(name="conteo_alertas")
        fig_al_bar = px.bar(
            df_al_suc,
            x="sucursal",
            y="conteo_alertas",
            color="categoria",
            title="Distribución de Productos en Alerta Crítica por Punto de Venta",
            labels={"conteo_alertas": "Artículos con Stock < 15", "sucursal": "Punto de Venta"}
        )
        apply_chart_theme(fig_al_bar)
        st.plotly_chart(fig_al_bar, width="stretch")

    with col_a2:
        fig_hist = px.histogram(
            df_al_fil,
            x="stock_actual",
            nbins=15,
            color_discrete_sequence=[COLOR_DANGER],
            title="Concentración de Unidades en Alerta (0 a 14)",
            labels={"stock_actual": "Piezas Físicas Restantes"}
        )
        apply_chart_theme(fig_hist)
        st.plotly_chart(fig_hist, width="stretch")

    st.markdown("##### Propuesta de Orden de Reposición Inmediata para Bodega")
    st.caption("Cálculo operativo sugerido: Unidades sugeridas = Meta de seguridad (35 unidades) - Stock actual.")
    
    df_reposicion = df_al_fil.copy()
    df_reposicion["unidades_a_pedir"] = 35 - df_reposicion["stock_actual"]
    df_reposicion["costo_estimado_reposicion"] = df_reposicion["unidades_a_pedir"] * df_reposicion["costo_promedio"]
    
    df_rep_show = df_reposicion.copy()
    df_rep_show["stock_actual"] = df_rep_show["stock_actual"].apply(lambda x: f"{int(x):,}")
    df_rep_show["unidades_a_pedir"] = df_rep_show["unidades_a_pedir"].apply(lambda x: f"{int(x):,}")
    df_rep_show["costo_promedio"] = df_rep_show["costo_promedio"].apply(lambda x: f"${x:,.2f}")
    df_rep_show["precio_venta"] = df_rep_show["precio_venta"].apply(lambda x: f"${x:,.2f}")
    df_rep_show["costo_estimado_reposicion"] = df_rep_show["costo_estimado_reposicion"].apply(lambda x: f"${x:,.2f}")
    
    df_rep_show = df_rep_show.rename(columns={
        "sucursal": "Punto de Venta",
        "sku": "Código SKU",
        "producto": "Descripción del Producto",
        "categoria": "Categoría",
        "stock_actual": "Stock en Tienda",
        "unidades_a_pedir": "Sugerido a Reponer",
        "costo_promedio": "Costo Promedio ($)",
        "costo_estimado_reposicion": "Inversión Estimada ($)"
    })
    
    st.dataframe(
        df_rep_show[["Punto de Venta", "Código SKU", "Descripción del Producto", "Categoría", "Stock en Tienda", "Sugerido a Reponer", "Costo Promedio ($)", "Inversión Estimada ($)"]],
        width="stretch",
        hide_index=True
    )
    
    csv_rep = df_reposicion.to_csv(index=False).encode('utf-8')
    st.download_button(
        label="Descargar Orden de Reposición en CSV",
        data=csv_rep,
        file_name="orden_reposicion_stock_critico.csv",
        mime="text/csv",
        icon=":material/download:"
    )

with tab_inv2:
    st.markdown("##### Rotación de Existencias y Días de Venta en Inventario (DSI)")
    st.caption("DSI = 365 / Ratio de Rotación. Indica cuántos días de cobertura comercial soportan las existencias actuales sin reabastecimiento.")
    
    df_rot_show = df_rotacion.copy()
    df_rot_show["dsi_dias"] = df_rot_show["ratio_rotacion_inventario"].apply(
        lambda r: round(365.0 / r, 1) if r > 0 else 999.0
    )
    
    col_rot1, col_rot2 = st.columns(2)
    
    with col_rot1:
        st.markdown("###### Top 10 Artículos con Mayor Movilidad (Alta Rotación)")
        top_rot = df_rot_show.sort_values(by="ratio_rotacion_inventario", ascending=False).head(10)
        fig_r1 = px.bar(
            top_rot,
            x="ratio_rotacion_inventario",
            y="producto",
            orientation="h",
            color="ratio_rotacion_inventario",
            color_continuous_scale="Viridis",
            labels={"ratio_rotacion_inventario": "Rotación Anualizada (veces)", "producto": "Producto"}
        )
        fig_r1.update_layout(yaxis=dict(autorange="reversed"))
        apply_chart_theme(fig_r1)
        st.plotly_chart(fig_r1, width="stretch")

    with col_rot2:
        st.markdown("###### Top 10 Artículos de Rotación Lenta (Riesgo de Inmovilización)")
        low_rot = df_rot_show[df_rot_show["stock_actual"] > 0].sort_values(by="ratio_rotacion_inventario", ascending=True).head(10)
        fig_r2 = px.bar(
            low_rot,
            x="dsi_dias",
            y="producto",
            orientation="h",
            color="dsi_dias",
            color_continuous_scale="Reds",
            labels={"dsi_dias": "Días de Cobertura (DSI)", "producto": "Producto"}
        )
        fig_r2.update_layout(yaxis=dict(autorange="reversed"))
        apply_chart_theme(fig_r2)
        st.plotly_chart(fig_r2, width="stretch")
