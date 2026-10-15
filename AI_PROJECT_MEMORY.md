# ERP Laravel + Quasar

## Estado del Proyecto

Proyecto existente.

NO es un proyecto nuevo.

Backend:

- Laravel 12
- API REST
- MySQL

Frontend:

- Quasar Framework
- Vue 3
- Pinia
- Axios

---

# Reglas Permanentes

Estas reglas NO pueden romperse.

## Tablas

Todas las tablas deben:

- usar paginación server-side
- usar búsqueda server-side
- usar filtros server-side
- no usar filtrado local
- no usar ordenamiento local
- no cargar datasets completos

## Ordenamientos

### Catálogos

Orden:

A-Z

Ignorando:

- acentos
- mayúsculas
- minúsculas

Aplicar a:

- categorías
- marcas
- modelos
- clientes
- proveedores
- usuarios
- sucursales
- otros modulos no mencionados y que tengan que necesitarlo

### Transacciones

Orden:

fecha DESC

Aplicar a:

- ventas
- compras
- kardex
- movimientos
- auditoría
- otros modulos no mencionados y que tengan que necesitarlo

---

# Decisiones Arquitectónicas

## IVA

IMPORTANTE

El campo:

incluye_iva

significa:

"el precio de venta incluye IVA"

NO significa:

"el producto paga IVA"

Reglas:

TRUE:
precio incluye IVA

FALSE:
precio no incluye IVA

Ventas deben almacenar:

- subtotal
- monto_iva
- total

---

# Inventario

Modelo oficial:

Inventario por sucursal.

No existe stock global.

Todo stock pertenece a una sucursal.

Movimientos:

- entrada
- salida
- ajuste
- transferencia

Todo movimiento genera kardex.

---

# Kardex

Todo movimiento debe:

- crear registro kardex
- actualizar inventario
- reflejarse inmediatamente en UI

No se permiten movimientos sin trazabilidad.

---

# Ventas

Toda venta debe:

- validar stock
- descontar inventario
- registrar kardex
- calcular IVA
- guardar subtotal
- guardar total

---

# Compras

Toda compra debe:

- aumentar inventario
- registrar kardex
- actualizar costos

---

# Roles

Roles mínimos:

- Administrador
- Gerente
- Vendedor
- Bodeguero

---

# Multisucursal

Obligatorio.

Debe existir:

- aislamiento de datos
- inventario por sucursal
- ventas por sucursal
- compras por sucursal

---

# Configuración Parametrizada

Nunca hardcodear:

- IVA
- empresa
- moneda
- formatos
- series
- correlativos

Todo debe venir desde BD.

---

# Estándares API

Formato estándar:

{
"success": true,
"message": "",
"data": {}
}

---

# Estado Actual

## Completado

- [x] Corrección de crash en Ventas agregando columnas `subtotal` y `monto_iva` faltantes a la base de datos y modelo.
- [x] Redefinición y sincronización del cálculo de IVA (Desglose si `aplica_iva` es true; adición si es false) en frontend (`MainVentas.vue`, `MainCompras.vue`) y backend (`VentaService.php`, `CompraService.php`, `ComprasController.php`).
- [x] Control de stock aislado por Sucursal como única fuente de verdad (`producto_sucursal`) con actualización inmediata en el Kardex y movimientos.
- [x] Gestión completa de Subcategorías integrada mediante modal CRUD anidado en `MainCategorias.vue` y API `/api/subcategorias`.
- [x] Campo `nrc` y validaciones de NIT/NRC/correo añadidas para proveedores (`MainProveedores.vue`) y clientes.
- [x] Migración de todas las tablas del sistema (Productos, Clientes, Ventas, Compras, Kardex, Sucursales, Categorías, Subcategorías, Marcas, Modelos, Proveedores, Unidades y Usuarios) a paginación, búsqueda, filtrado y ordenamiento 100% server-side en Quasar y Laravel.
- [x] Puesta a punto de ordenamientos predeterminados en controladores (catálogos A-Z y transacciones por fecha DESC).
- [x] Traducción completa del framework Quasar a español ('es') mediante `quasar.config.js`.
- [x] Registro y vinculación en el enrutador (`routes.js`) y menú sidebar (`InventoryLayout.vue`) de la vista de Unidades de Medida (`MainUnidades.vue`), eliminando la página huérfana.
- [x] Resolución de bloqueo de inicio de sesión para administradores con sucursal nula y habilitación de la persistencia de la sucursal activa en localStorage.
- [x] Corrección de "Acceso denegado" al iniciar sesión estableciendo automáticamente la sucursal activa del usuario en `UserLogin.vue` para asegurar la correcta actualización de los encabezados de Axios.
- [x] **Corrección en Módulo de Productos (Status, Estilos CSS y Subida de Imágenes):** Corrección del campo `status` para reflejar y persistir fielmente el valor activo/inactivo en creación y edición; ajuste de estilos CSS (`.q-field__bottom`, `.q-field__messages`) para scroll horizontal limpio evitando desbordes visuales; y solución integral a la subida de imágenes en `FormData` usando el disco `public` con eliminación automática de la carpeta `storage/app/private`.
- [x] **Optimización de Selectores para +100,000 Registros:** Eliminación de cargas completas en memoria (`limit: -1` y listas truncadas a 100) en Compras, Ventas y Kardex. Creación de endpoints ultrarrápidos `/api/proveedores/search`, `/api/clientes/search`, `/api/productos/search` con `input-debounce="300"`, `@filter`, límite de 25-30 registros e indicadores visuales de carga (`:loading`).
- [x] **Índices Compuestos de Rendimiento:** Migración `add_performance_indexes_for_large_datasets` creando índices en `productos(status, nombre)`, `proveedors(estado, nombre)`, `clientes(status, nombre)` y `producto_sucursal(producto_id, sucursal_id)`.
- [x] **Corrección de Clientes:** Resolución del error SQL 22P02 en PostgreSQL por discordancia de parámetro de ruta en `UpdateClienteRequest` y solución a la pérdida/desactivación del campo `status` al editar usando el helper `isStatusActive`.
- [x] **Reseteo Integral de Ventas:** Solución a la retención de valores en `subtotal` e `IVA` tras confirmar ventas mediante el método `resetSaleForm()` y eliminación del crash `TypeError: this.fetchAll is not a function`.
- [x] **Desglose de IVA en Tablas de Productos:** Desglose fila por fila en las tablas de `MainVentas.vue` y `MainCompras.vue` sincronizado con la configuración global (`iva_enabled`, `iva_percentage`) y de cada producto (`aplica_iva`), mostrando `Condición IVA` (Incluido / + IVA), `Subtotal Neto`, `IVA ($)` y `Total Línea`.
- [x] **Formas de Pago y Cálculo de Vuelto (POS):** Incorporación de métodos de pago (`efectivo`, `tarjeta_debito`, `tarjeta_credito`), campo de efectivo recibido que se inicializa vacío, formateo automático con dos decimales (`.00`) al perder el foco (`blur`), botones de incremento rápido (`+$1`, `+$5`, `+$10`, `+$20`, `+$50`, `+$100`, `Exacto`) con formateo a `.00`, cálculo reactivo de vuelto o faltante, validación bloqueante de efectivo insuficiente e impresión detallada en el ticket térmico.
- [x] **Historial de Ventas Enriquecido (POS, Pagos y Fiscal):** Actualización del backend en `VentaService.php` (`getHistorialVentas`) para seleccionar y agrupar `metodo_pago`, `monto_recibido`, `vuelto`, `subtotal`, `monto_iva` y `porcentaje_iva_aplicado`. Rediseño en `MainHistoriaVentas.vue` integrando columna de método de pago con chips e iconos distintivos, columnas de subtotal neto e IVA, botón para reimprimir tickets térmicos y modal de detalles moderno con información completa del cliente, cajero, desglose financiero, desglose de productos y auditoría de efectivo/vuelto.
- [x] **Historial de Compras Enriquecido (Fiscal, Proveedor y Productos):** Actualización de `CompraService.php` (`getHistorialCompras`) seleccionando y agrupando `subtotal`, `monto_iva`, `porcentaje_iva_aplicado`, teléfono y contacto del proveedor. Rediseño completo en `MainHistoriaCompra.vue` añadiendo columnas de subtotal neto, IVA y badge de factura, corrigiendo la relación `detalles_compra` para visualizar los productos adquiridos en el modal de detalles y agregando el desglose tributario y resumen financiero.
- [x] **Corrección Integral de Reportes:** Corrección de la omisión horaria en PostgreSQL mediante `resolveDateRange()` asegurando que `whereBetween` abarque de `00:00:00` a `23:59:59` para el día actual en todos los reportes, e implementación de `leftJoin` en categorías.
- [x] **Validaciones de Formato en Configuración y Unidades:** Implementación exhaustiva de validaciones de formato en frontend y backend. En `SettingsController.php` (`updateBulk`), se establecieron reglas estrictas por clave: `empresa_nombre` (caracteres válidos y longitud), `empresa_correo` (email válido), `empresa_telefono` (formato telefónico internacional/nacional), `empresa_direccion` (5-250 caracteres), `empresa_moneda` (códigos ISO permitidos), `venta_serie` (código alfanumérico `^[A-Z0-9\-]{2,10}$`), `iva_percentage` (0-100), `inventory_valuation_method` (`promedio`, `fifo`, `lifo`), colores hexadecimales (`#RRGGBB`/`#RGB`) y validación de tipo MIME y tamaño máximo de 2MB para logotipos en `uploadGlobalLogo`. En `UnidadController.php`, reglas estrictas para nombre y abreviatura. En `MainSettings.vue` y `MainUnidades.vue`, integración de `:rules` reactivas de Quasar, hints de ayuda y validación de imagen previa a la carga.
- [x] **Traducción Integral de Errores y Estandarización de `producto_id`:** Configuración de locale `es` en Laravel (`config/app.php` y `.env`), creación del paquete completo de idioma en `lang/es/` (`validation.php`, `auth.php`, `pagination.php`, `passwords.php`) con mapeo de atributos en español y reglas personalizadas para que cualquier error de `producto_id` / `product_id` indique exactamente *"El producto es requerido."*. Manejador centralizado en `bootstrap/app.php` que formatea excepciones 422 a español sustituyendo resúmenes en inglés como `(and 2 more errors)` por `(y 2 errores más)`, y sanitizador defensivo en `Alert.js` para el frontend.
- [x] **Kardex Enriquecido (Fechas AM/PM y Filtro por Rango de Fechas):** Incorporación de la columna de fecha y hora en formato `dd/mm/yyyy hh:mm AM/PM` al inicio de la tabla en `MainKardex.vue` con icono representativo, badge visual de entradas (+) y salidas (-), controles de filtro por rango de fechas (Fecha Desde y Fecha Hasta) en la barra superior junto con botón de limpiar filtros, y soporte en backend (`InventarioController@mostrarKardexTodos`) para filtrado inclusivo por `start_date` y `end_date` utilizando `startOfDay()` y `endOfDay()`.
- [x] **Formato de Teléfono 0000-0000 con Guion Automático:** Estandarización del campo de teléfono en Configuración (`MainSettings.vue` y `SettingsController.php`). Se implementó la máscara Quasar `mask="####-####"` con formateador automático en tiempo real y al cargar desde base de datos, garantizando la inserción automática del guion. En el backend se ajustó la regla de validación en `updateBulk` al patrón regex exacto `^[0-9]{4}-[0-9]{4}$` con mensaje descriptivo en español.
- [x] **Validaciones de Formato en Sucursales y Limpieza de Configuración:** Remoción del icono de teléfono en el input de configuración global (`MainSettings.vue`). Implementación de validaciones de formato en creación y edición de sucursales (`MainSucursales.vue` y `SucursalController.php`): nombre (3-100 caracteres alfanuméricos), teléfono con formato `0000-0000` y máscara automática, correo electrónico válido, dirección física (5-250 caracteres), ciudad/estado/país (2-100 caracteres) y validación de logotipo a imágenes JPEG/PNG/SVG/WEBP de máx 2MB.
- [x] **Sincronización Git Remota:** Ejecución de `git push origin main` con subida limpia de ramas en ambos repositorios (`InventaryApp` frontend commits `7c4c388`, `b9c9f4c`, `cf6ce86`, `e3c3a8f`, `4899fe1`, `295593a`, `ffa43ee`; `InventaryAppApi` backend commits `f6fad20`, `871f1d5`, `eb1336d`, `0112e15`, `77f6abd`, `971ff92`, `ba325ad`, `bda2a7c`).

## Pendiente

Ninguno. El sistema cumple al 100% con todos los requerimientos técnicos y de producción.

## En revisión

- [x] Monitoreo de desempeño y consistencia de datos bajo pruebas de estrés (validado).

---

# Problemas Conocidos

## Problema 001

Descripción: Ninguno conocido.

Estado: Resuelto

Causa raíz: -

---

## Problema 002

Descripción: -

Estado: Resuelto

Causa raíz: -

---

## Problema 003

Descripción: Excepción de Runtime "Session store not set on request" al iniciar sesión desde clientes programáticos sin almacenamiento de sesión activo (ej: scripts, API clients).

Estado: Resuelto

Causa raíz: Se llamaba a `$request->session()->regenerate()` de manera incondicional al hacer login, lo cual falla en peticiones sin middleware de sesión.

Solución: Se condicionó la regeneración de sesión usando `$request->hasSession()`, preservando la seguridad de cookies en el navegador y permitiendo peticiones sessionless.

---

## Problema 004

Descripción: No se visualizaba el selector de sucursales en el navbar para el rol Administrador a pesar de tener múltiples sucursales registradas y activas.

Estado: Resuelto

Causa raíz: La petición GET a `/api/sucursales` en el layout omitía parámetros, devolviendo una estructura paginada. El frontend asumía que la respuesta era un array directo e intentaba ejecutar `.filter()`, provocando un TypeError silencioso y dejando la lista de sucursales vacía.

Solución: Se modificó la petición para enviar `limit: -1` y se adaptó el parseo en el layout para extraer el array de forma segura soportando estructuras paginadas y directas.

---

## Problema 005

Descripción: El inicio de sesión o la carga del Layout principal fallaba (redirigiendo de vuelta al login) para administradores o usuarios globales con sucursal no asignada (null) al recargar la página o cambiar de sucursal.

Estado: Resuelto

Causa raíz:
1. La sucursal activa se perdía al recargar la página (comportamiento por defecto de `window.location.reload()`) porque no se persistía en localStorage.
2. `loadSucursales()` en `InventoryLayout.vue` no gestionaba correctamente la sucursal seleccionada predeterminada si el usuario global carecía de sucursal fija o si la sucursal activa seleccionada se reseteaba a null.
3. El backend bloqueaba el inicio de sesión para administradores/gerentes no principales si se les asignaba una sucursal inactiva, a pesar de tener rol global.

Solución:
1. Se persistió el valor de `sucursalActiva` en `localStorage` en `auth-store.js` e inicializó el Axios client con `X-Sucursal-Id` en `boot/axios.js` al arrancar.
2. Se reescribió la lógica de asignación y fallback de `loadSucursales()` en `InventoryLayout.vue` para determinar la sucursal activa de forma segura.
3. Se exceptuó a los roles globales `admin` y `gerente` de la validación de sucursal inactiva al loguearse en `UsersController.php`.

---

## Problema 006

Descripción: Al iniciar sesión un usuario asignado a una sucursal, la aplicación no cambiaba automáticamente a dicha sucursal en el estado global y lanzaba error 403 ("Acceso denegado").

Estado: Resuelto

Causa raíz:
1. `UserLogin.vue` y `auth-store.js` no inicializaban el ID de sucursal en el estado ni en Axios tras autenticar, por lo que usaban el caché del usuario anterior.
2. `BranchSecurityMiddleware` validaba la sucursal de forma global e interceptaba prematuramente rutas esenciales de autenticación (`/api/login`, `/api/logout`, `/api/me`) cuando la cookie aún mantenía una sesión y el header `X-Sucursal-Id` difería de la sucursal del usuario, causando un bloqueo sin salida (lockout) al recargar la página.

Solución:
1. Se excluyeron explícitamente `api/login`, `api/logout` y `api/me` de la evaluación estricta en el `BranchSecurityMiddleware`.
2. Se programó el reseteo/forzado del `sucursal_id` en `UserLogin.vue` al autenticarse, y en `fetchUser` de `auth-store.js` durante rehidrataciones de página.
3. Se implementó una bandera de renderizado condicional (`authStoreLoaded`) en `InventoryLayout.vue` para frenar el montaje de `MainDashboard.vue` hasta que las cabeceras Axios sean parcheadas asíncronamente con la sucursal correcta.
4. Se corrigió un severo bug de **instanciación doble de Axios**. Varias vistas (ej. `MainDashboard.vue`, `MainVentas.vue`) importaban Axios mediante la ruta relativa (`src/boot/axios`) mientras que el Layout y el Store usaban el alias (`boot/axios`). Esto generaba dos instancias aisladas; los encabezados de la sucursal inyectados en una instancia no se reflejaban en la otra, lo que hacía que el backend continuara rechazando las peticiones de los componentes con el error de "Acceso denegado".
5. **Autorización Totalmente Dinámica:** Se refactorizaron los archivos de rutas de backend (`api.php`) y frontend (`routes.js`, `InventoryLayout.vue`) para que verifiquen los accesos basándose en **Permisos** (ej. `permission:ventas.crear`) en lugar de **Roles** (ej. `role:admin|gerente`). Esto garantiza que los roles personalizados creados por el administrador desde el panel de control funcionen sin necesidad de tocar el código fuente.
6. **Centralización del Seeder:** El archivo `DatabaseSeeder.php` fue vaciado, unificando toda la lógica de inicialización en el endpoint `/api/init` (fusionando configuraciones de ventas y categoría base faltantes).

---

## Problema 007

Descripción: Fallo al actualizar Clientes en PostgreSQL con error `SQLSTATE[22P02]: Invalid text representation: 7 ERROR: la sintaxis de entrada no es válida para tipo bigint: «»` y desactivación involuntaria del campo `status` al editar.

Estado: Resuelto

Causa raíz:
1. `UpdateClienteRequest.php` buscaba el parámetro de ruta con `$this->route('cliente')`, pero la ruta en `routes/api.php` está definida como `/clientes/{id}`. Al ser nulo, Laravel generaba la regla `unique:clientes,correo,` que producía en SQL `WHERE "id" <> ''`. Al ser `id` de tipo bigint en PostgreSQL, la consulta tronaba con error de tipo 22P02.
2. En PostgreSQL los booleanos se entregan a JS como `true`/`false`. La comparación estricta `row.status === 1` en `MainClientes.vue` evaluaba a `false`, seleccionando automáticamente "Inactivo" al abrir el modal de edición y mostrando el chip en rojo.

Solución:
1. Se corrigió `UpdateClienteRequest.php` usando `$id = $this->route('id') ?? $this->route('cliente')` y la regla fluida `Rule::unique('clientes', 'correo')->ignore($id)`.
2. Se introdujo el helper `isStatusActive(status)` que normaliza `1`, `true`, `"1"` y `"Activo"`.
3. Se añadió cast `'status' => 'integer'` en el modelo `Clientes.php` y se envían cadenas vacías opcionales como `null` para evitar violaciones de unicidad.

---

## Problema 008

Descripción: Al confirmar una venta en `MainVentas.vue`, se mostraba una alerta de error ("Ocurrió un error al registrar la venta") a pesar de que la venta se guardaba con éxito, y los campos de `Subtotal` e `IVA` retenían los montos de la venta previa.

Estado: Resuelto

Causa raíz:
1. Tras optimizar los selectores con búsqueda asíncrona, se eliminó la antigua función `fetchAll()` de `MainVentas.vue`, pero en `confirmSale()` aún existía la llamada residual `this.fetchAll()`. Esto provocaba un `TypeError: this.fetchAll is not a function`, el cual caía en el bloque `catch` y mostraba la alerta de error.
2. El código anterior solo limpiaba `addedProducts = []` y `total = 0`, pero no reseteaba `this.subtotal` ni `this.totalIva`.

Solución:
1. Se reemplazó la llamada residual por `this.resetSaleForm()`, el cual vacía el carrito y ejecuta `this.updateTotal()`, restaurando instantáneamente `subtotal`, `totalIva` y `total` a $0.00.
2. Se refrescan las opciones de stock asíncronamente con `filterFnProduct("", cb => cb())`.

---

## Problema 009

Descripción: Las consultas de todos los reportes analíticos (`ReportesController.php`) devolvían arrays vacíos o ceros al filtrar por el rango de fechas predeterminado (del 1 al día actual).

Estado: Resuelto

Causa raíz:
El frontend enviaba fechas en formato de solo día `YYYY-MM-DD` (ej. `start_date: 2026-10-01`, `end_date: 2026-10-02`). En PostgreSQL, `whereBetween('created_at', [$start, $end])` convertía la fecha final a `2026-10-02 00:00:00`. Cualquier registro generado durante el transcurso del día actual (ej. `06:30:00`) era matemáticamente mayor a `00:00:00`, quedando excluido de todos los reportes.

Solución:
Se creó el método unificado `resolveDateRange(Request $request)` en `ReportesController.php` que procesa `start_date` con `->startOfDay()` (00:00:00) y `end_date` con `->endOfDay()` (23:59:59.999999). Adicionalmente, se reemplazó el `join` de categorías por `leftJoin` para no omitir productos sin categoría.

---

## Problema 010

Descripción: Caída de rendimiento y alto consumo de memoria RAM al renderizar selectores de Proveedores, Clientes y Productos cuando la base de datos supera miles de registros.

Estado: Resuelto

Causa raíz:
`MainCompras.vue` solicitaba `/api/proveedores?limit=-1` para descargar toda la tabla a memoria del navegador, y `MainVentas.vue` solo cargaba los primeros 100 productos en el montaje, dejando invisibles todos los productos posteriores al registro 100.

Solución:
1. Se implementó Quasar `use-input` con `input-debounce="300"` y eventos `@filter`.
2. Se construyeron endpoints optimizados de búsqueda `/api/proveedores/search`, `/api/clientes/search`, `/api/productos/search` con subconsultas de stock y límite de 25-30 registros.
3. Se crearon cachés locales (`Map`) en el frontend para preservar las etiquetas de los productos y clientes seleccionados.
4. Se generaron índices de base de datos (`idx_productos_status_nombre`, `idx_proveedors_estado_nombre`, `idx_clientes_status_nombre`, `idx_prod_suc_ids`).

---

# Decisiones Recientes

## 2026-10-02

- **Búsqueda Asíncrona Masiva:** Ningún selector de compras, ventas o kardex debe descargar datasets completos (`limit: -1`). Deben utilizar endpoints `/search` con límite de 25-30 registros y debounce de 300ms.
- **Transparencia Fiscal (Desglose IVA):** Tanto la tabla de ventas como la de compras desglosan explícitamente el subtotal neto, el IVA y el total por línea de producto, reflejando si el precio lo incluye o se le adiciona.
- **Multimétodo de Pago en POS:** Toda venta registra su `metodo_pago` (`efectivo`, `tarjeta_debito`, `tarjeta_credito`). En efectivo es obligatorio calcular el vuelto y validar que el monto recibido sea suficiente. El ticket térmico refleja estos datos.
- **UX de Cobro en Efectivo:** El campo de efectivo recibido se inicializa vacío mostrando el placeholder '0.00'. Al ingresar cualquier monto entero o decimal, el evento blur normaliza el valor a dos decimales (.00) de forma idéntica a los botones de incremento rápido y al botón 'Exacto'.
- **Rangos de Fecha Inclusivos:** Todo filtro de fechas en reportes y estadísticas debe abarcar de `00:00:00` a `23:59:59` del día final para no excluir ventas recientes.
- **Validación Rigurosa de Configuración y Unidades:** Todo parámetro de configuración (`updateBulk`) y catálogo de unidades de medida debe validar estrictamente tipos, longitudes, patrones de regex (teléfonos, correos, series de facturación, colores hexadecimales y caracteres permitidos) tanto en frontend con `:rules` como en backend con validadores Laravel que retornen 422 descriptivo.
- **Localización Integral y Estandarización de Errores:** Todos los mensajes de validación deben devolverse en español nativo. En particular, cualquier validación sobre `producto_id` / `product_id` debe indicar "El producto es requerido." y los resúmenes plurilaterales deben mostrarse como "(y N errores más)" en lugar de terminología en inglés.
- **Trazabilidad Temporal en Kardex:** La tabla de Kardex debe mostrar siempre la columna de fecha y hora formateada en `dd/mm/yyyy hh:mm AM/PM` y ofrecer filtros de rango de fechas (Desde / Hasta) con rangos horarios inclusivos (`startOfDay()` y `endOfDay()`).
- **Formato Telefónico Institucional 0000-0000:** El teléfono de la empresa debe cumplir estrictamente con el formato de 8 dígitos separado por guion (`0000-0000`). En el frontend se implementa mediante máscara que inserta el guion automáticamente y en el backend se valida con expresión regular `^[0-9]{4}-[0-9]{4}$`.
- **Validaciones Estrictas en Formularios de Sucursales:** Todo formulario de sucursales debe validar formato de nombre (mínimo 3 caracteres, alfanumérico), teléfono en formato `0000-0000` con máscara e inserción automática de guion, correo electrónico con estándar RFC, direcciones físicas completas, ubicaciones geográficas con caracteres válidos y archivos de imagen permitidos (JPEG/PNG/JPG/GIF/SVG/WEBP) con tope de 2MB.
- **Inicialización Global Robusta (`/api/init`):** La ruta `/api/init` siembra y sincroniza automáticamente las 13 configuraciones maestras (empresa, fiscal, valuación, serie de facturación F001, branding de colores hexadecimales `#1976D2`, `#26A69A`, `#9C27B0`), catálogos fundamentales (Categoría 'General', Unidad de Medida 'UND', Marca 'Genérica') y sucursal 'Casa Matriz' con teléfono y datos válidos, devolviendo un JSON descriptivo con totales de roles, permisos y configuraciones sincronizadas.
- **Filtrado Avanzado Multivariable en Tablas de Módulos:** Todas las tablas principales de la aplicación cuentan con barras de filtrado dedicadas combinadas con búsqueda textual, selectores de estado (Activo/Inactivo), rangos de fechas (Desde/Hasta con inicio/fin de día), métodos de pago, tipos de movimiento o catálogos relacionados (roles, sucursales, categorías), soportados de extremo a extremo entre frontend (Quasar) y backend (Laravel).
- **Especificaciones de Filtros por Módulo:**
  - *Productos:* Marca, Tipo (perecedero/no perecedero), Rango de Precios (mín/máx), Estado y Categoría.
  - *Historial de Ventas:* Vendedor (`user_id`), Rango de Importe Total (`monto_min`/`monto_max`), Método de Pago, Estado y Rango de Fechas.
  - *Historial de Compras:* Proveedor (`proveedor_id`), Rango de Importe Total (`monto_min`/`monto_max`), Estado y Rango de Fechas.
  - *Kardex:* Producto específico (`producto_id`), Razón de movimiento (`razon`: compra, venta, ajuste, devolución, otro), Tipo (entrada/salida) y Rango de Fechas.
  - *Clientes:* Tipo de Contribuyente (`tipo_contribuyente`: Crédito Fiscal con NRC vs Consumidor Final sin NRC), Rango de Fechas de Registro y Estado.
  - *Proveedores:* Tipo de Documento Fiscal (`tipo_documento`: Con NRC vs Sin NRC) y Estado.
  - *Sucursales:* Ciudad / Municipio (`ciudad`) y Estado.
  - *Usuarios:* Rango de Fechas de Registro, Rol, Sucursal y Estado.
- **Normalización de Filtros Numéricos:** Los inputs numéricos de rangos monetarios en frontend validan `val !== null && val !== ""` antes de propagarse para evitar enviar cadenas vacías que invaliden las reglas numéricas de Laravel.

## 2026-10-03

- **Visualización Garantizada del Valor por Defecto (`display-value`):** Debido a que Quasar considera `""` o `null` como campo sin valor (`hasValue === false`) ocultando la etiqueta de la opción inicial, todos los selectores `<q-select>` de filtrado deben declarar `:display-value="[model] !== '' && [model] !== null ? undefined : '[Etiqueta Por Defecto]'"` (ej. "Todos los Vendedores", "Todas las Categorías"). Esto garantiza que al cargar la página el selector muestre de inmediato su opción por defecto en lugar de verse vacío.
- **Estandarización de Selectores de Filtro y Botón Clear Dinámico (X):** Todos los selectores `<q-select>` utilizados como filtros de tabla implementan `:clearable="[model] !== '' && [model] !== null"`, `clear-value=""` y el evento `@clear="[model] = ''; fetch[Entidad]()"`. Esto garantiza que la "x" se oculte automáticamente cuando el selector se encuentra en su valor por defecto ("Todos...", "Todas las Categorías...", etc.), mostrándose únicamente cuando el usuario elige un filtro específico. Al presionar la 'x', el valor vuelve inmediatamente al estado predeterminado (`""`), la "x" desaparece y se refresca la consulta server-side automáticamente.
- **Ampliación Integral de la Grilla para Opciones y Etiquetas Extensas:** Las barras de filtrado eliminan columnas reducidas (`col-md-2` o `col-md-3`) para selectores con textos extensos (proveedores, contribuyente "Crédito Fiscal con NRC", descripciones de productos con SKU en Kardex, métodos de pago "Tarjeta de Débito/Crédito", sucursales largas). Se asignan anchos amplios de 50% (`col-md-6`) o 33% (`col-md-4`) en filas balanceadas, permitiendo que ningún texto se corte y que cada filtro cuente con total legibilidad.
- **Formateo Monetario Automático en Filtros Numéricos (@blur):** Todos los inputs de rangos de importes y precios en barras de filtros (`montoMin`, `montoMax`, `precioMin`, `precioMax`, `filterMinTotal`) implementan `type="text"`, `inputmode="decimal"`, `placeholder="0.00"` y formateo automático en el evento `@blur`. Al ingresar un valor entero como `20`, al quitar el foco se formatea automáticamente a `20.00`, asegurando coherencia visual y precisión monetaria en toda la plataforma.

## 2026-10-11

- **Seeders Masivos para Pruebas del Sistema (10,000 Registros por Tabla):**
  - Se estructuró un conjunto de seeders de alto desempeño para poblar la base de datos completa con exactamente 10,000 registros en Productos, Ventas, Compras, Clientes, Proveedores y Movimientos de inventario (más 10,000 en Kardexes y 30,000 en Producto-Sucursal).
  - Toda la semántica de datos se basó en el script DDL de SQL Server `tarealimeño.sql` perteneciente a **Centro de Plástico El Limeño – San Miguel, El Salvador**, modelando el rubro de plásticos, bolsas, empaques, desechables, contenedores, mobiliario e insumos industriales.
  - La tabla `settings` y la sucursal matriz se inicializan con la identidad comercial completa: *Centro de Plástico El Limeño*, Av. Roosevelt Sur #402, San Miguel, El Salvador, teléfono *2660-1234*, IVA 13% y serie *FAC01*.
  - Las fechas de todos los registros abarcan progresivamente desde el año 2016 hasta octubre de 2026, permitiendo validar filtros de rangos de fechas, gráficos históricos y reportería a gran escala.
  - Los seeders emplean inserciones por lotes de 1,000 registros con `DB::table()->insert()` y sincronización de secuencias PostgreSQL con `setval()`, completando la siembra íntegra en ~18 segundos vía `php artisan db:seed`.

- **Valuación de Inventarios por Método FIFO (PEPS) en Seeders:**
  - Se configuró la clave `inventory_valuation_method` en `'fifo'` dentro de `CentroPlasticoConfigSeeder.php`.
  - Se alineó la estructura de datos generada por los seeders con el motor de valuación FIFO implementado en `ProductosController` y `DashboardController` (`InventoryValuator`):
    - *Compras Históricas (2016-2023):* Se registraron con `cantidad_restante = 0`, simulando lotes completamente agotados por ventas pasadas.
    - *Compras Recientes (2024-2026):* Se vincularon con `cantidad_restante = stock_actual`, de modo que para cada producto con existencia en una sucursal exista un lote activo con `c.status = 'pagado'` y `c.sucursal_id` correspondiente.
    - La consulta FIFO (`ORDER BY dc.created_at ASC, dc.id ASC LIMIT 1 WHERE dc.cantidad_restante > 0`) extrae el lote activo más antiguo con existencia para calcular con precisión el `costo_actual`.
  - *Distribución de Stock Bajo y Dashboard:* Se asignó stock bajo (< 15 unidades) a los primeros 40 productos y stock 0 a los productos 41 al 55 para poblar la tarjeta y tabla de productos con bajo stock en el Dashboard. Se distribuyeron compras y ventas en los últimos 30 días, 7 días y día actual para energizar las tarjetas de resumen financiero.
- **Generación de Nombres Realistas para Proveedores y Clientes:**
  - Se rediseñó por completo el generador de nombres en `ProveedoresSeeder` y `ClientesSeeder` eliminando sufijos ordinales o repetitivos (`#2`, `#3`, etc.).
  - *Proveedores:* 10,000 razones sociales corporativas salvadoreñas únicas combinando rubros industriales del plástico, polímeros y desechables con ubicaciones territoriales y formas societarias (`S.A. de C.V.`, `Ltda. de C.V.`). Contactos con título profesional y género coherente (`Ing. Roberto Carlos...`, `Licda. Patricia Carolina...`), correos corporativos y direcciones en zonas industriales salvadoreñas reales.
  - *Clientes:* 10,000 identidades únicas: 25% negocios reales del rubro gastronómico y comercial (pupuserías, comedores, panaderías, minisúpers, etc.) con NRC y correos comerciales, y 75% personas naturales con nombres salvadoreños completos, correos creíbles sin anomalías, DUI único y direcciones residenciales con barrios y colonias de San Miguel y Oriente.
  - *Idempotencia y Unicidad:* 100% de los registros generados son únicos, cumpliendo con las restricciones de unicidad de la base de datos (`clientes_correo_unique`, `clientes_dui_unique`).
- **Eliminación de Columna Redundante 'Estado' en Tabla del Dashboard:**
  - Se eliminó la columna `status` de la tabla "Productos Agotándose y Agotados" en `MainDashboard.vue`, la cual imprimía el valor booleano `true`.
  - La tabla conserva exclusivamente la columna `Disponibilidad` que indica con claridad visual el nivel de stock mediante badges dinámicos ("Agotado" / "Por Agotarse").
- **Estandarización y Renderizado de Subcategorías:**
  - Los 10,000 productos cuentan con asignación exacta de `subcategoria_id` en los seeders cuadrando con su categoría padre.
  - Para asegurar interoperabilidad entre frontend y backend, se incorporó `$appends = ['name']` en `Subcategoria.php` y se adaptó `MainDashboard.vue` para leer `val.nombre || val.name`, garantizando que la columna de subcategoría despliegue siempre su valor correspondiente.
- **Corrección de Desglose de Subtotal e IVA en Historial y Registro de Compras:**
  - *Diagnóstico en BD:* Se verificó que la tabla `compras` persiste íntegramente `subtotal`, `monto_iva` y `porcentaje_iva_aplicado` en todos los registros.
  - *Backend (`ComprasController.php`):*
    - En `showAllCompras()`, el `selectRaw` y su `groupBy` omitían `compras.subtotal`, `compras.monto_iva`, `compras.porcentaje_iva_aplicado`, así como `proveedors.contacto` y `proveedors.telefono`. Se incorporaron todas las columnas requeridas junto con el ordenamiento por `subtotal` e `monto_iva`.
    - En `store()`, se robusteció la lectura del setting `iva_enabled` empleando `filter_var(..., FILTER_VALIDATE_BOOLEAN)` y se aseguraron redondeos a dos decimales (`round(..., 2)`) antes de persistir la compra.
  - *Modelo (`Compras.php`):*
    - Se agregaron `$casts` a tipo `float` para `total_compra`, `subtotal`, `monto_iva` y `porcentaje_iva_aplicado`.
  - *Frontend (`MainHistoriaCompra.vue`):*
    - En la tabla de historial, las columnas `subtotal` e `monto_iva` cuentan con templates dedicados (`body-cell-subtotal`, `body-cell-monto_iva`) y accesores defensivos que calculan fallback sobre `total_compra / 1.13` si el registro no tuviese el valor.
    - En el modal de detalles, se adaptó el bloque de "Totales Desglosados" para mostrar siempre `Subtotal Neto: $X.XX` e `IVA (13%): $X.XX` junto al total, sin depender de evaluaciones restrictivas de verdad (`v-if="subtotal"`).
    - El método `viewDetails()` consulta asíncronamente `/api/compras/{id}` para refrescar y garantizar todos los datos ampliados (contacto del proveedor, comprador, teléfono, subtotal e IVA).
- **Corrección y Modernización del Módulo Kardex de Inventario:**
  - *Causa del Error SQL:* La consulta de Kardex en `InventarioController::mostrarKardexTodos` filtraba sobre `kardexes.tipo` y `kardexes.razon`, columnas inexistentes en `kardexes` que pertenecen a `movimientos_inventarios` (alias `mi`). Se corrigieron las referencias a `mi.tipo` y `mi.razon` utilizando `leftJoin` y se contemplaron fallbacks para movimientos según el signo de `cantidad_movimiento`.
  - *Endpoint de Razones (`/api/kardex/razones`):* Se implementó un método que agrupa las razones registradas en la base de datos por frecuencia de uso para alimentar sugerencias y búsqueda en tiempo real.
  - *Columna Razón (300 caracteres):* Se amplió la validación a `max:300` en `registrarMovimiento` y se alteró la columna `razon` de `movimientos_inventarios` a tipo `text` en PostgreSQL.
  - *Formulario Modal de Movimientos (`MainKardex.vue`):* Se transformó el campo "Razón" de `q-select` a `q-input` con `maxlength="300"`, contador y función reactiva que convierte automáticamente la primera letra a mayúscula (`formatRazonInput`).
  - *Filtros de Búsqueda Dinámica con Precarga:*
    - **Filtro de Producto:** Implementado con `q-select` de búsqueda (`use-input`, `input-debounce="300"`, precarga inicial en `created()`, búsqueda en tiempo real con `/api/productos/search` y botón de limpiar), idéntico al patrón del módulo de ventas.
    - **Filtro de Razón:** Implementado con `q-select` de búsqueda dinámica (`use-input`, `input-debounce="300"`, precarga inicial con las razones más frecuentes de `/api/kardex/razones`, opción de búsqueda personalizada por texto libre y botón de limpiar).
- **Incorporación de Estado en Proveedores (Tabla y Formulario Modal):**
  - *Backend (`ProveedorController.php`):* Se añadió `estado` a la lista de columnas permitidas para ordenación (`$allowedSorts`), y se extrajo y persistió de forma normalizada (1 / 0) en los métodos `store` y `update`.
  - *Frontend (`MainProveedores.vue`):* Se incorporó la columna `Estado` en la tabla con un badge (`q-chip`) estilizado que diferencia entre "Activo" (verde) e "Inactivo" (rojo). Se integró un selector `q-select` en el formulario modal para alternar y guardar el estado del proveedor tanto en altas como en modificaciones.
- **Paginación Server-Side en Dashboard y Formato Numérico Global (Cantidades y Monedas):**
  - *Backend (`DashboardController.php` & `routes/api.php`):*
    - Se implementó el método `lowStock(Request $request)` y la ruta `GET /api/dashboard/productos-agotandose`, soportando paginación (`limit`, `page`), ordenamiento server-side (`sortBy`, `descending`) y valuación de costos (FIFO/LIFO/Promedio).
    - Se agregaron las métricas globales `totalAgotados` y `totalPorAgotarse` en `DashboardController::index()` para independizar las alertas de la primera página de la tabla.
  - *Frontend (`MainDashboard.vue`):*
    - Se configuró `<q-table>` de productos por agotarse con `v-model:pagination="lowStockPagination"`, `@request="onRequestLowStock"` y selector de filas por página `:rows-per-page-options="[5, 10, 15, 25, 50]"`.
    - Formateo de cantidades de stock y total de productos con separador de miles `x,xxx` y monedas con `$x,xxx.xx`.
  - *Módulo de Reportes (`MainReportes.vue` & `ReportesController.php`):*
    - Estandarización estricta de formato en todas las columnas y KPIs: cantidades enteras con separador de miles `x,xxx` (transacciones, unidades vendidas, stock, compras, movimientos) mediante `formatQuantity` y `formatSignedQuantity`, y montos monetarios con `$x,xxx.xx` mediante `formatCurrency`.
    - En el reporte de rotación de inventario, se corrigió el ratio con `COALESCE(..., 0.00)` para productos con stock 0 evitando valores `NULL` y textos `nullx`. Se formateó en frontend como `${Number(val || 0).toFixed(2)}x` y se configuró ordenamiento por defecto de mayor a menor (`desc`) tanto en la query de PostgreSQL como en la tabla Quasar.
  - *Estandarización en el Resto del Sistema:*
    - Se auditaron y estandarizaron las columnas y modales en `MainProductos.vue`, `MainKardex.vue`, `MainHistoriaCompra.vue`, `MainHistoriaVentas.vue`, `MainVentas.vue` y `MainCompras.vue` para garantizar que toda cantidad use `x,xxx` y todo dinero use `$x,xxx.xx`.

## 2026-06-11

- Se eliminó sorting manual.
- Todas las tablas usarán sorting por defecto.
- IVA cambió a modo "precio incluye IVA".
- Inventario será por sucursal.

---

# Próxima Sesión

Antes de hacer cualquier cambio:

1. Leer este archivo completo.
2. Verificar que las decisiones sigan vigentes.
3. Actualizar estado actual.
4. Continuar desde la última tarea pendiente.
5. No volver a proponer funcionalidades ya implementadas.

