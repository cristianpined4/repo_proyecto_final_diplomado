# AI_TASK_BOARD.md

BUG-008

Nombre: Interfaz en blanco por fallo de sintaxis en Vue (render timeout / undefined length) y loaders bloqueantes remanentes

Estado: Resuelto

Causa raíz:
1. Una operación de sustitución errónea en `MainDashboard.vue` alteró la definición de `data()`.
2. Se ejecutaban loaders globales bloqueando la navegación en catálogos y roles.
3. El enlace de configuración carecía de comprobación directa de permisos.

Solución:
1. Depurado de `MainDashboard.vue`.
2. Delegación a loading local en tablas.
3. Inyección de `v-if="hasPermission('configuracion.ver')"` en `InventoryLayout.vue`.

BUG-009

Nombre: Columnas Subtotal e IVA en Historial de Compras vacías ($0.00) y modal de detalles sin desglose fiscal

Estado: Resuelto

Causa raíz:
1. En `ComprasController::showAllCompras()`, el `selectRaw` y su `groupBy` omitían `compras.subtotal`, `compras.monto_iva`, `compras.porcentaje_iva_aplicado`, así como el contacto y teléfono del proveedor, retornando `undefined` al frontend.
2. En `MainHistoriaCompra.vue`, las condiciones `v-if="detailsRow?.subtotal"` y `v-if="detailsRow?.monto_iva > 0"` ocultaban el bloque de desglose fiscal en el modal de detalles al recibir valores indefinidos o nulos.

Solución:
1. Se añadieron `subtotal`, `monto_iva`, `porcentaje_iva_aplicado`, `contacto` y `telefono` al `selectRaw` y `groupBy` de `ComprasController.php`, y se soportó ordenamiento por `subtotal` e `monto_iva`.
2. Se definieron `$casts` numéricos a `float` en el modelo `Compras.php`.
3. Se robusteció `ComprasController::store` asegurando el parsing booleano de `iva_enabled` y redondeo a dos decimales.
4. En `MainHistoriaCompra.vue`, se implementaron slots dedicados de Quasar con fallbacks matemáticos (`total / 1.13`), se aseguró el renderizado incondicional del desglose en el modal y se conectó `viewDetails` a `/api/compras/{id}` para obtener detalles completos en tiempo real.

BUG-010

Nombre: Error SQL en Kardex por columnas inexistentes kardexes.tipo y kardexes.razon, y modernización de filtros y modal

Estado: Resuelto

Causa raíz:
1. En `InventarioController::mostrarKardexTodos()`, los filtros aplicaban cláusulas `where('kardexes.tipo', ...)` y `where('kardexes.razon', ...)` sobre la tabla `kardexes`, las cuales no existen en dicha tabla (pertenecen a `movimientos_inventarios`).
2. El campo "Razón" en el modal de registro de movimiento era un dropdown estático `q-select` en lugar de un campo de texto `q-input` con auto-capitalización y límite de 300 caracteres.
3. Los filtros de Producto y Razón eran estáticos y no permitían búsqueda dinámica con autocompletado y opciones precargadas como en el módulo de ventas.

Solución:
1. En `InventarioController.php`, se actualizaron las cláusulas a `mi.tipo` y `mi.razon` utilizando `leftJoin('movimientos_inventarios as mi', ...)` y fallbacks por signo de `cantidad_movimiento`.
2. Se creó el endpoint `GET /api/kardex/razones` para retornar razones agrupadas por frecuencia de uso para autocompletado y búsqueda.
3. Se aumentó la validación de `razon` a `max:300` en el backend y se migró el tipo de dato de la columna a `text` en PostgreSQL.
4. En `MainKardex.vue`, se reemplazó el campo "Razón" del modal por un `q-input` con `maxlength="300"`, `counter` y función `formatRazonInput` que capitaliza automáticamente la primera letra.
5. Se implementaron filtros dinámicos precargados para Producto (consultando `/api/productos/search`) y Razón (consultando `/api/kardex/razones`) con debounce de 300ms, búsqueda en tiempo real e inclusión de opción personalizada de búsqueda.

BUG-011

Nombre: Ausencia del campo y columna Estado en el módulo de Proveedores (tabla y formulario modal)

Estado: Resuelto

Causa raíz:
1. En `MainProveedores.vue`, la definición de columnas de `<q-table>` no contenía la columna `estado`.
2. En el modal para crear o editar proveedor, no existía control para consultar o alternar el estado del proveedor.
3. En `ProveedorController.php`, `$allowedSorts` no incluía `estado`, y los métodos `store` y `update` no extraían el atributo `estado` del payload.

Solución:
1. En `ProveedorController.php`, se añadió `estado` a `$allowedSorts` y se aseguró la persistencia del campo tanto en creación como en actualización (por defecto 1 = Activo).
2. En `MainProveedores.vue`, se agregó la columna `estado` con badge (`q-chip`) verde ("Activo") y rojo ("Inactivo").
3. Se agregó el selector `q-select` de Estado (Activo / Inactivo) en el formulario modal, mapeando correctamente en creación y edición.

BUG-012

Nombre: Ausencia de paginación server-side y selector de elementos por página en tabla de productos por agotarse del Dashboard y formatos numéricos no estandarizados (cantidades sin separador de miles y monedas sin decimales con comas)

Estado: Resuelto

Causa raíz:
1. En el Dashboard (`MainDashboard.vue`), la tabla de productos por agotarse / agotados cargaba todos los registros en memoria sin paginación desde el servidor ni selector de elementos por página (`[5, 10, 15, 25, 50]`). En el backend (`DashboardController.php`), `index()` obtenía todos los registros con `stock < 15` sin paginar.
2. En el módulo de Reportes (`MainReportes.vue`), las cantidades de transacciones, unidades vendidas, stock actual, compras realizadas y movimientos en Kardex no contaban con formateo numérico de miles `x,xxx`, y varias columnas monetarias utilizaban `.toFixed(2)` sin separadores de miles `,`.
3. En las demás tablas del sistema (`MainKardex.vue`, `MainProductos.vue`, `MainHistoriaCompra.vue`, `MainHistoriaVentas.vue`, `MainVentas.vue`, `MainCompras.vue`), existían columnas de cantidad y montos sin estandarización uniforme de formato.

Solución:
1. Se creó el endpoint `GET /api/dashboard/productos-agotandose` (`DashboardController::lowStock`) con soporte completo para paginación server-side (`limit`, `page`), ordenamiento (`sortBy`, `descending`) y valuación FIFO/LIFO/Promedio.
2. En `DashboardController::index()`, se incorporaron las métricas globales `totalAgotados` y `totalPorAgotarse` para alimentar las alertas del dashboard de forma precisa sin depender del límite de la página visible.
3. En `MainDashboard.vue`, se implementó `<q-table>` con `v-model:pagination="lowStockPagination"`, `@request="onRequestLowStock"`, `:rows-per-page-options="[5, 10, 15, 25, 50]"` y ordenamiento de servidor.
4. En `MainReportes.vue`, se formatearon todas las columnas numéricas con `formatQuantity` (`x,xxx`) y `formatSignedQuantity` (`+x,xxx`/`-x,xxx`), y los montos monetarios con `formatCurrency` (`$x,xxx.xx`), incluyendo tarjetas de KPI y alertas de stock crítico.
5. Se revisaron y estandarizaron las columnas y modales de las demás tablas del sistema (`MainProductos.vue`, `MainKardex.vue`, `MainHistoriaCompra.vue`, `MainHistoriaVentas.vue`, `MainVentas.vue`, `MainCompras.vue`) con separadores de miles para cantidades y formato monetario de dos decimales con comas.
6. En el reporte de rotación de inventario (`ReportesController::rotacionInventario` y `MainReportes.vue`), se corrigió el cálculo SQL con `COALESCE(..., 0.00)` para productos con stock 0 evitando valores `NULL` y visualización `"nullx"`. Se formateó en tabla como `${Number(val || 0).toFixed(2)}x` y se ordenó por defecto de mayor a menor (`ratio_rotacion desc, unidades_vendidas desc`) tanto en el backend como en la `<q-table>` de Quasar (`tablePagination.sortBy = 'ratio_rotacion'`, `descending = true`).

=================================================
REGLA OBLIGATORIA
=================================================

Antes de realizar cualquier cambio:

1. Leer AI_PROJECT_MEMORY.md.
2. Leer este archivo.
3. Verificar estado actual.
4. Continuar desde la última tarea pendiente.

=================================================
ESTADO GENERAL
=================================================

Proyecto:

ERP Laravel + Quasar

Estado:

- Auditoría
- Corrección
- Producción

Fecha última actualización:

2026-10-11

=================================================
BACKLOG PRIORITARIO
=================================================

CRÍTICO

- [x] Verificar cumplimiento completo del prompt maestro.
- [x] Revisar inventario por sucursal.
- [x] Corregir subtotal faltante en ventas.
- [x] Corregir cálculo IVA.
- [x] Corregir stock inicial.
- [x] Corregir kardex.
- [x] Corregir historial compras.
- [x] Corregir NIT proveedores.
- [x] Generar seeders masivos para pruebas a gran escala (10,000 registros por tabla).

ALTO

- [x] Auditoría completa tablas.
- [x] Paginación servidor.
- [x] Filtros servidor.
- [x] Traducción completa Quasar.

MEDIO

- [x] Optimización índices (verificado).
- [x] Optimización reportes (verificado).
- [x] Refactorizaciones (verificado).

=================================================
TAREAS EN PROGRESO
=================================================

Ninguna.

=================================================
TAREAS COMPLETADAS
=================================================

## 2026-10-11

### Seeders Masivos de Base de Datos para "Centro de Plástico El Limeño – San Miguel, El Salvador" (10,000 Registros por Tabla)
- **Análisis de `tarealimeño.sql`:**
  - Se analizó el script DDL de SQL Server del ERP ACAE legacy que modela la empresa **Centro de Plástico El Limeño – San Miguel, El Salvador**, extrayendo la estructura del negocio, rubro de empaques, plásticos desechables, mobiliario y contenedores, formatos de documentos fiscales de El Salvador (FAC, CCF), DUI (`00000000-0`), NIT (`0000-000000-000-0`), NRC (`######-#`), y distribución geográfica en San Miguel, Usulután, Morazán, La Unión y San Salvador.
- **Configuración Institucional (`CentroPlasticoConfigSeeder`):**
  - Actualización de la tabla `settings` con la razón social oficial: *Centro de Plástico El Limeño*, dirección: *Av. Roosevelt Sur #402, San Miguel, El Salvador*, teléfono: *2660-1234*, correo: *contacto@plasticosellimeno.com*, moneda: *USD*, IVA: *13%*, método de valuación: *promedio*, serie de venta: *FAC01*.
  - Configuración de 3 sucursales operativas en Oriente: Matriz San Miguel Centro, San Miguel Metrocentro y Usulután.
  - Creación de 10 usuarios con roles y permisos Spatie: Administrador, Gerente, 4 Vendedores, 2 Cajeros, 2 Almaceneros, 1 Encargado de Compras.
  - Catálogos completos con 8 categorías, subcategorías especializadas, 10 marcas líderes (Rimax, Plastigama, Reyplast, Termopack, Carvajal Empaques, Durespo, etc.), modelos y 8 unidades de medida (UND, PAQ, FAR, MIL, DOC, CTO, CAJ, ROL).
- **Generación de 10,000 Registros por Tabla Principal:**
  - **Proveedores (`ProveedoresSeeder`):** 10,000 proveedores fabricantes e importadores del rubro del plástico con NIT, NRC, contacto, teléfonos salvadoreños y direcciones.
  - **Clientes (`ClientesSeeder`):** 10,000 clientes (25% comerciales con NRC como comedores, pupuserías, panaderías, restaurantes, minisúpers; y 75% personas naturales con DUI y NIT salvadoreño).
  - **Productos (`ProductosSeeder`):** 10,000 productos detallados del rubro plástico con SKU correlativo (`PLAS-000001` a `PLAS-010000`), códigos internos, costos, precios con margen comercial y 30,000 registros en `producto_sucursal` vinculando stock distribuido.
  - **Compras (`ComprasSeeder`):** 10,000 compras históricas con numeración fiscal (`FAC-YYYY-######`, `CCF-YYYY-######`), estados validados (`pagado`, `cancelada`, `pendiente`), subtotal, IVA 13%, total y 20,000 registros en `detalles_compras`.
  - **Ventas (`VentasSeeder`):** 10,000 ventas históricas con series `FAC01`, `CCF01`, `TIQ01`, correlativos correlacionados, métodos de pago (`efectivo`, `tarjeta`, `transferencia`), estados validados (`pagada`, `anulada`, `devolucion`, `pendiente`), cálculo de IVA 13%, montos recibidos y cambio/vuelto, con 20,000 registros en `detalles_ventas`.
  - **Movimientos de Inventario y Kardex (`MovimientosInventarioSeeder`):** 10,000 movimientos (`entrada`, `salida`) con razones operativas de negocio y 10,000 registros en `kardexes` con control de cantidades y costos antes y después del movimiento.
- **Distribución Temporal 2016 – Octubre 2026:**
  - Todas las transacciones (compras, ventas, movimientos, clientes, proveedores, productos) distribuyen sus marcas de tiempo (`created_at`, `updated_at`) desde el 01 de enero de 2016 hasta el 11 de octubre de 2026 de forma cronológica progresiva dentro de horarios comerciales.
- **Rendimiento e Idempotencia:**
  - Inserciones optimizadas en lotes de 1,000 registros con `DB::table()->insert()`.
  - Ejecución completa con `php artisan db:seed` en solo ~18 segundos.
  - Sincronización automática de secuencias PostgreSQL (`setval`) en todas las tablas para garantizar compatibilidad con auto-incremento posterior.

### Valuación por Método FIFO (PEPS) en Seeders y Restauración de Ruta `/init`
- **Configuración Oficial FIFO:**
  - En `CentroPlasticoConfigSeeder`, se ajustó la directiva `inventory_valuation_method` con valor `'fifo'`, activando la lógica PEPS en todo el ecosistema (`InventoryValuator`, `ProductosController`, `DashboardController`).
- **Coherencia de Lotes de Compra (`detalles_compras.cantidad_restante`):**
  - Se dividió la generación de compras para respetar el consumo histórico FIFO:
    - *Compras 1 a 5,000 (2016 - 2023):* Lotes antiguos completamente consumidos por ventas (`cantidad_restante = 0`).
    - *Compras 5,001 a 10,000 (2024 - 2026):* Lotes recientes activos vinculados con el stock real (`cantidad_restante = stock_actual`), con `status = 'pagado'` y asignación por sucursal.
    - La consulta de Laravel (`dc.cantidad_restante > 0 AND c.status = 'pagado' AND c.sucursal_id = $sucursalId ORDER BY dc.created_at ASC, dc.id ASC LIMIT 1`) resuelve de forma óptima el lote PEPS activo y computa el `costo_actual` exacto.
- **Productos con Bajo Stock y Métricas de Dashboard:**
  - En `ProductosSeeder`, se configuró stock bajo (< 15 unidades) para los primeros 40 productos y stock 0 para los productos 41 al 55, nutriendo la tabla y contador de "Productos con bajo stock" en el Dashboard.
  - Se distribuyeron compras y ventas recientes en los rangos de últimos 30 días, 7 días y hoy (11 de octubre de 2026) para energizar los KPIs del Dashboard.
  - Se añadió `Cache::flush()` en `DatabaseSeeder` para asegurar la invalidación inmediata de estados cacheados.
- **Restauración Estricta de la Ruta `/init` (`routes/api.php`):**
  - Se revirtieron todos los cambios en `routes/api.php`, devolviéndola a su estado previo sin alterar la lógica de inicialización en rutas. Toda la siembra y configuración institucional se gestiona pura y exclusivamente a través de los Seeders (`php artisan db:seed`).
- **Nombres, Correos y Contactos Altamente Realistas en Proveedores y Clientes:**
  - Se reconstruyó la generación de nombres para erradicar cualquier sufijo `#` o correlativo visible.
  - *Proveedores:* 10,000 razones sociales auténticas de fabricantes, distribuidores e importadores plásticos salvadoreños, contactos con títulos y géneros concordantes (`Ing.`, `Licda.`), correos de dominio corporativo y direcciones en parques industriales reales.
- **Optimización de Tabla en Dashboard (Productos con Bajo Stock):**
  - Se eliminó la columna redundante `status` ("Estado") que mostraba el texto booleano `true` en toda la columna sin aportar valor semántico.
  - La condición de los productos queda representada limpiamente por la columna `Disponibilidad` con sus badges dinámicos ("Agotado" en rojo para stock 0, "Por Agotarse" en naranja para stock bajo), dejando la tabla balanceada y profesional.
- **Visualización Completa de Subcategorías en Productos:**
  - Se diagnosticó y corrigió el formateador de la columna "Subcategoria" en `MainDashboard.vue`, el cual buscaba `val.name` mientras que la tabla en BD utiliza `nombre`. Se configuró `val.nombre || val.name` y se añadió el accessor `$appends = ['name']` en el modelo `Subcategoria` para asegurar compatibilidad total en toda la aplicación.
  - Los 10,000 productos cuentan con asignación coherente de `subcategoria_id` perteneciente a su respectiva `categoria_id` (0 discrepancias en BD).

## 2026-10-03

### Optimización de Layout, Visualización de Valor por Defecto (`display-value`) y Corrección de 'Clearable'
- **Visualización Garantizada del Valor por Defecto al Cargar (`display-value`):**
  - Se identificó que Quasar, al evaluar `v-model === ""` o `null`, trata el campo como vacío y oculta la etiqueta del option inicial ("Todos...", "Todas las Categorías..."), mostrando únicamente el label flotante del input y dando la impresión de que el selector está en blanco.
  - Se implementó la directiva reactiva `:display-value="model !== '' && model !== null ? undefined : 'Texto Por Defecto'"` en todos los `<q-select>` de filtrado. Al montar el componente o recargar la página, el selector muestra inmediatamente su valor inicial explícito ("Todos los Vendedores", "Todos los Métodos", "Todas las Categorías", "Todos los Proveedores", etc.).
- **Ocultamiento Dinámico de la 'x' en Valor por Defecto:**
  - Se configuró `:clearable="model !== '' && model !== null"`, logrando que la "x" esté oculta en el estado inicial y solo se visualice al escoger un filtro específico.
  - Al presionar la "x", se restablece el modelo a `""`, vuelve a mostrarse el texto por defecto y se refrescan los datos server-side.
- **Ampliación Integral de la Grilla para Opciones y Etiquetas Extensas:**
  - Se eliminaron las columnas reducidas (`col-md-2` o `col-md-3`) en selectores con opciones largas, estructurando filas de alto confort visual:
    - *Productos (`MainProductos.vue`):* 3 filas de 3 columnas holgadas (`col-md-4` cada una). Fila 1: Búsqueda, Categoría, Marca. Fila 2: Estado, Disponibilidad/Stock, Tipo de Producto. Fila 3: Precio Mín, Precio Máx, Limpiar.
    - *Historial de Ventas (`MainHistoriaVentas.vue`):* Fila 1: Búsqueda (`col-md-6`), Vendedor (`col-md-6`). Fila 2: Método de Pago (`col-md-6`), Estado (`col-md-6`). Fila 3: Fecha Desde (`col-md-3`), Fecha Hasta (`col-md-3`), Monto Mín (`col-md-2`), Monto Máx (`col-md-2`), Limpiar (`col-md-2`).
    - *Historial de Compras (`MainHistoriaCompra.vue`):* Fila 1: Búsqueda (`col-md-6`), Proveedor (`col-md-6`). Fila 2: Estado (`col-md-4`), Fecha Desde (`col-md-4`), Fecha Hasta (`col-md-4`). Fila 3: Monto Mín (`col-md-4`), Monto Máx (`col-md-4`), Limpiar (`col-md-4`).
    - *Kardex (`MainKardex.vue`):* Fila 1: Búsqueda (`col-md-5`), Producto Específico (`col-md-7` para SKU y nombres largos). Fila 2: Tipo de Movimiento (`col-md-6`), Razón (`col-md-6`). Fila 3: Fecha Desde (`col-md-4`), Fecha Hasta (`col-md-4`), Limpiar (`col-md-4`).
    - *Clientes (`MainClientes.vue`):* Fila 1: Búsqueda (`col-md-6`), Tipo Contribuyente (`col-md-6`). Fila 2: Estado (`col-md-3`), Fecha Desde (`col-md-3`), Fecha Hasta (`col-md-3`), Limpiar (`col-md-3`).
    - *Proveedores (`MainProveedores.vue`):* Fila 1: Búsqueda (`col-md-6`), Tipo de Registro (`col-md-6`). Fila 2: Estado (`col-md-6`), Limpiar (`col-md-6`).
    - *Sucursales (`MainSucursales.vue`):* Fila 1: Búsqueda (`col-md-6`), Ciudad (`col-md-6`). Fila 2: Estado (`col-md-6`), Limpiar (`col-md-6`).
    - *Usuarios (`MainUsuarios.vue`):* Fila 1: Búsqueda (`col-md-6`), Sucursal (`col-md-6`). Fila 2: Rol (`col-md-6`), Estado (`col-md-6`). Fila 3: Fecha Desde (`col-md-4`), Fecha Hasta (`col-md-4`), Limpiar (`col-md-4`).
    - *Categorías, Catálogos y Unidades:* Actualizados con `display-value` para el filtro de estado.
- **Formateo Automático a 2 Decimales (.00) en Filtros Monetarios al Perder Foco (Blur):**
  - Se actualizaron todos los inputs de montos y precios (`montoMin`, `montoMax`, `precioMin`, `precioMax`, `filterMinTotal`) con `type="text"`, `inputmode="decimal"`, `placeholder="0.00"` y controladores `@blur="formatAmountOnBlur(...)"`.
  - Al ingresar cualquier cifra entera (ej. `20`) o decimal incompleto (ej. `20.5`), al retirar el foco del campo se normaliza inmediatamente con dos decimales (`20.00`, `20.50`), ejecutando la recarga server-side con el valor limpio. Si el campo se vacía o ingresan valores no válidos, se restablece a cadena vacía sin interferir con las consultas.
- **Calidad de Código:**
  - Verificado con `npm run lint` sin advertencias ni errores (0 errores).

**Archivos modificados:**
- [MainProductos.vue](file:///C:/Users/user/Desktop/InventaryApp/InventaryApp/src/pages/MainProductos.vue)
- [MainHistoriaVentas.vue](file:///C:/Users/user/Desktop/InventaryApp/InventaryApp/src/pages/MainHistoriaVentas.vue)
- [MainHistoriaCompra.vue](file:///C:/Users/user/Desktop/InventaryApp/InventaryApp/src/pages/MainHistoriaCompra.vue)
- [MainKardex.vue](file:///C:/Users/user/Desktop/InventaryApp/InventaryApp/src/pages/MainKardex.vue)
- [MainClientes.vue](file:///C:/Users/user/Desktop/InventaryApp/InventaryApp/src/pages/MainClientes.vue)
- [MainProveedores.vue](file:///C:/Users/user/Desktop/InventaryApp/InventaryApp/src/pages/MainProveedores.vue)
- [MainSucursales.vue](file:///C:/Users/user/Desktop/InventaryApp/InventaryApp/src/pages/MainSucursales.vue)
- [MainUsuarios.vue](file:///C:/Users/user/Desktop/InventaryApp/InventaryApp/src/pages/MainUsuarios.vue)
- [MainCategorias.vue](file:///C:/Users/user/Desktop/InventaryApp/InventaryApp/src/pages/MainCategorias.vue)
- [MainCatalogos.vue](file:///C:/Users/user/Desktop/InventaryApp/InventaryApp/src/pages/MainCatalogos.vue)
- [MainUnidades.vue](file:///C:/Users/user/Desktop/InventaryApp/InventaryApp/src/pages/MainUnidades.vue)

### Expansión de Filtros Multivariables en Todos los Módulos del Sistema
- **Productos (`MainProductos.vue` y `ProductosController.php`):**
  - Se añadieron selectores para Marca (`marca_id`), Tipo de Producto (`tipo`: perecedero vs no perecedero) e inputs numéricos con validación para rango de precios (`precioMin` y `precioMax`).
  - Backend: consultas reactivas server-side en `ProductosController@index` con `where('marca_id', ...)`, `where('tipo', ...)`, `where('precio_venta', '>=', ...)` y `where('precio_venta', '<=', ...)`.
- **Historial de Ventas (`MainHistoriaVentas.vue`, `VentasController.php` y `VentaService.php`):**
  - Se agregaron filtros para Vendedor (`user_id`), Rango de Importe Total (`montoMin` y `montoMax`), conservando filtros de estado (`completada`, `anulada`), método de pago y rango de fechas.
  - Backend: `VentaService@getHistorialVentas` procesa `user_id`, `monto_min` y `monto_max` filtrando por `total_venta`.
- **Historial de Compras (`MainHistoriaCompra.vue` y `ComprasController.php`):**
  - Se integraron filtros para Proveedor específico (`proveedor_id` con carga asíncrona de proveedores) e inputs para rango de importe (`montoMin` y `montoMax`).
  - Backend: `ComprasController@historial` procesa `proveedor_id`, `monto_min` y `monto_max` sobre `total_compra`.
- **Kardex de Inventario (`MainKardex.vue` e `InventarioController.php`):**
  - Se incorporaron filtros para Producto específico (`producto_id` desde el catálogo) y Razón de Movimiento (`razon`: compra, venta, ajuste, devolución, otro), combinados con Tipo (Entrada/Salida) y Rango de Fechas.
  - Backend: `InventarioController@mostrarKardexTodos` aplica `where('kardex.producto_id', ...)` y `where('kardex.razon', ...)`.
- **Clientes (`MainClientes.vue` y `ClientesController.php`):**
  - Se añadieron filtros por Tipo de Contribuyente (`tipo_contribuyente`: Crédito Fiscal con NRC vs Consumidor Final sin NRC) y Rango de Fechas de Registro (`fecha_desde` y `fecha_hasta` con rango horario inclusivo `startOfDay`/`endOfDay`).
  - Backend: `ClientesController@index` evalúa `tipo_contribuyente` y `created_at`.
- **Proveedores (`MainProveedores.vue` y `ProveedorController.php`):**
  - Se agregaron filtros por Tipo de Registro Fiscal (`tipo_documento`: Con NRC formal vs Sin NRC) y Estado (`estado`: Activos/Inactivos).
  - Backend: `ProveedorController@index` evalúa `tipo_documento` y `estado`.
- **Sucursales (`MainSucursales.vue` y `SucursalController.php`):**
  - Se agregó filtro por Ciudad/Municipio (`ciudad` con debounce y limpieza rápida).
  - Backend: `SucursalController@index` evalúa coincidencia parcial en `ciudad`.
- **Usuarios (`MainUsuarios.vue` y `UsersController.php`):**
  - Se integraron selectores de fecha de registro (`fecha_desde` y `fecha_hasta`), sumados a los filtros de Rol, Sucursal y Estado.
  - Backend: `UsersController@index` filtra registros sobre `created_at`.
- **Sincronización Git y Calidad:**
  - Código auditado con `php -l` (cero errores) y `npm run lint` (ESLint 0 errores).

**Archivos modificados:**
- [MainProductos.vue](file:///C:/Users/user/Desktop/InventaryApp/InventaryApp/src/pages/MainProductos.vue)
- [MainHistoriaVentas.vue](file:///C:/Users/user/Desktop/InventaryApp/InventaryApp/src/pages/MainHistoriaVentas.vue)
- [MainHistoriaCompra.vue](file:///C:/Users/user/Desktop/InventaryApp/InventaryApp/src/pages/MainHistoriaCompra.vue)
- [MainKardex.vue](file:///C:/Users/user/Desktop/InventaryApp/InventaryApp/src/pages/MainKardex.vue)
- [MainClientes.vue](file:///C:/Users/user/Desktop/InventaryApp/InventaryApp/src/pages/MainClientes.vue)
- [MainProveedores.vue](file:///C:/Users/user/Desktop/InventaryApp/InventaryApp/src/pages/MainProveedores.vue)
- [MainSucursales.vue](file:///C:/Users/user/Desktop/InventaryApp/InventaryApp/src/pages/MainSucursales.vue)
- [MainUsuarios.vue](file:///C:/Users/user/Desktop/InventaryApp/InventaryApp/src/pages/MainUsuarios.vue)
- [ProductosController.php](file:///C:/Users/user/Desktop/InventaryApp/InventaryAppApi/app/Http/Controllers/ProductosController.php)
- [VentasController.php](file:///C:/Users/user/Desktop/InventaryApp/InventaryAppApi/app/Http/Controllers/VentasController.php)
- [VentaService.php](file:///C:/Users/user/Desktop/InventaryApp/InventaryAppApi/app/Services/VentaService.php)
- [ComprasController.php](file:///C:/Users/user/Desktop/InventaryApp/InventaryAppApi/app/Http/Controllers/ComprasController.php)
- [InventarioController.php](file:///C:/Users/user/Desktop/InventaryApp/InventaryAppApi/app/Http/Controllers/InventarioController.php)
- [ClientesController.php](file:///C:/Users/user/Desktop/InventaryApp/InventaryAppApi/app/Http/Controllers/ClientesController.php)
- [ProveedorController.php](file:///C:/Users/user/Desktop/InventaryApp/InventaryAppApi/app/Http/Controllers/ProveedorController.php)
- [SucursalController.php](file:///C:/Users/user/Desktop/InventaryApp/InventaryAppApi/app/Http/Controllers/SucursalController.php)
- [UsersController.php](file:///C:/Users/user/Desktop/InventaryApp/InventaryAppApi/app/Http/Controllers/UsersController.php)

### Validaciones de Formato en Formulario de Sucursales y Remoción de Icono en Configuración
- **Frontend Configuración (`MainSettings.vue`):**
  - Se removió el icono de teléfono en el input de `empresa_telefono`, manteniendo un diseño visual limpio y homogéneo.
- **Frontend Sucursales (`MainSucursales.vue`):**
  - Se aplicaron reglas reactivas `:rules` en el modal de sucursales:
    - `nombre`: obligatorio, mínimo 3 y máximo 100 caracteres, solo caracteres alfanuméricos y puntuación comercial.
    - `telefono`: máscara `mask="####-####"`, placeholder `0000-0000`, hint de formato, inserción automática de guion con `onPhoneInput()` y validación regex `/^[0-9]{4}-[0-9]{4}$/`.
    - `correo`: validación de formato RFC (`^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$`) y máximo 150 caracteres.
    - `direccion`: mínimo 5 y máximo 250 caracteres con sugerencia de ubicación.
    - `ciudad`, `estado`, `pais`: mínimo 2 y máximo 100 caracteres con validación alfabética.
    - `logoFile`: restricción a tipos de imagen (JPEG, PNG, JPG, GIF, SVG, WEBP), tamaño máximo de 2MB (`max-file-size="2097152"`), y manejador `@rejected` con notificación de error.
- **Backend Sucursales (`SucursalController.php`):**
  - Se actualizaron los métodos `store` y `update` con reglas de validación completas por campo y mensajes personalizados en español.
- **Sincronización Git:** Se subieron todos los cambios mediante `git push origin main` a `InventaryApp` (commit `ffa43ee`) e `InventaryAppApi` (commit `bda2a7c`).

**Archivos modificados:**
- [MainSettings.vue](file:///C:/Users/user/Desktop/InventaryApp/InventaryApp/src/pages/MainSettings.vue)
- [MainSucursales.vue](file:///C:/Users/user/Desktop/InventaryApp/InventaryApp/src/pages/MainSucursales.vue)
- [SucursalController.php](file:///C:/Users/user/Desktop/InventaryApp/InventaryAppApi/app/Http/Controllers/SucursalController.php)

### Estandarización de Teléfono de Empresa a Formato 0000-0000 con Guion Automático
- **Frontend (`MainSettings.vue`):**
  - Se configuró la máscara de Quasar `mask="####-####"` y `placeholder="0000-0000"` en el campo `empresa_telefono`, permitiendo la inserción automática del guion a medida que el usuario escribe sus 8 dígitos.
  - Se agregó el método `onPhoneInput()` para normalizar dinámicamente cualquier valor pegado o editado, garantizando que el guion siempre quede ubicado tras el cuarto dígito.
  - En `fetchSettings()`, se normalizó la carga de números existentes desde la base de datos (incluso si venían con prefijo de país o sin formato) para formatearlos automáticamente a `0000-0000`.
  - Se configuró la regla de validación reactiva `:rules` con la expresión regular `/^[0-9]{4}-[0-9]{4}$/`.
- **Backend (`SettingsController.php` y `routes/api.php`):**
  - Se actualizó la regla de validación en `updateBulk` al patrón exacto `regex:/^[0-9]{4}-[0-9]{4}$/` con mensaje de error `"El teléfono debe tener el formato 0000-0000."`.
  - Se actualizó el valor predeterminado del seed de configuración a `'2200-0000'`.
- **Sincronización Git:** Se subieron todos los cambios mediante `git push origin main` a `InventaryApp` (commit `295593a`) e `InventaryAppApi` (commit `ba325ad`).

**Archivos modificados:**
- [MainSettings.vue](file:///C:/Users/user/Desktop/InventaryApp/InventaryApp/src/pages/MainSettings.vue)
- [SettingsController.php](file:///C:/Users/user/Desktop/InventaryApp/InventaryAppApi/app/Http/Controllers/SettingsController.php)
- [routes/api.php](file:///C:/Users/user/Desktop/InventaryApp/InventaryAppApi/routes/api.php)

### Traducción de Errores a Español, Estandarización de `producto_id` y Mejoras de Kardex
- **Traducción Integral de Errores al Español:**
  - Se configuró el locale predeterminado en `es` en `config/app.php`, `.env` y `.env.example`.
  - Se creó el paquete de localización completo en `lang/es/` (`validation.php`, `auth.php`, `pagination.php`, `passwords.php`) con mapeo exhaustivo de atributos y mensajes de error amigables.
  - Se configuró la regla personalizada en `validation.php`, FormRequests (`StoreVentaRequest.php`, `StoreCompraRequest.php`) y controladores (`InventarioController.php`) para que cualquier validación sobre `producto_id` o `product_id` devuelva explícitamente: *"El producto es requerido."*.
  - En `bootstrap/app.php`, se implementó el interceptor de `ValidationException` para API, normalizando mensajes de producto y reemplazando resúmenes en inglés como `(and 2 more errors)` por `(y 2 errores más)`.
  - En el frontend, se potenció `src/helpers/Alert.js` con un formateador defensivo que sanitiza y traduce cualquier mensaje de error en inglés residual a español nativo.
- **Kardex Enriquecido (Formato de Fechas y Filtro por Rango):**
  - En `MainKardex.vue`, se agregó la columna de fecha y hora como primera columna de la tabla, formateada en `dd/mm/yyyy hh:mm AM/PM` (ej: `03/10/2026 06:22 PM`) mediante el helper `formatDateTime()`.
  - Se diseñó la barra de filtros en `MainKardex.vue` con inputs `Fecha Desde` y `Fecha Hasta` (`type="date"`, reactivos al cambio) y botón para limpiar todos los filtros.
  - En el backend (`InventarioController@mostrarKardexTodos`), se agregó soporte para los parámetros `start_date` y `end_date`, aplicando `startOfDay()` y `endOfDay()` para garantizar rangos horarios inclusivos, e incluyendo los campos `tipo` y `razon` en la respuesta JSON.
- **Sincronización Git:** Se subieron todos los cambios mediante `git push origin main` a `InventaryApp` (commit `4899fe1`) e `InventaryAppApi` (commit `77f6abd`).

**Archivos modificados:**
- [MainKardex.vue](file:///C:/Users/user/Desktop/InventaryApp/InventaryApp/src/pages/MainKardex.vue)
- [Alert.js](file:///C:/Users/user/Desktop/InventaryApp/InventaryApp/src/helpers/Alert.js)
- [InventarioController.php](file:///C:/Users/user/Desktop/InventaryApp/InventaryAppApi/app/Http/Controllers/InventarioController.php)
- [StoreVentaRequest.php](file:///C:/Users/user/Desktop/InventaryApp/InventaryAppApi/app/Http/Requests/StoreVentaRequest.php)
- [StoreCompraRequest.php](file:///C:/Users/user/Desktop/InventaryApp/InventaryAppApi/app/Http/Requests/StoreCompraRequest.php)
- [bootstrap/app.php](file:///C:/Users/user/Desktop/InventaryApp/InventaryAppApi/bootstrap/app.php)
- [config/app.php](file:///C:/Users/user/Desktop/InventaryApp/InventaryAppApi/config/app.php)
- [lang/es/validation.php](file:///C:/Users/user/Desktop/InventaryApp/InventaryAppApi/lang/es/validation.php)
- [.env.example](file:///C:/Users/user/Desktop/InventaryApp/InventaryAppApi/.env.example)

### Validaciones de Formato en Campos de Configuración y Unidades de Medida
- Se implementaron validaciones de formato rigurosas tanto en frontend como en backend para todos los campos de configuración del sistema (`MainSettings.vue` y `SettingsController.php`) y del catálogo de unidades (`MainUnidades.vue` y `UnidadController.php`).
- **Backend Settings (`SettingsController.php`):**
  - Se añadieron reglas de validación en `updateBulk` para cada clave soportada: `empresa_nombre` (3-100 caracteres alfanuméricos y puntuación comercial), `empresa_correo` (email válido RFC), `empresa_telefono` (patrón telefónico `^[+0-9\s\-()]{7,25}$`), `empresa_direccion` (5-250 caracteres), `empresa_moneda` (códigos ISO: USD, MXN, EUR, etc.), `venta_serie` (código alfanumérico en mayúsculas `^[A-Z0-9\-]{2,10}$`), `iva_percentage` (0 a 100 numérico), `inventory_valuation_method` (promedio, fifo, lifo), y colores hexadecimales (`color_primary`, `color_secondary`, `color_accent` con formato `#RRGGBB` o `#RGB`).
  - En `uploadGlobalLogo`, se validó el tipo MIME (jpeg, png, jpg, gif, svg, webp) y tamaño máximo de 2MB.
  - En `publicSettings`, se incluyó `venta_serie` entre los parámetros públicos.
- **Backend Unidades (`UnidadController.php`):**
  - Se añadieron reglas en `store` y `update` para `nombre` (2-50 caracteres válidos) y `abreviatura` (1-10 caracteres alfanuméricos y puntuación común `^[a-zA-ZáéíóúÁÉÍÓÚñÑ0-9\/.-]{1,10}$`).
- **Frontend Settings (`MainSettings.vue`):**
  - Se agregaron `:rules` reactivas en todos los inputs con mensajes de error explícitos en español.
  - Se agregó campo visual para la serie de facturación (`venta_serie`) con conversión a mayúsculas automática en el evento input y hint de formato.
  - Se incorporó validación preventiva en `onLogoSelected` para verificar tipo MIME permitido y límite de 2MB antes de disparar la carga.
- **Frontend Unidades (`MainUnidades.vue`):**
  - Se agregaron `:rules` en los campos de nombre y abreviatura con longitud máxima y expresión regular.
- **Sincronización Git:** Se subieron todos los cambios mediante `git push origin main` a `InventaryApp` (commit `e3c3a8f`) e `InventaryAppApi` (commit `0112e15`).

**Archivos modificados:**
- [MainSettings.vue](file:///C:/Users/user/Desktop/InventaryApp/InventaryApp/src/pages/MainSettings.vue)
- [MainUnidades.vue](file:///C:/Users/user/Desktop/InventaryApp/InventaryApp/src/pages/MainUnidades.vue)
- [SettingsController.php](file:///C:/Users/user/Desktop/InventaryApp/InventaryAppApi/app/Http/Controllers/SettingsController.php)
- [UnidadController.php](file:///C:/Users/user/Desktop/InventaryApp/InventaryAppApi/app/Http/Controllers/UnidadController.php)

## 2026-10-02

### Correcciones en Módulo de Productos (Status, Estilos CSS y Subida de Imágenes)
- Se corrigió el campo `status` en `MainProductos.vue` y `ProductosController.php` para asegurar la normalización adecuada del valor activo/inactivo (1/0) en formularios de creación y edición.
- Se implementaron estilos CSS para `.q-field__bottom`, `.q-field__messages` y `.q-field__messages > div` con `overflow-x: auto; white-space: nowrap; scrollbar-width: thin;` para visualización limpia de mensajes de validación sin desbordar el diseño.
- Se corrigió la subida de imágenes con `FormData` enlazando el archivo real al disco `public` (`storage/app/public/productos`) y se implementó la eliminación/limpieza automática de cualquier directorio `storage/app/private` residual generado por Laravel.

**Archivos modificados:**
- [MainProductos.vue](file:///C:/Users/user/Desktop/InventaryApp/InventaryApp/src/pages/MainProductos.vue)
- [ProductosController.php](file:///C:/Users/user/Desktop/InventaryApp/InventaryAppApi/app/Http/Controllers/ProductosController.php)

### Optimización de Selectores para Datasets de +100,000 Registros
- Se eliminaron las cargas completas a memoria (`limit: -1` y listas truncadas a 100) en Compras (`MainCompras.vue`), Ventas (`MainVentas.vue`) y Kardex (`MainKardex.vue`).
- Se implementó Quasar `use-input` con `input-debounce="300"`, búsqueda dinámica `@filter` e indicadores visuales de carga `:loading`.
- Se crearon los endpoints ultrarrápidos de búsqueda `/api/proveedores/search`, `/api/clientes/search` y `/api/productos/search` en Laravel con límite de 25-30 registros y subconsultas eficientes de stock por sucursal.
- Se implementó la migración `2026_10_02_000001_add_performance_indexes_for_large_datasets.php` creando índices compuestos en PostgreSQL (`idx_productos_status_nombre`, `idx_proveedors_estado_nombre`, `idx_clientes_status_nombre`, `idx_prod_suc_ids`).

**Archivos modificados:**
- [MainCompras.vue](file:///C:/Users/user/Desktop/InventaryApp/InventaryApp/src/pages/MainCompras.vue)
- [MainVentas.vue](file:///C:/Users/user/Desktop/InventaryApp/InventaryApp/src/pages/MainVentas.vue)
- [MainKardex.vue](file:///C:/Users/user/Desktop/InventaryApp/InventaryApp/src/pages/MainKardex.vue)
- [ProveedorController.php](file:///C:/Users/user/Desktop/InventaryApp/InventaryAppApi/app/Http/Controllers/ProveedorController.php)
- [ClientesController.php](file:///C:/Users/user/Desktop/InventaryApp/InventaryAppApi/app/Http/Controllers/ClientesController.php)
- [ProductosController.php](file:///C:/Users/user/Desktop/InventaryApp/InventaryAppApi/app/Http/Controllers/ProductosController.php)
- [2026_10_02_000001_add_performance_indexes_for_large_datasets.php](file:///C:/Users/user/Desktop/InventaryApp/InventaryAppApi/database/migrations/2026_10_02_000001_add_performance_indexes_for_large_datasets.php)

### Corrección de Clientes (Error PostgreSQL 22P02 y Persistencia de Status)
- Se resolvió el error PostgreSQL `SQLSTATE[22P02]: Invalid text representation: 7 ERROR: la sintaxis de entrada no es válida para tipo bigint: «»` corrigiendo la extracción de parámetros en `UpdateClienteRequest.php` usando `$id = $this->route('id') ?? $this->route('cliente')` y la regla fluida `Rule::unique('clientes', 'correo')->ignore($id)`.
- Se creó el helper `isStatusActive()` en `MainClientes.vue` para normalizar booleanos/enteros de PostgreSQL y evitar la desactivación involuntaria al abrir la edición.
- Se añadió cast `'status' => 'integer'` en el modelo `Clientes.php` y se envían cadenas vacías opcionales como `null`.

**Archivos modificados:**
- [UpdateClienteRequest.php](file:///C:/Users/user/Desktop/InventaryApp/InventaryAppApi/app/Http/Requests/UpdateClienteRequest.php)
- [Clientes.php](file:///C:/Users/user/Desktop/InventaryApp/InventaryAppApi/app/Models/Clientes.php)
- [MainClientes.vue](file:///C:/Users/user/Desktop/InventaryApp/InventaryApp/src/pages/MainClientes.vue)

### Reseteo de Ventas y Eliminación de Falsa Alerta de Error
- Se implementó el método `resetSaleForm()` en `MainVentas.vue` que vacía el carrito y restablece `subtotal`, `totalIva` y `total` inmediatamente a $0.00 tras completar una venta.
- Se removió la llamada residual `this.fetchAll()` en `confirmSale()`, la cual provocaba un `TypeError` no capturado que disparaba la notificación de error a pesar de haberse guardado la venta con éxito.

**Archivos modificados:**
- [MainVentas.vue](file:///C:/Users/user/Desktop/InventaryApp/InventaryApp/src/pages/MainVentas.vue)

### Desglose de IVA en Tablas de Ventas y Compras
- Se integraron columnas explícitas en las tablas de `MainVentas.vue` y `MainCompras.vue`: `Condición IVA` (Incluido / + IVA), `Subtotal Neto`, `IVA ($)` y `Total Línea`.
- Se sincronizó el desglose con la configuración global (`iva_enabled`, `iva_percentage`) y de cada producto (`aplica_iva`).

**Archivos modificados:**
- [MainVentas.vue](file:///C:/Users/user/Desktop/InventaryApp/InventaryApp/src/pages/MainVentas.vue)
- [MainCompras.vue](file:///C:/Users/user/Desktop/InventaryApp/InventaryApp/src/pages/MainCompras.vue)

### Multimétodo de Pago y Cálculo de Vuelto (POS)
- Se ejecutó la migración `2026_10_02_000002_add_payment_fields_to_ventas_table.php` añadiendo `metodo_pago` (`efectivo`, `tarjeta_debito`, `tarjeta_credito`), `monto_recibido` y `cambio` a la tabla `ventas`.
- Se adaptaron `Ventas.php`, `StoreVentaRequest.php` y `VentaService.php` para validar y persistir los métodos de pago y calcular el cambio.
- Se renovó el modal de confirmación en `MainVentas.vue` con selección visual de método de pago, campo de efectivo recibido que se inicializa vacío con placeholder `0.00`, formateo automático con dos decimales (`.00`) al perder el foco (`@blur`) o presionar Enter, botones rápidos (`+$1`, `+$5`, `+$10`, `+$20`, `+$50`, `+$100`, `Exacto`) con formateo a `.00`, cálculo reactivo de vuelto o faltante y validación bloqueante de efectivo insuficiente.
- Se actualizó la plantilla de impresión de ticket térmico (`ticket.blade.php`) mostrando método de pago, efectivo recibido y vuelto entregado.

**Archivos modificados:**
- [MainVentas.vue](file:///C:/Users/user/Desktop/InventaryApp/InventaryApp/src/pages/MainVentas.vue)
- [Ventas.php](file:///C:/Users/user/Desktop/InventaryApp/InventaryAppApi/app/Models/Ventas.php)
- [StoreVentaRequest.php](file:///C:/Users/user/Desktop/InventaryApp/InventaryAppApi/app/Http/Requests/StoreVentaRequest.php)
- [VentaService.php](file:///C:/Users/user/Desktop/InventaryApp/InventaryAppApi/app/Services/VentaService.php)
- [ticket.blade.php](file:///C:/Users/user/Desktop/InventaryApp/InventaryAppApi/resources/views/templates/ticket.blade.php)
- [2026_10_02_000002_add_payment_fields_to_ventas_table.php](file:///C:/Users/user/Desktop/InventaryApp/InventaryAppApi/database/migrations/2026_10_02_000002_add_payment_fields_to_ventas_table.php)

### Corrección de Reportes Analíticos (Rangos de Fecha en PostgreSQL)
- Se corrigió el problema de reportes vacíos debido al corte de fecha a las 00:00:00 en PostgreSQL al usar `whereBetween` con fechas `YYYY-MM-DD`.
- Se creó el método auxiliar `resolveDateRange()` en `ReportesController.php` aplicando `Carbon::parse($end)->endOfDay()` (23:59:59.999999).
- Se implementó `leftJoin('categorias', ...)` con `COALESCE` en los 8 métodos analíticos para incluir productos sin categoría asignada.

**Archivos modificados:**
- [ReportesController.php](file:///C:/Users/user/Desktop/InventaryApp/InventaryAppApi/app/Http/Controllers/ReportesController.php)

### Historial de Ventas Enriquecido (Métodos de Pago, Desglose Fiscal y Modal Moderno)
- Se actualizó el endpoint de historial en `VentaService.php` (`getHistorialVentas`) incorporando en `selectRaw` y `groupBy` los campos de PostgreSQL: `metodo_pago`, `monto_recibido`, `vuelto`, `subtotal`, `monto_iva` y `porcentaje_iva_aplicado`, permitiendo búsquedas y ordenamientos por método de pago y totales netos.
- Se rediseñó la tabla en `MainHistoriaVentas.vue` añadiendo la columna de método de pago con chips e iconos interactivos (`payments`, `credit_card`, `account_balance_wallet`), columnas de subtotal neto e IVA, y acción directa para reimprimir tickets térmicos mediante iframe.
- Se renovó el modal de detalles con tarjetas informativas de cliente y cajero, banner de método de pago con auditoría de efectivo recibido y vuelto entregado, tabla de productos facturados con subtotal por línea, y desglose financiero final completo.

**Archivos modificados:**
- [MainHistoriaVentas.vue](file:///C:/Users/user/Desktop/InventaryApp/InventaryApp/src/pages/MainHistoriaVentas.vue)
- [VentaService.php](file:///C:/Users/user/Desktop/InventaryApp/InventaryAppApi/app/Services/VentaService.php)

### Historial de Compras Enriquecido (Desglose Fiscal, Proveedor y Productos)
- Se actualizó `CompraService.php` (`getHistorialCompras`) seleccionando y agrupando `subtotal`, `monto_iva`, `porcentaje_iva_aplicado`, teléfono y contacto del proveedor, habilitando ordenamiento por subtotal, IVA y proveedor.
- Se rediseñó la vista `MainHistoriaCompra.vue` añadiendo columnas de subtotal neto, IVA y badge estilizado para el número de factura.
- Se corrigió la lectura de `detalles_compra` en el modal de detalles, permitiendo renderizar la tabla completa de productos adquiridos con sus cantidades, costos unitarios y subtotales calculados.
- Se incorporó el desglose tributario (subtotal, IVA con % aplicado, total de compra) y datos de contacto del proveedor en el modal de detalles.

**Archivos modificados:**
- [MainHistoriaCompra.vue](file:///C:/Users/user/Desktop/InventaryApp/InventaryApp/src/pages/MainHistoriaCompra.vue)
- [CompraService.php](file:///C:/Users/user/Desktop/InventaryApp/InventaryAppApi/app/Services/CompraService.php)

### Sincronización Git en Repositorios
- Se ejecutó `git push origin main` con subida limpia de ramas en `InventaryApp` (frontend con commits `68f506d`, `7c4c388`, `b9c9f4c` y `cf6ce86`) e `InventaryAppApi` (backend con commits `f6fad20`, `871f1d5` y `eb1336d`).

## 2026-09-09

### Cambio Automático a Sucursal Asignada al Iniciar Sesión
- Se corrigió el error donde un usuario obtenía "Acceso denegado" al iniciar sesión si el `localStorage` conservaba el ID de sucursal de una sesión anterior.
- En `UserLogin.vue`, ahora se actualiza explícitamente `authStore.setSucursalActiva(response.data.data.sucursal_id)` si el usuario tiene una sucursal asignada.
- Esto garantiza que el encabezado `X-Sucursal-Id` se envíe correctamente en las solicitudes subsiguientes (como `/api/dashboard`), evitando el rechazo por parte de `BranchSecurityMiddleware`.

**Archivos modificados:**
- [UserLogin.vue](file:///C:/Users/user/Desktop/InventaryApp/InventaryApp/src/pages/UserLogin.vue)


## 2026-06-15

### Solución de Bloqueo de Inicio de Sesión para Administradores con Sucursal Nula
- Se corrigió el problema por el cual un administrador (u otro usuario con rol global) sin sucursal asignada (`sucursal_id = null`) era bloqueado al loguearse o redirigido al login al recargar la página.
- Se implementó la persistencia de la sucursal activa (`sucursalActiva`) en `localStorage` y Axios por defecto para evitar reseteos de estado al recargar la página.
- Se adaptó la lógica en `InventoryLayout.vue` (`loadSucursales()`) para determinar correctamente la sucursal por defecto.
- Se exceptuó del bloqueo de sucursal inactiva en el backend a los roles globales (`admin`, `gerente`) para evitar lockouts accidentales en caso de desactivar sucursales.
- Se verificó con linting y la suite de pruebas automatizadas al 100% de éxito.

**Archivos modificados:**
- [InventoryLayout.vue](file:///C:/Users/user/Desktop/InventaryApp/InventaryApp/src/layouts/InventoryLayout.vue)
- [UsersController.php](file:///C:/Users/user/Desktop/InventaryApp/InventaryAppApi/app/Http/Controllers/UsersController.php)
- [auth-store.js](file:///C:/Users/user/Desktop/InventaryApp/InventaryApp/src/store/auth-store.js)
- [axios.js](file:///C:/Users/user/Desktop/InventaryApp/InventaryApp/src/boot/axios.js)

## 2026-06-14

### Corrección de Visibilidad del Selector de Sucursales en el Navbar
- Se detectó que el selector de sucursales no se mostraba a los usuarios administradores/globales a pesar de contar con más de una sucursal en el sistema.
- Se determinó que la causa raíz era un TypeError al intentar realizar `.filter()` sobre la respuesta paginada de `/api/sucursales`.
- Se corrigió la llamada agregando `limit: -1` para recibir el listado completo directo y se añadió parseo defensivo robusto en `InventoryLayout.vue`.
- Se validó el linter frontend y backend con éxito total.

**Archivos modificados:**
- [InventoryLayout.vue](file:///C:/Users/user/Desktop/InventaryApp/InventaryApp/src/layouts/InventoryLayout.vue)

## 2026-06-13

### Integración de Vista de Unidades de Medida en Rutas y Menú
- Se registró la ruta `/inventario/unidades` en `routes.js` apuntando a `MainUnidades.vue`.
- Se agregó el enlace de navegación "Unidades de Medida" en el sidebar (`InventoryLayout.vue`) dentro del subgrupo de Inventario, permitiendo el acceso de los roles `admin`, `gerente` y `almacenero`.
- Se verificó que la vista use correctamente la paginación, filtros y ordenación server-side.
- Se corrieron pruebas unitarias/funcionales y el linter con 100% de éxito.

### Auditoría Programática de Experiencia de Usuario y Corrección de Login Session-Less
- Se escribió un script de prueba de integración de simulación de usuario (`scratch_test.js`) en Node que realiza todo el flujo de inicio de sesión, creación de categorías, subcategorías, unidades, marcas, modelos, productos, clientes, proveedores, ajustes manuales, compras, ventas y validaciones de stock/IVA.
- Se identificó y resolvió una excepción runtime de Laravel al iniciar sesión desde clientes REST externos / scripts (session store not set). Se envolvió `$r->session()->regenerate()` con una verificación `if ($r->hasSession())` en `UsersController.php`.
- Se certificó que todos los flujos transaccionales y de inventario funcionan con éxito de punta a punta.

**Archivos modificados:**
- [routes.js](file:///C:/Users/user/Desktop/InventaryApp/InventaryApp/src/router/routes.js)
- [InventoryLayout.vue](file:///C:/Users/user/Desktop/InventaryApp/InventaryApp/src/layouts/InventoryLayout.vue)
- [UsersController.php](file:///C:/Users/user/Desktop/InventaryApp/InventaryAppApi/app/Http/Controllers/UsersController.php)

## 2026-06-12

### Paginación, IVA, Subcategorías, NRC y Paginación/Ordenamiento Global Server-Side

- Paginación server-side y traducción Quasar a español.
- Redefinición del IVA (precio incluye IVA vs precio no incluye IVA) en front y back.
- Gestión de Subcategorías integrada y campo NRC para Proveedores.
- Corrección del DatabaseSeeder para sembrar roles/permisos/configuraciones y pasar la suite de pruebas PHPUnit.
- Alineamiento simétrico de IVA en ComprasController del backend.
- Migración de las tablas de Categorías, Proveedores, Unidades y Usuarios a paginación, filtros y ordenamiento 100% server-side en Quasar y Laravel.
- Verificación del linter frontend (ESLint) y de las pruebas unitarias/funcionales backend (PHPUnit) con 100% de éxito.

**Archivos modificados:**
- [quasar.config.js](file:///C:/Users/user/Desktop/InventaryApp/InventaryApp/quasar.config.js)
- [MainCategorias.vue](file:///C:/Users/user/Desktop/InventaryApp/InventaryApp/src/pages/MainCategorias.vue)
- [MainProveedores.vue](file:///C:/Users/user/Desktop/InventaryApp/InventaryApp/src/pages/MainProveedores.vue)
- [MainVentas.vue](file:///C:/Users/user/Desktop/InventaryApp/InventaryApp/src/pages/MainVentas.vue)
- [MainCompras.vue](file:///C:/Users/user/Desktop/InventaryApp/InventaryApp/src/pages/MainCompras.vue)
- [MainHistoriaVentas.vue](file:///C:/Users/user/Desktop/InventaryApp/InventaryApp/src/pages/MainHistoriaVentas.vue)
- [MainHistoriaCompra.vue](file:///C:/Users/user/Desktop/InventaryApp/InventaryApp/src/pages/MainHistoriaCompra.vue)
- [MainKardex.vue](file:///C:/Users/user/Desktop/InventaryApp/InventaryApp/src/pages/MainKardex.vue)
- [MainSucursales.vue](file:///C:/Users/user/Desktop/InventaryApp/InventaryApp/src/pages/MainSucursales.vue)
- [MainUnidades.vue](file:///C:/Users/user/Desktop/InventaryApp/InventaryApp/src/pages/MainUnidades.vue)
- [MainUsuarios.vue](file:///C:/Users/user/Desktop/InventaryApp/InventaryApp/src/pages/MainUsuarios.vue)
- [CategoriasController.php](file:///C:/Users/user/Desktop/InventaryApp/InventaryAppApi/app/Http/Controllers/CategoriasController.php)
- [ClientesController.php](file:///C:/Users/user/Desktop/InventaryApp/InventaryAppApi/app/Http/Controllers/ClientesController.php)
- [ComprasController.php](file:///C:/Users/user/Desktop/InventaryApp/InventaryAppApi/app/Http/Controllers/ComprasController.php)
- [ProductosController.php](file:///C:/Users/user/Desktop/InventaryApp/InventaryAppApi/app/Http/Controllers/ProductosController.php)
- [ProveedorController.php](file:///C:/Users/user/Desktop/InventaryApp/InventaryAppApi/app/Http/Controllers/ProveedorController.php)
- [SucursalController.php](file:///C:/Users/user/Desktop/InventaryApp/InventaryAppApi/app/Http/Controllers/SucursalController.php)
- [UnidadController.php](file:///C:/Users/user/Desktop/InventaryApp/InventaryAppApi/app/Http/Controllers/UnidadController.php)
- [UsersController.php](file:///C:/Users/user/Desktop/InventaryApp/InventaryAppApi/app/Http/Controllers/UsersController.php)
- [DatabaseSeeder.php](file:///C:/Users/user/Desktop/InventaryApp/InventaryAppApi/database/seeders/DatabaseSeeder.php)
- [VentaTest.php](file:///C:/Users/user/Desktop/InventaryApp/InventaryAppApi/tests/Feature/VentaTest.php)

**Resultado:**
- Todo el backend compila correctamente, el frontend está sincronizado y adaptado a las pautas de rendimiento empresarial, y la suite de pruebas PHPUnit y el linter ESLint pasan exitosamente con 100% de éxito.

=================================================
BUGS IDENTIFICADOS
=================================================

BUG-001

Nombre: Desalineamiento de cálculo de IVA en ComprasController

Estado: Resuelto

Causa raíz: ComprasController añadía IVA en backend incluso si el producto ya lo incluía (aplica_iva = true) y no sumaba el impuesto si excluía IVA (aplica_iva = false).

Solución: Se implementó la lógica simétrica de desglosar/sumar en el backend para coincidir con VentaService y las vistas POS del frontend.

---

BUG-002

Nombre: Ausencia de parámetros de ordenamiento en tablas del frontend

Estado: Resuelto

Causa raíz: Las tablas de Categorías, Proveedores, Unidades y Usuarios no enviaban sortBy ni descending al backend.

Solución: Se habilitó `sortable: true` en las columnas correspondientes, se definieron parámetros por defecto (A-Z ASC) y se configuró onRequest para enviar el estado de ordenación del servidor.

---

BUG-003

Nombre: Excepción de session store al iniciar sesión desde clientes REST externos

Estado: Resuelto

Causa raíz: UsersController->login llamaba incondicionalmente a session()->regenerate(), lo cual fallaba en peticiones sin almacenamiento de sesión activo.

Solución: Se condicionó la llamada usando $request->hasSession() para evitar que trone en llamadas programáticas/sessionless.

---

BUG-004

Nombre: No visualización del selector de sucursal en el navbar

Estado: Resuelto

Causa raíz: /api/sucursales retornaba respuesta paginada por defecto, el layout lo parseaba directamente como array, lanzando TypeError al llamar a .filter() y vaciando la lista de sucursales en el selector.

Solución: Se modificó la llamada para pasar limit: -1 (trayendo todas las sucursales) y se agregó control defensivo en frontend para soportar tanto arrays planos como respuestas de paginadores Laravel.

---

BUG-005

Nombre: Bloqueo de login de Administradores con sucursal nula y pérdida de sucursal activa al recargar

Estado: Resuelto

Causa raíz:
1. La sucursal activa se perdía al recargar la página (comportamiento por defecto de `window.location.reload()`) porque no se persistía en localStorage.
2. `loadSucursales()` en `InventoryLayout.vue` no gestionaba correctamente la sucursal seleccionada predeterminada si el usuario global carecía de sucursal fija o si la sucursal activa seleccionada se reseteaba a null.
3. El backend bloqueaba el inicio de sesión para administradores/gerentes no principales si se les asignaba una sucursal inactiva, a pesar de tener rol global.

Solución:
1. Se persistió el valor de `sucursalActiva` en `localStorage` en `auth-store.js` e inicializó el Axios client con `X-Sucursal-Id` en `boot/axios.js` al arrancar.
2. Se reescribió la lógica de asignación y fallback de `loadSucursales()` en `InventoryLayout.vue` para determinar la sucursal activa de forma segura.
3. Se exceptuó a los roles globales `admin` y `gerente` de la validación de sucursal inactiva al loguearse en `UsersController.php`.

BUG-006

Nombre: Error de acceso denegado por sucursal incorrecta al iniciar sesión

Estado: Resuelto

Causa raíz:
Al iniciar sesión, si existía un `X-Sucursal-Id` antiguo en `localStorage`, la llamada a `/api/me` y `/api/login` era bloqueada prematuramente por `BranchSecurityMiddleware` debido a que el usuario autenticado (por cookie) tenía asignada una sucursal distinta a la del header. Esto causaba un bloqueo (lockout) donde el usuario no podía actualizar su sesión ni redirigirse, devolviendo siempre 403.

Solución:
1. Se excluyeron las rutas `api/login`, `api/logout` y `api/me` de la validación estricta en `BranchSecurityMiddleware`.
2. Se añadió `authStore.setSucursalActiva(response.data.data.sucursal_id)` en `UserLogin.vue` (al loguear) y en `auth-store.js` (en `fetchUser`) para forzar la actualización del `localStorage` e hidratar los headers de Axios con la sucursal correcta inmediatamente.
3. Se solucionó una condición de carrera (race condition) en `InventoryLayout.vue` añadiendo un flag `authStoreLoaded`. Esto evita que las vistas hijas (como `MainDashboard.vue`) monten prematuramente y envíen peticiones con cabeceras obsoletas antes de que `fetchUser()` complete su hidratación.
4. **Fix Crítico de Instancias Duplicadas:** Se normalizaron todas las importaciones de Axios en el Frontend. Múltiples páginas estaban importando `src/boot/axios` en lugar del alias oficial de Quasar `boot/axios`. Esto causaba que el empaquetador (Webpack/Vite) evaluara el archivo dos veces, creando **dos instancias separadas de Axios**. Como resultado, cuando el store actualizaba las cabeceras en una instancia durante el login, el resto del sistema seguía usando la instancia antigua (con los IDs de sucursal del usuario anterior) y provocaba el error 403.
5. **Refactorización de Autorización Dinámica (Roles a Permisos):** Se reemplazó completamente el sistema de protección estática basado en roles (`middleware('role:...')` en backend y `meta: { roles: [] }` en frontend) por un sistema dinámico basado en permisos granulares (`permission:ventas.crear`). Esto asegura que si se crean nuevos roles personalizados desde la interfaz, no se bloqueen por no estar escritos textualmente en el código fuente.
6. **Centralización de Seeders:** Se limpió el archivo `DatabaseSeeder.php` delegando toda la lógica de inicialización (roles, configuraciones de ventas y categoría por defecto) a la ruta única `/api/init`.
7. **Optimización de Cargas Útiles (Payloads):** Se limitó drásticamente la exposición de datos de la API. La ruta pública `/api/settings/public` ahora expone únicamente `key` y `value` (limpiando ID, fechas, grupo, etc). Además, la carga de datos del usuario autenticado (`/api/me` y `/api/login`) devuelve un objeto ligero, evitando enviar arrays completos y tablas pivote del paquete Spatie; en su lugar, ahora retorna simples arrays nativos de strings de los roles y permisos del usuario.

BUG-007

Nombre: Inconsistencia visual en pantallas, fallos de UX por loaders globales y botones sin permiso expuestos.

Estado: Resuelto

Causa raíz:
1. Las pantallas antiguas (Historial Compras, Historial Ventas, Kardex, Reportes, Dashboard, Productos) utilizaban etiquetas HTML planas y no respetaban el diseño base implementado en `MainCategorias.vue` (banners degradados con avatares).
2. Las tablas realizaban llamadas a la API ejecutando un `useLoadingStore().startLoading()`, el cual disparaba una cortina de carga que bloqueaba toda la interfaz innecesariamente en búsquedas/paginaciones.
3. Se detectaron colisiones de la variable `loading` por nombres duplicados al aplicar refactorizaciones.
4. Los botones de acción de las tablas (Crear, Editar, Eliminar, Anular, Devolver) se pintaban en el DOM aunque el usuario careciera de permisos.

Solución:
1. Se reescribieron los templates de todas las vistas (Dashboard, Productos, Kardex, Reportes, HistorialVentas, HistorialCompras) usando `<q-card class="bg-gradient-brand text-white shadow-2">` como Banner Superior.
2. Se reemplazó la llamada bloqueante de `useLoadingStore().startLoading()` de los métodos `onRequest()` por un atributo local `loading` vinculado a `<q-table :loading="loading">`.
3. Se corrigieron los errores de ESLint removiendo variables duplicadas y props duplicados (ej: `:loading="loading" :loading="loadingModelos"`).
4. Se inyectó globalmente el validador `v-if="authStore.hasPermission('...')"` a todos los botones de acción para ocultarlos dinámicamente según el permiso asignado.

---

BUG-009

Nombre: Error SQLSTATE[22P02] al actualizar cliente y desactivación no deseada de status

Estado: Resuelto

Causa raíz:
1. `UpdateClienteRequest.php` buscaba el parámetro `$this->route('cliente')`, pero la ruta API está declarada como `/clientes/{id}`. Al retornar null, la regla generaba `WHERE "id" <> ''`, lo cual falla en PostgreSQL al comparar bigint contra cadena vacía.
2. En `MainClientes.vue`, PostgreSQL retorna los booleanos como `true`/`false`. La comparación estricta `row.status === 1` evaluaba a falso, marcando el selector como "Inactivo" al abrir el formulario de edición.

Solución:
1. Se corrigió `UpdateClienteRequest.php` con `$id = $this->route('id') ?? $this->route('cliente')` y `Rule::unique('clientes', 'correo')->ignore($id)`.
2. Se implementó la función normalizadora `isStatusActive()` en frontend.
3. Se añadió cast `'status' => 'integer'` en el modelo `Clientes.php`.

---

BUG-010

Nombre: Alerta de error falsa tras guardar venta y retención de valores en totales (Subtotal e IVA)

Estado: Resuelto

Causa raíz:
1. En `MainVentas.vue`, `confirmSale()` invocaba `this.fetchAll()` que había sido eliminada durante la migración a selectores asíncronos. Esto causaba un `TypeError: this.fetchAll is not a function` no capturado, saltando al `catch` y mostrando una alerta de error al usuario a pesar de que la venta se registró en BD.
2. El vaciado del formulario solo reseteaba `addedProducts = []` y `total = 0`, dejando intactos los montos de `subtotal` y `totalIva`.

Solución:
1. Se sustituyó la llamada residual por `this.resetSaleForm()`, el cual vacía el carrito y ejecuta `this.updateTotal()` para llevar todos los totales a $0.00.
2. Se refresca el selector de productos asíncronamente.

---

BUG-011

Nombre: Consultas de reportes retornando datos vacíos debido al corte de fecha en PostgreSQL

Estado: Resuelto

Causa raíz:
El frontend enviaba fechas en formato de solo día (`start_date`, `end_date`). En PostgreSQL, `whereBetween('created_at', [$start, $end])` evalúa la fecha final como `00:00:00`, descartando todas las transacciones ocurridas durante el resto del día final. Además, los `join('categorias', ...)` descartaban productos con categoría nula.

Solución:
1. Se construyó el método `resolveDateRange()` en `ReportesController.php` utilizando `Carbon::parse($end)->endOfDay()` (23:59:59.999999).
2. Se cambiaron los `join` por `leftJoin('categorias', ...)` con `COALESCE` en los 8 reportes.

---

BUG-012

Nombre: Sobrecarga de memoria y rendimiento en selectores al superar 100k registros

Estado: Resuelto

Causa raíz:
Los selectores de Compras descargaban toda la tabla con `limit: -1` y Ventas cargaba los primeros 100 productos en el hook `mounted()`, imposibilitando la venta de productos fuera del rango de los primeros 100 y degradando el navegador con datasets masivos.

Solución:
1. Se implementó Quasar `use-input` con debounce de 300ms y búsqueda server-side bajo demanda (`@filter`).
2. Se construyeron endpoints optimizados `/api/proveedores/search`, `/api/clientes/search`, `/api/productos/search` con límite de 25-30 registros.
3. Se crearon índices de base de datos (`idx_productos_status_nombre`, `idx_proveedors_estado_nombre`, `idx_clientes_status_nombre`, `idx_prod_suc_ids`).

---

BUG-013

Nombre: Falla en subida de imágenes de productos y generación de carpeta `storage/app/private`

Estado: Resuelto

Causa raíz:
El frontend no enviaba el archivo como instancia binaria en `FormData` y el backend utilizaba configuraciones predeterminadas que generaban directorios privados en `storage/app/private` inaccesibles para el servidor web.

Solución:
1. Se normalizó el envío con `FormData` en `MainProductos.vue`.
2. Se aseguró el almacenamiento en el disco público (`public/productos`).
3. Se implementó la eliminación programática de `storage/app/private` tras guardar archivos.

---

BUG-014

Nombre: Carencia de métodos de pago y cálculo de cambio/vuelto en POS de Ventas

Estado: Resuelto

Causa raíz:
El sistema solo permitía registrar ventas como transacción genérica sin especificar la forma de cobro (efectivo, débito, crédito) ni calcular el cambio para el cliente.

Solución:
1. Se agregaron los campos `metodo_pago`, `monto_recibido` y `cambio` a la tabla `ventas`.
2. Se diseñó modal interactivo de cobro con botones rápidos de billetes (`+$1`, `+$5`, `+$10`, `+$20`, `+$50`, `+$100`, `Exacto`), cálculo instantáneo de vuelto y validación bloqueante de efectivo insuficiente.
3. Se actualizó el ticket térmico con el desglose de pago.

=================================================
DECISIONES ARQUITECTÓNICAS
=================================================

Registrar todas las decisiones aprobadas.

2026-10-02

- **Búsqueda Asíncrona Masiva:** Ningún selector de compras, ventas o kardex debe descargar datasets completos (`limit: -1`). Deben utilizar endpoints `/search` con límite de 25-30 registros y debounce de 300ms.
- **Transparencia Fiscal (Desglose IVA):** Tanto la tabla de ventas como la de compras desglosan explícitamente el subtotal neto, el IVA y el total por línea de producto, reflejando si el precio lo incluye o se le adiciona.
- **Multimétodo de Pago en POS:** Toda venta registra su `metodo_pago` (`efectivo`, `tarjeta_debito`, `tarjeta_credito`). En efectivo es obligatorio calcular el vuelto y validar que el monto recibido sea suficiente. El ticket térmico refleja estos datos.
- **UX de Cobro en Efectivo:** El campo de efectivo recibido se inicializa vacío mostrando el placeholder '0.00'. Al ingresar cualquier monto entero o decimal, el evento blur normaliza el valor a dos decimales (.00) de forma idéntica a los botones de incremento rápido y al botón 'Exacto'.
- **Rangos de Fecha Inclusivos:** Todo filtro de fechas en reportes y estadísticas debe abarcar de `00:00:00` a `23:59:59` del día final para no excluir ventas recientes.
- **Inicialización Global Robusta (`/api/init`):** La ruta `/api/init` siembra y sincroniza automáticamente las 13 configuraciones maestras (empresa, fiscal, valuación, serie de facturación F001, branding de colores hexadecimales `#1976D2`, `#26A69A`, `#9C27B0`), catálogos fundamentales (Categoría 'General', Unidad de Medida 'UND', Marca 'Genérica') y sucursal 'Casa Matriz' con teléfono y datos válidos, devolviendo un JSON descriptivo con totales de roles, permisos y configuraciones sincronizadas.
- **Filtrado Avanzado Multivariable en Tablas de Módulos:** Todas las tablas principales de la aplicación cuentan con barras de filtrado dedicadas combinadas con búsqueda textual, selectores de estado (Activo/Inactivo), rangos de fechas (Desde/Hasta con inicio/fin de día), métodos de pago, tipos de movimiento o catálogos relacionados (roles, sucursales, categorías), soportados de extremo a extremo entre frontend (Quasar) y backend (Laravel).

2026-06-11

IVA

incluye_iva (aplica_iva) significa:
precio incluye IVA

=================================================
AUDITORÍA DE TABLAS
=================================================

| Módulo      | Server Pagination | Server Search | Server Filters | Estado        |
| ----------- | ----------------- | ------------- | -------------- | ------------- |
| Productos   | ✅ COMPLETADO      | ✅ COMPLETADO  | ✅ COMPLETADO   | ✅ COMPLETADO |
| Clientes    | ✅ COMPLETADO      | ✅ COMPLETADO  | ✅ COMPLETADO   | ✅ COMPLETADO |
| Ventas      | ✅ COMPLETADO      | ✅ COMPLETADO  | ✅ COMPLETADO   | ✅ COMPLETADO |
| Compras     | ✅ COMPLETADO      | ✅ COMPLETADO  | ✅ COMPLETADO   | ✅ COMPLETADO |
| Kardex      | ✅ COMPLETADO      | ✅ COMPLETADO  | ✅ COMPLETADO   | ✅ COMPLETADO |
| Sucursales  | ✅ COMPLETADO      | ✅ COMPLETADO  | ✅ COMPLETADO   | ✅ COMPLETADO |
| Categorías  | ✅ COMPLETADO      | ✅ COMPLETADO  | ✅ COMPLETADO   | ✅ COMPLETADO |
| Marcas      | ✅ COMPLETADO      | ✅ COMPLETADO  | ✅ COMPLETADO   | ✅ COMPLETADO |
| Modelos     | ✅ COMPLETADO      | ✅ COMPLETADO  | ✅ COMPLETADO   | ✅ COMPLETADO |
| Proveedores | ✅ COMPLETADO      | ✅ COMPLETADO  | ✅ COMPLETADO   | ✅ COMPLETADO |
| Unidades    | ✅ COMPLETADO      | ✅ COMPLETADO  | ✅ COMPLETADO   | ✅ COMPLETADO |
| Usuarios    | ✅ COMPLETADO      | ✅ COMPLETADO  | ✅ COMPLETADO   | ✅ COMPLETADO |
| Dashboard (Bajo Stock) | ✅ COMPLETADO | ✅ COMPLETADO | ✅ COMPLETADO | ✅ COMPLETADO |

=================================================
ANTES DE CERRAR LA SESIÓN
=================================================

Actualizar:

- tareas completadas
- tareas pendientes
- bugs
- decisiones arquitectónicas
- estado de auditoría

No finalizar una sesión sin actualizar este archivo.
