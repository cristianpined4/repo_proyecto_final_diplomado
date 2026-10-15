# 📦 Sistema ERP de Inventario y Ventas

> **Proyecto Final — Pre-Especialización en Bases de Datos e Inteligencia de Negocios**
> Universidad de El Salvador (UES)

---

## 👥 Integrantes del Equipo

| Nombre | Carnet |
|---|---|
| Cristian Alberto Pineda Blanco | PB20002 |
| Andrés Isaí Vásquez Vásquez | VV18009 |
| Luis Andrés Parada Barrero | PB19022 |

---

## 📋 Descripción del Proyecto

Sistema ERP (Enterprise Resource Planning) de gestión de inventario y ventas orientado a empresas con múltiples sucursales. Permite administrar productos, ventas, compras, kardex, clientes, proveedores, usuarios y reportes desde una interfaz de escritorio moderna.

El sistema fue desarrollado como proyecto final de la Pre-Especialización en Bases de Datos e Inteligencia de Negocios de la Universidad de El Salvador, aplicando principios de diseño de bases de datos relacionales, arquitectura de APIs REST y desarrollo de software orientado a la inteligencia de negocios.

---

## 🏗️ Arquitectura del Sistema

El proyecto está compuesto por dos aplicaciones independientes:

```
InventaryApp/
├── InventaryApp/        # Frontend — Quasar Framework (Vue 3) + Electron
└── InventaryAppApi/     # Backend — Laravel 11 (API REST)
```

### Stack Tecnológico

#### Backend (`InventaryAppApi`)
| Tecnología | Versión | Uso |
|---|---|---|
| PHP | ^8.2 | Lenguaje principal |
| Laravel | ^11.9 | Framework API REST |
| Laravel Sanctum | ^4.3 | Autenticación por tokens |
| Spatie Permission | ^6.25 | Gestión de roles y permisos |
| MySQL | 8.x | Base de datos principal |

#### Frontend (`InventaryApp`)
| Tecnología | Versión | Uso |
|---|---|---|
| Vue 3 | ^3.4 | Framework reactivo |
| Quasar Framework | ^2.16 | UI Components + Build |
| Electron | ^42 | Empaquetado de escritorio |
| Pinia | ^2.3 | Manejo de estado global |
| Axios | ^1.2 | Cliente HTTP |
| Vue Router | ^4.0 | Enrutamiento SPA |

---

## ✨ Módulos del Sistema

### 📊 Dashboard
- Métricas generales del negocio en tiempo real
- Gráficas de ventas, compras e inventario

### 🏪 Gestión de Ventas (POS)
- Punto de venta con búsqueda dinámica de productos
- Cálculo automático de IVA (incluido / a agregar)
- Múltiples métodos de pago: efectivo, tarjeta débito, tarjeta crédito
- Cálculo automático de vuelto con botones de incremento rápido
- Impresión de tickets térmicos
- Validación de stock en tiempo real

### 🛒 Gestión de Compras
- Registro de compras a proveedores
- Actualización automática de inventario y kardex
- Desglose tributario (subtotal, IVA, total)
- Historial completo con modal de detalles enriquecido

### 📦 Inventario y Kardex
- Stock aislado por sucursal
- Trazabilidad completa de movimientos (entrada, salida, ajuste, transferencia)
- Kardex con fechas en formato AM/PM y filtro por rango de fechas
- Badges visuales para entradas (+) y salidas (-)

### 🏷️ Catálogos
- **Productos**: gestión completa con imágenes, categorías, subcategorías, marcas, modelos
- **Categorías y Subcategorías**: CRUD anidado con modal integrado
- **Marcas y Modelos**
- **Unidades de Medida**
- **Clientes**: validación de NIT/NRC/correo
- **Proveedores**: validación de NIT/NRC/correo/teléfono
- **Sucursales**: aislamiento completo de datos por sucursal

### 👤 Usuarios y Roles
- Gestión de usuarios con asignación de sucursal
- Roles: Administrador, Gerente, Vendedor, Bodeguero
- Control de acceso basado en permisos (Spatie Permission)

### 📈 Reportes
- Reportes de ventas, compras e inventario por rango de fechas
- Filtrado por sucursal, categoría y otros parámetros
- Soporte para grandes volúmenes de datos con paginación server-side

### ⚙️ Configuración Global
- Parámetros de empresa: nombre, correo, teléfono, dirección, logotipo
- Configuración de IVA (porcentaje parametrizable)
- Moneda, series y correlativos de documentos
- Método de valoración de inventario (promedio, FIFO, LIFO)

---

## 🚀 Instalación y Puesta en Marcha

### Prerrequisitos

- **PHP** >= 8.2
- **Composer** >= 2.x
- **Node.js** >= 18.x / **npm** >= 6.13
- **MySQL** 8.x
- **Git**

---

### 1. Clonar el Repositorio

```bash
git clone https://github.com/cristianpined4/repo_proyecto_final_diplomado.git
cd repo_proyecto_final_diplomado
```

---

### 2. Configurar el Backend (API Laravel)

```bash
cd InventaryAppApi

# Instalar dependencias de PHP
composer install

# Copiar el archivo de entorno
cp .env.example .env

# Generar la clave de la aplicación
php artisan key:generate
```

Editar el archivo `.env` con los datos de tu base de datos MySQL:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=api_inventaty_app
DB_USERNAME=root
DB_PASSWORD=tu_contraseña
```

```bash
# Ejecutar migraciones y seeders
php artisan migrate --seed

# Crear el enlace simbólico para el almacenamiento de imágenes
php artisan storage:link

# Iniciar el servidor de desarrollo
php artisan serve
```

> La API quedará disponible en `http://localhost:8000`

---

### 3. Configurar el Frontend (Quasar + Electron)

```bash
cd ../InventaryApp

# Instalar dependencias de Node
npm install

# Instalar Quasar CLI (si no está instalado globalmente)
npm install -g @quasar/cli

# Iniciar en modo desarrollo (web)
npm run dev
```

> La aplicación web quedará disponible en `http://localhost:9000`

Para compilar la aplicación de escritorio Electron:

```bash
npm run build
```

---

## 🗄️ Base de Datos

### Modelo Principal

El sistema utiliza una base de datos MySQL con las siguientes entidades principales:

- `users` — Usuarios del sistema
- `sucursals` — Sucursales de la empresa
- `categorias` — Categorías de productos
- `subcategorias` — Subcategorías anidadas
- `marcas` — Marcas de productos
- `modelos` — Modelos de productos
- `unidads` — Unidades de medida
- `productos` — Catálogo de productos
- `producto_sucursal` — Stock por sucursal (inventario aislado)
- `clientes` — Clientes
- `proveedors` — Proveedores
- `ventas` — Cabecera de ventas
- `detalles_ventas` — Detalle de líneas de venta
- `compras` — Cabecera de compras
- `detalles_compras` — Detalle de líneas de compra
- `kardex` — Trazabilidad de movimientos de inventario
- `movimientos_inventarios` — Movimientos de inventario
- `settings` — Configuración global parametrizada
- `audit_logs` — Auditoría del sistema

El script SQL base se encuentra en [`tarealimeño.sql`](./tarealimeño.sql).

---

## 🔑 Credenciales por Defecto

Tras ejecutar los seeders, se crean las siguientes credenciales de acceso:

| Usuario | Contraseña | Rol |
|---|---|---|
| `admin@admin.com` | `password` | Administrador |

> ⚠️ **Cambiar la contraseña por defecto antes de poner en producción.**

---

## 📐 Principios de Diseño del Sistema

- **Paginación 100% server-side** — No se cargan datasets completos en memoria
- **Búsqueda y filtros server-side** — Óptimo para +100,000 registros
- **Inventario por sucursal** — No existe stock global; todo pertenece a una sucursal
- **Trazabilidad total** — Todo movimiento genera un registro en el kardex
- **IVA parametrizable** — El porcentaje de IVA se configura desde base de datos
- **Configuración sin hardcoding** — Empresa, moneda, series y correlativos vienen de BD
- **API REST estandarizada** — Respuestas uniformes `{ success, message, data }`
- **Roles y permisos granulares** — Control de acceso basado en Spatie Permission

---

## 📁 Estructura de Carpetas

### Backend (`InventaryAppApi/`)
```
app/
├── Http/
│   ├── Controllers/     # Controladores de la API
│   └── Requests/        # Validaciones de formularios
├── Models/              # Modelos Eloquent
├── Services/            # Lógica de negocio
├── Policies/            # Políticas de autorización
├── Observers/           # Observadores de modelos
└── Helpers/             # Funciones auxiliares
database/
├── migrations/          # Migraciones de base de datos
└── seeders/             # Datos semilla iniciales
routes/
└── api.php              # Definición de rutas de la API
```

### Frontend (`InventaryApp/`)
```
src/
├── pages/               # Vistas principales de cada módulo
├── layouts/             # Layouts (InventoryLayout, Login)
├── store/               # Stores de Pinia
├── router/              # Definición de rutas Vue Router
├── boot/                # Inicialización de plugins (Axios, i18n)
├── helpers/             # Funciones auxiliares del frontend
└── assets/              # Recursos estáticos
src-electron/            # Configuración de Electron (modo escritorio)
```

---

## 📄 Documentación Adicional

- [`Lineamientos_Proyecto_Final.pdf`](./Lineamientos_Proyecto_Final.pdf) — Requerimientos oficiales del proyecto
- [`tarealimeño.sql`](./tarealimeño.sql) — Script SQL de la base de datos

---

## 🎓 Contexto Académico

**Programa:** Pre-Especialización en Bases de Datos e Inteligencia de Negocios
**Institución:** Universidad de El Salvador (UES)
**Tipo de proyecto:** Proyecto Final Integrador

Este sistema integra los conocimientos adquiridos durante la pre-especialización en las áreas de:
- Diseño y modelado de bases de datos relacionales
- Optimización de consultas SQL e índices compuestos
- Desarrollo de APIs REST para inteligencia de negocios
- Arquitectura de software multicapa
- Gestión de roles, permisos y auditoría de datos

---

*Desarrollado con ❤️ por el equipo PB20002 · VV18009 · PB19022 — UES 2025*
