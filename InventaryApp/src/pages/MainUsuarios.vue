<template>
  <q-page class="q-pa-md">
    <!-- Header Banner -->
    <q-card
      class="bg-gradient-brand text-white q-mb-md shadow-2 rounded-card overflow-hidden"
    >
      <q-card-section class="q-py-md">
        <div class="row items-center justify-between no-wrap">
          <div class="row items-center">
            <q-avatar
              icon="people"
              size="48px"
              class="q-mr-md bg-white-translucent text-white"
            />
            <div>
              <div class="text-h5 text-weight-bold">Control de Usuarios</div>
              <div class="text-subtitle2 opacity-80">
                Administra las credenciales, sucursales asignadas, roles y
                accesos de los empleados
              </div>
            </div>
          </div>
          <q-btn
            v-if="authStore.hasPermission('usuarios.crear')"
            color="white"
            text-color="primary"
            label="Agregar Usuario"
            icon="add"
            unelevated
            class="rounded-btn q-px-md text-weight-bold"
            @click="openModal(null)"
          />
        </div>
      </q-card-section>
    </q-card>

    <!-- Search / Filter Card -->
    <q-card flat bordered class="q-pa-md q-mb-md rounded-card bg-white shadow-1">
      <div class="row q-col-gutter-md items-center">
        <!-- Búsqueda -->
        <div class="col-12 col-md-6">
          <q-input
            v-model="search"
            label="Buscar por nombre, usuario, correo..."
            outlined
            dense
            debounce="300"
            class="rounded-input"
            @update:model-value="fetchUsers"
          >
            <template v-slot:prepend>
              <q-icon name="search" color="grey-6" />
            </template>
            <template v-slot:append v-if="search">
              <q-icon
                name="close"
                class="cursor-pointer"
                @click="
                  search = '';
                  fetchUsers();
                "
              />
            </template>
          </q-input>
        </div>

        <!-- Filtro Sucursal -->
        <div class="col-12 col-md-6">
          <q-select
            v-model="sucursalFilter"
            :options="sucursalesFilterOptions"
            label="Sucursal"
            outlined
            dense
            emit-value
            map-options
            :display-value="sucursalFilter !== '' && sucursalFilter !== null ? undefined : 'Todas las Sucursales'"
            :clearable="sucursalFilter !== '' && sucursalFilter !== null"
            clear-value=""
            @clear="sucursalFilter = ''; fetchUsers()"
            class="rounded-input"
            @update:model-value="fetchUsers"
          >
            <template v-slot:prepend>
              <q-icon name="store" color="primary" />
            </template>
          </q-select>
        </div>

        <!-- Filtro Rol -->
        <div class="col-12 col-sm-6 col-md-6">
          <q-select
            v-model="rolFilter"
            :options="rolesFilterOptions"
            label="Rol"
            outlined
            dense
            emit-value
            map-options
            :display-value="rolFilter !== '' && rolFilter !== null ? undefined : 'Todos los Roles'"
            :clearable="rolFilter !== '' && rolFilter !== null"
            clear-value=""
            @clear="rolFilter = ''; fetchUsers()"
            class="rounded-input"
            @update:model-value="fetchUsers"
          >
            <template v-slot:prepend>
              <q-icon name="badge" color="primary" />
            </template>
          </q-select>
        </div>

        <!-- Filtro Estado -->
        <div class="col-12 col-sm-6 col-md-6">
          <q-select
            v-model="statusFilter"
            :options="[
              { label: 'Todos los Estados', value: '' },
              { label: 'Activos', value: '1' },
              { label: 'Inactivos', value: '0' },
            ]"
            label="Estado"
            outlined
            dense
            emit-value
            map-options
            :display-value="statusFilter !== '' && statusFilter !== null ? undefined : 'Todos los Estados'"
            :clearable="statusFilter !== '' && statusFilter !== null"
            clear-value=""
            @clear="statusFilter = ''; fetchUsers()"
            class="rounded-input"
            @update:model-value="fetchUsers"
          >
            <template v-slot:prepend>
              <q-icon name="toggle_on" color="primary" />
            </template>
          </q-select>
        </div>

        <!-- Fecha Desde -->
        <div class="col-12 col-sm-4 col-md-4">
          <q-input
            v-model="fechaDesde"
            type="date"
            label="Registrado Desde"
            outlined
            dense
            stack-label
            class="rounded-input"
            @update:model-value="fetchUsers"
          >
            <template v-slot:prepend><q-icon name="event" color="primary" /></template>
            <template v-slot:append v-if="fechaDesde">
              <q-icon name="close" class="cursor-pointer" @click="fechaDesde = ''; fetchUsers()" />
            </template>
          </q-input>
        </div>

        <!-- Fecha Hasta -->
        <div class="col-12 col-sm-4 col-md-4">
          <q-input
            v-model="fechaHasta"
            type="date"
            label="Registrado Hasta"
            outlined
            dense
            stack-label
            class="rounded-input"
            @update:model-value="fetchUsers"
          >
            <template v-slot:prepend><q-icon name="event" color="primary" /></template>
            <template v-slot:append v-if="fechaHasta">
              <q-icon name="close" class="cursor-pointer" @click="fechaHasta = ''; fetchUsers()" />
            </template>
          </q-input>
        </div>

        <!-- Limpiar Filtros -->
        <div class="col-12 col-sm-4 col-md-4 row justify-end">
          <q-btn
            flat
            color="primary"
            icon="filter_alt_off"
            label="Limpiar"
            @click="clearFilters"
            class="rounded-btn full-width"
          />
        </div>
      </div>
    </q-card>

    <!-- Table Card -->
    <q-card flat bordered class="rounded-card overflow-hidden bg-white">
      <q-table
        :loading="loading"
        :rows="rows"
        :columns="columns"
        row-key="id"
        :pagination="pagination"
        @update:pagination="(val) => (pagination = val)"
        @request="onRequest"
        :filter="search"
        flat
        class="no-shadow"
        binary-state-sort
        no-data-label="No se encontraron usuarios registrados"
      >
        <!-- Slot para status -->
        <template v-slot:body-cell-status="props">
          <q-td align="center">
            <q-chip
              :color="
                props.row.status === 1 || props.row.status === true
                  ? 'green-2'
                  : 'red-2'
              "
              :text-color="
                props.row.status === 1 || props.row.status === true
                  ? 'green-9'
                  : 'red-9'
              "
              :label="
                props.row.status === 1 || props.row.status === true
                  ? 'Activo'
                  : 'Inactivo'
              "
              dense
              class="text-weight-bold"
            />
          </q-td>
        </template>

        <template v-slot:body-cell-options="props">
          <q-td align="right">
            <q-btn-dropdown
              v-if="authStore.hasPermission('usuarios.editar') || authStore.hasPermission('usuarios.eliminar')"
              flat
              color="dark"
              icon="more_vert"
              size="md"
            >
              <q-list>
                <q-item
                  clickable
                  v-close-popup
                  v-if="authStore.hasPermission('usuarios.editar')"
                  @click="openModal(props.row)"
                >
                  <q-item-section avatar>
                    <q-icon name="edit" color="primary" />
                  </q-item-section>
                  <q-item-section> Editar </q-item-section>
                </q-item>

                <q-item
                  clickable
                  v-close-popup
                  v-if="
                    authStore.hasPermission('usuarios.eliminar') &&
                    props.row.id !== dataUser?.id &&
                    props.row.username !== 'admin'
                  "
                  @click="confirmDelete(props.row)"
                >
                  <q-item-section avatar>
                    <q-icon name="delete" color="negative" />
                  </q-item-section>
                  <q-item-section> Eliminar </q-item-section>
                </q-item>
              </q-list>
            </q-btn-dropdown>
            <span v-else class="text-caption text-grey-5">—</span>
          </q-td>
        </template>
      </q-table>
    </q-card>

    <!-- Modal para Agregar/Editar Usuario -->
    <q-dialog v-model="modalOpen" persistent>
      <q-card
        style="
          max-width: 700px;
          width: 90%;
          border-radius: 12px;
          overflow: hidden;
        "
      >
        <q-form ref="userForm" @submit.prevent="saveRow">
          <q-card-section class="bg-primary text-white q-py-md">
            <div class="text-h6 text-weight-bold row items-center">
              <q-icon name="person" class="q-mr-xs" />
              {{ editRow ? "Editar Usuario" : "Agregar Usuario" }}
            </div>
            <div class="text-caption opacity-80">
              Rellena los datos de acceso y perfil del usuario
            </div>
          </q-card-section>

          <q-card-section class="q-pa-md q-gutter-y-sm">
            <div class="row q-col-gutter-sm">
              <div class="col-6">
                <q-input
                  v-model="form.name"
                  label="Nombre"
                  filled
                  class="rounded-input"
                  :rules="[(val) => !!val || 'El nombre es requerido']"
                />
              </div>
              <div class="col-6">
                <q-input
                  v-model="form.lastname"
                  label="Apellido"
                  filled
                  class="rounded-input"
                  :rules="[(val) => !!val || 'El apellido es requerido']"
                />
              </div>
            </div>
            <div class="row q-col-gutter-sm">
              <div class="col-6">
                <q-input
                  v-model="form.username"
                  label="Nombre de Usuario"
                  filled
                  class="rounded-input"
                  :rules="[(val) => !!val || 'El usuario es requerido']"
                />
              </div>
              <div class="col-6">
                <q-input
                  v-model="form.password"
                  type="password"
                  :label="editRow ? 'Contraseña (Opcional)' : 'Contraseña'"
                  filled
                  class="rounded-input"
                  :rules="[
                    (val) =>
                      !editRow && !val ? 'La contraseña es requerida' : true,
                  ]"
                />
              </div>
            </div>
            <div class="row q-col-gutter-sm">
              <div class="col-6">
                <q-input
                  v-model="form.email"
                  label="Correo Electrónico"
                  type="email"
                  filled
                  class="rounded-input"
                  :rules="[
                    (val) =>
                      !val ||
                      /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val) ||
                      'Formato de correo no válido',
                  ]"
                />
              </div>
              <div class="col-6">
                <q-input
                  v-model="form.phone"
                  label="Teléfono"
                  filled
                  class="rounded-input"
                  mask="####-####"
                  hint="Formato: 1234-5678"
                />
              </div>
            </div>
            <div class="row q-col-gutter-sm">
              <div class="col-6">
                <q-select
                  v-model="form.role"
                  :options="rolesList"
                  option-label="name"
                  option-value="name"
                  emit-value
                  map-options
                  label="Rol de Usuario"
                  filled
                  class="rounded-input"
                  :rules="[(val) => !!val || 'El rol es obligatorio']"
                />
              </div>
              <div class="col-6">
                <q-select
                  v-model="form.sucursal_id"
                  :options="sucursalesList"
                  option-label="nombre"
                  option-value="id"
                  emit-value
                  map-options
                  label="Sucursal Asignada"
                  filled
                  class="rounded-input"
                  clearable
                  hint="Dejar vacío para acceso Global (Admin)"
                />
              </div>
            </div>
            <q-select
              v-model="form.status"
              :options="[
                { label: 'Activo', value: 1 },
                { label: 'Inactivo', value: 0 },
              ]"
              emit-value
              map-options
              label="Estado"
              filled
              class="rounded-input"
            />
          </q-card-section>

          <q-card-actions align="right" class="q-pa-md bg-grey-2">
            <q-btn
              flat
              label="Cancelar"
              color="grey-7"
              v-close-popup
              class="rounded-btn"
            />
            <q-btn
              type="submit"
              label="Guardar"
              color="primary"
              unelevated
              class="rounded-btn q-px-md"
            />
          </q-card-actions>
        </q-form>
      </q-card>
    </q-dialog>

    <!-- Confirmación de Eliminación -->
    <q-dialog v-model="confirmOpen" persistent>
      <q-card style="border-radius: 12px">
        <q-card-section class="row items-center">
          <q-avatar
            icon="warning"
            color="warning"
            text-color="white"
            class="q-mr-sm"
          />
          <span class="text-h6 text-weight-bold">¿Eliminar usuario?</span>
        </q-card-section>
        <q-card-section class="q-pt-none">
          ¿Estás seguro de que deseas eliminar permanentemente el usuario
          <strong>{{ rowToDeleteName }}</strong
          >? Esta acción no se puede deshacer.
        </q-card-section>

        <q-card-actions align="right" class="q-pa-md bg-grey-1">
          <q-btn
            flat
            label="Cancelar"
            color="grey-7"
            v-close-popup
            class="rounded-btn"
          />
          <q-btn
            label="Eliminar Usuario"
            color="negative"
            unelevated
            class="rounded-btn q-px-md"
            @click="deleteRow"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script>
import { useLoadingStore } from "src/store/loading-store";
import { api } from "boot/axios";
import Alert from "src/helpers/Alert";
import { useAuthStore } from "src/store/auth-store";

export default {
  data() {
    return {
      loading: false,
      search: "",
      rows: [],
      pagination: {
        sortBy: "name",
        descending: false,
        page: 1,
        rowsPerPage: 15,
        rowsNumber: 0,
      },
      columns: [
        {
          name: "name",
          label: "Nombre",
          align: "left",
          field: "name",
          sortable: true,
        },
        {
          name: "lastname",
          label: "Apellido",
          align: "left",
          field: "lastname",
          sortable: true,
        },
        {
          name: "username",
          label: "Usuario",
          align: "left",
          field: "username",
          sortable: true,
        },
        {
          name: "email",
          label: "Correo",
          align: "left",
          field: "email",
          sortable: true,
        },
        {
          name: "phone",
          label: "Teléfono",
          align: "left",
          field: "phone",
          sortable: true,
        },
        {
          name: "role",
          label: "Rol",
          align: "left",
          field: (row) => row.roles?.[0]?.name || "Ninguno",
        },
        {
          name: "sucursal",
          label: "Sucursal",
          align: "left",
          field: (row) => row.sucursal?.nombre || "Global",
        },
        { name: "status", label: "Estado", align: "center", sortable: true },
        { name: "options", label: "Opciones", align: "right" },
      ],
      form: {
        name: "",
        lastname: "",
        username: "",
        email: "",
        phone: "",
        password: "",
        sucursal_id: null,
        role: "",
        status: 1,
      },
      rolesList: [],
      sucursalesList: [],
      modalOpen: false,
      confirmOpen: false,
      editRow: null,
      rowToDelete: null,
      rowToDeleteName: "",
      rolFilter: "",
      sucursalFilter: "",
      statusFilter: "",
      fechaDesde: "",
      fechaHasta: "",
    };
  },
  created() {
    this.fetchUsers();
  },
  computed: {
    rolesFilterOptions() {
      return [
        { label: "Todos los Roles", value: "" },
        ...this.rolesList.map((r) => ({ label: r.name, value: r.name })),
      ];
    },
    sucursalesFilterOptions() {
      return [
        { label: "Todas las Sucursales", value: "" },
        ...this.sucursalesList.map((s) => ({ label: s.nombre, value: s.id })),
      ];
    },
    authStore() {
      return useAuthStore();
    },
    dataUser() {
      return useAuthStore().user;
    },
  },
  methods: {
    clearFilters() {
      this.search = "";
      this.rolFilter = "";
      this.sucursalFilter = "";
      this.statusFilter = "";
      this.fechaDesde = "";
      this.fechaHasta = "";
      this.fetchUsers();
    },
    async fetchUsers() {
      this.onRequest({
        pagination: this.pagination,
        filter: this.search,
      });
    },
    async onRequest(props) {
      const { page, rowsPerPage, sortBy, descending } = props.pagination;
      const filter = props.filter;

      this.loading = true;
      try {
        const response = await api.get("/api/usuarios", {
          params: {
            page: page,
            limit: rowsPerPage === 0 ? -1 : rowsPerPage,
            search: filter,
            rol: this.rolFilter || undefined,
            sucursal_id: this.sucursalFilter || undefined,
            status: this.statusFilter !== "" ? this.statusFilter : undefined,
            fecha_desde: this.fechaDesde || undefined,
            fecha_hasta: this.fechaHasta || undefined,
            sortBy: sortBy,
            descending: descending,
          },
        });

        const data = response.data.data;
        if (data.data) {
          this.rows = data.data;
          this.pagination.rowsNumber = data.total;
        } else {
          this.rows = data;
          this.pagination.rowsNumber = data.length;
        }

        // Cargar listas de roles y sucursales para el formulario
        if (response.data.roles) {
          this.rolesList = response.data.roles;
        }
        if (response.data.sucursales) {
          this.sucursalesList = response.data.sucursales;
        }

        this.pagination.page = page;
        this.pagination.rowsPerPage = rowsPerPage;
        this.pagination.sortBy = sortBy;
        this.pagination.descending = descending;
      } catch (error) {
        Alert(
          "Error",
          error.response?.data?.message || error.message || "Error",
          "error"
        );
      } finally {
        this.loading = false;
      }
    },
    openModal(row) {
      if (row && !this.authStore.hasPermission("usuarios.editar")) {
        Alert("Acceso denegado", "No tienes permiso para editar usuarios.", "warning");
        return;
      }
      if (!row && !this.authStore.hasPermission("usuarios.crear")) {
        Alert("Acceso denegado", "No tienes permiso para agregar usuarios.", "warning");
        return;
      }
      if (row) {
        this.editRow = row;
        this.form = {
          name: row.name,
          lastname: row.lastname,
          username: row.username,
          email: row.email || "",
          phone: row.phone || "",
          password: "",
          sucursal_id: row.sucursal_id,
          role: row.roles?.[0]?.name || "",
          status: row.status === 1 || row.status === true ? 1 : 0,
        };
      } else {
        this.editRow = null;
        this.form = {
          name: "",
          lastname: "",
          username: "",
          email: "",
          phone: "",
          password: "",
          sucursal_id: null,
          role: "",
          status: 1,
        };
      }
      this.modalOpen = true;
    },
    async saveRow() {
      const formValid = await this.$refs.userForm.validate();
      if (!formValid) return;

      Alert("Guardando...", "Enviando datos del usuario...", "info");
      useLoadingStore().startLoading();
      try {
        const formData = {
          ...this.form,
          status: Number(this.form.status),
        };

        if (this.editRow) {
          await api.put(`/api/usuarios/${this.editRow.id}`, formData);
          this.fetchUsers();
        } else {
          await api.post("/api/usuarios", formData);
          this.fetchUsers();
        }
        this.modalOpen = false;

        Alert(
          "Éxito",
          this.editRow
            ? "El usuario ha sido actualizado exitosamente."
            : "El nuevo usuario ha sido creado exitosamente.",
          "success"
        );
      } catch (error) {
        Alert(
          "Error",
          error.response?.data?.message || error.message || "Error",
          "error"
        );
      } finally {
        useLoadingStore().stopLoading();
      }
    },
    confirmDelete(row) {
      if (!this.authStore.hasPermission("usuarios.eliminar")) {
        Alert("Acceso denegado", "No tienes permiso para eliminar usuarios.", "warning");
        return;
      }
      this.rowToDelete = row.id;
      this.rowToDeleteName = row.name;
      this.confirmOpen = true;
    },
    async deleteRow() {
      if (!this.authStore.hasPermission("usuarios.eliminar")) {
        Alert("Acceso denegado", "No tienes permiso para eliminar usuarios.", "warning");
        return;
      }
      useLoadingStore().startLoading();
      try {
        await api.delete(`/api/usuarios/${this.rowToDelete}`);
        this.rows = this.rows.filter((row) => row.id !== this.rowToDelete);
        this.confirmOpen = false;
        this.rowToDelete = null;
        this.rowToDeleteName = "";

        Alert("Éxito", "El usuario ha sido eliminado exitosamente.", "success");
      } catch (error) {
        Alert(
          "Error",
          error.response?.data?.message || error.message || "Error",
          "error"
        );
      } finally {
        useLoadingStore().stopLoading();
      }
    },
  },
};
</script>

<style scoped>
.rounded-card {
  border-radius: 12px;
}

.rounded-btn {
  border-radius: 8px;
  text-transform: none;
}

.rounded-input {
  border-radius: 8px;
}

.bg-white-translucent {
  background-color: rgba(255, 255, 255, 0.15);
}

/* Custom Table Styles */
:deep(.q-table__card) {
  box-shadow: none;
}

:deep(.q-table th) {
  font-weight: 700;
  background-color: #f5f5f5;
  color: #333;
}

:deep(.q-table tbody tr:hover) {
  background-color: rgba(var(--q-primary-rgb), 0.04);
}
</style>
