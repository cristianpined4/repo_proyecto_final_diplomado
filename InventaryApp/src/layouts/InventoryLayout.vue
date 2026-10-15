<template>
  <q-layout view="hHh Lpr lFf">
    <q-header elevated class="border-bottom-translucent glassmorphic-header">
      <q-toolbar>
        <!-- Toggle button for desktop (collapses sidebar into mini mode) -->
        <q-btn
          flat
          round
          dense
          @click="mini = !mini"
          class="q-mx-xs gt-sm"
          :icon="mini ? 'menu' : 'menu_open'"
        />

        <!-- Toggle button for mobile/tablet (opens/closes drawer completely) -->
        <q-btn
          flat
          round
          dense
          @click="drawer = !drawer"
          class="q-mx-xs lt-md"
          icon="menu"
        />

        <!-- Header Title (only visible on mobile or when sidebar is mini) -->
        <q-toolbar-title
          v-if="$q.screen.lt.md || mini"
          class="row items-center"
        >
          <q-avatar v-if="companyLogo" size="28px" class="q-mr-sm">
            <q-img
              :src="companyLogo"
              style="height: 28px; width: 28px; object-fit: contain"
            />
          </q-avatar>
          <span class="text-subtitle1 text-weight-bold">{{
            companyName || "Inventory"
          }}</span>
        </q-toolbar-title>

        <q-space />

        <!-- Selector de Sucursales -->
        <q-select
          v-if="sucursales && sucursales.length > 0"
          v-model="sucursalSeleccionada"
          :options="sucursales"
          option-value="id"
          option-label="nombre"
          label="Sucursal Activa"
          dense
          filled
          dark
          :disable="isBranchSelectorDisabled"
          class="branch-select q-mr-md gt-xs"
          style="min-width: 220px"
          @update:model-value="changeBranch"
        >
          <template v-slot:prepend>
            <q-icon name="storefront" size="20px" class="branch-icon" />
          </template>
        </q-select>

        <!-- Campana de Alertas de Inventario / Notificaciones -->
        <q-btn flat round dense icon="notifications" class="q-mr-sm">
          <q-badge v-if="lowStockProducts.length > 0" color="red" floating>
            {{ lowStockProducts.length }}
          </q-badge>

          <q-menu
            style="min-width: 320px; border-radius: 12px"
            class="shadow-15"
          >
            <q-list style="max-width: 350px">
              <q-item-label
                header
                class="text-weight-bold text-primary text-subtitle1 row items-center justify-between q-py-sm"
              >
                <span>Alertas de Inventario</span>
                <q-chip
                  color="red-2"
                  text-color="red-9"
                  dense
                  class="text-weight-bold"
                  v-if="lowStockProducts.length > 0"
                >
                  {{ lowStockProducts.length }} crítico(s)
                </q-chip>
              </q-item-label>

              <q-separator />

              <q-item
                v-if="lowStockProducts.length === 0"
                class="q-py-xl text-center text-grey-6"
              >
                <q-item-section>
                  <q-icon
                    name="check_circle"
                    color="green"
                    size="48px"
                    class="q-mx-auto q-mb-sm"
                  />
                  <q-item-label
                    class="text-subtitle1 text-weight-bold text-grey-8"
                    >¡Todo al día!</q-item-label
                  >
                  <q-item-label caption
                    >No hay productos agotados o con bajo stock</q-item-label
                  >
                </q-item-section>
              </q-item>

              <div v-else style="max-height: 280px; overflow-y: auto">
                <q-item
                  v-for="prod in lowStockProducts"
                  :key="prod.id"
                  clickable
                  to="/inventario/productos"
                  class="q-py-md border-bottom-light"
                >
                  <q-item-section avatar>
                    <q-avatar
                      :color="prod.stock <= 0 ? 'red-2' : 'orange-2'"
                      :text-color="prod.stock <= 0 ? 'red-9' : 'orange-9'"
                    >
                      <q-icon name="warning" size="20px" />
                    </q-avatar>
                  </q-item-section>
                  <q-item-section>
                    <q-item-label
                      class="text-weight-bold text-grey-8 truncate-text"
                      style="max-width: 200px"
                    >
                      {{ prod.nombre }}
                    </q-item-label>
                    <q-item-label
                      caption
                      class="text-weight-medium"
                      :class="prod.stock <= 0 ? 'text-red-9' : 'text-orange-9'"
                    >
                      {{
                        prod.stock <= 0
                          ? "AGOTADO"
                          : `Bajo stock: ${prod.stock} disp.`
                      }}
                    </q-item-label>
                  </q-item-section>
                </q-item>
              </div>
            </q-list>
          </q-menu>
        </q-btn>

        <!-- Dark Mode Toggle -->
        <q-btn
          flat
          round
          dense
          :icon="$q.dark.isActive ? 'light_mode' : 'dark_mode'"
          @click="toggleDarkMode"
          class="q-mr-sm"
        />

        <!-- Menú de Usuario Premium -->
        <q-btn-dropdown flat no-caps class="profile-dropdown-btn">
          <template v-slot:label>
            <div class="row items-center no-wrap">
              <q-avatar
                size="32px"
                color="primary"
                text-color="white"
                class="q-mr-sm shadow-1"
              >
                {{
                  dataUser && dataUser.fullName
                    ? dataUser.fullName.charAt(0).toUpperCase()
                    : "U"
                }}
              </q-avatar>
              <div class="column justify-center gt-xs text-left">
                <div
                  class="text-subtitle2 text-weight-bold"
                  style="line-height: 1.2"
                >
                  {{ dataUser ? dataUser.fullName : "" }}
                </div>
                <div
                  class="text-caption text-grey-4 text-weight-medium"
                  style="font-size: 10px; line-height: 1.1; margin-top: 1px"
                >
                  {{ dataUser ? dataUser.role : "" }}
                </div>
              </div>
            </div>
          </template>

          <q-list style="min-width: 230px; border-radius: 12px">
            <q-item class="q-py-md bg-grey-2">
              <q-item-section avatar>
                <q-avatar color="primary" text-color="white">
                  {{
                    dataUser && dataUser.fullName
                      ? dataUser.fullName.charAt(0).toUpperCase()
                      : "U"
                  }}
                </q-avatar>
              </q-item-section>
              <q-item-section>
                <q-item-label class="text-weight-bold">{{
                  dataUser ? dataUser.fullName : "Usuario"
                }}</q-item-label>
                <div class="column items-start q-gutter-x-xs">
                  <span class="text-caption text-grey-7"
                    >{{
                      dataUser && dataUser.username
                        ? `@${dataUser.username}`
                        : ""
                    }}
                  </span>
                  <q-chip
                    dense
                    color="primary"
                    text-color="white"
                    class="text-weight-bold text-caption"
                    style="
                      font-size: 10px;
                      margin: 0;
                      padding: 2px 6px;
                      height: 18px;
                    "
                  >
                    {{ dataUser ? dataUser.role : "" }}
                  </q-chip>
                </div>
              </q-item-section>
            </q-item>

            <q-separator />

            <q-item
              clickable
              v-close-popup
              @click="openProfileModal"
              class="q-py-sm"
            >
              <q-item-section avatar>
                <q-icon name="manage_accounts" color="primary" />
              </q-item-section>
              <q-item-section>
                <q-item-label>Mi Perfil</q-item-label>
              </q-item-section>
            </q-item>

            <q-separator />

            <q-item
              clickable
              v-close-popup
              @click="logout"
              class="text-negative q-py-sm"
            >
              <q-item-section avatar>
                <q-icon name="logout" color="negative" />
              </q-item-section>
              <q-item-section>
                <q-item-label class="text-weight-bold"
                  >Cerrar Sesión</q-item-label
                >
              </q-item-section>
            </q-item>
          </q-list>
        </q-btn-dropdown>
      </q-toolbar>
    </q-header>

    <q-drawer
      show-if-above
      v-model="drawer"
      side="left"
      :mini="mini"
      @click="mini = false"
      class="sidebar-drawer"
    >
      <!-- Sidebar Header Branding (Expanded) -->
      <div
        v-if="!mini"
        class="sidebar-brand-section text-center q-py-lg border-bottom-translucent"
      >
        <q-avatar
          v-if="companyLogo"
          size="64px"
          class="q-mb-sm shadow-3 bg-white q-pa-xs"
        >
          <q-img
            :src="companyLogo"
            style="height: 56px; width: 56px; object-fit: contain"
          />
        </q-avatar>
        <q-avatar
          v-else
          size="64px"
          color="primary"
          text-color="white"
          class="q-mb-sm shadow-3"
        >
          <q-icon name="storefront" size="36px" />
        </q-avatar>
        <div
          class="text-h6 text-weight-bold text-brand-title q-px-md truncate-text"
        >
          {{ companyName || "Inventory App" }}
        </div>
        <div class="text-caption text-brand-subtitle tracking-wide">
          SISTEMA DE GESTIÓN
        </div>
      </div>

      <!-- Sidebar Header Branding (Mini) -->
      <div
        v-else
        class="sidebar-brand-section text-center q-py-md border-bottom-translucent"
      >
        <q-avatar
          v-if="companyLogo"
          size="36px"
          class="bg-white q-pa-xs shadow-2"
        >
          <q-img
            :src="companyLogo"
            style="height: 28px; width: 28px; object-fit: contain"
          />
        </q-avatar>
        <q-avatar
          v-else
          size="36px"
          color="primary"
          text-color="white"
          class="shadow-2"
        >
          <q-icon name="storefront" size="20px" />
        </q-avatar>
      </div>

      <!-- Scrollable Navigation Container -->
      <div class="nav-scroll-container">
        <!-- Navigation Menu -->
        <q-list class="nav q-mt-sm">
          <!-- Escritorio -->
          <q-item
            to="/dashboard"
            exact
            active-class="active-link"
            class="menu-item dashboard-item"
          >
            <q-item-section avatar>
              <q-icon name="dashboard" />
            </q-item-section>
            <q-item-section>Escritorio</q-item-section>
            <q-tooltip v-if="mini" anchor="center right" self="center left"
              >Escritorio</q-tooltip
            >
          </q-item>

          <!-- Compras -->
          <q-expansion-item
            v-if="hasPermission('compras.historial')"
            v-model="comprasOpen"
            expand-icon="expand_more"
            class="menu-expansion compras-expansion"
          >
            <template v-slot:header>
              <q-item-section avatar>
                <q-icon name="add_shopping_cart" />
              </q-item-section>
              <q-item-section>Compras</q-item-section>
              <q-tooltip v-if="mini" anchor="center right" self="center left"
                >Compras</q-tooltip
              >
            </template>
            <q-list>
              <q-item
                v-if="hasPermission('compras.crear')"
                to="/compras/registrar-compra"
                active-class="active-link"
                class="nested-item"
              >
                <q-item-section>Registrar Compra</q-item-section>
              </q-item>
              <q-item
                v-if="hasPermission('compras.historial')"
                to="/compras/historial-de-compras"
                active-class="active-link"
                class="nested-item"
              >
                <q-item-section>Historial de Compras</q-item-section>
              </q-item>
            </q-list>
          </q-expansion-item>

          <!-- Inventario -->
          <q-expansion-item
            v-if="hasPermission('inventario.ver') || hasPermission('categorias.ver') || hasPermission('productos.ver') || hasPermission('inventario.kardex')"
            v-model="inventarioOpen"
            expand-icon="expand_more"
            class="menu-expansion inventario-expansion"
          >
            <template v-slot:header>
              <q-item-section avatar>
                <q-icon name="inventory" />
              </q-item-section>
              <q-item-section>Inventario</q-item-section>
              <q-tooltip v-if="mini" anchor="center right" self="center left"
                >Inventario</q-tooltip
              >
            </template>
            <q-list>
              <q-item
                v-if="hasPermission('categorias.ver')"
                to="/inventario/categorias"
                active-class="active-link"
                class="nested-item"
              >
                <q-item-section>Categorías</q-item-section>
              </q-item>
              <q-item
                v-if="hasPermission('productos.ver')"
                to="/inventario/productos"
                active-class="active-link"
                class="nested-item"
              >
                <q-item-section>Productos</q-item-section>
              </q-item>
              <q-item
                v-if="hasPermission('inventario.kardex')"
                to="/inventario/kardex"
                active-class="active-link"
                class="nested-item"
              >
                <q-item-section>Kardex</q-item-section>
              </q-item>
              <q-item
                v-if="hasPermission('categorias.ver') || hasPermission('inventario.ver')"
                to="/inventario/catalogos"
                active-class="active-link"
                class="nested-item"
              >
                <q-item-section>Marcas y Modelos</q-item-section>
              </q-item>
              <q-item
                v-if="hasPermission('categorias.ver') || hasPermission('inventario.ver')"
                to="/inventario/unidades"
                active-class="active-link"
                class="nested-item"
              >
                <q-item-section>Unidades de Medida</q-item-section>
              </q-item>
              <q-item
                v-if="hasPermission('proveedores.ver')"
                to="/proveedores"
                active-class="active-link"
                class="nested-item"
              >
                <q-item-section>Proveedores</q-item-section>
              </q-item>
            </q-list>
          </q-expansion-item>

          <!-- Ventas -->
          <q-expansion-item v-if="hasPermission('clientes.ver') || hasPermission('ventas.crear') || hasPermission('ventas.historial')" v-model="ventasOpen" expand-icon="expand_more" class="menu-expansion ventas-expansion">
            <template v-slot:header>
              <q-item-section avatar>
                <q-icon name="monetization_on" />
              </q-item-section>
              <q-item-section>Ventas</q-item-section>
              <q-tooltip v-if="mini" anchor="center right" self="center left"
                >Ventas</q-tooltip
              >
            </template>
            <q-list>
              <q-item v-if="hasPermission('clientes.ver')" to="/ventas/clientes" active-class="active-link" class="nested-item">
                <q-item-section>Clientes</q-item-section>
              </q-item>
              <q-item v-if="hasPermission('ventas.crear')" to="/ventas/realizar-venta" active-class="active-link" class="nested-item">
                <q-item-section>Realizar Venta</q-item-section>
              </q-item>
              <q-item v-if="hasPermission('ventas.historial')" to="/ventas/historial-de-ventas" active-class="active-link" class="nested-item">
                <q-item-section>Historial de Ventas</q-item-section>
              </q-item>
            </q-list>
          </q-expansion-item>

          <!-- Reportes -->
          <q-item
            v-if="hasPermission('reportes.ver')"
            to="/reportes"
            exact
            active-class="active-link"
            class="menu-item reportes-item"
          >
            <q-item-section avatar>
              <q-icon name="analytics" />
            </q-item-section>
            <q-item-section>Reportes</q-item-section>
            <q-tooltip v-if="mini" anchor="center right" self="center left"
              >Reportes</q-tooltip
            >
          </q-item>

          <!-- Configuración General -->
          <q-expansion-item
            v-if="hasPermission('configuracion.ver') || hasPermission('sucursales.ver') || hasPermission('roles.ver') || hasPermission('usuarios.ver')"
            v-model="configuracionGeneralOpen"
            expand-icon="expand_more"
            class="menu-expansion config-expansion"
          >
            <template v-slot:header>
              <q-item-section avatar>
                <q-icon name="settings" />
              </q-item-section>
              <q-item-section>Configuración General</q-item-section>
              <q-tooltip v-if="mini" anchor="center right" self="center left"
                >Configuración</q-tooltip
              >
            </template>
            <q-list>
              <q-item
                  v-if="hasPermission('configuracion.ver')"
                  to="/configuracion"
                  active-class="active-link"
                  class="nested-item"
                >
                <q-item-section>Configuración</q-item-section>
              </q-item>
              <q-item v-if="hasPermission('roles.ver')" to="/roles" active-class="active-link" class="nested-item">
                <q-item-section>Roles y Permisos</q-item-section>
              </q-item>
              <q-item v-if="hasPermission('usuarios.ver')" to="/usuarios" active-class="active-link" class="nested-item">
                <q-item-section>Usuarios</q-item-section>
              </q-item>
              <q-item v-if="hasPermission('sucursales.ver')" to="/configuracion/sucursales" active-class="active-link" class="nested-item">
                <q-item-section>Sucursales</q-item-section>
              </q-item>
            </q-list>
          </q-expansion-item>
        </q-list>
      </div>
    </q-drawer>

    <q-page-container class="main-page-container">
      <div v-if="loading" class="loading-overlay flex flex-center">
        <q-spinner size="100px" color="primary" />
      </div>
      <router-view v-if="authStoreLoaded" />

      <!-- DIALOG DE PERFIL DE USUARIO -->
      <q-dialog v-model="profileModalOpen" persistent>
        <q-card style="min-width: 420px; border-radius: 12px; overflow: hidden">
          <q-card-section class="bg-primary text-white q-py-md">
            <div class="text-h6 text-weight-bold row items-center">
              <q-icon name="manage_accounts" class="q-mr-xs" />
              Mi Perfil de Usuario
            </div>
            <div class="text-caption opacity-80">
              Edita tus datos personales de acceso al sistema
            </div>
          </q-card-section>

          <q-card-section class="q-pa-md q-gutter-y-sm">
            <q-form ref="profileFormRef" @submit.prevent="saveProfile">
              <q-input
                v-model="profileForm.name"
                label="Nombre"
                filled
                class="rounded-input q-mb-sm"
                :rules="[(val) => !!val || 'El nombre es obligatorio']"
              />
              <q-input
                v-model="profileForm.lastname"
                label="Apellido"
                filled
                class="rounded-input q-mb-sm"
                :rules="[(val) => !!val || 'El apellido es obligatorio']"
              />
              <q-input
                v-model="profileForm.username"
                label="Nombre de Usuario"
                filled
                class="rounded-input q-mb-sm"
                :rules="[(val) => !!val || 'El usuario es obligatorio']"
              />
              <q-input
                v-model="profileForm.email"
                label="Correo Electrónico"
                filled
                type="email"
                class="rounded-input q-mb-sm"
              />
              <q-input
                v-model="profileForm.phone"
                label="Teléfono"
                filled
                class="rounded-input q-mb-sm"
              />
              <q-input
                v-model="profileForm.password"
                label="Nueva Contraseña (Opcional)"
                type="password"
                filled
                class="rounded-input q-mb-sm"
              />
              <q-input
                v-model="profileForm.password_confirmation"
                label="Confirmar Contraseña"
                type="password"
                filled
                class="rounded-input q-mb-md"
              />

              <div class="row justify-end q-mt-md">
                <q-btn
                  flat
                  label="Cancelar"
                  color="grey-7"
                  v-close-popup
                  class="q-mr-sm rounded-btn"
                />
                <q-btn
                  type="submit"
                  label="Guardar"
                  color="primary"
                  unelevated
                  class="rounded-btn q-px-md"
                />
              </div>
            </q-form>
          </q-card-section>
        </q-card>
      </q-dialog>
    </q-page-container>
  </q-layout>
</template>

<script>
import { api } from "boot/axios";
import Alert from "src/helpers/Alert";
import { useAuthStore } from "src/store/auth-store";
import { useLoadingStore } from "src/store/loading-store";
import { useSettingsStore } from "src/store/settings-store";

export default {
  data() {
    return {
      drawer: false,
      mini: false,
      inventarioOpen:
        this.$route.path.includes("inventario") ||
        this.$route.path.includes("proveedores"),
      ventasOpen: this.$route.path.includes("ventas"),
      comprasOpen: this.$route.path.includes("compras"),
      configuracionGeneralOpen:
        this.$route.path.includes("configuracion") ||
        this.$route.path.includes("roles") ||
        this.$route.path.includes("usuarios") ||
        this.$route.path.includes("sucursales"),
      sucursales: [],
      sucursalSeleccionada: null,
      profileModalOpen: false,
      lowStockProducts: [],
      profileForm: {
        name: "",
        lastname: "",
        username: "",
        email: "",
        phone: "",
        password: "",
        password_confirmation: "",
      },
      authStoreLoaded: false,
    };
  },
  computed: {
    loading() {
      const loadingStore = useLoadingStore();
      return loadingStore.loading;
    },
    companyName() {
      const settingsStore = useSettingsStore();
      return settingsStore.companyName;
    },
    companyLogo() {
      const settingsStore = useSettingsStore();
      return settingsStore.companyLogo;
    },
    dataUser() {
      const authStore = useAuthStore();
      const rolesList = (authStore.roles || [])
        .map((r) => {
          const name = typeof r === "string" ? r : r.name;
          if (name === "admin") return "Administrador";
          return name ? name.charAt(0).toUpperCase() + name.slice(1) : "";
        })
        .filter(Boolean);
      const rolesStr = rolesList.join(", ") || "Empleado";

      return {
        fullName: authStore.user
          ? `${authStore.user.name} ${authStore.user.lastname}`
          : "",
        username: authStore.user ? authStore.user.username : "",
        email: authStore.user ? authStore.user.email : "",
        id: authStore.user ? authStore.user.id : null,
        role: rolesStr,
      };
    },
    isBranchSelectorDisabled() {
      const authStore = useAuthStore();
      const isGlobalUser = this.hasRole("admin") || this.hasRole("gerente");
      return !!(
        authStore.user &&
        authStore.user.sucursal_id !== null &&
        !isGlobalUser
      );
    },
  },
  async mounted() {
    const savedDarkMode = localStorage.getItem("dark_mode");
    if (savedDarkMode !== null) {
      this.$q.dark.set(savedDarkMode === "true");
    }

    const loadingStore = useLoadingStore();
    loadingStore.startLoading();
    try {
      const authStore = useAuthStore();
      if (!authStore.isAuthenticated) {
        await authStore.fetchUser();
      }
      this.authStoreLoaded = true;

      // Cargar alertas de inventario (Bajo stock)
      try {
        const dashboardRes = await api.get("/api/dashboard");
        this.lowStockProducts = (
          dashboardRes.data.lowStockProducts || []
        ).filter((p) => p.stock <= 15);
      } catch (err) {
        console.error("No se pudieron cargar las alertas en el layout", err);
      }

      await this.loadSucursales();
      window.addEventListener("branch-updated", this.loadSucursales);
    } catch (error) {
      this.$router.push("/");
    } finally {
      loadingStore.stopLoading();
    }
  },
  unmounted() {
    window.removeEventListener("branch-updated", this.loadSucursales);
  },
  methods: {
    toggleDarkMode() {
      this.$q.dark.toggle();
      localStorage.setItem(
        "dark_mode",
        this.$q.dark.isActive ? "true" : "false"
      );
    },
    async loadSucursales() {
      try {
        const { useAuthStore } = await import("src/store/auth-store");
        const authStore = useAuthStore();
        const res = await api.get("/api/sucursales", { params: { limit: -1 } });
        const rawData = res.data.data || [];
        const rawList = Array.isArray(rawData) ? rawData : (rawData.data || []);
        this.sucursales = rawList.filter(
          (s) => s.activa === 1 || s.activa === true || s.activa === "1"
        );
        if (Array.isArray(this.sucursales) && this.sucursales.length > 0) {
          const isGlobalUser = this.hasRole("admin") || this.hasRole("gerente");
          
          let targetBranchId = authStore.sucursalActiva;

          if (authStore.user && authStore.user.sucursal_id !== null && !isGlobalUser) {
            targetBranchId = parseInt(authStore.user.sucursal_id);
          } else {
            const exists = this.sucursales.some((s) => parseInt(s.id) === parseInt(targetBranchId));
            if (!targetBranchId || !exists) {
              if (authStore.user && authStore.user.sucursal_id !== null) {
                targetBranchId = parseInt(authStore.user.sucursal_id);
              } else {
                targetBranchId = this.sucursales[0].id;
              }
            }
          }

          const branchObj = this.sucursales.find((s) => parseInt(s.id) === parseInt(targetBranchId));
          if (branchObj) {
            this.sucursalSeleccionada = branchObj;
            authStore.setSucursalActiva(parseInt(targetBranchId));
          } else {
            this.sucursalSeleccionada = {
              id: targetBranchId,
              nombre: "Sucursal Asignada (Inactiva o No encontrada)",
            };
            authStore.setSucursalActiva(parseInt(targetBranchId));
          }
        }
      } catch (e) {
        console.error("Error al cargar sucursales", e);
      }
    },
    hasPermission(permission) {
      const authStore = useAuthStore();
      if (this.hasRole('admin')) return true;
      const userPermissions = authStore.permissions || [];
      return userPermissions.includes(permission) || userPermissions.some(p => p.name === permission);
    },
    hasRole(role) {
      const authStore = useAuthStore();
      const roles = authStore.roles || [];
      return (
        roles.includes(role) ||
        roles.some((r) => (typeof r === "string" ? r : r.name) === role)
      );
    },
    toggleDrawer() {
      this.drawer = !this.drawer;
    },
    changeBranch(newVal) {
      const authStore = useAuthStore();
      authStore.setSucursalActiva(newVal.id);
      window.location.reload();
    },
    async logout() {
      const loadingStore = useLoadingStore();
      const authStore = useAuthStore();
      loadingStore.startLoading();
      try {
        await authStore.logout();
        this.$router.push("/");
      } catch (error) {
        Alert("Error", "No se pudo cerrar sesión", "error");
      } finally {
        loadingStore.stopLoading();
      }
    },
    openProfileModal() {
      const authStore = useAuthStore();
      if (authStore.user) {
        this.profileForm = {
          name: authStore.user.name,
          lastname: authStore.user.lastname,
          username: authStore.user.username,
          email: authStore.user.email || "",
          phone: authStore.user.phone || "",
          password: "",
          password_confirmation: "",
        };
        this.profileModalOpen = true;
      }
    },
    async saveProfile() {
      if (
        this.profileForm.password &&
        this.profileForm.password !== this.profileForm.password_confirmation
      ) {
        Alert("Error", "Las contraseñas no coinciden.", "warning");
        return;
      }
      const loadingStore = useLoadingStore();
      loadingStore.startLoading();
      try {
        const response = await api.post("/api/profile", this.profileForm);
        const authStore = useAuthStore();
        authStore.user = response.data.data;
        Alert("Éxito", "Perfil actualizado correctamente.", "success");
        this.profileModalOpen = false;
      } catch (error) {
        Alert(
          "Error",
          error.response?.data?.message || "No se pudo actualizar el perfil",
          "error"
        );
      } finally {
        loadingStore.stopLoading();
      }
    },
  },
};
</script>

<style scoped>
.glassmorphic-header {
  backdrop-filter: blur(12px) saturate(180%);
  -webkit-backdrop-filter: blur(12px) saturate(180%);
  transition: background-color 0.3s ease, color 0.3s ease;
  background-color: rgba(28, 28, 30, 0.9) !important; /* Always premium dark */
  color: #ffffff !important;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.border-bottom-translucent {
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

/* Sidebar Drawer Content Flex layout to handle screen height */
.sidebar-drawer :deep(.q-drawer__content) {
  display: flex;
  flex-direction: column;
  height: 100vh;
  overflow: hidden;
}

.sidebar-drawer {
  transition: background-color 0.3s ease, border-color 0.3s ease;
}

.body--light .sidebar-drawer {
  background-color: #e4e4e7 !important; /* Distinct grey background in light theme */
  border-right: 1px solid rgba(0, 0, 0, 0.08) !important;
}

.body--dark .sidebar-drawer {
  background-color: #1c1c1e !important; /* Premium dark in dark theme */
  border-right: 1px solid rgba(255, 255, 255, 0.08) !important;
}

.sidebar-brand-section {
  flex-shrink: 0; /* Keeps brand section fixed at top */
  transition: background-color 0.3s ease;
  background-color: #121212 !important; /* Always dark */
}

/* Brand logo titles color adaptation */
.text-brand-title {
  color: #ffffff !important;
}

.text-brand-subtitle {
  color: #9f9f9f !important;
}

.nav-scroll-container {
  flex-grow: 1; /* Automatically stretches to fill remaining drawer height */
  overflow-y: auto; /* Vertically scrolls only menu list */
}

/* Custom Scrollbar for Sidebar menu */
.nav-scroll-container::-webkit-scrollbar {
  width: 4px;
}

.body--light .nav-scroll-container::-webkit-scrollbar-thumb {
  background-color: rgba(0, 0, 0, 0.15);
  border-radius: 4px;
}

.body--dark .nav-scroll-container::-webkit-scrollbar-thumb {
  background-color: rgba(255, 255, 255, 0.12);
  border-radius: 4px;
}

.nav-scroll-container::-webkit-scrollbar-track {
  background: transparent;
}

.truncate-text {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.tracking-wide {
  letter-spacing: 0.12em;
}

.nav {
  padding: 8px 0;
}

/* High Contrast Menu Items */
.menu-item {
  margin: 6px 12px;
  border-radius: 8px;
  transition: all 0.3s ease;
  font-size: 0.95rem;
}

.body--light .menu-item {
  color: #09090b !important; /* Dark black links in light theme */
  font-weight: 500;
}

.body--dark .menu-item {
  color: #e4e4e7 !important; /* Zinc white links in dark theme */
  font-weight: 500;
}

/* Hover styles for menu item */
.body--light .menu-item:hover {
  background-color: rgba(0, 0, 0, 0.06) !important;
  color: #000000 !important;
}

.body--dark .menu-item:hover {
  background-color: rgba(255, 255, 255, 0.06) !important;
  color: #ffffff !important;
}

.body--light .menu-item:hover :deep(.q-icon) {
  color: #000000 !important;
}

.body--dark .menu-item:hover :deep(.q-icon) {
  color: #ffffff !important;
}

/* Expansion Items custom styling */
:deep(.q-expansion-item) {
  margin: 6px 12px;
  border-radius: 8px;
  transition: all 0.3s ease;
  font-size: 0.95rem;
}

.body--light :deep(.q-expansion-item) {
  color: #09090b !important;
  font-weight: 600;
}

.body--dark :deep(.q-expansion-item) {
  color: #e4e4e7 !important;
  font-weight: 500;
}

.body--light :deep(.q-expansion-item__container .q-item) {
  color: #09090b !important;
}

.body--dark :deep(.q-expansion-item__container .q-item) {
  color: #e4e4e7 !important;
}

.body--light :deep(.q-expansion-item__container .q-item:hover) {
  background-color: rgba(0, 0, 0, 0.06) !important;
  color: #000000 !important;
}

.body--dark :deep(.q-expansion-item__container .q-item:hover) {
  background-color: rgba(255, 255, 255, 0.06) !important;
  color: #ffffff !important;
}

.body--light :deep(.q-expansion-item__container .q-item:hover .q-icon) {
  color: #000000 !important;
}

.body--dark :deep(.q-expansion-item__container .q-item:hover .q-icon) {
  color: #ffffff !important;
}

.body--light :deep(.q-expansion-item--expanded) {
  background-color: rgba(0, 0, 0, 0.03);
}

.body--dark :deep(.q-expansion-item--expanded) {
  background-color: rgba(255, 255, 255, 0.03);
}

.nested-item {
  padding-left: 3.2rem !important;
  margin-left: 28px !important;
  border-radius: 0 8px 8px 0 !important;
  font-size: 0.9rem;
}

.body--light .nested-item {
  border-left: 2px solid rgba(0, 0, 0, 0.15);
  color: #1f2937 !important;
  font-weight: 500;
}

.body--dark .nested-item {
  border-left: 2px solid rgba(255, 255, 255, 0.08);
  color: #d4d4d8 !important;
  font-weight: 500;
}

.body--light .nested-item:hover {
  background-color: rgba(0, 0, 0, 0.05) !important;
  color: #000000 !important;
}

.body--dark .nested-item:hover {
  background-color: rgba(255, 255, 255, 0.05) !important;
  color: #ffffff !important;
}

/* COLOR CODED MENU ICONS FOR HIGH LEGIBILITY */
/* Dashboard Sky Blue */
.body--light .dashboard-item :deep(.q-icon),
.body--light .dashboard-item .q-icon {
  color: #0284c7 !important;
}
.body--dark .dashboard-item :deep(.q-icon),
.body--dark .dashboard-item .q-icon {
  color: #63c2e7 !important;
}

/* Compras Amber */
.body--light .compras-expansion :deep(.q-item__section--avatar .q-icon) {
  color: #d97706 !important;
}
.body--dark .compras-expansion :deep(.q-item__section--avatar .q-icon) {
  color: #f6c05d !important;
}

/* Inventario Green */
.body--light .inventario-expansion :deep(.q-item__section--avatar .q-icon) {
  color: #15803d !important;
}
.body--dark .inventario-expansion :deep(.q-item__section--avatar .q-icon) {
  color: #9fcb66 !important;
}

/* Ventas Orange/Gold */
.body--light .ventas-expansion :deep(.q-item__section--avatar .q-icon) {
  color: #e65100 !important;
}
.body--dark .ventas-expansion :deep(.q-item__section--avatar .q-icon) {
  color: #ff9f43 !important;
}

/* Reportes Lavender Purple */
.body--light .reportes-item :deep(.q-icon),
.body--light .reportes-item .q-icon {
  color: #6a1b9a !important;
}
.body--dark .reportes-item :deep(.q-icon),
.body--dark .reportes-item .q-icon {
  color: #b388ff !important;
}

/* Config Vibrant Cyan */
.body--light .config-expansion :deep(.q-item__section--avatar .q-icon) {
  color: #00838f !important;
}
.body--dark .config-expansion :deep(.q-item__section--avatar .q-icon) {
  color: #00d4ff !important;
}

/* SOLID background instead of gradient for active link */
.active-link,
.body--light .active-link,
.body--dark .active-link {
  background-color: var(--q-primary) !important;
  color: #ffffff !important;
  font-weight: 600;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  border-left: none !important;
}

/* Force white icons on active links */
.active-link .q-icon,
.active-link :deep(.q-icon),
.body--light .active-link :deep(.q-icon),
.body--dark .active-link :deep(.q-icon) {
  color: #ffffff !important;
}

/* Branch selector customization */
.branch-select :deep(.q-field__control) {
  border-radius: 8px;
  background-color: rgba(255, 255, 255, 0.08) !important;
}

.branch-select :deep(.q-field__native),
.branch-select :deep(.q-field__prefix),
.branch-select :deep(.q-field__icon),
.branch-select .branch-icon {
  color: #ffffff !important;
}

.branch-select :deep(.q-field__label) {
  color: rgba(255, 255, 255, 0.6) !important;
}

.main-page-container {
  padding: 0.5rem 1rem;
  transition: background-color 0.3s ease;
}

.body--light .main-page-container {
  background-color: #f4f4f7 !important; /* Sleek, professional light grey layout background */
}

.loading-overlay {
  width: 100%;
  position: absolute;
  z-index: 9999;
  background: rgba(0, 0, 0, 0.4);
  backdrop-filter: blur(4px);
  top: 0;
  bottom: 0;
  left: 0;
  right: 0;
}

.rounded-btn {
  border-radius: 8px;
  text-transform: none;
}

.rounded-input {
  border-radius: 8px;
}

.border-bottom-light {
  border-bottom: 1px solid rgba(0, 0, 0, 0.05);
}
</style>

