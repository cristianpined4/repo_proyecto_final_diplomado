import UserLogin from "src/pages/UserLogin.vue";
import InventoryLayout from "src/layouts/InventoryLayout.vue";
import Dashboard from "src/pages/MainDashboard.vue";
import Compras from "src/pages/MainCompras.vue";
import HistorialCompras from "src/pages/MainHistoriaCompra.vue";
import Productos from "src/pages/MainProductos.vue";
import Categorias from "src/pages/MainCategorias.vue";
import Kardex from "src/pages/MainKardex.vue";
import Clientes from "src/pages/MainClientes.vue";
import Ventas from "src/pages/MainVentas.vue";
import HistorialVentas from "src/pages/MainHistoriaVentas.vue";
import Usuarios from "src/pages/MainUsuarios.vue";
import Settings from "src/pages/MainSettings.vue";
import Reportes from "src/pages/MainReportes.vue";
import Error403 from "src/pages/Error403.vue";
import ErrorNotFound from "src/pages/Error404.vue";

const routes = [
  {
    path: "/",
    component: UserLogin,
    name: "UserLogin",
    meta: { title: "Acceso" }
  },
  {
    path: "/login",
    component: UserLogin,
    name: "UserLogin2",
    meta: { title: "Acceso" }
  },
  {
    path: "/403",
    component: Error403,
    name: "Error403",
    meta: { title: "Acceso Denegado" }
  },
  {
    path: "/dashboard",
    component: InventoryLayout,
    children: [
      {
        path: "/dashboard",
        component: Dashboard,
        meta: { title: "Escritorio", permissions: ["dashboard.ver"] },
      },
      {
        path: "/reportes",
        component: Reportes,
        meta: { title: "Reportes", permissions: ["reportes.ver"] },
      },
      {
        path: "/proveedores",
        component: () => import("src/pages/MainProveedores.vue"),
        meta: { title: "Proveedores", permissions: ["proveedores.ver"] },
      },
      {
        path: "/compras",
        children: [
          {
            path: "/compras/registrar-compra",
            component: Compras,
            meta: { title: "Registrar Compra", permissions: ["compras.crear"] },
          },
          {
            path: "/compras/historial-de-compras",
            component: HistorialCompras,
            meta: { title: "Historial de Compras", permissions: ["compras.historial"] },
          },
        ],
      },
      {
        path: "/inventario",
        children: [
          {
            path: "/inventario/categorias",
            component: Categorias,
            meta: { title: "Categorías", permissions: ["categorias.ver"] },
          },
          {
            path: "/inventario/productos",
            component: Productos,
            meta: { title: "Productos", permissions: ["productos.ver"] },
          },
          {
            path: "/inventario/kardex",
            component: Kardex,
            meta: { title: "Kardex", permissions: ["inventario.kardex"] },
          },
          {
            path: "/inventario/catalogos",
            component: () => import("src/pages/MainCatalogos.vue"),
            meta: { title: "Marcas y Modelos", permissions: ["categorias.ver", "inventario.ver"] },
          },
          {
            path: "/inventario/unidades",
            component: () => import("src/pages/MainUnidades.vue"),
            meta: { title: "Unidades de Medida", permissions: ["categorias.ver", "inventario.ver"] },
          },
        ],
      },
      {
        path: "/ventas",
        children: [
          {
            path: "/ventas/clientes",
            component: Clientes,
            meta: { title: "Clientes", permissions: ["clientes.ver"] },
          },
          {
            path: "/ventas/realizar-venta",
            component: Ventas,
            meta: { title: "Realizar Venta", permissions: ["ventas.crear"] },
          },
          {
            path: "/ventas/historial-de-ventas",
            component: HistorialVentas,
            meta: { title: "Historial de Ventas", permissions: ["ventas.historial"] },
          },
        ],
      },
      {
        path: "/usuarios",
        component: Usuarios,
        meta: { title: "Usuarios", permissions: ["usuarios.ver"] },
      },
      {
        path: "/configuracion",
        component: Settings,
        meta: { title: "Configuración General", permissions: ["configuracion.ver"] },
      },
      {
        path: "/roles",
        component: () => import("src/pages/MainRoles.vue"),
        meta: { title: "Roles y Permisos", permissions: ["roles.ver"] },
      },
      {
        path: "/configuracion/sucursales",
        component: () => import("src/pages/MainSucursales.vue"),
        meta: { title: "Sucursales", permissions: ["sucursales.ver"] },
      },
    ],
  },
  {
    path: "/:catchAll(.*)*",
    component: ErrorNotFound,
    meta: { title: "Página No Encontrada" }
  },
];

export default routes;
