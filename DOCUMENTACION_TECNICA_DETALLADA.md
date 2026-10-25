# DOCUMENTACIÓN TÉCNICA Y METODOLÓGICA INTEGRAL
## Sistema de Data Warehouse, Proceso ETL y Catálogo de Indicadores de Negocio
### Proyecto: InventoryApp (Plásticos Limeño / UES FMO 2026)

---

## 1. RESUMEN EJECUTIVO Y ALCANCE

El presente documento detalla la arquitectura, fundamentos metodológicos, diseño dimensional, desarrollo de código del pipeline ETL en Python, infraestructura en Docker y el catálogo exhaustivo de indicadores analíticos construidos para el sistema **InventoryApp** (*Plásticos Limeño*).

La solución fue desarrollada bajo la **Regla Crítica de Aislamiento**:
- **Cero modificaciones** al código del frontend (`InventaryApp/`).
- **Cero modificaciones** al backend/API (`InventaryAppApi/`).
- **Cero modificaciones** a archivos de entorno o configuraciones del sistema transaccional.
- Acceso a la base de datos operacional en modo de **estricta solo lectura**.
- Toda la solución analítica está contenida de forma independiente en:
  - `DataWareHouse/`: Infraestructura PostgreSQL en Docker y scripts DDL dimensionales.
  - `ProcesoETL/`: Código Python del pipeline ETL (Extracción, Transformación, Carga, Pruebas).
  - `AI_MEMORY.md`: Bitácora persistente del proyecto.

---

## 2. ARQUITECTURA GLOBAL DE LA SOLUCIÓN

La arquitectura implementa un flujo por capas desacopladas que garantiza la gobernanza, reproducibilidad e integridad de los datos:

```
[ Fuente Operacional OLTP ]
  - PostgreSQL Cloud (InventaryAppApi)
  - 15 tablas operacionales inspeccionadas en modo solo lectura
        │
        ▼ (Extracción por lotes vía SQLAlchemy / psycopg2)
[ Capa de Extracción y Transformación: Python ETL ]
  - ProcesoETL/extract.py   -> Carga de datos a DataFrames de Pandas
  - ProcesoETL/transform.py -> Reglas de negocio, tipado, limpieza, cálculo de COGS e IVA
  - ProcesoETL/load.py      -> Carga ordenada e idempotente
        │
        ├────────────────────────────────────────────────────────┐
        ▼                                                        ▼
[ Capa de Aterrizaje / Staging: stg.* ]           [ Capa Dimensional DW: dw.* ]
  - 15 tablas staging intermedias                   - Dimensiones: dim_tiempo, dim_sucursal,
  - Almacena copia exacta sin transformación          dim_producto, dim_cliente, dim_proveedor,
  - Auditoría, conciliación y cuadratura              dim_usuario.
                                                    - Tablas de Hechos: fact_ventas,
                                                      fact_compras, fact_inventario_snapshot.
                                                    - Capa Semántica: 7 vistas analíticas con
                                                      índices B-Tree de alto rendimiento.
```

---

## 3. GUÍA CONCEPTUAL Y GLOSARIO: ENTENDIENDO LOS CONCEPTOS DE NEGOCIO Y FACTURACIÓN

Para entender cómo funciona el Data Warehouse y de dónde salen los cálculos, es indispensable comprender qué ocurre en la vida real cuando un cliente realiza una compra en **Plásticos Limeño**.

### 3.1 El Ejemplo Práctico del Ticket o Factura de Compra
Imaginemos que un cliente visita la sucursal de *Usulután* y compra dos productos distintos en una misma visita:
1. **10 Botes Plásticos Herméticos de 4 Litros** (precio unitario en catálogo: $2.00 c/u; a la empresa le costó comprárselos al fabricante $1.20 c/u).
2. **5 Paquetes de Vasos Plásticos Desechables 16oz** (precio unitario en catálogo: $1.00 c/u; a la empresa le costó comprárselos al fabricante $0.60 c/u).

Cuando la cajera emite la **Factura #1001**, el comprobante de pago se desglosa renglón por renglón:

| # Renglón (Línea) | Producto | Cantidad | Precio Unitario (sin IVA) | Subtotal Línea (Base Neta) | Monto IVA Línea (13%) | Total Línea (Cobrado al Cliente) | Costo Unitario de Compra | Costo Total (COGS) | Utilidad Bruta (Ganancia Neta) | Margen Bruto (%) |
|:---:|---|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|
| **Línea 1** | Bote Hermético 4L | 10 | $2.00 | **$20.00** | $2.60 | **$22.60** | $1.20 | $12.00 | **$8.00** | 40.0% |
| **Línea 2** | Vaso Plástico 16oz | 5 | $1.00 | **$5.00** | $0.65 | **$5.65** | $0.60 | $3.00 | **$2.00** | 40.0% |
| **TOTALES FACTURA** | *(1 Transacción / 2 Líneas)* | **15 unids.** | — | **$25.00** | **$3.25** | **$28.25** | — | **$15.00** | **$10.00** | **40.0%** |

---

### 3.2 Desglose en Lenguaje Sencillo de Cada Término

#### ¿Qué es una "Línea de Detalle" (o Línea de Venta)?
En una factura, cada producto diferente que se compra ocupa un renglón independiente. A ese renglón se le llama **"línea de venta"** o **"línea de detalle"**.
En el ejemplo anterior, hubo **1 sola venta (Factura #1001)**, pero esa venta tuvo **2 líneas de detalle** (los botes y los vasos).

#### ¿Qué es `subtotal_linea`?
Es el valor monetario de ese producto específico **antes de aplicarle impuestos (sin IVA)**:
$$\text{subtotal\_linea} = \text{cantidad} \times \text{precio\_unitario}$$
- Para los botes: $10 \text{ unidades} \times \$2.00 = \$20.00$.
- Para los vasos: $5 \text{ unidades} \times \$1.00 = \$5.00$.
- **¿Por qué es importante?**: Representa el dinero neto que realmente ingresa a la empresa por la venta de ese producto (se le llama *Base Imponible* contable).

#### ¿Qué es `monto_iva_linea`?
Es el Impuesto al Valor Agregado (13% en El Salvador) que corresponde exclusivamente a esa línea de producto:
$$\text{monto\_iva\_linea} = \text{subtotal\_linea} \times 0.13$$
- Para los botes: $\$20.00 \times 0.13 = \$2.60$.
- Para los vasos: $\$5.00 \times 0.13 = \$0.65$.
- **Nota clave**: Este dinero no le pertenece a la empresa; se le cobra al cliente para entregarlo mensualmente al Ministerio de Hacienda (se le llama *Débito Fiscal*).

#### ¿Qué es `total_linea`?
Es la cantidad final de dinero que el cliente paga en caja por ese renglón específico, **incluyendo el impuesto**:
$$\text{total\_linea} = \text{subtotal\_linea} + \text{monto\_iva\_linea}$$
- Para los botes: $\$20.00 + \$2.60 = \$22.60$.
- Para los vasos: $\$5.00 + \$0.65 = \$5.65$.
- La suma de todos los `total_linea` da el monto total que pagó el cliente por su factura ($\$22.60 + \$5.65 = \$28.25$).

#### ¿Qué es `costo_unitario` y `costo_total` (COGS)?
- `costo_unitario`: Lo que a la empresa le costó comprarle 1 producto a su proveedor.
- `costo_total` (o **COGS** - *Cost of Goods Sold* / Costo de la Mercancía Vendida): Es el dinero que la empresa invirtió originalmente para comprar las unidades que se vendieron en esa línea:
  $$\text{costo\_total} = \text{cantidad} \times \text{costo\_unitario}$$
  - Para los botes: $10 \times \$1.20 = \$12.00$.
  - Para los vasos: $5 \times \$0.60 = \$3.00$.

#### ¿Qué es `utilidad_bruta`?
Es la ganancia directa que ese producto le deja a la empresa (el ingreso neto que entró menos lo que costó comprar la mercadería):
$$\text{utilidad\_bruta} = \text{subtotal\_linea} - \text{costo\_total}$$
- Para los botes: $\$20.00 - \$12.00 = \$8.00$ de ganancia.
- Para los vasos: $\$5.00 - \$3.00 = \$2.00$ de ganancia.

#### ¿Qué es `margen_bruto_porcentaje`?
Indica qué porcentaje de cada dólar vendido se convierte en ganancia directa:
$$\text{Margen \%} = \left(\frac{\text{Utilidad Bruta}}{\text{Subtotal Neto}}\right) \times 100$$
- Para los botes: $\left(\frac{\$8.00}{\$20.00}\right) \times 100 = 40.0\%$.

#### ¿Qué es el `ticket_promedio`?
Mide cuánto dinero gasta en promedio un cliente cada vez que realiza una compra completa en una tienda:
$$\text{Ticket Promedio} = \frac{\text{Suma de Ingresos Totales de Todas las Facturas}}{\text{Número de Facturas o Clientes Atendidos}}$$
Si en un mes la sucursal de San Miguel atendió 3,066 ventas y facturó en total $1,080,756.65, el ticket promedio es:
$$\frac{\$1,080,756.65}{3,066} = \$352.50 \text{ por compra}$$

---

### 3.3 ¿Por qué el Data Warehouse almacena los datos a nivel de Línea (`dw.fact_ventas`) y no solo el total de la factura?
Esta decisión de diseño se denomina en ingeniería de datos **Granularidad Atómica**:
- Si solo guardáramos el total general de la factura ($28.25), sabríamos cuánto vendió la tienda en total, pero **sería imposible saber**:
  - ¿Cuál es el producto de plástico que más se vende?
  - ¿Qué categoría de productos genera mayor margen de ganancia?
  - ¿Cuáles productos se están quedando sin existencias en bodega?
- Al almacenar cada **línea de detalle** vinculada con la dimensión de producto (`dim_producto`), podemos hacer análisis tanto a nivel micro (por producto específico o SKU) como a nivel macro (por factura completa, por sucursal o por mes).

---

### 3.4 Glosario de Claves y Términos Técnicos del Data Warehouse
- **`id_bk` (Business Key / Clave de Negocio)**: Es el número o identificador original que tenía el registro en la aplicación del negocio (ej. el ID que tenía el cliente o el producto en la base de datos operacional).
- **`*_sk` (Surrogate Key / Clave Subrogada)**: Es una clave artificial numérica (1, 2, 3...) que crea el Data Warehouse para independizar el análisis de lo que ocurra en el sistema operacional. Si en el sistema de ventas eliminan o modifican un ID, el Data Warehouse no se rompe porque utiliza sus propias claves subrogadas.
- **`dim_*` (Tablas de Dimensión)**: Contienen el contexto del negocio: ¿Quién compró? (`dim_cliente`), ¿Qué producto llevó? (`dim_producto`), ¿En qué tienda? (`dim_sucursal`), ¿Cuándo ocurrió? (`dim_tiempo`), ¿Quién lo atendió? (`dim_usuario`).
- **`fact_*` (Tablas de Hechos)**: Contienen los eventos numéricos y cuantitativos del negocio (ventas, compras, mediciones de inventario).
- **`stock_bajo_alerta`**: Una bandera booleana (`TRUE`/`FALSE`) que se enciende automáticamente en el Data Warehouse cuando el stock de un producto en una sucursal es menor a 15 unidades, avisando al encargado que debe reabastecer.

---

## 4. TAXONOMÍA Y MARCO TEÓRICO DE INDICADORES (UNIDAD 7)

Con base en el documento curricular oficial **"Módulo IV · Unidad 7: Indicadores, Métricas y Análisis"** (Diplomado en Administración de Bases de Datos e Inteligencia de Negocios, UES FMO 2026), se establecen los siguientes fundamentos conceptuales:

### 4.1 Medida, Métrica e Indicador
- **Medida**: Valor numérico observado directamente de los hechos, sin interpretación adicional (ej. *el importe cobrado en una venta: $85.40*).
- **Métrica**: Valor derivado de una o varias medidas mediante un cálculo definido (ej. *el ticket promedio del mes: $355.84*).
- **Indicador**: Una métrica dotada de contexto institucional: tiene una **meta cuantitativa**, una **banda de tolerancia**, una **dirección de mejora**, un **cargo responsable** y una **decisión asociada** (ej. *Ticket promedio mensual, meta $350.00, responsable Jefatura Comercial, acción: reentrenamiento en venta cruzada si cae a semáforo rojo*).

### 4.2 La Taxonomía de David Parmenter
Parmenter (2015) sostiene que el error más común es denominar "KPI" a cualquier métrica. Se clasifican cuatro categorías rigurosas:
1. **Indicador Clave de Resultado (KRI - Key Result Indicator)**:
   - *¿Qué responde?*: Cómo le ha ido a la organización desde una perspectiva global de alto nivel.
   - *Destinatario*: Junta Directiva, Consejo de Administración, Gerencia General.
   - *Características*: Sintetiza el resultado acumulado de múltiples áreas. No permite una acción operativa inmediata de lunes por la mañana.
   - *Ejemplos en el proyecto*: Utilidad Bruta Acumulada, Margen Bruto Global (%).
2. **Indicador de Resultado (RI - Result Indicator)**:
   - *¿Qué responde?*: Qué se ha logrado como consecuencia del trabajo conjunto de varios equipos.
   - *Destinatario*: Gerencias de área y Jefaturas departamentales.
   - *Características*: Métricas financieras y de volumen. Miden lo que ya ocurrió (rezagados).
   - *Ejemplos en el proyecto*: Total de Ingresos Brutos por Ventas, Ventas por Sucursal, Compras por Proveedor.
3. **Indicador de Desempeño (PI - Performance Indicator)**:
   - *¿Qué responde?*: Qué está haciendo un equipo o proceso concreto identificable.
   - *Destinatario*: Jefaturas operativas y supervisores de tienda/almacén.
   - *Características*: Permite reasignar recursos o ajustar tácticas a nivel mensual o semanal.
   - *Ejemplos en el proyecto*: Ticket Promedio por Venta, Balance Neto de IVA Fiscal.
4. **Indicador Clave de Desempeño (KPI - Key Performance Indicator)**:
   - *¿Qué responde?*: Qué debe hacerse inmediatamente para mejorar el desempeño de forma significativa antes de que se dañe el resultado final.
   - *Destinatario*: Personal operativo de primera línea (dependientes, bodegueros, cajeros).
   - *Las 7 Características de Parmenter*:
     1. **Es no financiero**: No se expresa en dinero. En cuanto se mide en dinero, se vuelve un indicador de resultado que llega tarde para corregir.
     2. **Medición frecuente**: Diario, por turno o continuo.
     3. **La dirección actúa sobre él**: Conocido e intervenido de inmediato.
     4. **Comprensible por todo el personal**: Todo colaborador sabe qué acción correctiva ejecutar.
     5. **Responsabilidad asignada a un equipo o cargo concreto**.
     6. **Impacto significativo**: Afecta a más de un factor crítico de éxito del Cuadro de Mando.
     7. **No induce comportamientos perversos**.
   - *Ejemplo en el proyecto*: **Productos con Stock Crítico (< 15 unidades)** y **Tasa de Rotación de Inventarios**.

### 4.3 Cuadro de Mando Integral (Kaplan & Norton)
Se distribuyen las métricas en las cuatro perspectivas interconectadas:
1. **Perspectiva Financiera**: Ingresos Brutos, Utilidad Bruta, Margen Bruto %, Débito/Crédito Fiscal IVA.
2. **Perspectiva del Cliente**: Ticket Promedio por Visita, Ventas Netas por Canal/Sucursal.
3. **Perspectiva de Procesos Internos**: Alertas de Quiebre de Stock (<15), Rotación de Inventario, Valorización de Existencias en Bodega.
4. **Perspectiva de Aprendizaje y Crecimiento**: Productividad por Usuario/Operador y Cobertura de Catálogo.

### 4.4 Indicadores Adelantados (Leading) vs. Rezagados (Lagging) y Cadena Causal
- **Rezagado (Lagging)**: Mide un resultado posterior (ej. *Ingresos mensuales de ventas*). Es fácil de cuantificar, pero cuando se detecta una caída, el mes ya terminó y no se puede recuperar el dinero perdido.
- **Adelantado (Leading)**: Mide una actividad que precede causalmente al resultado (ej. *Alertas de Stock Crítico*). Si un producto de alta demanda cae a menos de 15 unidades en bodega, anticipa ventas perdidas por desabasto semanas antes de que se desplome el ingreso financiero.

**Cadena Causal de InventoryApp**:
```
[ INDICADOR ADELANTADO ]                      [ PROCESO INTERNO ]                     [ INDICADOR REZAGADO ]
Productos con Stock Crítico (< 15)  ───►  Desabasto en góndola / sucursal  ───►  Pérdida de ventas e ingresos
(Diario, accionable por bodeguero)          (Ventas no concretadas)                 (Mensual, evaluativo directivo)
```

### 4.5 Criterio SMART (George Doran 1981)
Cada indicador fue formulado bajo los 5 pilares:
- **S (Specific)**: Mide un único fenómeno operacional sin mezclar conceptos.
- **M (Measurable)**: Derivado de datos verificables y disponibles en la base de datos.
- **A (Assignable)**: Asignado a un **cargo**, nunca a un nombre propio (las personas rotan, el cargo permanece).
- **R (Realistic)**: Metas calibradas con base en la capacidad operativa real.
- **T (Time-related)**: Horizonte temporal y fecha de corte claramente delimitados.

### 4.6 Principio de Aditividad y Error del "Promedio de Promedios"
Una métrica de razón o porcentaje (como el Ticket Promedio o el Margen %) **no es aditiva**:
$$\text{Ticket Promedio} = \frac{\sum \text{Ingresos Totales}}{\sum \text{Transacciones Totales}} \neq \text{AVG}(\text{Tickets Promedio por Sucursal})$$
Promediar razones otorga el mismo peso ponderado a sucursales o períodos con volúmenes radicalmente desiguales, distorsionando la cifra final. En la capa semántica de este proyecto, todas las razones y porcentajes se recalculan rigurosamente sumando numeradores y denominadores independientes.

### 4.7 Ley de Goodhart, Efectos Perversos y Métricas de Contrapeso
> *"Cuando una medida se convierte en objetivo de control, deja de ser una buena medida."* (Goodhart 1975 / Strathern 1997).

Ningún indicador debe presentarse aislado, pues incentiva comportamientos oportunistas:
- Si se presiona únicamente el **Ticket Promedio**, los vendedores pueden desalentar o no registrar ventas pequeñas para no perjudicar su promedio. **Contrapeso**: Se vigila en paralelo el **Total de Transacciones (Tickets)** y las **Unidades Vendidas**.
- Si se presiona únicamente el **Margen de Utilidad**, el personal puede promover únicamente productos caros desatendiendo el volumen de productos populares de plástico. **Contrapeso**: Se monitorea la **Rotación de Inventario** y las **Alertas de Stock Crítico**.

---

## 5. CATÁLOGO TÉCNICO Y FICHAS DETALLADAS DE INDICADORES

A continuación se presentan las fichas técnicas formales de los 7 conjuntos de indicadores implementados en la base analítica:

---

### FICHA 1: KPIs GENERALES DE VENTAS Y RENTABILIDAD (`dw.vw_kpis_generales`)

| Atributo | Especificación |
|---|---|
| **Nombre** | Panel de Rendimiento Financiero y Eficiencia Comercial |
| **Clasificación Parmenter** | • Total Transacciones, Ingresos, COGS: **Indicador de Resultado (RI)**<br>• Utilidad Bruta, Margen Bruto %: **Indicador Clave de Resultado (KRI)**<br>• Ticket Promedio: **Indicador de Desempeño (PI)** |
| **Nivel Organizacional** | Estratégico (Dirección General / Junta Directiva) |
| **Perspectiva CMI** | Financiera |
| **Naturaleza Temporal** | Rezagado (Lagging) |
| **Cargo Responsable** | Gerencia General y Gerencia Financiera |
| **Frecuencia de Revisión** | Mensual / Cierre Contable |
| **Fuente de Datos** | `dw.fact_ventas` |
| **Propiedad Aditiva** | • Ingresos, COGS, Utilidad, Transacciones: **Aditivo** (sumable entre sucursales y meses)<br>• Margen Bruto %, Ticket Promedio: **No Aditivo** (debe recalcularse desde sus sumatorias) |

#### Fórmulas Matemáticas y Explicación Paso a Paso:
1. **Total Ingresos Brutos ($)**:
   Suma de todo el dinero pagado por los clientes en todas las líneas de venta del período:
   $$\text{Ingresos Brutos} = \sum \text{total\_linea}$$
2. **Subtotal Ventas Neto (Base Imponible sin IVA)**:
   Suma del dinero de las ventas antes de sumarle el impuesto de IVA (el dinero real que queda en la empresa):
   $$\text{Subtotal Neto} = \sum \text{subtotal\_linea}$$
3. **Débito Fiscal IVA (13%)**:
   El monto total de IVA recaudado de los clientes para reportar a Hacienda:
   $$\text{IVA Débito} = \sum \text{monto\_iva\_linea} = \text{Subtotal Neto} \times 0.13$$
4. **Costo de Mercancía Vendida (COGS - Cost of Goods Sold)**:
   Cuánto costó comprar todos los productos que se lograron vender en el período:
   $$\text{COGS} = \sum (\text{cantidad} \times \text{costo\_unitario})$$
5. **Utilidad Bruta ($)**:
   Ingreso neto de la empresa menos el costo de la mercancía:
   $$\text{Utilidad Bruta} = \text{Subtotal Neto} - \text{COGS}$$
6. **Margen Bruto Porcentual (%)**:
   Qué porcentaje del ingreso neto es ganancia:
   $$\text{Margen Bruto \%} = \left( \frac{\text{Utilidad Bruta}}{\text{Subtotal Neto}} \right) \times 100$$
7. **Ticket Promedio ($)**:
   Gasto promedio por cliente o factura:
   $$\text{Ticket Promedio} = \frac{\text{Ingresos Brutos Totales}}{\text{Conteo de Facturas Distintas (COUNT DISTINCT venta\_id\_bk)}}$$

#### Expresión SQL Implementada ([04_create_indexes_and_views.sql](file:///c:/Users/user/Desktop/repo_proyecto_final_diplomado/DataWareHouse/sql/04_create_indexes_and_views.sql#L13-L27)):
```sql
CREATE OR REPLACE VIEW dw.vw_kpis_generales AS
SELECT
    COUNT(DISTINCT venta_id_bk) AS total_transacciones,
    SUM(cantidad) AS total_unidades_vendidas,
    ROUND(SUM(subtotal_linea), 2) AS subtotal_ventas,
    ROUND(SUM(monto_iva_linea), 2) AS total_iva_debito,
    ROUND(SUM(total_linea), 2) AS total_ingresos_brutos,
    ROUND(SUM(costo_total), 2) AS costo_mercancia_vendida_cogs,
    ROUND(SUM(utilidad_bruta), 2) AS utilidad_bruta_total,
    ROUND((SUM(utilidad_bruta) / NULLIF(SUM(subtotal_linea), 0)) * 100, 2) AS margen_bruto_porcentaje,
    ROUND(SUM(total_linea) / NULLIF(COUNT(DISTINCT venta_id_bk), 0), 2) AS ticket_promedio
FROM dw.fact_ventas;
```

#### Valores Reales Obtenidos en el Data Warehouse:
- Transacciones Pagadas: **9,201**
- Unidades Vendidas: **174,835**
- Subtotal Neto: **$2,897,415.49**
- IVA Débito (13%): **$376,664.01**
- Ingresos Brutos Totales: **$3,274,079.50**
- Costo de Mercancía (COGS): **$2,226,136.48**
- Utilidad Bruta: **$671,279.01**
- Margen Bruto: **23.17%**
- Ticket Promedio: **$355.84**

---

### FICHA 2: RENDIMIENTO COMERCIAL POR SUCURSAL (`dw.vw_ventas_por_sucursal`)

| Atributo | Especificación |
|---|---|
| **Nombre** | Rendimiento y Rentabilidad por Sucursal Física |
| **Clasificación Parmenter** | **Indicador de Resultado (RI)** por unidad de negocio |
| **Nivel Organizacional** | Táctico / Operativo (Jefes de Sucursal y Gerencia de Operaciones) |
| **Perspectiva CMI** | Clientes y Procesos Internos |
| **Naturaleza Temporal** | Rezagado (Lagging) |
| **Cargo Responsable** | Jefe de Sucursal (Usulután, San Miguel Centro, San Miguel Metrocentro) |
| **Frecuencia de Revisión** | Semanal / Mensual |
| **Fuente de Datos** | `dw.fact_ventas` JOIN `dw.dim_sucursal` |
| **Propiedad Aditiva** | Montos y conteos aditivos; Margen y Ticket no aditivos |

#### Fórmulas Matemáticas:
$$\text{Margen Sucursal \%} = \left( \frac{\sum \text{utilidad\_bruta}_{\text{sucursal}}}{\sum \text{subtotal\_linea}_{\text{sucursal}}} \right) \times 100$$
$$\text{Ticket Promedio Sucursal} = \frac{\sum \text{total\_linea}_{\text{sucursal}}}{\text{COUNT(DISTINCT } \text{venta\_id\_bk}_{\text{sucursal}})}$$

#### Expresión SQL Implementada ([04_create_indexes_and_views.sql](file:///c:/Users/user/Desktop/repo_proyecto_final_diplomado/DataWareHouse/sql/04_create_indexes_and_views.sql#L30-L46)):
```sql
CREATE OR REPLACE VIEW dw.vw_ventas_por_sucursal AS
SELECT
    s.id_bk AS id_sucursal,
    s.nombre AS sucursal,
    COUNT(DISTINCT v.venta_id_bk) AS transacciones,
    SUM(v.cantidad) AS unidades_vendidas,
    ROUND(SUM(v.subtotal_linea), 2) AS subtotal_ventas,
    ROUND(SUM(v.total_linea), 2) AS total_ventas,
    ROUND(SUM(v.costo_total), 2) AS costo_total,
    ROUND(SUM(v.utilidad_bruta), 2) AS utilidad_bruta,
    ROUND((SUM(v.utilidad_bruta) / NULLIF(SUM(v.subtotal_linea), 0)) * 100, 2) AS margen_porcentaje,
    ROUND(SUM(v.total_linea) / NULLIF(COUNT(DISTINCT v.venta_id_bk), 0), 2) AS ticket_promedio
FROM dw.fact_ventas v
JOIN dw.dim_sucursal s ON v.sucursal_sk = s.sucursal_sk
GROUP BY s.id_bk, s.nombre;
```

#### Hallazgos y Resultados Reales:
1. **Sucursal Usulután**: Genera el mayor volumen ($1,633,508.87) con 3,067 ventas y un ticket promedio elevado de $532.61, con margen de 23.12%.
2. **Sucursal San Miguel - Metrocentro**: 3,066 transacciones, $1,080,756.65 facturados, margen de 21.36%, ticket de $352.50.
3. **Sucursal Matriz Centro**: Menor facturación absoluta ($559,813.99) debido a compras minoristas frecuentes (ticket $182.47), pero alcanza el **mayor margen de rentabilidad: 26.80%**.

---

### FICHA 3: ALERTAS DE STOCK CRÍTICO (`dw.vw_alertas_stock_critico`)

| Atributo | Especificación |
|---|---|
| **Nombre** | Monitor Preventivo de Riesgo de Desabasto y Quiebre de Stock |
| **Clasificación Parmenter** | **Indicador Clave de Desempeño (KPI)** genuino (no financiero, diario, accionable) |
| **Nivel Organizacional** | Operativo (Encargados de Bodega, Compradores, Jefes de Almacén) |
| **Perspectiva CMI** | Procesos Internos |
| **Naturaleza Temporal** | **Adelantado (Leading)**: Anticipa y previene la pérdida de ventas futuras |
| **Cargo Responsable** | Encargado de Bodega e Inventarios |
| **Frecuencia de Revisión** | Diaria / Continua en cada apertura de turno |
| **Fuente de Datos** | `dw.fact_inventario_snapshot` JOIN `dw.dim_producto`, `dw.dim_sucursal` |
| **Regla de Negocio** | $\text{stock\_actual} < 15 \implies \text{Alerta Activa (Crítico)}$ |

#### Expresión SQL Implementada ([04_create_indexes_and_views.sql](file:///c:/Users/user/Desktop/repo_proyecto_final_diplomado/DataWareHouse/sql/04_create_indexes_and_views.sql#L125-L141)):
```sql
CREATE OR REPLACE VIEW dw.vw_alertas_stock_critico AS
SELECT
    s.nombre AS sucursal,
    p.sku,
    p.nombre AS producto,
    p.categoria,
    i.stock_actual,
    p.stock_minimo,
    ROUND(i.costo_promedio, 2) AS costo_promedio,
    ROUND(i.precio_venta, 2) AS precio_venta,
    ROUND(i.valor_inventario_costo, 2) AS valor_stock_costo
FROM dw.fact_inventario_snapshot i
JOIN dw.dim_producto p ON i.producto_sk = p.producto_sk
JOIN dw.dim_sucursal s ON i.sucursal_sk = s.sucursal_sk
WHERE i.stock_bajo_alerta = TRUE
ORDER BY i.stock_actual ASC;
```

#### Resultados Reales:
- Se detectaron exactamente **1,660 combinaciones producto-sucursal** en estado crítico ($<15$ unidades), permitiendo emitir órdenes de compra inmediatas antes de generar quiebres de inventario.

---

### FICHA 4: RENTABILIDAD POR PRODUCTO Y CATEGORÍA (`dw.vw_rentabilidad_por_producto`)

| Atributo | Especificación |
|---|---|
| **Nombre** | Análisis de Margen de Contribución por Producto |
| **Clasificación Parmenter** | **Indicador de Desempeño (PI)** |
| **Nivel Organizacional** | Táctico (Jefatura de Compras y Categorías) |
| **Perspectiva CMI** | Financiera y Procesos |
| **Cargo Responsable** | Category Manager / Jefe de Compras |
| **Fuente de Datos** | `dw.fact_ventas`, `dw.dim_producto` |

#### Fórmulas Matemáticas:
$$\text{Ingresos Netos}_p = \sum \text{subtotal\_linea}_p$$
$$\text{Utilidad Bruta}_p = \text{Ingresos Netos}_p - \sum (\text{cantidad}_p \times \text{costo\_unitario}_p)$$
$$\text{Margen \%}_p = \left( \frac{\text{Utilidad Bruta}_p}{\text{Ingresos Netos}_p} \right) \times 100$$

#### Comportamiento Perverso Previsible y Contrapeso:
- *Efecto Perverso*: Promover artículos con margen porcentual muy alto pero nula rotación, dejando estancado el capital de trabajo.
- *Contrapeso*: Ordenar tanto por margen (%) como por masa de utilidad bruta ($) e incluir las unidades vendidas en la misma vista.

---

### FICHA 5: ROTACIÓN DE INVENTARIO Y COGS (`dw.vw_rotacion_inventario`)

| Atributo | Especificación |
|---|---|
| **Nombre** | Ratio de Rotación de Stock y Cobertura de Ventas |
| **Clasificación Parmenter** | **Indicador de Desempeño (PI)** |
| **Nivel Organizacional** | Táctico / Logístico |
| **Perspectiva CMI** | Procesos Internos |
| **Cargo Responsable** | Jefe de Logística y Cadena de Suministro |

#### Fórmulas Matemáticas:
$$\text{Índice de Rotación} = \frac{\text{Unidades Totales Vendidas}}{\text{NULLIF}(\text{Stock Actual en Bodega}, 0)}$$

---

### FICHA 6: BALANCE DE IVA FISCAL (`dw.vw_balance_iva_fiscal`)

| Atributo | Especificación |
|---|---|
| **Nombre** | Cuadratura Fiscal Tributaria (Débito vs. Crédito IVA 13%) |
| **Clasificación Parmenter** | **Indicador de Resultado (RI)** Financiero / Fiscal |
| **Nivel Organizacional** | Táctico / Cumplimiento Tributario |
| **Perspectiva CMI** | Financiera |
| **Cargo Responsable** | Contador General / Jefe de Finanzas |

#### Fórmulas Matemáticas:
$$\text{IVA Débito (Ventas)} = \sum \text{subtotal\_ventas} \times 0.13$$
$$\text{IVA Crédito (Compras)} = \sum \text{subtotal\_compras} \times 0.13$$
$$\text{Balance Fiscal IVA} = \text{IVA Débito} - \text{IVA Crédito}$$
- Si $\text{Balance} > 0$: Impuesto neto a pagar al Ministerio de Hacienda.
- Si $\text{Balance} < 0$: Remanente de crédito fiscal a favor de la empresa.

---

### FICHA 7: GASTO Y VOLUMEN POR PROVEEDOR (`dw.vw_compras_por_proveedor`)

| Atributo | Especificación |
|---|---|
| **Nombre** | Concentración del Gasto de Abastecimiento en Proveedores |
| **Clasificación Parmenter** | **Indicador de Resultado (RI)** |
| **Nivel Organizacional** | Táctico / Estratégico (Compras y Negociaciones) |
| **Perspectiva CMI** | Procesos Internos / Proveedores |
| **Cargo Responsable** | Jefe de Compras |

---

## 6. DISEÑO E IMPLEMENTACIÓN DEL DATA WAREHOUSE (`DataWareHouse/`)

### 6.1 Infraestructura en Docker Compose
En el archivo [`DataWareHouse/docker-compose.yml`](file:///c:/Users/user/Desktop/repo_proyecto_final_diplomado/DataWareHouse/docker-compose.yml) se configuró:
- **Imagen Base**: `postgres:16-alpine` (oficial, ligera, segura).
- **Nombre de Servicio**: `dw_postgres`.
- **Puerto de Host Expuesto**: `5433:5432` (evita conflicto con el puerto estándar `5432` si existiera otra base de datos en la máquina anfitriona).
- **Persistencia**: Volumen Docker nombrado `dw_postgres_data`.
- **Healthcheck**: Inspección periódica con `pg_isready -U dw_admin -d inventory_dw`.
- **Montaje Automático de Inicialización**: Directorio `./sql` montado en `/docker-entrypoint-initdb.d` para inicialización transparente al primer inicio.

### 6.2 Capa Staging (`stg.*`)
El script [`01_create_schemas_and_staging.sql`](file:///c:/Users/user/Desktop/repo_proyecto_final_diplomado/DataWareHouse/sql/01_create_schemas_and_staging.sql) crea los esquemas `stg` y `dw`. Contiene 15 tablas espejo de la base operacional:
`stg.rol`, `stg.categoria`, `stg.departamento`, `stg.municipio`, `stg.sucursal`, `stg.proveedor`, `stg.cliente`, `stg.usuario`, `stg.producto`, `stg.producto_sucursal`, `stg.venta`, `stg.venta_detalle`, `stg.compra`, `stg.compra_detalle`, `stg.movimiento_inventario`.

### 6.3 Dimensiones y Hechos (`dw.*`)
- [`02_create_dimensions.sql`](file:///c:/Users/user/Desktop/repo_proyecto_final_diplomado/DataWareHouse/sql/02_create_dimensions.sql):
  - `dim_tiempo`: Generada mediante `generate_series('2015-01-01'::date, '2030-12-31'::date, '1 day'::interval)` garantizando que transacciones históricas desde 2016 hasta 2026 encuentren su clave foránea sin descartes silenciosos. Incluye registro comodín `fecha_sk = 0` para valores nulos.
  - `dim_sucursal`, `dim_producto`, `dim_cliente`, `dim_proveedor`, `dim_usuario`: Incorporan clave subrogada serial (`*_sk`), clave natural operacional (`id_bk`), atributos categóricos y marcas de auditoría temporal (`fecha_carga`, `fecha_actualizacion`).
- [`03_create_facts.sql`](file:///c:/Users/user/Desktop/repo_proyecto_final_diplomado/DataWareHouse/sql/03_create_facts.sql):
  - `fact_ventas`: Contiene FKs hacia `dim_tiempo`, `dim_sucursal`, `dim_producto`, `dim_cliente`, `dim_usuario`. Restricción de unicidad: `UNIQUE (venta_id_bk, producto_sk)` para asegurar cargas repetibles sin duplicados.
  - `fact_compras`: FKs hacia `dim_tiempo`, `dim_sucursal`, `dim_producto`, `dim_proveedor`, `dim_usuario`. `UNIQUE (compra_id_bk, producto_sk)`.
  - `fact_inventario_snapshot`: FKs hacia `dim_tiempo`, `dim_sucursal`, `dim_producto`. `UNIQUE (fecha_sk, sucursal_sk, producto_sk)`.
- [`04_create_indexes_and_views.sql`](file:///c:/Users/user/Desktop/repo_proyecto_final_diplomado/DataWareHouse/sql/04_create_indexes_and_views.sql):
  - Índices B-Tree en todas las claves foráneas de hechos para optimizar joins.
  - Vistas analíticas con `ROUND()` a 2 decimales y control de divisiones por cero mediante `NULLIF()`.

---

## 7. EXPLICACIÓN DETALLADA DEL CÓDIGO DEL PROCESO ETL (`ProcesoETL/`)

El pipeline se estructuró modularmente en Python siguiendo las mejores prácticas de ingeniería de datos:

### 7.1 Gestión Segura de Configuración ([`config.py`](file:///c:/Users/user/Desktop/repo_proyecto_final_diplomado/ProcesoETL/config.py))
- **Mecanismo No Invasivo**: Localiza y analiza el archivo `InventaryAppApi/.env` utilizando `python-dotenv` en modo estrictamente de solo lectura.
- **Detección de Conexión Origen**: Extrae la variable `DB_URL` de producción.
- **Construcción de Motores SQLAlchemy**:
  - `get_oltp_engine()`: Crea el pool de conexiones hacia la base operacional.
  - `get_dw_engine()`: Crea la conexión hacia el Data Warehouse local en el puerto parametrizado (`5433`).
- **Seguridad**: En ningún momento se imprimen contraseñas o credenciales en los logs.

### 7.2 Extracción de Datos ([`extract.py`](file:///c:/Users/user/Desktop/repo_proyecto_final_diplomado/ProcesoETL/extract.py))
- Función `extract_all(oltp_engine)`:
  - Itera sobre las 15 tablas de negocio: `roles`, `categorias`, `departamentos`, `municipios`, `sucursals`, `proveedors`, `clientes`, `usuarios`, `productos`, `producto_sucursal`, `ventas`, `ventas_detalles`, `compras`, `compras_detalles`, `movimiento_inventarios`.
  - Utiliza `pd.read_sql_table()` para cargar los datos en DataFrames en memoria.
  - Emite logs estructurados con la cantidad exacta de filas extraídas por cada entidad.

### 7.3 Transformación de Negocio ([`transform.py`](file:///c:/Users/user/Desktop/repo_proyecto_final_diplomado/ProcesoETL/transform.py))
Contiene las funciones modulares de transformación:
1. `transform_dim_sucursales(df_sucursales)`:
   - Limpia espacios, renombra `id` a `id_bk`, estandariza nombre, dirección y teléfono.
2. `transform_dim_productos(df_productos, df_categorias)`:
   - Resuelve la discrepancia de esquemas: la columna de categorías en el OLTP se llama `name` (no `nombre`). Realiza un join por la izquierda para enriquecer cada producto con el nombre de su categoría.
   - Aplica conversiones a tipo float en `precio_venta` y `costo_promedio`.
3. `transform_dim_clientes(df_clientes, df_departamentos, df_municipios)`:
   - Resuelve nombres de columnas (`clientes.correo` en vez de `email`). Desnormaliza la ubicación uniendo cliente con su municipio y departamento correspondiente.
4. `transform_dim_proveedores(df_proveedores, df_departamentos, df_municipios)`:
   - Asocia cada proveedor con su ubicación geográfica y estructura de contacto.
5. `transform_dim_usuarios(df_usuarios, df_roles)`:
   - Desnormaliza el nombre del rol asignado a cada usuario para auditoría de ventas.
6. `transform_fact_ventas(df_ventas, df_detalles, df_productos)`:
   - **Regla Crítica de Negocio**: Filtra estrictamente `status = 'pagada'` (descarta ventas anuladas o pendientes).
   - Realiza join entre cabecera y detalle por `venta_id`.
   - Extrae la clave de fecha YYYYMMDD (`fecha_sk`).
   - Calcula métricas financieras a nivel de línea:
     - `costo_unitario` obtenido del catálogo o detalle.
     - `costo_total = cantidad * costo_unitario`.
     - `utilidad_bruta = subtotal_linea - costo_total`.
7. `transform_fact_compras(df_compras, df_detalles)`:
   - Filtra compras con `status = 'pagado'`.
   - Calcula fecha subrogada, subtotales, IVA y total de la línea de abastecimiento.
8. `transform_fact_inventario(df_prod_sucursal, df_productos)`:
   - Detecta la columna real `stock_actual` (y no `stock`).
   - Calcula valor de inventario al costo (`stock_actual * costo_promedio`) y valor a la venta (`stock_actual * precio_venta`).
   - **Regla de Alerta de Stock**: Asigna `stock_bajo_alerta = (stock_actual < 15)`.

### 7.4 Carga Idempotente ([`load.py`](file:///c:/Users/user/Desktop/repo_proyecto_final_diplomado/ProcesoETL/load.py))
- `load_staging(staging_dict, dw_engine)`:
  - Carga los DataFrames a las tablas `stg.*` usando truncado controlado previo para garantizar la idempotencia de la foto operacional.
- `load_dimensions(...)`:
  - Aplica la técnica de **Upsert Dimensional** mediante `ON CONFLICT (id_bk) DO UPDATE SET ... fecha_actualizacion = NOW()`. Si un producto cambia de precio o categoría en el sistema operacional, se actualiza en el DW sin duplicar la clave subrogada.
- `load_fact_ventas`, `load_fact_compras`, `load_fact_inventario`:
  - **Mapeo de Claves Subrogadas**: Consulta en memoria los diccionarios `{id_bk: *_sk}` de las dimensiones recién sincronizadas para reemplazar las claves de negocio por las claves surrogadas analíticas (`_sk`).
  - **Carga Idempotente por Lotes**: Utiliza sentencias `INSERT INTO dw.fact_* (...) VALUES ... ON CONFLICT (...) DO NOTHING` enviadas en bloques de 1,000 registros para evitar sobrecarga de memoria y fallos por bloqueos de red.

### 7.5 Orquestador Principal ([`main.py`](file:///c:/Users/user/Desktop/repo_proyecto_final_diplomado/ProcesoETL/main.py))
- Coordina secuencialmente las etapas: Extracción -> Carga a Staging -> Transformación Dimensional -> Carga a Dimensiones -> Transformación de Hechos -> Carga a Tablas de Hechos.
- Mide y reporta el tiempo transcurrido total y el conteo de filas en cada etapa.

### 7.6 Suite de Pruebas Automatizadas ([`test_etl.py`](file:///c:/Users/user/Desktop/repo_proyecto_final_diplomado/ProcesoETL/test_etl.py))
Desarrollada bajo el framework `pytest`, valida 5 invariantes fundamentales del almacén:
1. `test_staging_tables_loaded`: Confirma que las 15 tablas en `stg` contengan datos.
2. `test_dimensions_loaded`: Verifica conteos y ausencia de nulos en claves subrogadas de dimensiones.
3. `test_facts_integrity`: Comprueba la integridad referencial de claves foráneas entre hechos y dimensiones (cero huérfanos).
4. `test_stock_alert_rule`: Valida formalmente que ningún registro con `stock_actual >= 15` tenga activa la bandera `stock_bajo_alerta`.
5. `test_idempotency_dim_product`: Ejecuta dos veces seguidas la carga de productos y confirma que el conteo de filas en `dw.dim_producto` permanece idéntico.

---

## 8. GUÍA DE EJECUCIÓN Y VALIDACIÓN

### Paso 1: Iniciar el Contenedor Docker
```bash
wsl docker compose -f DataWareHouse/docker-compose.yml up -d
```
Verificar que el estado sea `healthy`:
```bash
wsl docker compose -f DataWareHouse/docker-compose.yml ps
```

### Paso 2: Ejecutar el Proceso ETL
```bash
python ProcesoETL/main.py
```

### Paso 3: Ejecutar la Suite de Pruebas
```bash
python -m pytest ProcesoETL/test_etl.py -v
```

### Paso 4: Consultar las Vistas de Negocio en PostgreSQL
```sql
-- Conectado a localhost:5433 / inventory_dw / dw_admin
SELECT * FROM dw.vw_kpis_generales;
SELECT * FROM dw.vw_ventas_por_sucursal;
SELECT * FROM dw.vw_alertas_stock_critico LIMIT 10;
SELECT * FROM dw.vw_balance_iva_fiscal LIMIT 12;
```

---

## 9. CONCLUSIÓN Y CONFORMIDAD

La solución analítica implementada para **InventoryApp** cumple rigurosamente con los estándares académicos y profesionales de la Universidad de El Salvador (UES FMO 2026), respetando en un 100% las restricciones de no modificación del aplicativo original y proveyendo un entorno de Business Intelligence gobernado, idempotente y de alto rendimiento.
