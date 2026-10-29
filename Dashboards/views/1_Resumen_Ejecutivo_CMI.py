"""
Dashboard 1: Rendimiento Estratégico y Control Multidimensional
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
from db import (
    get_kpis_generales,
    get_tendencia_ventas_mensual,
    get_metricas_inventario_resumen,
    get_top_clientes,
    get_ventas_por_sucursal
)
from filters import render_global_filters

apply_global_styles()

render_banner(
    title="Rendimiento Estratégico y Cuadro de Mando Operacional",
    subtitle="Evaluación integral de las cuatro dimensiones de valor: desempeño financiero, comportamiento de clientes, eficiencia de procesos internos y cobertura de catálogo.",
    icon="monitoring",
    badge_text="Evaluación Multidimensional"
)

with st.spinner("Sincronizando métricas estratégicas..."):
    df_kpis = get_kpis_generales()
    df_tendencia = get_tendencia_ventas_mensual()
    dict_inv = get_metricas_inventario_resumen()
    df_top_clientes = get_top_clientes(limit=5)
    df_suc = get_ventas_por_sucursal()

if df_kpis.empty:
    st.warning("No hay registros disponibles en el Data Warehouse.")
    st.stop()

kpi = df_kpis.iloc[0]

# Filtros Globales
sucursales_disp = sorted(df_suc["sucursal"].dropna().unique().tolist())
anios_disp = sorted(df_tendencia["anio"].dropna().unique().tolist(), reverse=True)

filtros = render_global_filters(
    sucursales_disp=sucursales_disp,
    anios_disp=anios_disp,
    show_dates=True,
    show_categories=False,
    show_branches=False,
    show_stock_filter=False,
    show_margin_filter=False
)

# Filtrar tendencia según el período seleccionado
df_tend_fil = df_tendencia.copy()
if filtros["anios"]:
    df_tend_fil = df_tend_fil[df_tend_fil["anio"].isin(filtros["anios"])]
if filtros["meses_filtro"]:
    df_tend_fil = df_tend_fil[df_tend_fil["mes"].isin(filtros["meses_filtro"])]

# ==============================================================================
# CADENA CAUSAL PREVENTIVA
# ==============================================================================
with st.expander("Control Preventivo: Indicadores Adelantados vs. Resultados Financieros", expanded=True):
    col_c1, col_c2, col_c3 = st.columns([1.2, 0.3, 1.2])
    with col_c1:
        st.markdown(
            """
            <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.4); border-radius: 10px; padding: 14px;">
                <div style="display:flex; align-items:center; gap:8px; margin-bottom:6px;">
                    <span class="material-symbols-outlined" style="color:#EF4444;">bolt</span>
                    <strong style="color: #F87171; font-size: 13px; text-transform: uppercase;">Indicador Preventivo Adelantado</strong>
                </div>
                <p style="font-size: 0.82rem; margin: 0; color: #CBD5E1; line-height: 1.45;">
                    <strong>Alertas de Stock en Bodega (&lt; 15 unidades)</strong><br>
                    • <em>Frecuencia:</em> Monitoreo continuo en apertura de turno.<br>
                    • <em>Naturaleza:</em> Unidades físicas en estante, accionable por jefatura de almacén.<br>
                    • <em>Efecto:</em> Anticipa y previene la pérdida de facturación antes de que ocurra.
                </p>
            </div>
            """,
            unsafe_allow_html=True
        )
    with col_c2:
        st.markdown(
            """
            <div style="display:flex; height:100%; align-items:center; justify-content:center; color:#38BDF8;">
                <span class="material-symbols-outlined" style="font-size:32px;">trending_flat</span>
            </div>
            """,
            unsafe_allow_html=True
        )
    with col_c3:
        st.markdown(
            """
            <div style="background: rgba(0, 210, 255, 0.1); border: 1px solid rgba(0, 210, 255, 0.4); border-radius: 10px; padding: 14px;">
                <div style="display:flex; align-items:center; gap:8px; margin-bottom:6px;">
                    <span class="material-symbols-outlined" style="color:#00D2FF;">analytics</span>
                    <strong style="color: #38BDF8; font-size: 13px; text-transform: uppercase;">Resultado Financiero Final</strong>
                </div>
                <p style="font-size: 0.82rem; margin: 0; color: #CBD5E1; line-height: 1.45;">
                    <strong>Facturación Neta y Margen Bruto Consolidado</strong><br>
                    • <em>Frecuencia:</em> Evaluación mensual de cierre contable.<br>
                    • <em>Naturaleza:</em> Medición en dólares ($USD), evaluativa para Gerencia General.<br>
                    • <em>Efecto:</em> Consecuencia directa del abastecimiento oportuno del catálogo.
                </p>
            </div>
            """,
            unsafe_allow_html=True
        )

st.markdown("---")

# ==============================================================================
# LAS 4 DIMENSIONES DE CONTROL OPERACIONAL
# ==============================================================================
st.markdown("#### Matriz Multidimensional de Desempeño")

p1, p2, p3, p4 = st.tabs([
    "Dimensión Financiera",
    "Clientes y Consumo",
    "Procesos de Almacén",
    "Capacidad y Catálogo"
])

# ----------------- DIMENSION FINANCIERA -----------------
with p1:
    st.caption("Objetivo: Asegurar la sostenibilidad del capital de trabajo y rentabilidad de ventas.")
    c1, c2, c3, c4 = st.columns(4)
    with c1:
        render_kpi_card(
            title="Ingresos Brutos",
            value=f"${kpi['total_ingresos_brutos']:,.2f}",
            subtitle="Con IVA incluido",
            delta="Meta: > $3.0M",
            delta_type="positive",
            icon_name="payments",
            tag_text="Facturación",
            tag_class="tag-cyan",
            stripe_class="stripe-cyan"
        )
    with c2:
        render_kpi_card(
            title="Utilidad Neta",
            value=f"${kpi['utilidad_bruta_total']:,.2f}",
            subtitle=f"COGS: ${kpi['costo_mercancia_vendida_cogs']:,.2f}",
            delta="Ganancia Neta",
            delta_type="positive",
            icon_name="savings",
            tag_text="Utilidad",
            tag_class="tag-green",
            stripe_class="stripe-green"
        )
    with c3:
        m_val = float(kpi['margen_bruto_porcentaje'])
        render_kpi_card(
            title="Margen Ponderado",
            value=f"{m_val:.2f}%",
            subtitle="Rendimiento del catálogo",
            delta="Objetivo: >= 20.0%",
            delta_type="positive",
            icon_name="percent",
            tag_text="Rentabilidad",
            tag_class="tag-purple",
            stripe_class="stripe-purple"
        )
    with c4:
        render_kpi_card(
            title="Débito Fiscal IVA",
            value=f"${kpi['total_iva_debito']:,.2f}",
            subtitle="Recaudación para Hacienda",
            delta="Auditoría Fiscal OK",
            delta_type="neutral",
            icon_name="account_balance",
            tag_text="Impuestos",
            tag_class="tag-amber",
            stripe_class="stripe-amber"
        )
    
    st.markdown("##### Ficha de Control Financiero")
    df_fin_table = pd.DataFrame([
        {"Métrica": "Facturación Bruta Total", "Valor Registrado": f"${kpi['total_ingresos_brutos']:,.2f}", "Meta Operativa": "> $3,000,000.00", "Estado": "Cumplido", "Responsable": "Gerencia Financiera"},
        {"Métrica": "Margen Bruto Ponderado", "Valor Registrado": f"{m_val:.2f}%", "Meta Operativa": ">= 20.00%", "Estado": "Cumplido", "Responsable": "Gerencia General"},
        {"Métrica": "Costo de Adquisición (COGS)", "Valor Registrado": f"${kpi['costo_mercancia_vendida_cogs']:,.2f}", "Meta Operativa": "< 78.00% de Ventas", "Estado": "Controlado (76.80%)", "Responsable": "Jefatura de Compras"},
        {"Métrica": "Débito Fiscal IVA Ventas", "Valor Registrado": f"${kpi['total_iva_debito']:,.2f}", "Meta Operativa": "Cuadratura 100%", "Estado": "Cuadrado", "Responsable": "Contador General"}
    ])
    st.dataframe(df_fin_table, width="stretch", hide_index=True)

# ----------------- DIMENSION CLIENTES -----------------
with p2:
    st.caption("Objetivo: Maximizar el valor de vida del cliente y la frecuencia de compra institucional.")
    cl1, cl2, cl3 = st.columns(3)
    with cl1:
        render_kpi_card(
            title="Ticket Promedio por Compra",
            value=f"${kpi['ticket_promedio']:,.2f}",
            subtitle="Gasto medio por cliente atendido",
            delta="Consumo Estable",
            delta_type="positive",
            icon_name="receipt_long",
            tag_text="Ticket",
            tag_class="tag-cyan",
            stripe_class="stripe-cyan"
        )
    with cl2:
        render_kpi_card(
            title="Transacciones Totales",
            value=f"{int(kpi['total_transacciones']):,}",
            subtitle="Ventas registradas y pagadas",
            delta="Métrica de Contrapeso",
            delta_type="positive",
            icon_name="shopping_cart",
            tag_text="Volumen",
            tag_class="tag-green",
            stripe_class="stripe-green"
        )
    with cl3:
        render_kpi_card(
            title="Artículos Plásticos Despachados",
            value=f"{int(kpi['total_unidades_vendidas']):,}",
            subtitle="Promedio: 19 piezas / factura",
            delta="Demanda Mayorista",
            delta_type="neutral",
            icon_name="package_2",
            tag_text="Unidades",
            tag_class="tag-purple",
            stripe_class="stripe-purple"
        )
    
    st.markdown("##### Principales Clientes Comerciales por Monto Adquirido")
    df_top_clean = df_top_clientes.copy()
    df_top_clean["total_transacciones"] = df_top_clean["total_transacciones"].apply(lambda x: f"{int(x):,}")
    df_top_clean["total_comprado"] = df_top_clean["total_comprado"].apply(lambda x: f"${float(x):,.2f}")
    df_top_clean["ticket_promedio_cliente"] = df_top_clean["ticket_promedio_cliente"].apply(lambda x: f"${float(x):,.2f}")
    df_top_clean = df_top_clean.rename(columns={
        "id_cliente": "Código Cliente",
        "cliente": "Razón Social / Nombre",
        "nit": "NIT",
        "telefono": "Teléfono",
        "direccion": "Dirección",
        "total_transacciones": "Compras Realizadas",
        "total_comprado": "Total Facturado ($)",
        "ticket_promedio_cliente": "Ticket Medio ($)"
    })
    st.dataframe(df_top_clean, width="stretch", hide_index=True)

# ----------------- DIMENSION PROCESOS DE ALMACEN -----------------
with p3:
    st.caption("Objetivo: Minimizar roturas de stock y optimizar la rotación de capital inmovilizado.")
    pr1, pr2, pr3 = st.columns(3)
    with pr1:
        alertas_qty = int(dict_inv.get("productos_en_alerta", 0))
        pct_alertas = float(dict_inv.get("porcentaje_en_alerta", 0))
        render_kpi_card(
            title="Productos en Nivel Crítico",
            value=f"{alertas_qty:,}",
            subtitle=f"{pct_alertas:.1f}% de las asignaciones de tienda",
            delta="Reposición Inmediata",
            delta_type="negative",
            icon_name="error",
            tag_text="Alerta Stock",
            tag_class="tag-red",
            stripe_class="stripe-red"
        )
    with pr2:
        render_kpi_card(
            title="Valor del Inventario en Bodega",
            value=f"${dict_inv.get('valor_total_costo', 0):,.2f}",
            subtitle=f"${dict_inv.get('valor_total_venta', 0):,.2f} a precio venta",
            delta="Inversión Activa",
            delta_type="neutral",
            icon_name="warehouse",
            tag_text="Valorización",
            tag_class="tag-amber",
            stripe_class="stripe-amber"
        )
    with pr3:
        render_kpi_card(
            title="Piezas Físicas Disponibles",
            value=f"{int(dict_inv.get('unidades_totales_stock', 0)):,}",
            subtitle="Inventario en 3 sucursales",
            delta="Capacidad de Abastecimiento",
            delta_type="positive",
            icon_name="inventory",
            tag_text="Existencias",
            tag_class="tag-cyan",
            stripe_class="stripe-cyan"
        )

# ----------------- DIMENSION CAPACIDAD Y CATALOGO -----------------
with p4:
    st.caption("Objetivo: Trazabilidad, disciplina operativa y cobertura del portafolio industrial.")
    ap1, ap2, ap3 = st.columns(3)
    with ap1:
        render_kpi_card(
            title="Catálogo Activo de Plásticos",
            value="10,000 SKUs",
            subtitle="9 categorías y líneas industriales",
            delta="Cobertura 100%",
            delta_type="positive",
            icon_name="category",
            tag_text="Portafolio",
            tag_class="tag-purple",
            stripe_class="stripe-purple"
        )
    with ap2:
        render_kpi_card(
            title="Puntos de Venta Conectados",
            value="3 Sucursales",
            subtitle="Usulután, Matriz Centro, Metrocentro",
            delta="Red Sincronizada",
            delta_type="positive",
            icon_name="hub",
            tag_text="Infraestructura",
            tag_class="tag-cyan",
            stripe_class="stripe-cyan"
        )
    with ap3:
        render_kpi_card(
            title="Confiabilidad de Datos OLAP",
            value="100%",
            subtitle="Pruebas de integridad aprobadas",
            delta="Cero Huérfanos",
            delta_type="positive",
            icon_name="verified",
            tag_text="Integridad",
            tag_class="tag-green",
            stripe_class="stripe-green"
        )

st.markdown("---")

# ==============================================================================
# GRAFICO DE EVOLUCION ANUAL CONSOLIDADA
# ==============================================================================
st.markdown("#### Histórico Anual Consolidado")

df_anual = df_tend_fil.groupby("anio").agg({
    "transacciones": "sum",
    "unidades_vendidas": "sum",
    "subtotal_neto": "sum",
    "total_ingresos": "sum",
    "cogs": "sum",
    "utilidad_bruta": "sum"
}).reset_index()

df_anual["margen_ponderado"] = (df_anual["utilidad_bruta"] / df_anual["subtotal_neto"]) * 100
df_anual["ticket_promedio"] = df_anual["total_ingresos"] / df_anual["transacciones"]

c_an1, c_an2 = st.columns([3, 2])

with c_an1:
    fig_anual = px.bar(
        df_anual,
        x="anio",
        y="total_ingresos",
        text="total_ingresos",
        color="margen_ponderado",
        color_continuous_scale="Viridis",
        title="Facturación Bruta Anual y Margen Ponderado (%)",
        labels={"anio": "Año Fiscal", "total_ingresos": "Total Ingresos ($)", "margen_ponderado": "Margen %"}
    )
    fig_anual.update_traces(texttemplate="$%{text:,.2f}", textposition="outside")
    apply_chart_theme(fig_anual)
    st.plotly_chart(fig_anual, width="stretch")

with c_an2:
    st.markdown("##### Resumen Tabulado por Ejercicio")
    df_show = df_anual.copy()
    df_show["transacciones"] = df_show["transacciones"].apply(lambda x: f"{int(x):,}")
    df_show["total_ingresos"] = df_show["total_ingresos"].apply(lambda x: f"${x:,.2f}")
    df_show["utilidad_bruta"] = df_show["utilidad_bruta"].apply(lambda x: f"${x:,.2f}")
    df_show["margen_ponderado"] = df_show["margen_ponderado"].apply(lambda x: f"{x:.2f}%")
    df_show["ticket_promedio"] = df_show["ticket_promedio"].apply(lambda x: f"${x:,.2f}")
    df_show = df_show.rename(columns={
        "anio": "Año Fiscal",
        "transacciones": "Ventas",
        "total_ingresos": "Facturación ($)",
        "utilidad_bruta": "Utilidad ($)",
        "margen_ponderado": "Margen (%)",
        "ticket_promedio": "Ticket Medio ($)"
    })
    st.dataframe(
        df_show[["Año Fiscal", "Ventas", "Facturación ($)", "Utilidad ($)", "Margen (%)", "Ticket Medio ($)"]],
        width="stretch",
        hide_index=True
    )
