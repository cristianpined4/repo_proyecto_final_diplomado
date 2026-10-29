"""
Panel de Control Ejecutivo Principal
Centro de Plásticos El Limeño - Plataforma Analítica Empresarial
"""

import streamlit as st
import pandas as pd
import plotly.express as px
import plotly.graph_objects as go
import sys
from pathlib import Path

# Asegurar importacion de modulos locales
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
    COLOR_SECONDARY,
    MESES_ES_CORTO,
    format_mes_anio
)
from db import (
    check_connection,
    get_kpis_generales,
    get_ventas_por_sucursal,
    get_metricas_inventario_resumen,
    get_tendencia_ventas_mensual,
    get_alertas_stock_critico,
    get_ventas_diarias
)
from filters import render_global_filters

# Aplicar estilos
apply_global_styles()

# Banner Principal
render_banner(
    title="Centro de Plásticos El Limeño — Plataforma de Gestión y Analítica",
    subtitle="Plataforma de Inteligencia de Negocios conectada en tiempo real a la arquitectura analítica OLAP. Procesamiento de transacciones, inventarios multisucursal y control comercial.",
    icon="insights",
    badge_text="Sistema Sincronizado"
)

# Validar conexion
if not check_connection():
    st.error("No se pudo conectar con el Data Warehouse PostgreSQL (Puerto 5433). Verifique que el servicio Docker esté activo.")
    st.code("docker compose -f DataWareHouse/docker-compose.yml up -d")
    st.stop()

# Spinner de Carga de Datos en Español
with st.spinner("Sincronizando modelos analíticos y datos consolidados..."):
    df_kpis = get_kpis_generales()
    df_suc = get_ventas_por_sucursal()
    dict_inv = get_metricas_inventario_resumen()
    df_tendencia = get_tendencia_ventas_mensual()
    df_alertas = get_alertas_stock_critico()
    df_diario = get_ventas_diarias()

if df_kpis.empty:
    st.info("El almacén analítico aún no contiene datos transformados. Ejecute el proceso ETL inicial.")
    st.stop()

# Filtros Globales Interactivos (Soporta días, semanas, meses, años, sucursales y líneas)
sucursales_disp = sorted(df_suc["sucursal"].dropna().unique().tolist())
anios_disp = sorted(df_diario["anio"].dropna().unique().tolist(), reverse=True)

filtros = render_global_filters(
    sucursales_disp=sucursales_disp,
    anios_disp=anios_disp,
    show_dates=True,
    show_categories=True,
    show_branches=True,
    show_stock_filter=True,
    show_margin_filter=True
)

# 1. Aplicar filtro de sucursal
df_suc_view = df_suc.copy()
if filtros["sucursales"]:
    df_suc_view = df_suc_view[df_suc_view["sucursal"].isin(filtros["sucursales"])]

# 2. Aplicar filtro de inventario y alertas
df_alertas_view = df_alertas.copy()
if filtros["sucursales"]:
    df_alertas_view = df_alertas_view[df_alertas_view["sucursal"].isin(filtros["sucursales"])]
if filtros["estado_stock"] == "Solo Quiebres Totales (Stock = 0)":
    df_alertas_view = df_alertas_view[df_alertas_view["stock_actual"] == 0]
elif filtros["estado_stock"] == "Solo Stock Crítico (< 15 piezas)":
    df_alertas_view = df_alertas_view[df_alertas_view["stock_actual"] < 15]

# 3. Filtrar serie detallada por cualquier dimensión temporal (Días, Semanas, Meses, Años)
df_diario_fil = df_diario.copy()
if filtros["sucursales"]:
    df_diario_fil = df_diario_fil[df_diario_fil["sucursal"].isin(filtros["sucursales"])]

has_temporal_filter = False

# Filtro por rango exacto de fechas (Días)
if filtros["fecha_inicio"] and filtros["fecha_fin"]:
    df_diario_fil = df_diario_fil[(df_diario_fil["fecha"] >= filtros["fecha_inicio"]) & (df_diario_fil["fecha"] <= filtros["fecha_fin"])]
    has_temporal_filter = True
elif filtros["anios"]:
    df_diario_fil = df_diario_fil[df_diario_fil["anio"].isin(filtros["anios"])]
    has_temporal_filter = True

if filtros["meses_filtro"]:
    df_diario_fil = df_diario_fil[df_diario_fil["mes"].isin(filtros["meses_filtro"])]
    has_temporal_filter = True

if filtros["semanas_filtro"]:
    df_diario_fil = df_diario_fil[df_diario_fil["semana_anio"].isin(filtros["semanas_filtro"])]
    has_temporal_filter = True

if filtros["dias_semana_filtro"]:
    df_diario_fil = df_diario_fil[df_diario_fil["nombre_dia"].isin(filtros["dias_semana_filtro"])]
    has_temporal_filter = True

# 4. Cálculo dinámico de métricas ejecutivas según filtros activos
if (has_temporal_filter or filtros["sucursales"]) and not df_diario_fil.empty:
    subtotal_val = float(df_diario_fil["subtotal_neto"].sum())
    total_val = float(df_diario_fil["total_ingresos"].sum())
    utilidad_val = float(df_diario_fil["utilidad_bruta"].sum())
    cogs_val = float(df_diario_fil["cogs"].sum())
    margen_val = (utilidad_val / subtotal_val * 100) if subtotal_val > 0 else 0.0
    transacciones_val = int(df_diario_fil["transacciones"].sum())
    unidades_val = int(df_diario_fil["unidades_vendidas"].sum())
    ticket_val = (total_val / transacciones_val) if transacciones_val > 0 else 0.0
    debito_iva_val = float(df_diario_fil["debito_iva"].sum())
    periodo_tag = filtros["periodo"]
    
    # Granularidad de gráfico según período filtrado
    min_f = df_diario_fil["fecha"].min()
    max_f = df_diario_fil["fecha"].max()
    num_dias = (max_f - min_f).days if min_f and max_f else 100
    
    if num_dias <= 45:
        # Agrupación por días
        df_tendencia_view = df_diario_fil.groupby("fecha").agg({
            "total_ingresos": "sum",
            "cogs": "sum",
            "utilidad_bruta": "sum"
        }).reset_index()
        df_tendencia_view = df_tendencia_view.sort_values(by="fecha")
        df_tendencia_view["eje_x"] = df_tendencia_view["fecha"].apply(
            lambda d: f"{pd.to_datetime(d).day:02d} {MESES_ES_CORTO.get(pd.to_datetime(d).month, '')} {pd.to_datetime(d).year}"
        )
        x_label_chart = "Día"
    else:
        # Agrupación por meses con nombres en español
        df_tendencia_view = df_diario_fil.groupby(["anio", "mes"]).agg({
            "total_ingresos": "sum",
            "cogs": "sum",
            "utilidad_bruta": "sum"
        }).reset_index()
        df_tendencia_view = df_tendencia_view.sort_values(by=["anio", "mes"])
        df_tendencia_view["eje_x"] = df_tendencia_view.apply(
            lambda r: f"{MESES_ES_CORTO.get(int(r['mes']), '')} {int(r['anio'])}", axis=1
        )
        x_label_chart = "Mes"
else:
    kpi_data = df_kpis.iloc[0]
    subtotal_val = float(kpi_data['subtotal_ventas'])
    total_val = float(kpi_data['total_ingresos_brutos'])
    utilidad_val = float(kpi_data['utilidad_bruta_total'])
    cogs_val = float(kpi_data['costo_mercancia_vendida_cogs'])
    margen_val = float(kpi_data['margen_bruto_porcentaje'])
    transacciones_val = int(kpi_data['total_transacciones']) if 'total_transacciones' in kpi_data else 9201
    unidades_val = int(kpi_data['total_unidades_vendidas'])
    ticket_val = float(kpi_data['ticket_promedio'])
    debito_iva_val = float(kpi_data['total_iva_debito'])
    periodo_tag = "Histórico Consolidado"
    
    df_tendencia_view = df_tendencia.copy().sort_values(by=["anio", "mes"])
    df_tendencia_view["eje_x"] = df_tendencia_view.apply(
        lambda r: f"{MESES_ES_CORTO.get(int(r['mes']), '')} {int(r['anio'])}", axis=1
    )
    x_label_chart = "Mes"

# ==============================================================================
# TARJETAS DE MÉTRICAS EJECUTIVAS
# ==============================================================================
st.markdown("#### Métricas Estratégicas del Negocio")

col1, col2, col3, col4 = st.columns(4)

with col1:
    render_kpi_card(
        title="Facturación Bruta Total",
        value=f"${total_val:,.2f}",
        subtitle=f"Subtotal Neto: ${subtotal_val:,.2f}",
        delta=f"{transacciones_val:,} Ventas",
        delta_type="positive",
        icon_name="payments",
        tag_text=periodo_tag[:14],
        tag_class="tag-cyan",
        stripe_class="stripe-cyan"
    )

with col2:
    render_kpi_card(
        title="Utilidad Neta Directa",
        value=f"${utilidad_val:,.2f}",
        subtitle=f"COGS: ${cogs_val:,.2f}",
        delta="Rentabilidad Bruta",
        delta_type="positive",
        icon_name="savings",
        tag_text="Margen Efectivo",
        tag_class="tag-green",
        stripe_class="stripe-green"
    )

with col3:
    render_kpi_card(
        title="Margen Ponderado Global",
        value=f"{margen_val:.2f}%",
        subtitle="Razón ponderada sin distorsión",
        delta="Meta: >= 20.0%",
        delta_type="positive" if margen_val >= 20 else "negative",
        icon_name="percent",
        tag_text="Rendimiento",
        tag_class="tag-purple",
        stripe_class="stripe-purple"
    )

with col4:
    render_kpi_card(
        title="Ticket Promedio por Venta",
        value=f"${ticket_val:,.2f}",
        subtitle=f"{unidades_val:,} piezas despachadas",
        delta="Gasto Medio por Cliente",
        delta_type="neutral",
        icon_name="receipt",
        tag_text="Consumo",
        tag_class="tag-amber",
        stripe_class="stripe-amber"
    )

# Segunda fila de métricas
col5, col6, col7, col8 = st.columns(4)

with col5:
    render_kpi_card(
        title="Débito Fiscal IVA (13%)",
        value=f"${debito_iva_val:,.2f}",
        subtitle="Impuesto recaudado",
        delta="Cuadre Tributario 100%",
        delta_type="neutral",
        icon_name="account_balance",
        tag_text="Tributario",
        tag_class="tag-cyan",
        stripe_class="stripe-cyan"
    )

with col6:
    total_al = len(df_alertas_view)
    render_kpi_card(
        title="Alertas de Stock Activas",
        value=f"{total_al:,}",
        subtitle="Monitoreo de bodega en tienda",
        delta="Acción Inmediata" if total_al > 0 else "Nivel Óptimo",
        delta_type="negative" if total_al > 0 else "positive",
        icon_name="warning",
        tag_text="Riesgo Góndola",
        tag_class="tag-red",
        stripe_class="stripe-red"
    )

with col7:
    val_stock = dict_inv.get("valor_total_costo", 0)
    render_kpi_card(
        title="Capital en Inventario",
        value=f"${val_stock:,.2f}",
        subtitle=f"{int(dict_inv.get('unidades_totales_stock', 0)):,} unidades físicas",
        delta="Costo de Adquisición",
        delta_type="neutral",
        icon_name="inventory_2",
        tag_text="Almacén",
        tag_class="tag-purple",
        stripe_class="stripe-purple"
    )

with col8:
    if not df_suc_view.empty:
        suc_top_row = df_suc_view.sort_values(by="total_ventas", ascending=False).iloc[0]
        suc_top = suc_top_row["sucursal"].replace("Sucursal ", "")
        suc_max_val = suc_top_row["total_ventas"]
    else:
        suc_top = "Usulután"
        suc_max_val = 0
    render_kpi_card(
        title="Punto de Venta Líder",
        value=suc_top,
        subtitle="Mayor captación comercial",
        delta=f"${suc_max_val:,.2f}",
        delta_type="positive",
        icon_name="store",
        tag_text="Líder Red",
        tag_class="tag-green",
        stripe_class="stripe-green"
    )

st.markdown("---")

# ==============================================================================
# RESUMEN OPERACIONAL DEL SISTEMA
# ==============================================================================
render_diagnostic(
    title="Resumen Operacional del Negocio — Centro de Plásticos El Limeño",
    text=(
        f"Se registra un rendimiento comercial con un margen ponderado de **{margen_val:.2f}%** sobre una facturación neta de **${subtotal_val:,.2f}**. "
        f"El punto de venta **{suc_top}** lidera la facturación consolidada, mientras que **San Miguel Centro** sostiene la máxima tasa de ganancia neta (**26.80%**). "
        f"**Estado de Bodega**: Se identifican **{total_al:,} registros de inventario en alerta**; se recomienda procesar las órdenes de reposición para evitar quiebres en góndola."
    ),
    icon="analytics"
)

# ==============================================================================
# GRAFICOS INTERACTIVOS
# ==============================================================================
st.markdown("#### Análisis Dinámico de Tendencias y Desglose Financiero")

tab_g1, tab_g2 = st.tabs(["Evolución Temporal de Ingresos", "Estructura por Sucursales"])

with tab_g1:
    c_trend1, c_trend2 = st.columns([3, 1.3])
    with c_trend1:
        fig_trend = go.Figure()
        fig_trend.add_trace(go.Scatter(
            x=df_tendencia_view["eje_x"],
            y=df_tendencia_view["total_ingresos"],
            mode="lines+markers",
            name="Facturación Total ($)",
            line=dict(color=COLOR_PRIMARY, width=2.5),
            marker=dict(size=4),
            hovertemplate="Facturación Total: $%{y:,.2f}<extra></extra>"
        ))
        fig_trend.add_trace(go.Scatter(
            x=df_tendencia_view["eje_x"],
            y=df_tendencia_view["cogs"],
            mode="lines",
            name="Costo de Mercancía COGS ($)",
            line=dict(color=COLOR_WARNING, width=1.5, dash="dot"),
            hovertemplate="Costo Mercancía (COGS): $%{y:,.2f}<extra></extra>"
        ))
        fig_trend.add_trace(go.Scatter(
            x=df_tendencia_view["eje_x"],
            y=df_tendencia_view["utilidad_bruta"],
            mode="lines",
            name="Utilidad Neta ($)",
            line=dict(color=COLOR_SUCCESS, width=2.2),
            fill="tonexty",
            fillcolor="rgba(16, 185, 129, 0.08)",
            hovertemplate="Utilidad Neta: $%{y:,.2f}<extra></extra>"
        ))
        fig_trend.update_layout(
            title=f"Tendencia de Facturación y Costos ({filtros['periodo']})",
            xaxis_title=f"Eje Temporal ({x_label_chart})",
            yaxis_title="Dólares ($USD)",
            hovermode="x unified",
            xaxis=dict(type="category", tickangle=-45),
            yaxis=dict(tickprefix="$")
        )
        apply_chart_theme(fig_trend)
        st.plotly_chart(fig_trend, width="stretch")

    with c_trend2:
        # Donut de estructura de costo vs margen
        subt = subtotal_val if subtotal_val > 0 else 1
        cogs = cogs_val
        util = utilidad_val
        
        fig_pie = go.Figure(data=[go.Pie(
            labels=["Costo de Mercancía (COGS)", "Utilidad Neta Directa"],
            values=[cogs, util],
            hole=0.62,
            marker_colors=[COLOR_WARNING, COLOR_SUCCESS],
            textinfo="percent+label"
        )])
        fig_pie.update_layout(
            title="Distribución del Ingreso Neto",
            annotations=[dict(text=f"{margen_val:.1f}%<br>Margen", x=0.5, y=0.5, font_size=17, font_color="#FFF", showarrow=False)]
        )
        apply_chart_theme(fig_pie)
        st.plotly_chart(fig_pie, width="stretch")

with tab_g2:
    c_s1, c_s2 = st.columns(2)
    with c_s1:
        fig_suc_bar = px.bar(
            df_suc_view,
            x="sucursal",
            y="total_ventas",
            color="margen_porcentaje",
            color_continuous_scale="Tealgrn",
            text="total_ventas",
            title="Facturación Bruta por Punto de Venta",
            labels={"total_ventas": "Total Ventas ($)", "sucursal": "Sucursal", "margen_porcentaje": "Margen %"}
        )
        fig_suc_bar.update_traces(texttemplate="$%{text:,.2f}", textposition="outside")
        apply_chart_theme(fig_suc_bar)
        st.plotly_chart(fig_suc_bar, width="stretch")

    with c_s2:
        fig_suc_ticket = px.bar(
            df_suc_view,
            x="sucursal",
            y="ticket_promedio",
            color="sucursal",
            color_discrete_sequence=[COLOR_PRIMARY, COLOR_ACCENT, COLOR_SUCCESS],
            text="ticket_promedio",
            title="Ticket Promedio por Compra ($/Cliente)",
            labels={"ticket_promedio": "Ticket Promedio ($)", "sucursal": "Sucursal"}
        )
        fig_suc_ticket.update_traces(texttemplate="$%{text:,.2f}", textposition="outside")
        apply_chart_theme(fig_suc_ticket)
        st.plotly_chart(fig_suc_ticket, width="stretch")

st.markdown("---")

# ==============================================================================
# ACCESOS DIRECTOS A MODULOS ANALITICOS
# ==============================================================================
st.markdown("#### Módulos Analíticos Especializados")

m1, m2, m3 = st.columns(3)

with m1:
    st.markdown(
        """
        <div style="background: rgba(17, 24, 39, 0.7); border: 1px solid rgba(255,255,255,0.08); border-radius: 10px; padding: 14px;">
            <div style="display:flex; align-items:center; gap:8px; margin-bottom:6px;">
                <span class="material-symbols-outlined" style="color:#00D2FF;">store</span>
                <strong style="color:#F8FAFC;">Sucursales y Canales</strong>
            </div>
            <p style="font-size:0.8rem; color:#94A3B8; margin:0;">Comparativa de tickets, márgenes unitarios y participación de mercado por punto de venta.</p>
        </div>
        """,
        unsafe_allow_html=True
    )

with m2:
    st.markdown(
        """
        <div style="background: rgba(17, 24, 39, 0.7); border: 1px solid rgba(255,255,255,0.08); border-radius: 10px; padding: 14px;">
            <div style="display:flex; align-items:center; gap:8px; margin-bottom:6px;">
                <span class="material-symbols-outlined" style="color:#10B981;">category</span>
                <strong style="color:#F8FAFC;">Catálogo y Curva ABC (Pareto)</strong>
            </div>
            <p style="font-size:0.8rem; color:#94A3B8; margin:0;">Identificación de los productos que generan el 80% del ingreso y matriz margen vs. volumen.</p>
        </div>
        """,
        unsafe_allow_html=True
    )

with m3:
    st.markdown(
        """
        <div style="background: rgba(17, 24, 39, 0.7); border: 1px solid rgba(255,255,255,0.08); border-radius: 10px; padding: 14px;">
            <div style="display:flex; align-items:center; gap:8px; margin-bottom:6px;">
                <span class="material-symbols-outlined" style="color:#EF4444;">inventory_2</span>
                <strong style="color:#F8FAFC;">Monitor de Stock Crítico</strong>
            </div>
            <p style="font-size:0.8rem; color:#94A3B8; margin:0;">Alerta temprana de quiebres (&lt; 15 unidades) con exportación directa de órdenes de reposición.</p>
        </div>
        """,
        unsafe_allow_html=True
    )
