"""
Modulo de Filtros Interactivos Multidimensionales
Centro de Plásticos El Limeño - Plataforma de Gestión y Analítica
"""

import streamlit as st
import pandas as pd
from datetime import date
from typing import Dict, Any, List


CATEGORIAS_DEFAULT = [
    "Bolsas y Empaques",
    "Contenedores y Almacenamiento",
    "Desechables y Fiestas",
    "Hogar y Limpieza",
    "Juguetería y Novedades",
    "Línea Industrial y Agrícola",
    "Mobiliario Plástico",
    "Recipientes Térmicos y Bebidas",
    "General"
]

ANIOS_DISPONIBLES = [2026, 2025, 2024, 2023, 2022, 2021, 2020, 2019, 2018, 2017, 2016]

MESES_NOMBRES = [
    (1, "Enero"), (2, "Febrero"), (3, "Marzo"), (4, "Abril"),
    (5, "Mayo"), (6, "Junio"), (7, "Julio"), (8, "Agosto"),
    (9, "Septiembre"), (10, "Octubre"), (11, "Noviembre"), (12, "Diciembre")
]

DIAS_SEMANA = ["Lunes", "Martes", "Miércoles", "Jueves", "Viernes", "Sábado", "Domingo"]


def render_global_filters(
    sucursales_disp: List[str] = None,
    categorias_disp: List[str] = None,
    anios_disp: List[int] = None,
    show_dates: bool = True,
    show_categories: bool = True,
    show_branches: bool = True,
    show_stock_filter: bool = True,
    show_margin_filter: bool = True,
    show_abc_filter: bool = False
) -> Dict[str, Any]:
    """
    Renderiza un centro de control de filtros interactivos en la barra lateral.
    Permite filtrar por rangos de fechas (días), años (cualquiera entre 2016 y 2026),
    meses, semanas del año y días de la semana, así como sucursales y categorías.
    """
    st.sidebar.markdown(
        """
        <div style="display:flex; align-items:center; justify-content:space-between; margin:14px 0 10px 0; padding-bottom:8px; border-bottom:1px solid rgba(255,255,255,0.08);">
            <div style="display:flex; align-items:center; gap:8px;">
                <span class="material-symbols-outlined" style="color:#00D2FF; font-size:20px;">tune</span>
                <span style="font-weight:700; color:#F8FAFC; font-size:13px; text-transform:uppercase; letter-spacing:0.06em;">Filtros de Control</span>
            </div>
            <span style="font-size:10px; color:#94A3B8; background:rgba(255,255,255,0.05); padding:2px 6px; border-radius:4px;">En Vivo</span>
        </div>
        """,
        unsafe_allow_html=True
    )

    filtros: Dict[str, Any] = {
        "sucursales": [],
        "categorias": [],
        "anios": [],
        "periodo": "Todo el Histórico",
        "tipo_filtro_tiempo": "Todos los Años",
        "fecha_inicio": None,
        "fecha_fin": None,
        "meses_filtro": [],
        "semanas_filtro": [],
        "dias_semana_filtro": [],
        "clasificacion_abc": [],
        "estado_stock": "Todos los Niveles de Existencia",
        "margen_rango": "Todos los Márgenes"
    }

    # ==============================================================================
    # 1. FILTROS TEMPORALES: AÑOS, MESES, SEMANAS Y DÍAS (CALENDARIO)
    # ==============================================================================
    if show_dates:
        with st.sidebar.expander("Calendario y Temporalidad", expanded=True):
            lista_anios = anios_disp if anios_disp else ANIOS_DISPONIBLES
            
            tipo_tiempo = st.radio(
                "Nivel de Granularidad Temporal:",
                options=[
                    "Por Año(s) y Meses",
                    "Por Rango de Fechas (Días)",
                    "Por Semanas del Año",
                    "Todo el Histórico (2016-2026)"
                ],
                index=0,
                help="Seleccione el nivel de detalle temporal que desea analizar."
            )
            filtros["tipo_filtro_tiempo"] = tipo_tiempo

            if tipo_tiempo == "Por Año(s) y Meses":
                # 1.1 Multiselect de años disponibles
                opciones_anios = [str(a) for a in lista_anios]
                anios_sel = st.multiselect(
                    "Seleccionar Año(s):",
                    options=opciones_anios,
                    default=["2026"],
                    placeholder="Seleccione uno o varios años...",
                    help="Puede elegir cualquier año entre 2016 y 2026, o combinar varios."
                )
                if anios_sel:
                    filtros["anios"] = [int(a) for a in anios_sel]
                    filtros["periodo"] = f"Año(s): {', '.join(anios_sel)}"
                else:
                    filtros["anios"] = []
                    filtros["periodo"] = "Todo el Histórico"

                # 1.2 Multiselect de meses
                opciones_meses = [f"{num:02d} - {nom}" for num, nom in MESES_NOMBRES]
                meses_sel = st.multiselect(
                    "Mes(es) del Año:",
                    options=opciones_meses,
                    default=[],
                    placeholder="Todos los meses...",
                    help="Filtra meses específicos dentro del año o años seleccionados."
                )
                if meses_sel:
                    filtros["meses_filtro"] = [int(m.split(" - ")[0]) for m in meses_sel]

            elif tipo_tiempo == "Por Rango de Fechas (Días)":
                # 1.3 Selector exacto de días (Date input)
                min_d = date(2016, 1, 4)
                max_d = date(2026, 10, 15)
                # Default a año actual
                fechas_sel = st.date_input(
                    "Seleccionar Rango de Días (Desde - Hasta):",
                    value=(date(2026, 1, 1), max_d),
                    min_value=min_d,
                    max_value=max_d,
                    help="Filtra las operaciones entre dos días específicos."
                )
                if isinstance(fechas_sel, (tuple, list)) and len(fechas_sel) == 2:
                    filtros["fecha_inicio"] = fechas_sel[0]
                    filtros["fecha_fin"] = fechas_sel[1]
                    filtros["periodo"] = f"{fechas_sel[0].strftime('%d/%m/%Y')} a {fechas_sel[1].strftime('%d/%m/%Y')}"
                elif isinstance(fechas_sel, (tuple, list)) and len(fechas_sel) == 1:
                    filtros["fecha_inicio"] = fechas_sel[0]
                    filtros["fecha_fin"] = fechas_sel[0]
                    filtros["periodo"] = f"Día {fechas_sel[0].strftime('%d/%m/%Y')}"

            elif tipo_tiempo == "Por Semanas del Año":
                # 1.4 Filtro por semanas y días de semana
                opciones_semanas = [f"Semana {w}" for w in range(1, 53)]
                sem_sel = st.multiselect(
                    "Semanas Operacionales (1 a 52):",
                    options=opciones_semanas,
                    default=[],
                    placeholder="Todas las semanas...",
                    help="Analiza el rendimiento en semanas operativas específicas."
                )
                if sem_sel:
                    filtros["semanas_filtro"] = [int(s.replace("Semana ", "")) for s in sem_sel]

                dias_sel = st.multiselect(
                    "Días de la Semana:",
                    options=DIAS_SEMANA,
                    default=[],
                    placeholder="Todos los días (Lun-Dom)...",
                    help="Compara ventas entre días laborales y fines de semana."
                )
                if dias_sel:
                    filtros["dias_semana_filtro"] = dias_sel
                filtros["periodo"] = "Filtro Semanal"

            else:
                filtros["anios"] = []
                filtros["periodo"] = "Todo el Histórico (2016-2026)"

    # ==============================================================================
    # 2. FILTROS COMERCIALES Y PUNTOS DE VENTA
    # ==============================================================================
    with st.sidebar.expander("Puntos de Venta y Líneas", expanded=True):
        if show_branches and sucursales_disp:
            opciones_suc = ["Todas las Sucursales"] + sorted(sucursales_disp)
            suc_sel = st.multiselect(
                "Punto de Venta:",
                options=opciones_suc,
                default=["Todas las Sucursales"],
                placeholder="Seleccione sucursales...",
                help="Filtra las operaciones por tienda física (Usulután, Matriz Centro, Metrocentro)."
            )
            if "Todas las Sucursales" not in suc_sel and len(suc_sel) > 0:
                filtros["sucursales"] = suc_sel
            else:
                filtros["sucursales"] = []

        if show_categories:
            cats = categorias_disp if categorias_disp else CATEGORIAS_DEFAULT
            opciones_cat = ["Todas las Categorías"] + sorted(cats)
            cat_sel = st.multiselect(
                "Línea de Plásticos:",
                options=opciones_cat,
                default=["Todas las Categorías"],
                placeholder="Seleccione categorías...",
                help="Segmenta los artículos por familia de producto (Bolsas, Mobiliario, Térmicos, etc.)."
            )
            if "Todas las Categorías" not in cat_sel and len(cat_sel) > 0:
                filtros["categorias"] = cat_sel
            else:
                filtros["categorias"] = []

        if show_abc_filter:
            opciones_abc = [
                "Todas las Clases",
                "A (Alto Impacto - 80% Facturación)",
                "B (Impacto Medio - 15% Facturación)",
                "C (Bajo Impacto - 5% Facturación)"
            ]
            abc_sel = st.multiselect(
                "Clasificación ABC (Pareto):",
                options=opciones_abc,
                default=["Todas las Clases"],
                placeholder="Seleccione clase...",
                help="Filtra por importancia estratégica según volumen acumulado de facturación."
            )
            if "Todas las Clases" not in abc_sel and len(abc_sel) > 0:
                filtros["clasificacion_abc"] = abc_sel
            else:
                filtros["clasificacion_abc"] = []

    # ==============================================================================
    # 3. FILTROS OPERATIVOS Y NIVELES DE STOCK
    # ==============================================================================
    if show_stock_filter or show_margin_filter:
        with st.sidebar.expander("Umbrales de Control & Riesgo", expanded=False):
            if show_stock_filter:
                opciones_stock = [
                    "Todos los Niveles de Existencia",
                    "Solo Quiebres Totales (Stock = 0)",
                    "Solo Stock Crítico (< 15 piezas)",
                    "Stock de Seguridad (15 a 50 piezas)",
                    "Stock Abundante (> 50 piezas)"
                ]
                filtros["estado_stock"] = st.selectbox(
                    "Estado de Inventario en Bodega:",
                    options=opciones_stock,
                    index=0,
                    help="Filtra existencias según su nivel de riesgo de desabastecimiento."
                )

            if show_margin_filter:
                opciones_margen = [
                    "Todos los Márgenes",
                    "Alto Rendimiento (> 25% Margen)",
                    "Rendimiento Estándar (20% a 25% Margen)",
                    "Margen Moderado (15% a 20% Margen)",
                    "Margen Ajustado (< 15% Margen)"
                ]
                filtros["margen_rango"] = st.selectbox(
                    "Rango de Rentabilidad Bruta:",
                    options=opciones_margen,
                    index=0,
                    help="Filtra productos o transacciones según la tasa de margen bruto directo."
                )

    # ==============================================================================
    # 4. BOTONES DE ACCIÓN RÁPIDA (PRESETS)
    # ==============================================================================
    c_btn1, c_btn2 = st.sidebar.columns(2)
    with c_btn1:
        if st.button("Restablecer", width="stretch", type="secondary", help="Limpia todos los filtros"):
            st.session_state.clear()
            st.rerun()

    with c_btn2:
        if st.button("Año 2026", width="stretch", type="secondary", help="Filtra directamente al ejercicio 2026"):
            filtros["anios"] = [2026]
            filtros["periodo"] = "Año 2026"
            st.rerun()

    # ==============================================================================
    # RESUMEN DE FILTROS ACTIVOS
    # ==============================================================================
    filtros_activos = []
    if filtros["sucursales"]:
        filtros_activos.append(f"{len(filtros['sucursales'])} sucursales")
    if filtros["categorias"]:
        filtros_activos.append(f"{len(filtros['categorias'])} líneas")
    if filtros["fecha_inicio"] and filtros["fecha_fin"]:
        filtros_activos.append(f"{filtros['fecha_inicio'].strftime('%d/%m/%y')}-{filtros['fecha_fin'].strftime('%d/%m/%y')}")
    elif filtros["anios"]:
        filtros_activos.append(f"Años: {','.join(str(a) for a in filtros['anios'])}")
    if filtros["meses_filtro"]:
        filtros_activos.append(f"{len(filtros['meses_filtro'])} mes(es)")
    if filtros["semanas_filtro"]:
        filtros_activos.append(f"{len(filtros['semanas_filtro'])} sem")
    if filtros["dias_semana_filtro"]:
        filtros_activos.append(f"{len(filtros['dias_semana_filtro'])} días")
    if filtros["clasificacion_abc"]:
        filtros_activos.append(f"Clase {','.join(c[0] for c in filtros['clasificacion_abc'])}")
    if filtros["estado_stock"] != "Todos los Niveles de Existencia":
        filtros_activos.append("Bodega")
    if filtros["margen_rango"] != "Todos los Márgenes":
        filtros_activos.append("Margen")

    if filtros_activos:
        resumen_txt = " · ".join(filtros_activos)
        st.sidebar.markdown(
            f"""
            <div style="background:rgba(0, 210, 255, 0.08); border:1px solid rgba(0, 210, 255, 0.25); border-radius:6px; padding:6px 10px; margin-top:8px;">
                <span style="font-size:11px; color:#38BDF8; font-weight:600;">Filtros activos:</span>
                <span style="font-size:11px; color:#CBD5E1;">{resumen_txt}</span>
            </div>
            """,
            unsafe_allow_html=True
        )
    else:
        st.sidebar.caption("Sin restricciones: datos consolidados al 100%")

    st.sidebar.markdown("<hr style='border:none; border-top:1px solid rgba(255,255,255,0.08); margin:12px 0;'>", unsafe_allow_html=True)
    return filtros
