# Sistema de Inteligencia de Negocios & Analítica Empresarial
## Centro de Plásticos El Limeño
### Arquitectura Analítica Desacoplada (Data Warehouse OLAP + Streamlit)

---

## 1. DESCRIPCIÓN GENERAL

La carpeta `Dashboards/` contiene la plataforma empresarial de Inteligencia de Negocios y Analítica desarrollada en **Streamlit** y **Plotly** para **Centro de Plásticos El Limeño**.

La solución implementa la **Regla Crítica de Aislamiento**:
- **Cero consultas directas a la base transaccional OLTP**: El sistema consulta exclusivamente el Data Warehouse PostgreSQL (`dw.*`).
- **Cero modificaciones al código preexistente**: El frontend y la API transaccional permanecen 100% inalterados.
- **Diseño Ejecutivo Moderno**: Interfaz en modo oscuro tecnológico con Google Material Symbols, microanimaciones de estado (pulse dots), diagnósticos automatizados y filtros globales dinámicos.

---

## 2. ARQUITECTURA DE LA SOLUCIÓN

```
┌─────────────────────────────────┐
│     SISTEMA TRANSACCIONAL       │
│  PostgreSQL Cloud (OLTP)        │
└────────────────┬────────────────┘
                 │
                 ▼
┌─────────────────────────────────┐
│       PIPELINE ETL PYTHON       │
│  Extracción, Transformación     │
│  y Carga Idempotente (ProcesoETL)│
└────────────────┬────────────────┘
                 │
                 ▼
┌─────────────────────────────────┐
│    DATA WAREHOUSE POSTGRESQL    │
│  Esquema Estrella en Docker     │
│  - dw.fact_ventas               │
│  - dw.fact_compras              │
│  - dw.fact_inventario_snapshot  │
│  - dw.dim_* (dimensiones)       │
│  - 7 Vistas Analíticas          │
└────────────────┬────────────────┘
                 │
                 ▼  (Lectura desacoplada vía SQLAlchemy)
┌─────────────────────────────────┐
│ PLATAFORMA ANALÍTICA STREAMLIT  │
│   (Carpeta Dashboards/)         │
│   - app.py (st.navigation)      │
│   - filters.py                  │
│   - pages/0 a 7                 │
└─────────────────────────────────┘
```

---

## 3. CATÁLOGO DE MÓDULOS DEL MENÚ DE NAVEGACIÓN

La navegación lateral se orquesta dinámicamente mediante `st.navigation`, eliminando enlaces genéricos (`app`) y organizando las secciones en categorías empresariales con iconos Material:

### Sección: Visión Estratégica
- **Panel Principal** (`views/0_Inicio.py`): Vista ejecutiva de KPIs globales, facturación neta ($3.27M), margen ponderado (23.17%), estado del almacén en tiempo real, tendencias temporales y diagnósticos analíticos y preventivos.
- **Rendimiento y Estrategia** (`views/1_Resumen_Ejecutivo_CMI.py`): Matriz de 4 dimensiones de control (Financiera, Clientes, Procesos, Catálogo), cadena causal de alertas preventivas e histórico anual.

### Sección: Operaciones Comerciales
- **Sucursales y Canales** (`views/2_Rendimiento_Sucursales.py`): Comparativa Head-to-Head entre Usulután (Líder en Facturación $1.63M), San Miguel Centro (Líder en Margen 26.8%) y Metrocentro. Matriz de dispersión Ticket vs. Margen.
- **Rentabilidad y Catálogo ABC** (`views/3_Rentabilidad_Productos.py`): Curva de Pareto 80/20, matriz margen vs. volumen para evitar la trampa de artículos inmovilizados, y rankings Top/Bottom.
- **Control de Stock y Alertas** (`views/4_Inventario_y_Alertas.py`): Monitor preventivo de artículos con stock crítico (< 15 unidades), rotación de bodega, DSI y generador exportable de órdenes de reposición.

### Sección: Cadena de Suministro y Finanzas
- **Abastecimiento y Proveedores** (`views/5_Compras_y_Proveedores.py`): Concentración del gasto en fabricantes, facturación de compras, crédito fiscal IVA 13% y cartera de distribuidores.
- **Cuadratura y Balance Fiscal** (`views/6_Balance_IVA_Fiscal.py`): Auditoría tributaria mensual de IVA Débito (ventas) vs. IVA Crédito (compras) y balance neto para declaraciones.
- **Diccionario de Métricas del Negocio** (`views/7_Catalogo_Indicadores.py`): Fichas técnicas formales de los 7 conjuntos de métricas con fórmulas matemáticas institucionales, trazabilidad de datos y cargos responsables.

---

## 4. GUÍA DE INSTALACIÓN Y EJECUCIÓN

### Requisito: Data Warehouse Activo
Asegúrese de que el contenedor Docker del Data Warehouse esté en ejecución:
```bash
wsl docker compose -f DataWareHouse/docker-compose.yml up -d
```

### Ejecución de la Plataforma Streamlit

Puede ejecutar la aplicación tanto desde la raíz del proyecto como desde la carpeta `Dashboards/`:

**Opción A — Desde la raíz del repositorio:**
```bash
python -m streamlit run Dashboards/app.py
```

**Opción B — Desde la carpeta `Dashboards/`:**
```bash
cd Dashboards
python -m streamlit run app.py
```

La plataforma se abrirá de forma automática en su navegador en:
`http://localhost:8501`

---

## 5. ESTRUCTURA DE ARCHIVOS DE `Dashboards/`

```
Dashboards/
├── README.md                          # Documentación ejecutiva de la plataforma
├── requirements.txt                   # Dependencias (streamlit, plotly, pandas, etc.)
├── db.py                              # Acceso optimizado a PostgreSQL con cache y saneamiento
├── styles.py                          # Tokens CSS, temas oscuros ejecutivos y componentes Material Symbols
├── filters.py                         # Motor de filtros interactivos globales con sesión persistente
├── app.py                             # Orquestador principal de navegación (st.navigation)
└── views/                             # Módulos analíticos especializados
    ├── 0_Inicio.py                    # Panel Ejecutivo Principal
    ├── 1_Resumen_Ejecutivo_CMI.py     # Rendimiento Estratégico y Control Multidimensional
    ├── 2_Rendimiento_Sucursales.py    # Desempeño Comercial por Punto de Venta
    ├── 3_Rentabilidad_Productos.py    # Rentabilidad de Catálogo y Pareto ABC
    ├── 4_Inventario_y_Alertas.py      # Control de Stock y Quiebres Preventivos
    ├── 5_Compras_y_Proveedores.py     # Abastecimiento y Red de Proveedores
    ├── 6_Balance_IVA_Fiscal.py        # Cuadratura Tributaria y Balance IVA 13%
    └── 7_Catalogo_Indicadores.py      # Diccionario y Catálogo de Fichas Técnicas
```
