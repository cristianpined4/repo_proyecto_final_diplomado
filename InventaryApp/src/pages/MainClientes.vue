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
              icon="person_add"
              size="48px"
              class="q-mr-md bg-white-translucent text-white"
            />
            <div>
              <div class="text-h5 text-weight-bold">Control de Clientes</div>
              <div class="text-subtitle2 opacity-80">
                Administra el directorio de clientes y sus datos fiscales para
                facturación y ventas
              </div>
            </div>
          </div>
          <q-btn
            v-if="authStore.hasPermission('clientes.crear')"
            color="white"
            text-color="primary"
            label="Agregar Cliente"
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
            label="Buscar por nombre, DUI, NIT o correo..."
            outlined
            dense
            debounce="300"
            class="rounded-input"
            @update:model-value="fetchItems"
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
                  fetchItems();
                "
              />
            </template>
          </q-input>
        </div>

        <!-- Filtro Tipo Contribuyente -->
        <div class="col-12 col-md-6">
          <q-select
            v-model="tipoContribuyenteFilter"
            :options="[
              { label: 'Todos los Contribuyentes', value: '' },
              { label: 'Crédito Fiscal (con NRC)', value: 'credito_fiscal' },
              { label: 'Consumidor Final (sin NRC)', value: 'consumidor_final' },
            ]"
            label="Tipo Contribuyente"
            outlined
            dense
            emit-value
            map-options
            :display-value="tipoContribuyenteFilter !== '' && tipoContribuyenteFilter !== null ? undefined : 'Todos los Contribuyentes'"
            :clearable="tipoContribuyenteFilter !== '' && tipoContribuyenteFilter !== null"
            clear-value=""
            @clear="tipoContribuyenteFilter = ''; fetchItems()"
            class="rounded-input"
            @update:model-value="fetchItems"
          >
            <template v-slot:prepend>
              <q-icon name="badge" color="primary" />
            </template>
          </q-select>
        </div>

        <!-- Filtro Estado -->
        <div class="col-12 col-sm-6 col-md-3">
          <q-select
            v-model="statusFilter"
            :options="[
              { label: 'Todos los Estados', value: '' },
              { label: 'Activos', value: 'Activo' },
              { label: 'Inactivos', value: 'Inactivo' },
            ]"
            label="Estado"
            outlined
            dense
            emit-value
            map-options
            :display-value="statusFilter !== '' && statusFilter !== null ? undefined : 'Todos los Estados'"
            :clearable="statusFilter !== '' && statusFilter !== null"
            clear-value=""
            @clear="statusFilter = ''; fetchItems()"
            class="rounded-input"
            @update:model-value="fetchItems"
          >
            <template v-slot:prepend>
              <q-icon name="toggle_on" color="primary" />
            </template>
          </q-select>
        </div>

        <!-- Fecha Desde -->
        <div class="col-12 col-sm-6 col-md-3">
          <q-input
            v-model="fechaDesde"
            type="date"
            label="Registrado Desde"
            outlined
            dense
            stack-label
            class="rounded-input"
            @update:model-value="fetchItems"
          >
            <template v-slot:prepend><q-icon name="event" color="primary" /></template>
            <template v-slot:append v-if="fechaDesde">
              <q-icon name="close" class="cursor-pointer" @click="fechaDesde = ''; fetchItems()" />
            </template>
          </q-input>
        </div>

        <!-- Fecha Hasta -->
        <div class="col-12 col-sm-6 col-md-3">
          <q-input
            v-model="fechaHasta"
            type="date"
            label="Registrado Hasta"
            outlined
            dense
            stack-label
            class="rounded-input"
            @update:model-value="fetchItems"
          >
            <template v-slot:prepend><q-icon name="event" color="primary" /></template>
            <template v-slot:append v-if="fechaHasta">
              <q-icon name="close" class="cursor-pointer" @click="fechaHasta = ''; fetchItems()" />
            </template>
          </q-input>
        </div>

        <!-- Limpiar Filtros -->
        <div class="col-12 col-sm-6 col-md-3 row justify-end">
          <q-btn
            flat
            color="primary"
            label="Limpiar"
            icon="filter_alt_off"
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
        v-model:pagination="pagination"
        @request="onRequest"
        :filter="search"
        flat
        class="no-shadow"
        binary-state-sort
        no-data-label="No se encontraron clientes registrados"
      >
        <!-- Slot para status -->
        <template v-slot:body-cell-status="props">
          <q-td align="center">
            <q-chip
              :color="isStatusActive(props.row.status) ? 'green-2' : 'red-2'"
              :text-color="isStatusActive(props.row.status) ? 'green-9' : 'red-9'"
              :label="isStatusActive(props.row.status) ? 'Activo' : 'Inactivo'"
              dense
              class="text-weight-bold"
            />
          </q-td>
        </template>

        <template v-slot:body-cell-options="props">
          <q-td align="right">
            <q-btn-dropdown
              v-if="authStore.hasPermission('clientes.editar') || authStore.hasPermission('clientes.eliminar')"
              flat
              color="dark"
              icon="more_vert"
              size="md"
            >
              <q-list>
                <q-item
                  clickable
                  v-close-popup
                  v-if="authStore.hasPermission('clientes.editar')"
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
                  v-if="authStore.hasPermission('clientes.eliminar')"
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

    <!-- Modal para Agregar/Editar -->
    <q-dialog v-model="modalOpen" persistent>
      <q-card
        style="
          max-width: 600px;
          width: 90%;
          border-radius: 12px;
          overflow: hidden;
        "
      >
        <q-form ref="clientForm" @submit.prevent="saveRow">
          <q-card-section class="bg-primary text-white q-py-md">
            <div class="text-h6 text-weight-bold row items-center">
              <q-icon name="person_add" class="q-mr-xs" />
              {{ editRow ? "Editar Cliente" : "Agregar Cliente" }}
            </div>
            <div class="text-caption opacity-80">
              Rellena la información fiscal de contacto del cliente
            </div>
          </q-card-section>

          <q-card-section class="q-pa-md q-gutter-y-sm">
            <q-input
              v-model="form.nombre"
              label="Nombre Completo / Razón Social"
              filled
              class="rounded-input"
              :rules="[(val) => !!val || 'El nombre es obligatorio']"
            />

            <div class="row q-col-gutter-sm">
              <div class="col-6">
                <q-input
                  v-model="form.telefono"
                  label="Teléfono"
                  filled
                  class="rounded-input"
                  mask="####-####"
                  hint="Formato: 1234-5678"
                  :rules="[
                    (val) => !!val || 'El teléfono es obligatorio',
                    (val) => val.length === 9 || 'Debe tener formato ####-####',
                  ]"
                />
              </div>
              <div class="col-6">
                <q-input
                  v-model="form.correo"
                  type="email"
                  label="Correo Electrónico"
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
            </div>

            <div class="row q-col-gutter-sm">
              <div class="col-6">
                <q-input
                  v-model="form.dui"
                  label="DUI"
                  filled
                  class="rounded-input"
                  mask="########-#"
                  hint="Formato: 12345678-9"
                  :rules="[
                    (val) =>
                      !val ||
                      val.length === 10 ||
                      'Debe tener formato ########-#',
                  ]"
                />
              </div>
              <div class="col-6">
                <q-input
                  v-model="form.nrc"
                  label="NRC"
                  filled
                  class="rounded-input"
                  mask="######-#"
                  hint="Formato: 123456-7"
                />
              </div>
            </div>

            <q-input
              v-model="form.nit"
              label="NIT"
              filled
              class="rounded-input"
              mask="####-######-###-#"
              hint="Formato: 1234-567890-123-4"
              :rules="[
                (val) =>
                  !val ||
                  val.length === 17 ||
                  'Debe tener formato ####-######-###-#',
              ]"
            />

            <q-input
              v-model="form.direccion"
              label="Dirección de Domicilio"
              filled
              class="rounded-input"
              type="textarea"
              autogrow
              :rules="[(val) => !!val || 'La dirección es obligatoria']"
            />

            <q-select
              v-model="form.status"
              :options="['Activo', 'Inactivo']"
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
          <span class="text-h6 text-weight-bold">¿Eliminar cliente?</span>
        </q-card-section>
        <q-card-section class="q-pt-none">
          ¿Estás seguro de que deseas eliminar permanentemente el cliente
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
            label="Eliminar Cliente"
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
import { useAuthStore } from "src/store/auth-store";
import { useLoadingStore } from "src/store/loading-store";
import { api } from "boot/axios";
import Alert from "src/helpers/Alert";

export default {
  computed: {
    authStore() {
      return useAuthStore();
    },
  },
  data() {
    return {
      loading: false,
      search: "",
      rows: [],
      pagination: {
        sortBy: "nombre",
        descending: false,
        page: 1,
        rowsPerPage: 15,
        rowsNumber: 0,
      },
      columns: [
        {
          name: "nombre",
          label: "Nombre / Razón Social",
          align: "left",
          field: "nombre",
          sortable: true,
        },
        {
          name: "correo",
          label: "Correo",
          align: "left",
          field: "correo",
          sortable: true,
        },
        {
          name: "dui",
          label: "DUI",
          align: "left",
          field: "dui",
          sortable: true,
        },
        { name: "nit", label: "NIT", align: "left", field: "nit" },
        { name: "nrc", label: "NRC", align: "left", field: "nrc" },
        {
          name: "telefono",
          label: "Teléfono",
          align: "left",
          field: "telefono",
          sortable: true,
        },
        {
          name: "direccion",
          label: "Dirección",
          align: "left",
          field: "direccion",
        },
        { name: "status", label: "Estado", align: "center", sortable: true },
        { name: "options", label: "Acciones", align: "right" },
      ],
      form: {
        nombre: "",
        correo: "",
        dui: "",
        nit: "",
        nrc: "",
        telefono: "",
        direccion: "",
        status: "",
      },
      estadoOptions: ["Activo", "Inactivo"],
      modalOpen: false,
      confirmOpen: false,
      editRow: null,
      rowToDelete: null,
      rowToDeleteName: "",
      statusFilter: "",
      tipoContribuyenteFilter: "",
      fechaDesde: "",
      fechaHasta: "",
    };
  },
  created() {
    this.fetchItems();
  },
  methods: {
    clearFilters() {
      this.search = "";
      this.statusFilter = "";
      this.tipoContribuyenteFilter = "";
      this.fechaDesde = "";
      this.fechaHasta = "";
      this.fetchItems();
    },
    async fetchItems() {
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
        const response = await api.get("/api/clientes", {
          params: {
            page: page,
            limit: rowsPerPage === 0 ? -1 : rowsPerPage,
            search: filter,
            status: this.statusFilter || undefined,
            tipo_contribuyente: this.tipoContribuyenteFilter || undefined,
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

        this.pagination.page = page;
        this.pagination.rowsPerPage = rowsPerPage;
        this.pagination.sortBy = sortBy;
        this.pagination.descending = descending;
      } catch (error) {
        Alert(
          "Error",
          error.response?.data?.message || "Error al cargar clientes",
          "error"
        );
      } finally {
        this.loading = false;
      }
    },
    isStatusActive(status) {
      return status === 1 || status === true || status === "1" || status === "Activo";
    },
    openModal(row) {
      if (row && !this.authStore.hasPermission("clientes.editar")) {
        Alert("Acceso denegado", "No tienes permiso para editar clientes.", "warning");
        return;
      }
      if (!row && !this.authStore.hasPermission("clientes.crear")) {
        Alert("Acceso denegado", "No tienes permiso para agregar clientes.", "warning");
        return;
      }
      if (row) {
        this.editRow = row;
        const active = this.isStatusActive(row.status);
        this.form = {
          nombre: row.nombre || "",
          correo: row.correo || "",
          dui: row.dui || "",
          nit: row.nit || "",
          nrc: row.nrc || "",
          telefono: row.telefono || "",
          direccion: row.direccion || "",
          status: active ? "Activo" : "Inactivo",
        };
      } else {
        this.editRow = null;
        this.form = {
          nombre: "",
          correo: "",
          dui: "",
          nit: "",
          nrc: "",
          telefono: "",
          direccion: "",
          status: "Activo",
        };
      }
      this.modalOpen = true;
    },
    async saveRow() {
      const formValid = await this.$refs.clientForm.validate();
      if (!formValid) return;

      Alert("Guardando...", "Enviando datos del cliente...", "info");
      useLoadingStore().startLoading();
      try {
        const formData = {
          nombre: this.form.nombre,
          telefono: this.form.telefono || null,
          correo: this.form.correo ? this.form.correo.trim() : null,
          dui: this.form.dui ? this.form.dui.trim() : null,
          nit: this.form.nit ? this.form.nit.trim() : null,
          nrc: this.form.nrc ? this.form.nrc.trim() : null,
          direccion: this.form.direccion || null,
          status: this.form.status === "Activo" ? 1 : 0,
        };

        if (this.editRow) {
          await api.put(`/api/clientes/${this.editRow.id}`, formData);
        } else {
          await api.post("/api/clientes", formData);
        }
        this.modalOpen = false;
        this.fetchItems();
        Alert(
          "Éxito",
          this.editRow
            ? "Cliente actualizado exitosamente."
            : "Cliente creado exitosamente.",
          "success"
        );
      } catch (error) {
        Alert(
          "Error",
          error.response?.data?.message || "Error al guardar el cliente",
          "error"
        );
      } finally {
        useLoadingStore().stopLoading();
      }
    },
    confirmDelete(row) {
      if (!this.authStore.hasPermission("clientes.eliminar")) {
        Alert("Acceso denegado", "No tienes permiso para eliminar clientes.", "warning");
        return;
      }
      this.rowToDelete = row.id;
      this.rowToDeleteName = row.nombre;
      this.confirmOpen = true;
    },
    async deleteRow() {
      if (!this.authStore.hasPermission("clientes.eliminar")) {
        Alert("Acceso denegado", "No tienes permiso para eliminar clientes.", "warning");
        return;
      }
      useLoadingStore().startLoading();
      try {
        await api.delete(`/api/clientes/${this.rowToDelete}`);
        this.confirmOpen = false;
        this.rowToDelete = null;
        this.rowToDeleteName = "";
        this.fetchItems();
        Alert("Éxito", "Cliente eliminado exitosamente.", "success");
      } catch (error) {
        Alert(
          "Error",
          error.response?.data?.message || "Error al eliminar el cliente",
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
