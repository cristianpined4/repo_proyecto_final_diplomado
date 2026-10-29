"""
Modulo de Estilos Ejecutivos, Tokens Visuales y Componentes UI
Centro de Plásticos El Limeño - Plataforma de Gestión y Analítica
"""

import streamlit as st
import plotly.graph_objects as go

# Paleta de Colores Corporativa Ejecutiva
COLOR_BG = "#0B0F19"
COLOR_CARD = "#111827"
COLOR_CARD_HOVER = "#1E293B"
COLOR_PRIMARY = "#00D2FF"      # Cyan Corporativo
COLOR_SECONDARY = "#3A7BD5"    # Azul Ejecutivo
COLOR_ACCENT = "#7C3AED"       # Violeta Analítico
COLOR_SUCCESS = "#10B981"      # Esmeralda Rentabilidad
COLOR_WARNING = "#F59E0B"      # Ámbar Atención
COLOR_DANGER = "#EF4444"       # Coral Alerta / Quiebre
COLOR_MUTED = "#64748B"        # Texto secundario
COLOR_TEXT = "#F8FAFC"         # Texto principal

# Nombres de meses en español para gráficos y reportes
MESES_ES_CORTO = {
    1: "Ene", 2: "Feb", 3: "Mar", 4: "Abr", 5: "May", 6: "Jun",
    7: "Jul", 8: "Ago", 9: "Sep", 10: "Oct", 11: "Nov", 12: "Dic"
}

MESES_ES_COMPLETO = {
    1: "Enero", 2: "Febrero", 3: "Marzo", 4: "Abril", 5: "Mayo", 6: "Junio",
    7: "Julio", 8: "Agosto", 9: "Septiembre", 10: "Octubre", 11: "Noviembre", 12: "Diciembre"
}

def format_mes_anio(mes: int, anio: int, corto: bool = True) -> str:
    """Retorna etiqueta de mes y año en español (ej. 'Ene 2024' o 'Enero 2024')."""
    d = MESES_ES_CORTO if corto else MESES_ES_COMPLETO
    return f"{d.get(int(mes), f'Mes {mes}')} {int(anio)}"

def format_currency(value) -> str:
    """Formatea valores monetarios al estándar institucional $x,xxx.xx."""
    if value is None:
        return "$0.00"
    try:
        val = float(value)
        return f"${val:,.2f}"
    except (ValueError, TypeError):
        return str(value)

def format_integer(value) -> str:
    """Formatea cantidades y conteos enteros al estándar institucional x,xxx."""
    if value is None:
        return "0"
    try:
        val = int(round(float(value)))
        return f"{val:,}"
    except (ValueError, TypeError):
        return str(value)

def format_percent(value) -> str:
    """Formatea tasas porcentuales al estándar xx.xx%."""
    if value is None:
        return "0.00%"
    try:
        val = float(value)
        return f"{val:.2f}%"
    except (ValueError, TypeError):
        return str(value)

# Aliases de compatibilidad de colores
PRIMARY_COLOR = COLOR_PRIMARY
SUCCESS_COLOR = COLOR_SUCCESS
WARNING_COLOR = COLOR_WARNING
DANGER_COLOR = COLOR_DANGER
INFO_COLOR = COLOR_PRIMARY
PURPLE_COLOR = COLOR_ACCENT

CUSTOM_CSS = """
<style>
    /* Tipografía Corporativa y Google Material Symbols */
    @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap');
    @import url('https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200');

    html, body, [class*="css"] {
        font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
    }

    /* Reducción y armonización de márgenes */
    .block-container {
        padding-top: 1.2rem;
        padding-bottom: 2rem;
        padding-left: 2rem;
        padding-right: 2rem;
        max-width: 100%;
    }

    /* Ocultar elementos genéricos */
    #MainMenu {visibility: hidden;}
    footer {visibility: hidden;}

    /* Material Symbols Utility */
    .material-symbols-outlined {
        font-variation-settings: 'FILL' 1, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        vertical-align: middle;
        line-height: 1;
    }

    /* Encabezado Principal */
    .corp-banner {
        background: linear-gradient(135deg, rgba(17, 24, 39, 0.95) 0%, rgba(15, 23, 42, 0.98) 50%, rgba(30, 41, 59, 0.85) 100%);
        border: 1px solid rgba(0, 210, 255, 0.2);
        border-radius: 14px;
        padding: 1.4rem 1.8rem;
        margin-bottom: 1.5rem;
        position: relative;
        overflow: hidden;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4), inset 0 1px 0 rgba(255, 255, 255, 0.05);
    }
    .corp-banner::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 4px;
        height: 100%;
        background: linear-gradient(180deg, #00D2FF 0%, #7C3AED 100%);
    }
    .corp-banner-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 0.5rem;
    }
    .corp-banner-title {
        color: #F8FAFC;
        font-size: 1.35rem;
        font-weight: 700;
        letter-spacing: -0.02em;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 0.6rem;
    }
    .corp-banner-title .material-symbols-outlined {
        color: #00D2FF;
        font-size: 1.6rem;
    }
    .corp-banner-subtitle {
        color: #94A3B8;
        font-size: 0.88rem;
        margin: 0;
        line-height: 1.45;
        max-width: 90%;
    }
    .corp-pill {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        background: rgba(0, 210, 255, 0.1);
        border: 1px solid rgba(0, 210, 255, 0.35);
        color: #00D2FF;
        padding: 0.3rem 0.75rem;
        border-radius: 20px;
        font-size: 0.72rem;
        font-weight: 600;
        letter-spacing: 0.05em;
        text-transform: uppercase;
    }
    .pulse-dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: #00D2FF;
        box-shadow: 0 0 8px #00D2FF;
        animation: pulseAnimation 2s infinite;
    }
    @keyframes pulseAnimation {
        0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(0, 210, 255, 0.7); }
        70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(0, 210, 255, 0); }
        100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(0, 210, 255, 0); }
    }

    /* Tarjetas de Métricas Ejecutivas */
    .metric-card-corp {
        background: linear-gradient(145deg, rgba(17, 24, 39, 0.95), rgba(15, 23, 42, 0.9));
        border: 1px solid rgba(255, 255, 255, 0.07);
        border-radius: 14px;
        padding: 1.15rem 1.3rem;
        margin-bottom: 0.85rem;
        position: relative;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.35);
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        overflow: hidden;
    }
    .metric-card-corp:hover {
        transform: translateY(-3px);
        border-color: rgba(0, 210, 255, 0.4);
        box-shadow: 0 10px 28px rgba(0, 210, 255, 0.15);
    }
    .metric-card-stripe {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
    }
    .stripe-cyan   { background: linear-gradient(90deg, #00D2FF, #3A7BD5); }
    .stripe-purple { background: linear-gradient(90deg, #7C3AED, #C084FC); }
    .stripe-green  { background: linear-gradient(90deg, #10B981, #34D399); }
    .stripe-amber  { background: linear-gradient(90deg, #F59E0B, #FBBF24); }
    .stripe-red    { background: linear-gradient(90deg, #EF4444, #F87171); }

    .metric-card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 0.4rem;
    }
    .metric-card-title {
        font-size: 0.8rem;
        font-weight: 600;
        color: #94A3B8;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        display: flex;
        align-items: center;
        gap: 0.4rem;
    }
    .metric-card-title .material-symbols-outlined {
        font-size: 1.1rem;
        color: #38BDF8;
    }
    .metric-card-tag {
        font-size: 0.68rem;
        font-weight: 700;
        padding: 0.2rem 0.55rem;
        border-radius: 6px;
        letter-spacing: 0.03em;
    }
    .tag-cyan   { background: rgba(0, 210, 255, 0.15); color: #38BDF8; border: 1px solid rgba(0, 210, 255, 0.3); }
    .tag-purple { background: rgba(124, 58, 237, 0.15); color: #C084FC; border: 1px solid rgba(124, 58, 237, 0.3); }
    .tag-green  { background: rgba(16, 185, 129, 0.15); color: #34D399; border: 1px solid rgba(16, 185, 129, 0.3); }
    .tag-amber  { background: rgba(245, 158, 11, 0.15); color: #FBBF24; border: 1px solid rgba(245, 158, 11, 0.3); }
    .tag-red    { background: rgba(239, 68, 68, 0.15);  color: #F87171; border: 1px solid rgba(239, 68, 68, 0.3); }

    .metric-card-value {
        font-size: 1.85rem;
        font-weight: 800;
        color: #FFFFFF;
        letter-spacing: -0.03em;
        line-height: 1.2;
        margin-bottom: 0.25rem;
    }
    .metric-card-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-size: 0.78rem;
        color: #64748B;
    }
    .metric-delta {
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 0.2rem;
    }
    .delta-positive { color: #34D399; }
    .delta-negative { color: #F87171; }
    .delta-neutral  { color: #94A3B8; }

    /* Caja de Resumen Operacional */
    .corp-insight-box {
        background: linear-gradient(135deg, rgba(30, 41, 59, 0.7) 0%, rgba(15, 23, 42, 0.8) 100%);
        border: 1px solid rgba(0, 210, 255, 0.25);
        border-radius: 12px;
        padding: 1.1rem 1.4rem;
        margin: 1.2rem 0;
        display: flex;
        gap: 1rem;
        align-items: flex-start;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.25);
    }
    .corp-insight-icon {
        background: linear-gradient(135deg, #00D2FF, #3A7BD5);
        color: #FFFFFF;
        width: 38px;
        height: 38px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .corp-insight-content h5 {
        margin: 0 0 0.25rem 0;
        color: #F8FAFC;
        font-size: 0.95rem;
        font-weight: 700;
        letter-spacing: -0.01em;
    }
    .corp-insight-content p {
        margin: 0;
        color: #CBD5E1;
        font-size: 0.85rem;
        line-height: 1.55;
    }
    .corp-insight-content p strong,
    .corp-insight-content strong {
        color: #38BDF8;
        font-weight: 700;
    }

    /* Píldoras de Estado Operacional */
    .status-pill {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.2rem 0.6rem;
        border-radius: 12px;
        font-size: 0.75rem;
        font-weight: 600;
    }
    .status-active   { background: rgba(16, 185, 129, 0.15); color: #34D399; border: 1px solid rgba(16, 185, 129, 0.3); }
    .status-warning  { background: rgba(245, 158, 11, 0.15); color: #FBBF24; border: 1px solid rgba(245, 158, 11, 0.3); }
    .status-critical { background: rgba(239, 68, 68, 0.15);  color: #F87171; border: 1px solid rgba(239, 68, 68, 0.3); }

    /* Estilización del panel de filtros */
    div[data-testid="stSidebar"] {
        background-color: #0D121F;
        border-right: 1px solid rgba(255, 255, 255, 0.06);
    }
    div[data-testid="stSidebar"] h1, 
    div[data-testid="stSidebar"] h2, 
    div[data-testid="stSidebar"] h3,
    div[data-testid="stSidebar"] h4 {
        color: #F8FAFC;
        letter-spacing: -0.01em;
    }

    /* Tablas Dataframe */
    .stDataFrame {
        border-radius: 10px;
        border: 1px solid rgba(255, 255, 255, 0.08);
        overflow: hidden;
    }

    /* Corrección de truncamiento en st.metric */
    div[data-testid="stMetric"] {
        background: rgba(17, 24, 39, 0.7);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 10px;
        padding: 0.75rem 0.9rem;
    }
    div[data-testid="stMetricLabel"] > div,
    div[data-testid="stMetricLabel"] p,
    div[data-testid="stMetricLabel"] {
        white-space: normal !important;
        overflow: visible !important;
        text-overflow: clip !important;
        word-break: break-word !important;
        font-size: 0.75rem !important;
        color: #94A3B8 !important;
        font-weight: 600 !important;
        line-height: 1.25 !important;
    }
    div[data-testid="stMetricValue"] > div,
    div[data-testid="stMetricValue"] {
        white-space: normal !important;
        overflow: visible !important;
        text-overflow: clip !important;
        word-break: break-word !important;
        font-size: 1.05rem !important;
        line-height: 1.3 !important;
        color: #F8FAFC !important;
        font-weight: 700 !important;
    }

    /* Tarjetas de Metadatos de Ficha Técnica / Glosario */
    .indicator-meta-card {
        background: linear-gradient(145deg, rgba(17, 24, 39, 0.85), rgba(15, 23, 42, 0.9));
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 10px;
        padding: 0.85rem 1rem;
        height: 100%;
        min-height: 82px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        transition: all 0.2s ease;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
    }
    .indicator-meta-card:hover {
        border-color: rgba(0, 210, 255, 0.35);
        box-shadow: 0 4px 16px rgba(0, 210, 255, 0.1);
    }
    .indicator-meta-label {
        font-size: 0.72rem;
        font-weight: 600;
        color: #94A3B8;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        display: flex;
        align-items: center;
        gap: 0.35rem;
        margin-bottom: 0.35rem;
        white-space: normal;
        line-height: 1.25;
    }
    .indicator-meta-label .material-symbols-outlined {
        font-size: 0.95rem;
        color: #38BDF8;
    }
    .indicator-meta-value {
        font-size: 1.05rem;
        font-weight: 700;
        color: #F8FAFC;
        line-height: 1.3;
        letter-spacing: -0.01em;
        white-space: normal;
        word-break: break-word;
    }
</style>
"""

def apply_global_styles():
    """Aplica la hoja de estilos personalizada para la plataforma corporativa."""
    st.markdown(CUSTOM_CSS, unsafe_allow_html=True)


def get_plotly_layout_defaults():
    """Configuración estética para gráficos interactivos con Plotly."""
    return dict(
        font=dict(family="Plus Jakarta Sans, sans-serif", color="#CBD5E1", size=12),
        paper_bgcolor="rgba(17, 24, 39, 0.7)",
        plot_bgcolor="rgba(17, 24, 39, 0.7)",
        margin=dict(l=40, r=30, t=45, b=40),
        xaxis=dict(
            gridcolor="rgba(255, 255, 255, 0.06)",
            linecolor="rgba(255, 255, 255, 0.12)",
            tickfont=dict(color="#94A3B8")
        ),
        yaxis=dict(
            gridcolor="rgba(255, 255, 255, 0.06)",
            linecolor="rgba(255, 255, 255, 0.12)",
            tickfont=dict(color="#94A3B8")
        ),
        legend=dict(
            bgcolor="rgba(11, 15, 25, 0.8)",
            bordercolor="rgba(255, 255, 255, 0.08)",
            borderwidth=1,
            font=dict(color="#E2E8F0")
        ),
        hoverlabel=dict(
            bgcolor="#0B0F19",
            font_size=12,
            font_family="Plus Jakarta Sans, sans-serif"
        )
    )


def apply_chart_theme(fig: go.Figure) -> go.Figure:
    """Aplica el tema visual corporativo al gráfico Plotly."""
    defaults = get_plotly_layout_defaults()
    fig.update_layout(**defaults)
    return fig


def format_bold_markdown(text: str) -> str:
    """Convierte sintaxis de formato markdown (**negrita**, *cursiva*, `código`) a etiquetas HTML reales."""
    if not isinstance(text, str):
        return text
    import re
    # **negrita** o __negrita__
    text = re.sub(r'\*\*(.+?)\*\*', lambda m: f"<strong>{m.group(1)}</strong>", text)
    text = re.sub(r'__(.+?)__', lambda m: f"<strong>{m.group(1)}</strong>", text)
    # *cursiva* o _cursiva_
    text = re.sub(r'\*(.+?)\*', lambda m: f"<em>{m.group(1)}</em>", text)
    # `codigo`
    text = re.sub(r'`(.+?)`', lambda m: f"<code>{m.group(1)}</code>", text)
    return text


def render_banner(
    title: str,
    subtitle: str,
    icon: str = "monitoring",
    badge_text: str = "Sistema Sincronizado"
):
    """Renderiza el encabezado corporativo sin emoticones."""
    c_title = format_bold_markdown(title)
    c_subtitle = format_bold_markdown(subtitle)
    html = f"""
    <div class="corp-banner">
        <div class="corp-banner-header">
            <h3 class="corp-banner-title">
                <span class="material-symbols-outlined">{icon}</span>
                {c_title}
            </h3>
            <span class="corp-pill">
                <span class="pulse-dot"></span>
                {badge_text}
            </span>
        </div>
        <p class="corp-banner-subtitle">{c_subtitle}</p>
    </div>
    """
    st.markdown(html, unsafe_allow_html=True)


def render_kpi_card(
    title: str,
    value: str,
    subtitle: str = "",
    delta: str = "",
    delta_type: str = "neutral",
    icon_name: str = "trending_up",
    tag_text: str = "",
    tag_class: str = "tag-cyan",
    stripe_class: str = "stripe-cyan"
):
    """Renderiza tarjetas KPI ejecutivas con Google Material Symbols."""
    c_title = format_bold_markdown(title)
    c_subtitle = format_bold_markdown(subtitle)
    c_delta = format_bold_markdown(delta)
    delta_class = f"delta-{delta_type}"
    tag_html = f'<span class="metric-card-tag {tag_class}">{tag_text}</span>' if tag_text else ''
    delta_html = f'<span class="metric-delta {delta_class}">{c_delta}</span>' if c_delta else ''
    
    html = f"""
    <div class="metric-card-corp">
        <div class="metric-card-stripe {stripe_class}"></div>
        <div class="metric-card-header">
            <span class="metric-card-title">
                <span class="material-symbols-outlined">{icon_name}</span>
                {c_title}
            </span>
            {tag_html}
        </div>
        <div class="metric-card-value">{value}</div>
        <div class="metric-card-footer">
            {delta_html}
            <span>{c_subtitle}</span>
        </div>
    </div>
    """
    st.markdown(html, unsafe_allow_html=True)


def render_diagnostic(
    text: str,
    title: str = "Resumen Operacional del Sistema",
    icon: str = "analytics"
):
    """Renderiza una caja de resumen operacional corporativo con soporte de negrita real."""
    c_title = format_bold_markdown(title)
    c_text = format_bold_markdown(text)
    html = f"""
    <div class="corp-insight-box">
        <div class="corp-insight-icon">
            <span class="material-symbols-outlined">{icon}</span>
        </div>
        <div class="corp-insight-content">
            <h5>{c_title}</h5>
            <p>{c_text}</p>
        </div>
    </div>
    """
    st.markdown(html, unsafe_allow_html=True)


def render_indicator_meta_card(
    label: str,
    value: str,
    icon: str = "info"
):
    """Renderiza una tarjeta de metadatos de ficha técnica sin truncamiento de texto."""
    c_label = format_bold_markdown(label)
    c_value = format_bold_markdown(value)
    html = f"""
    <div class="indicator-meta-card">
        <div class="indicator-meta-label">
            <span class="material-symbols-outlined">{icon}</span>
            {c_label}
        </div>
        <div class="indicator-meta-value">{c_value}</div>
    </div>
    """
    st.markdown(html, unsafe_allow_html=True)


# Aliases directos e intercambiables para prevenir cualquier error de importación
render_ai_banner = render_banner
render_ai_kpi = render_kpi_card
render_ai_diagnostic = render_diagnostic

__all__ = [
    "format_bold_markdown",
    "apply_global_styles",
    "apply_chart_theme",
    "get_plotly_layout_defaults",
    "render_banner",
    "render_kpi_card",
    "render_diagnostic",
    "render_indicator_meta_card",
    "format_currency",
    "format_integer",
    "format_percent",
    "format_mes_anio",
    "MESES_ES_CORTO",
    "MESES_ES_COMPLETO",
    "render_ai_banner",
    "render_ai_kpi",
    "render_ai_diagnostic",
    "COLOR_BG",
    "COLOR_CARD",
    "COLOR_CARD_HOVER",
    "COLOR_PRIMARY",
    "COLOR_SECONDARY",
    "COLOR_ACCENT",
    "COLOR_SUCCESS",
    "COLOR_WARNING",
    "COLOR_DANGER",
    "COLOR_MUTED",
    "COLOR_TEXT",
    "PRIMARY_COLOR",
    "SUCCESS_COLOR",
    "WARNING_COLOR",
    "DANGER_COLOR",
    "INFO_COLOR",
    "PURPLE_COLOR",
]
