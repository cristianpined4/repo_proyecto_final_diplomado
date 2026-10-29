"""
Dashboard 7: Diccionario y Catálogo de Métricas del Negocio
Centro de Plásticos El Limeño - Manual de Gobernanza y Métricas Financieras
"""

import streamlit as st
import pandas as pd
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
    render_banner,
    render_diagnostic,
    render_indicator_meta_card,
    format_bold_markdown
)

apply_global_styles()

render_banner(
    title="Manual Institucional de Métricas y Gobernanza Financiera",
    subtitle="Estándares oficiales de cálculo financiero, formulación matemática, asignación de responsabilidades y protocolos de auditoría interna para Centro de Plásticos El Limeño.",
    icon="menu_book",
    badge_text="Gobernanza Financiera"
)

# ==============================================================================
# PILARES INSTITUCIONALES DE MEDICIÓN
# ==============================================================================
col_p1, col_p2 = st.columns(2)

with col_p1:
    st.markdown(
        """
        <div style="background: rgba(17, 24, 39, 0.85); border: 1px solid rgba(0, 210, 255, 0.25); border-radius: 10px; padding: 14px; height: 100%;">
            <div style="display:flex; align-items:center; gap:8px; margin-bottom:8px;">
                <span class="material-symbols-outlined" style="color:#00D2FF; font-size:20px;">account_balance</span>
                <strong style="color:#F8FAFC; font-size:13px; text-transform:uppercase; letter-spacing:0.04em;">Criterios Contables y Financieros</strong>
            </div>
            <p style="font-size:0.83rem; color:#CBD5E1; margin:0; line-height:1.55;">
                • <strong>Principio de Base Devengada</strong>: Los ingresos y costos se reconocen al perfeccionarse la entrega física de la mercancía y la emisión del comprobante fiscal.<br>
                • <strong>Regla de No Aditividad</strong>: Ningún ratio o porcentaje se obtiene promediando promedios. Toda tasa consolidada se calcula sobre la sumatoria agregada de sus componentes elementales.<br>
                • <strong>Depuración Transaccional</strong>: Se incluyen estrictamente transacciones cerradas con estatus pagado, deduciendo notas de crédito y anulaciones.
            </p>
        </div>
        """,
        unsafe_allow_html=True
    )

with col_p2:
    st.markdown(
        """
        <div style="background: rgba(17, 24, 39, 0.85); border: 1px solid rgba(16, 185, 129, 0.25); border-radius: 10px; padding: 14px; height: 100%;">
            <div style="display:flex; align-items:center; gap:8px; margin-bottom:8px;">
                <span class="material-symbols-outlined" style="color:#10B981; font-size:20px;">verified_user</span>
                <strong style="color:#F8FAFC; font-size:13px; text-transform:uppercase; letter-spacing:0.04em;">Auditoría y Contrapeso Operativo</strong>
            </div>
            <p style="font-size:0.83rem; color:#CBD5E1; margin:0; line-height:1.55;">
                • <strong>Vigilancia Cruzada de Volumen y Margen</strong>: No se premia el incremento del ticket promedio si este se logra a costa de una contracción no planificada en el número de operaciones de mostrador.<br>
                • <strong>Contrapeso Margen vs. Rotación</strong>: Un alto margen bruto carece de validez si los artículos presentan días de cobertura superiores a 120 días en sala de ventas.<br>
                • <strong>Cuadre Tributario</strong>: Conciliación obligatoria mensual entre el IVA Débito fiscal de facturación y el IVA Crédito soportado en adquisiciones.
            </p>
        </div>
        """,
        unsafe_allow_html=True
    )

st.markdown("<div style='margin-bottom: 20px;'></div>", unsafe_allow_html=True)

# ==============================================================================
# PESTAÑAS DE VISTA
# ==============================================================================
tab_fichas, tab_matriz, tab_protocolo = st.tabs([
    "Fichas Técnicas Normativas",
    "Matriz Consolidada de Indicadores",
    "Protocolo de Conciliación y Auditoría"
])

with tab_fichas:
    cat_opciones = {
        "MET-FIN-01": "MET-FIN-01: Facturación Bruta, Utilidad y Margen Comercial Ponderado",
        "MET-COM-02": "MET-COM-02: Rendimiento Comercial y Participación por Sucursal",
        "MET-LOG-03": "MET-LOG-03: Umbral Preventivo de Alertas de Stock Crítico (< 15 unidades)",
        "MET-CAT-04": "MET-CAT-04: Margen de Contribución por Artículo y Familia de Productos",
        "MET-LOG-05": "MET-LOG-05: Ratio de Rotación de Inventarios y Días de Cobertura (DSI)",
        "MET-FIS-06": "MET-FIS-06: Liquidación Mensual y Balance Fiscal de IVA (13%)",
        "MET-ABAS-07": "MET-ABAS-07: Concentración de Adquisiciones y Gestión de Proveedores"
    }
    
    col_sel1, col_sel2 = st.columns([3, 1])
    with col_sel1:
        sel_indicador = st.selectbox(
            "Seleccione el Indicador Normativo a Consultar:",
            list(cat_opciones.keys()),
            format_func=lambda k: cat_opciones[k]
        )
    with col_sel2:
        st.markdown(
            f"""
            <div style="background: rgba(17, 24, 39, 0.6); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 8px; padding: 10px; text-align: center; margin-top: 4px;">
                <span style="font-size: 0.72rem; color: #94A3B8; text-transform: uppercase; font-weight: 600;">Código de Métrica</span>
                <div style="font-size: 1.05rem; font-weight: 800; color: #00D2FF; letter-spacing: 0.02em;">{sel_indicador}</div>
            </div>
            """,
            unsafe_allow_html=True
        )

    st.markdown("<hr style='border:0; border-top:1px solid rgba(255,255,255,0.08); margin:15px 0;'>", unsafe_allow_html=True)

    # --------------------------------------------------------------------------
    # FICHA 1: MET-FIN-01
    # --------------------------------------------------------------------------
    if sel_indicador == "MET-FIN-01":
        st.markdown("### Facturación Bruta, Utilidad y Margen Comercial Ponderado")
        
        c1, c2, c3, c4 = st.columns(4)
        with c1:
            render_indicator_meta_card("Área Responsable", "Dirección Financiera", "corporate_fare")
        with c2:
            render_indicator_meta_card("Frecuencia de Corte", "Mensual / Cierre Contable", "calendar_today")
        with c3:
            render_indicator_meta_card("Meta Institucional", "Margen ≥ 22.00%", "flag")
        with c4:
            render_indicator_meta_card("Valor Histórico Verificado", "23.17% Margen", "verified")

        col_det1, col_det2 = st.columns(2)
        with col_det1:
            st.markdown(
                """
                <div style="background: rgba(17, 24, 39, 0.75); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 8px; padding: 14px;">
                    <div style="font-weight: 700; color: #F8FAFC; margin-bottom: 6px; font-size: 0.9rem;">
                        <span class="material-symbols-outlined" style="font-size: 16px; color: #00D2FF;">description</span>
                        Definición Conceptual y Alcance
                    </div>
                    <p style="font-size: 0.82rem; color: #CBD5E1; margin: 0; line-height: 1.5;">
                        Representa el desempeño comercial global consolidado de Centro de Plásticos El Limeño. 
                        Cuantifica los ingresos brutos facturados (incluyendo IVA), la base imponible neta devengada, 
                        el costo real de adquisición de los productos entregados y el remanente de ganancia operativa bruta 
                        disponible para cubrir gastos administrativos y de operación.
                    </p>
                </div>
                """,
                unsafe_allow_html=True
            )
        with col_det2:
            st.markdown(
                """
                <div style="background: rgba(17, 24, 39, 0.75); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 8px; padding: 14px;">
                    <div style="font-weight: 700; color: #F8FAFC; margin-bottom: 6px; font-size: 0.9rem;">
                        <span class="material-symbols-outlined" style="font-size: 16px; color: #10B981;">rule</span>
                        Criterios de Depuración y Calidad
                    </div>
                    <p style="font-size: 0.82rem; color: #CBD5E1; margin: 0; line-height: 1.5;">
                        • <strong>Estatus Transaccional</strong>: Exclusivamente ventas cerradas con estatus <code>pagada</code>.<br>
                        • <strong>Descuentos y Devoluciones</strong>: Integrados en el importe neto liquidado.<br>
                        • <strong>Cálculo no aditivo</strong>: El margen se evalúa como la razón entre la utilidad total acumulada y las ventas netas del período.
                    </p>
                </div>
                """,
                unsafe_allow_html=True
            )

        st.markdown("<div style='margin-top: 15px;'></div>", unsafe_allow_html=True)
        st.markdown("#### Formulación Matemática Institucional:")
        
        st.markdown("**1. Facturación Bruta Consolidada (Con IVA):**")
        st.latex(r"\text{Facturación Bruta Total} = \sum_{i=1}^{N} \text{Importe Transaccional Cobrado}_i")
        
        st.markdown("**2. Ventas Netas de Operación (Sin IVA):**")
        st.latex(r"\text{Ventas Netas} = \sum_{i=1}^{N} \text{Base Imponible de Venta}_i")
        
        st.markdown("**3. Costo Total de Mercancía Vendida (COGS):**")
        st.latex(r"\text{Costo de Ventas (COGS)} = \sum_{k=1}^{M} \left( \text{Unidades Físicas Vendidas}_k \times \text{Costo Unitario de Compra}_k \right)")
        
        st.markdown("**4. Utilidad Bruta Comercial:**")
        st.latex(r"\text{Utilidad Bruta} = \text{Ventas Netas} - \text{Costo de Ventas (COGS)}")
        
        st.markdown("**5. Margen Bruto Ponderado (%):**")
        st.latex(r"\text{Margen Bruto Ponderado (\%)} = \left( \frac{\text{Utilidad Bruta}}{\text{Ventas Netas}} \right) \times 100\%")

        st.markdown("**6. Ticket Promedio General:**")
        st.latex(r"\text{Ticket Promedio} = \frac{\text{Facturación Bruta Total}}{\text{Total de Operaciones de Venta Liquidadas}}")

        render_diagnostic(
            title="Protocolo de Auditoría Cruzada y Prevención de Distorsión",
            text=(
                "Para evitar que el personal comercial priorice operaciones de alto ticket desatendiendo el mostrador minorista, "
                "el sistema audita permanentemente el volumen global de transacciones (9,201 operaciones verificadas) "
                "y el flujo de unidades físicas entregadas (174,835 artículos despachados)."
            ),
            icon="security"
        )

    # --------------------------------------------------------------------------
    # FICHA 2: MET-COM-02
    # --------------------------------------------------------------------------
    elif sel_indicador == "MET-COM-02":
        st.markdown("### Rendimiento Comercial y Participación por Sucursal")
        
        c1, c2, c3, c4 = st.columns(4)
        with c1:
            render_indicator_meta_card("Área Responsable", "Jefaturas de Sucursal", "store")
        with c2:
            render_indicator_meta_card("Puntos de Venta", "3 Sucursales Físicas", "storefront")
        with c3:
            render_indicator_meta_card("Líder en Facturación", "Usulután ($1.63M)", "leaderboard")
        with c4:
            render_indicator_meta_card("Líder en Rentabilidad", "Matriz Centro (26.80%)", "trending_up")

        st.markdown("#### Formulación Matemática Institucional:")
        
        st.markdown("**1. Cuota de Participación en Facturación por Sucursal (%):**")
        st.latex(r"\text{Participación de Sucursal } s \text{ (\%)} = \left( \frac{\text{Facturación Bruta de Sucursal}_s}{\sum_{j=1}^{S} \text{Facturación Bruta de Sucursal}_j} \right) \times 100\%")

        st.markdown("**2. Ticket Promedio del Punto de Venta:**")
        st.latex(r"\text{Ticket Promedio}_s = \frac{\text{Facturación Bruta de Sucursal}_s}{\text{Número de Ventas de Sucursal}_s}")

        st.markdown("**3. Margen Operativo por Sucursal (%):**")
        st.latex(r"\text{Margen de Sucursal } s \text{ (\%)} = \left( \frac{\text{Utilidad Bruta de Sucursal}_s}{\text{Ventas Netas de Sucursal}_s} \right) \times 100\%")

        st.markdown(
            """
            <div style="background: rgba(17, 24, 39, 0.7); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 8px; padding: 14px; margin-top: 15px;">
                <div style="font-weight: 700; color: #F8FAFC; margin-bottom: 8px;">Comportamiento Operativo Verificado por Punto de Venta</div>
                <div style="font-size: 0.82rem; color: #CBD5E1; line-height: 1.6;">
                    • <strong>Sucursal Usulután</strong>: Facturación acumulada de $1,633,508.87 en 3,067 transacciones. Modelo mayorista y semi-mayorista con ticket medio elevado ($532.61) y margen equilibrado de 23.12%.<br>
                    • <strong>Sucursal San Miguel - Metrocentro</strong>: Facturación de $1,080,756.65 en 3,066 transacciones. Canal de tráfico comercial continuo con ticket de $352.50 y margen de 21.36%.<br>
                    • <strong>Sucursal San Miguel - Matriz Centro</strong>: Facturación de $559,813.99 en 3,068 transacciones. Perfil minorista de alta frecuencia con ticket de $182.47 y el mayor margen porcentual del negocio (26.80%).
                </div>
            </div>
            """,
            unsafe_allow_html=True
        )

    # --------------------------------------------------------------------------
    # FICHA 3: MET-LOG-03
    # --------------------------------------------------------------------------
    elif sel_indicador == "MET-LOG-03":
        st.markdown("### Umbral Preventivo de Alertas de Stock Crítico (< 15 unidades)")
        
        c1, c2, c3, c4 = st.columns(4)
        with c1:
            render_indicator_meta_card("Área Responsable", "Jefatura de Bodega", "warehouse")
        with c2:
            render_indicator_meta_card("Frecuencia de Revisión", "Diaria / Por Turno", "update")
        with c3:
            render_indicator_meta_card("Umbral de Seguridad", "< 15 Unidades Físicas", "warning")
        with c4:
            render_indicator_meta_card("Artículos en Alerta", "16 SKUs Críticos", "report_problem")

        st.markdown("#### Formulación Matemática Institucional:")
        
        st.markdown("**1. Condición Operativa de Alerta Preventiva:**")
        st.latex(r"\text{Estatus de Stock} = \begin{cases} \mathbf{Alerta\ de\ Reposición\ Inmediata}, & \text{si } \text{Existencias Físicas en Bodega} < 15 \text{ unidades} \\ \mathbf{Nivel\ Operativo\ Seguro}, & \text{si } \text{Existencias Físicas en Bodega} \ge 15 \text{ unidades} \end{cases}")

        st.markdown("**2. Cobertura de Inventario Inmediata (Días):**")
        st.latex(r"\text{Días de Cobertura Inmediata} = \frac{\text{Existencias Físicas Actuales}}{\text{Demanda Promedio Diaria de los Últimos 30 Días}}")

        render_diagnostic(
            title="Mecanismo Preventivo contra Pérdida de Ingresos",
            text=(
                "El umbral de 15 unidades actúa como barrera de seguridad operativa. "
                "Garantiza el tiempo suficiente para emitir y recibir órdenes de reabastecimiento antes de que se produzca una rotura de existencias "
                "que afecte las ventas en los mostradores de Usulután, Matriz Centro y Metrocentro."
            ),
            icon="notifications_active"
        )

    # --------------------------------------------------------------------------
    # FICHA 4: MET-CAT-04
    # --------------------------------------------------------------------------
    elif sel_indicador == "MET-CAT-04":
        st.markdown("### Margen de Contribución por Artículo y Familia de Productos")
        
        c1, c2, c3, c4 = st.columns(4)
        with c1:
            render_indicator_meta_card("Área Responsable", "Gestión de Categorías / Compras", "category")
        with c2:
            render_indicator_meta_card("Frecuencia de Corte", "Mensual", "event")
        with c3:
            render_indicator_meta_card("Categoría Líder Ventas", "Bolsas Plásticas", "shopping_bag")
        with c4:
            render_indicator_meta_card("Categoría Líder Margen", "Desechables (27.4%)", "percent")

        st.markdown("#### Formulación Matemática Institucional:")
        
        st.markdown("**1. Margen Bruto Unitario por Artículo:**")
        st.latex(r"\text{Margen Unitario} = \text{Precio de Venta al Público (Sin IVA)} - \text{Costo Promedio de Compra}")

        st.markdown("**2. Margen Porcentual por Artículo (%):**")
        st.latex(r"\text{Margen del Artículo (\%)} = \left( \frac{\text{Margen Unitario}}{\text{Precio de Venta al Público (Sin IVA)}} \right) \times 100\%")

        st.markdown("**3. Margen Ponderado por Categoría (%):**")
        st.latex(r"\text{Margen de la Categoría } c \text{ (\%)} = \left( \frac{\sum_{i \in c} \text{Utilidad Bruta del Artículo}_i}{\sum_{i \in c} \text{Ventas Netas del Artículo}_i} \right) \times 100\%")

        render_diagnostic(
            title="Contrapeso: Prevención de la Trampa del Alto Margen",
            text=(
                "Los artículos con márgenes superiores al 35% pero baja rotación no deben sobreponderarse en compras. "
                "El catálogo se evalúa mediante la matriz bidimensional de Margen Bruto vs. Volumen Vendido, "
                "priorizando los productos Clase A de rotación masiva que generan el flujo de caja operativo principal."
            ),
            icon="balance"
        )

    # --------------------------------------------------------------------------
    # FICHA 5: MET-LOG-05
    # --------------------------------------------------------------------------
    elif sel_indicador == "MET-LOG-05":
        st.markdown("### Ratio de Rotación de Inventarios y Días de Cobertura (DSI)")
        
        c1, c2, c3, c4 = st.columns(4)
        with c1:
            render_indicator_meta_card("Área Responsable", "Jefatura de Logística", "local_shipping")
        with c2:
            render_indicator_meta_card("Periodicidad", "Mensual / Trimestral", "schedule")
        with c3:
            render_indicator_meta_card("Rotación Promedio", "3.12 Veces al Año", "sync")
        with c4:
            render_indicator_meta_card("DSI Promedio", "116.9 Días", "hourglass_top")

        st.markdown("#### Formulación Matemática Institucional:")
        
        st.markdown("**1. Índice de Rotación de Existencias (Veces al Año):**")
        st.latex(r"\text{Rotación de Inventarios} = \frac{\text{Costo Total de Ventas Anualizado}}{\text{Valorización Promedio de Existencias al Costo}}")

        st.markdown("**2. Días de Inventario en Bodega (DSI - Days Sales of Inventory):**")
        st.latex(r"\text{DSI (Días de Cobertura)} = \frac{365}{\text{Rotación de Inventarios}}")

        st.markdown(
            """
            <div style="background: rgba(17, 24, 39, 0.7); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 8px; padding: 14px; margin-top: 15px;">
                <div style="font-weight: 700; color: #F8FAFC; margin-bottom: 6px;">Interpretación Operativa del DSI</div>
                <p style="font-size: 0.82rem; color: #CBD5E1; margin: 0; line-height: 1.55;">
                    El indicador DSI refleja cuántos días tarda Centro de Plásticos El Limeño en convertir su inventario promedio en ventas efectivas.
                    Un DSI inferior a 90 días en categorías de consumo diario (bolsas, desechables) indica alta fluidez de capital de trabajo, 
                    mientras que valores superiores a 150 días exigen congelar compras de reposición para evitar deterioro y costo financiero de almacenamiento.
                </p>
            </div>
            """,
            unsafe_allow_html=True
        )

    # --------------------------------------------------------------------------
    # FICHA 6: MET-FIS-06
    # --------------------------------------------------------------------------
    elif sel_indicador == "MET-FIS-06":
        st.markdown("### Liquidación Mensual y Balance Fiscal de IVA (13%)")
        
        c1, c2, c3, c4 = st.columns(4)
        with c1:
            render_indicator_meta_card("Área Responsable", "Contabilidad General", "receipt_long")
        with c2:
            render_indicator_meta_card("Régimen Fiscal", "El Salvador (IVA 13%)", "account_balance")
        with c3:
            render_indicator_meta_card("IVA Débito Generado", "$376,648.51", "arrow_upward")
        with c4:
            render_indicator_meta_card("IVA Crédito Soportado", "$289,886.72", "arrow_downward")

        st.markdown("#### Formulación Matemática Institucional:")
        
        st.markdown("**1. IVA Débito Fiscal Generado en Ventas (13%):**")
        st.latex(r"\text{IVA Débito Fiscal} = \text{Base Imponible Total de Ventas} \times 0.13")

        st.markdown("**2. IVA Crédito Fiscal Deducible en Compras (13%):**")
        st.latex(r"\text{IVA Crédito Fiscal} = \text{Base Imponible de Compras a Proveedores Formales} \times 0.13")

        st.markdown("**3. Posición Fiscal Neta a Liquidar:**")
        st.latex(r"\text{Balance Neto de IVA} = \text{IVA Débito Fiscal} - \text{IVA Crédito Fiscal}")
        st.latex(r"\text{Resultado Contable} = \begin{cases} \mathbf{Impuesto\ a\ Pagar\ a\ Hacienda}, & \text{si } \text{Balance Neto de IVA} > 0 \\ \mathbf{Remanente\ de\ Crédito\ a\ Favor}, & \text{si } \text{Balance Neto de IVA} < 0 \end{cases}")

        render_diagnostic(
            title="Cumplimiento Tributario y Cuadre Mensual",
            text=(
                "El saldo neto mensual de IVA ($86,761.79 histórico) representa la obligación tributaria neta liquidable "
                "ante el Ministerio de Hacienda dentro de los primeros 10 días hábiles de cada mes fiscal. "
                "Toda adquisición sin comprobante de crédito fiscal formal queda excluida del crédito deducible."
            ),
            icon="account_balance_wallet"
        )

    # --------------------------------------------------------------------------
    # FICHA 7: MET-ABAS-07
    # --------------------------------------------------------------------------
    elif sel_indicador == "MET-ABAS-07":
        st.markdown("### Concentración de Adquisiciones y Gestión de Proveedores")
        
        c1, c2, c3, c4 = st.columns(4)
        with c1:
            render_indicator_meta_card("Área Responsable", "Jefatura de Abastecimiento", "inventory")
        with c2:
            render_indicator_meta_card("Cartera de Fabricantes", "8 Proveedores Clave", "group")
        with c3:
            render_indicator_meta_card("Proveedor Principal", "Plásticos de El Salvador", "star")
        with c4:
            render_indicator_meta_card("Gasto Total Acumulado", "$2,519,784.60", "payments")

        st.markdown("#### Formulación Matemática Institucional:")
        
        st.markdown("**1. Índice de Concentración de Compras por Proveedor (%):**")
        st.latex(r"\text{Cuota de Proveedor } p \text{ (\%)} = \left( \frac{\text{Total de Compras Facturadas por el Proveedor}_p}{\text{Presupuesto Total de Adquisiciones de la Empresa}} \right) \times 100\%")

        st.markdown("**2. Índice de Dependencia de Suministro (Top 3 Fabricantes):**")
        st.latex(r"\text{Concentración Top 3 (\%)} = \sum_{k=1}^{3} \text{Cuota del Proveedor Principal}_k")

        render_diagnostic(
            title="Mitigación de Riesgo en la Cadena de Suministro",
            text=(
                "Una concentración superior al 45% en un único fabricante de resinas o películas plásticas expone a la empresa a riesgos "
                "de desabastecimiento o ajustes unilaterales de precios. La política corporativa exige mantener activas líneas secundarias "
                "de crédito y suministro con distribuidores alternativos."
            ),
            icon="local_shipping"
        )

# ==============================================================================
# PESTAÑA 2: MATRIZ CONSOLIDADA
# ==============================================================================
with tab_matriz:
    st.markdown("#### Matriz Maestra de Indicadores Institucionales")
    st.markdown(
        """
        <p style="font-size: 0.85rem; color: #94A3B8; margin-bottom: 15px;">
            Compendio de control y gobernanza con la asignación formal de responsabilidades, 
            periodicidad de medición y objetivos fijados para Centro de Plásticos El Limeño.
        </p>
        """,
        unsafe_allow_html=True
    )
    
    df_matriz_completa = pd.DataFrame([
        {
            "Código": "MET-FIN-01",
            "Nombre del Indicador": "Facturación Bruta y Margen Comercial Ponderado",
            "Naturaleza": "Resultado Financiero",
            "Área Responsable": "Dirección Financiera",
            "Frecuencia": "Mensual",
            "Unidad de Medida": "USD ($) / Porcentaje (%)",
            "Objetivo Institucional": "Margen ≥ 22.00%"
        },
        {
            "Código": "MET-COM-02",
            "Nombre del Indicador": "Rendimiento y Margen por Sucursal",
            "Naturaleza": "Operacional / Comercial",
            "Área Responsable": "Jefaturas de Sucursal",
            "Frecuencia": "Semanal / Mensual",
            "Unidad de Medida": "USD ($) / Ticket ($)",
            "Objetivo Institucional": "Cumplimiento de presupuesto semanal"
        },
        {
            "Código": "MET-LOG-03",
            "Nombre del Indicador": "Monitor de Alertas de Stock Crítico",
            "Naturaleza": "Preventiva (Alerta Temprana)",
            "Área Responsable": "Jefatura de Bodega",
            "Frecuencia": "Diaria (por turno)",
            "Unidad de Medida": "Unidades Físicas (SKU)",
            "Objetivo Institucional": "0 roturas de stock en mostrador"
        },
        {
            "Código": "MET-CAT-04",
            "Nombre del Indicador": "Margen de Contribución por Categoría y SKU",
            "Naturaleza": "Comercial / Portafolio",
            "Área Responsable": "Category Management",
            "Frecuencia": "Mensual",
            "Unidad de Medida": "Margen Bruto (%)",
            "Objetivo Institucional": "Equilibrio entre volumen y rentabilidad"
        },
        {
            "Código": "MET-LOG-05",
            "Nombre del Indicador": "Rotación de Inventarios y Días de Cobertura",
            "Naturaleza": "Eficiencia Logística",
            "Área Responsable": "Jefe de Logística",
            "Frecuencia": "Mensual",
            "Unidad de Medida": "Veces al Año / Días (DSI)",
            "Objetivo Institucional": "DSI Promedio ≤ 90 días"
        },
        {
            "Código": "MET-FIS-06",
            "Nombre del Indicador": "Cuadratura Mensual de IVA Fiscal (13%)",
            "Naturaleza": "Cumplimiento Tributario",
            "Área Responsable": "Contador General",
            "Frecuencia": "Mensual (día 10)",
            "Unidad de Medida": "USD ($)",
            "Objetivo Institucional": "Conciliación 100% con libros de IVA"
        },
        {
            "Código": "MET-ABAS-07",
            "Nombre del Indicador": "Concentración del Presupuesto de Proveedores",
            "Naturaleza": "Cadena de Suministro",
            "Área Responsable": "Jefatura de Compras",
            "Frecuencia": "Trimestral",
            "Unidad de Medida": "Porcentaje de Participación (%)",
            "Objetivo Institucional": "Proveedor principal ≤ 45% del total"
        }
    ])
    
    st.dataframe(df_matriz_completa, width="stretch", hide_index=True)

# ==============================================================================
# PESTAÑA 3: PROTOCOLO DE CONCILIACIÓN
# ==============================================================================
with tab_protocolo:
    st.markdown("#### Protocolo de Auditoría y Verificación de Cifras")
    st.markdown(
        """
<div style="background: rgba(17, 24, 39, 0.7); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 10px; padding: 18px; margin-bottom: 20px;">
    <div style="font-weight: 700; color: #F8FAFC; margin-bottom: 14px; font-size: 1rem; display: flex; align-items: center; gap: 8px;">
        <span class="material-symbols-outlined" style="color: #00D2FF; font-size: 20px;">assignment_turned_in</span>
        Procedimiento Operativo Estandarizado (POE) para Cierres de Período
    </div>
    <div style="display: flex; flex-direction: column; gap: 12px;">
        <div style="background: rgba(15, 23, 42, 0.85); border: 1px solid rgba(255, 255, 255, 0.07); border-radius: 8px; padding: 12px 14px;">
            <div style="color: #38BDF8; font-weight: 700; font-size: 0.88rem; margin-bottom: 4px;">Paso 1: Bloqueo Transaccional y Cierre de Cajas (20:00 hrs)</div>
            <div style="font-size: 0.82rem; color: #CBD5E1; line-height: 1.5;">A las 20:00 horas del último día hábil del mes, cada sucursal (Usulután, Matriz Centro, Metrocentro) efectúa el corte Z de sus terminales de punto de venta. No se admiten ingresos extemporáneos sin previa autorización de la Dirección Financiera.</div>
        </div>
        <div style="background: rgba(15, 23, 42, 0.85); border: 1px solid rgba(255, 255, 255, 0.07); border-radius: 8px; padding: 12px 14px;">
            <div style="color: #38BDF8; font-weight: 700; font-size: 0.88rem; margin-bottom: 4px;">Paso 2: Conciliación de Facturación vs. Depósitos Bancarios</div>
            <div style="font-size: 0.82rem; color: #CBD5E1; line-height: 1.5;">El área de auditoría interna concilia el total transaccional con las remesas bancarias y liquidaciones de cobro electrónico con tarjeta. Toda diferencia no justificada superior a $5.00 es investigada y documentada en el informe de corte.</div>
        </div>
        <div style="background: rgba(15, 23, 42, 0.85); border: 1px solid rgba(255, 255, 255, 0.07); border-radius: 8px; padding: 12px 14px;">
            <div style="color: #38BDF8; font-weight: 700; font-size: 0.88rem; margin-bottom: 4px;">Paso 3: Validación de Costo de Ventas e Inventario Físico</div>
            <div style="font-size: 0.82rem; color: #CBD5E1; line-height: 1.5;">Se cotejan los despachos de bodega con las existencias reportadas. El costo unitario de adquisición aplicado a cada SKU corresponde al valor promedio ponderado vigente en la fecha de la transacción.</div>
        </div>
        <div style="background: rgba(15, 23, 42, 0.85); border: 1px solid rgba(255, 255, 255, 0.07); border-radius: 8px; padding: 12px 14px;">
            <div style="color: #38BDF8; font-weight: 700; font-size: 0.88rem; margin-bottom: 4px;">Paso 4: Certificación del Balance de IVA Fiscal (13%)</div>
            <div style="font-size: 0.82rem; color: #CBD5E1; line-height: 1.5;">El Contador General emite la declaración jurada mensual de IVA conciliando el libro de ventas a contribuyentes y consumidores finales con el libro de compras a distribuidores formalmente inscritos.</div>
        </div>
    </div>
</div>
""",
        unsafe_allow_html=True
    )
