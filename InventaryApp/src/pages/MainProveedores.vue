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
              icon="local_shipping"
              size="48px"
              class="q-mr-md bg-white-translucent text-white"
            />
            <div>
              <div class="text-h5 text-weight-bold">Control de Proveedores</div>
              <div class="text-subtitle2 opacity-80">
                Administra la lista de empresas asociadas que suministran
                mercancías al inventario
              </div>
            </div>
          </div>
          <q-btn
            v-if="authStore.hasPermission('proveedores.crear')"
            color="white"
            text-color="primary"
            label="Agregar Proveedor"
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
        <div class="col-12 col-md-6">
          <q-input
            v-model="search"
            label="Buscar por nombre, NIT, contacto o correo..."
            outlined
            dense
            debounce="300"
            class="rounded-input"
            @update:model-value="fetchData"
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
                  fetchData();
                "
              />
            </template>
          </q-input>
        </div>

        <div class="col-12 col-md-6">
          <q-select
            v-model="tipoDocumentoFilter"
            :options="[
              { label: 'Todos los Tipos', value: '' },
              { label: 'Con NRC (Formal)', value: 'con_nrc' },
              { label: 'Sin NRC', value: 'sin_nrc' }
            ]"
            label="Tipo de Registro"
            outlined
            dense
            emit-value
            map-options
            :display-value="tipoDocumentoFilter !== '' && tipoDocumentoFilter !== null ? undefined : 'Todos los Tipos'"
            :clearable="tipoDocumentoFilter !== '' && tipoDocumentoFilter !== null"
            clear-value=""
            @clear="tipoDocumentoFilter = ''; fetchData()"
            class="rounded-input"
            @update:model-value="fetchData"
          >
            <template v-slot:prepend>
              <q-icon name="assignment" color="primary" />
            </template>
          </q-select>
        </div>

        <div class="col-12 col-sm-6 col-md-6">
          <q-select
            v-model="estadoFilter"
            :options="[
              { label: 'Todos los Estados', value: '' },
              { label: 'Activos', value: '1' },
              { label: 'Inactivos', value: '0' }
            ]"
            label="Estado"
            outlined
            dense
            emit-value
            map-options
            :display-value="estadoFilter !== '' && estadoFilter !== null ? undefined : 'Todos los Estados'"
            :clearable="estadoFilter !== '' && estadoFilter !== null"
            clear-value=""
            @clear="estadoFilter = ''; fetchData()"
            class="rounded-input"
            @update:model-value="fetchData"
          >
            <template v-slot:prepend>
              <q-icon name="toggle_on" color="primary" />
            </template>
          </q-select>
        </div>

        <div class="col-12 col-sm-6 col-md-6 row justify-end">
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
        :pagination="pagination"
        @update:pagination="(val) => (pagination = val)"
        @request="onRequest"
        :filter="search"
        flat
        class="no-shadow"
        binary-state-sort
        no-data-label="No se encontraron proveedores registrados"
      >
        <template v-slot:body-cell-estado="props">
          <q-td align="center">
            <q-chip
              :color="isEstadoActive(props.row.estado) ? 'green-2' : 'red-2'"
              :text-color="isEstadoActive(props.row.estado) ? 'green-9' : 'red-9'"
              :label="isEstadoActive(props.row.estado) ? 'Activo' : 'Inactivo'"
              dense
              class="text-weight-bold"
            />
          </q-td>
        </template>

        <template v-slot:body-cell-options="props">
          <q-td :props="props" align="right">
            <q-btn-dropdown
              v-if="authStore.hasPermission('proveedores.editar') || authStore.hasPermission('proveedores.eliminar')"
              flat
              color="dark"
              icon="more_vert"
              size="md"
            >
              <q-list>
                <q-item
                  clickable
                  v-close-popup
                  v-if="authStore.hasPermission('proveedores.editar')"
                  @click="openModal(props.row)"
                >
                  <q-item-section avatar>
                    <q-icon name="edit" color="primary" />
                  </q-item-section>
                  <q-item-section>Editar</q-item-section>
                </q-item>
                <q-item
                  clickable
                  v-close-popup
                  v-if="authStore.hasPermission('proveedores.eliminar')"
                  @click="confirmDelete(props.row)"
                >
                  <q-item-section avatar>
                    <q-icon name="delete" color="negative" />
                  </q-item-section>
                  <q-item-section>Eliminar</q-item-section>
                </q-item>
              </q-list>
            </q-btn-dropdown>
            <span v-else class="text-caption text-grey-5">—</span>
          </q-td>
        </template>
      </q-table>
    </q-card>

    <!-- Modal Form -->
    <q-dialog v-model="modalOpen" persistent>
      <q-card
        style="
          max-width: 600px;
          width: 90%;
          border-radius: 12px;
          overflow: hidden;
        "
      >
        <q-form ref="proveedorForm" @submit.prevent="saveRow">
          <q-card-section class="bg-primary text-white q-py-md">
            <div class="text-h6 text-weight-bold row items-center">
              <q-icon name="local_shipping" class="q-mr-xs" />
              {{ editRow ? "Editar Proveedor" : "Agregar Proveedor" }}
            </div>
            <div class="text-caption opacity-80">
              Ingresa la información comercial del proveedor
            </div>
          </q-card-section>

          <q-card-section class="q-pa-md q-gutter-y-sm">
            <q-input
              v-model="form.nombre"
              label="Nombre / Razón Social"
              filled
              class="rounded-input"
              :rules="[(val) => !!val || 'El nombre es obligatorio']"
            />
            <div class="row q-col-gutter-sm">
              <div class="col-6">
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
                    (val) =>
                      !val ||
                      val.length === 9 ||
                      'Debe tener formato ####-####',
                  ]"
                />
              </div>
              <div class="col-6">
                <q-input
                  v-model="form.correo"
                  label="Correo Electrónico"
                  filled
                  class="rounded-input"
                  type="email"
                  :rules="[
                    (val) =>
                      !val ||
                      /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val) ||
                      'Formato de correo no válido',
                  ]"
                />
              </div>
            </div>
            <q-input
              v-model="form.contacto"
              label="Persona de Contacto"
              filled
              class="rounded-input"
            />
            <q-input
              v-model="form.direccion"
              label="Dirección / Oficina"
              filled
              class="rounded-input"
              type="textarea"
              autogrow
            />
            <q-select
              v-model="form.estado"
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

    <!-- Confirm Dialog -->
    <q-dialog v-model="confirmOpen" persistent>
      <q-card style="border-radius: 12px">
        <q-card-section class="row items-center">
          <q-avatar
            icon="warning"
            color="warning"
            text-color="white"
            class="q-mr-sm"
          />
          <span class="text-h6 text-weight-bold">¿Eliminar proveedor?</span>
        </q-card-section>
        <q-card-section class="q-pt-none">
          Esta acción no se puede deshacer y podría fallar si el proveedor ya
          está asociado con transacciones de compra en el sistema.
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
            label="Eliminar"
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
          name: "nit",
          label: "NIT",
          align: "left",
          field: "nit",
          sortable: true,
        },
        {
          name: "nrc",
          label: "NRC",
          align: "left",
          field: "nrc",
          sortable: true,
        },
        {
          name: "telefono",
          label: "Teléfono",
          align: "left",
          field: "telefono",
          sortable: true,
        },
        {
          name: "contacto",
          label: "Contacto",
          align: "left",
          field: "contacto",
          sortable: true,
        },
        {
          name: "estado",
          label: "Estado",
          align: "center",
          field: "estado",
          sortable: true,
        },
        { name: "options", label: "Acciones", align: "right" },
      ],
      form: {
        nombre: "",
        nit: "",
        nrc: "",
        telefono: "",
        correo: "",
        contacto: "",
        direccion: "",
        estado: 1,
      },
      tipoDocumentoFilter: "",
      estadoFilter: "",
      modalOpen: false,
      confirmOpen: false,
      editRow: null,
      rowToDelete: null,
    };
  },
  created() {
    this.fetchData();
  },
  methods: {
    clearFilters() {
      this.search = "";
      this.tipoDocumentoFilter = "";
      this.estadoFilter = "";
      this.fetchData();
    },
    async fetchData() {
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
        const res = await api.get("/api/proveedores", {
          params: {
            page: page,
            limit: rowsPerPage === 0 ? -1 : rowsPerPage,
            search: filter,
            tipo_documento: this.tipoDocumentoFilter || undefined,
            estado: this.estadoFilter || undefined,
            sortBy: sortBy,
            descending: descending,
          },
        });

        const data = res.data.data;
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
        Alert("Error", "Error al cargar proveedores", "error");
      } finally {
        this.loading = false;
      }
    },
    isEstadoActive(val) {
      return val === 1 || val === true || val === "1" || val === "true" || val === "Activo";
    },
    openModal(row) {
      if (row && !this.authStore.hasPermission("proveedores.editar")) {
        Alert("Acceso denegado", "No tienes permiso para editar proveedores.", "warning");
        return;
      }
      if (!row && !this.authStore.hasPermission("proveedores.crear")) {
        Alert("Acceso denegado", "No tienes permiso para agregar proveedores.", "warning");
        return;
      }
      if (row) {
        this.editRow = row;
        this.form = {
          nombre: row.nombre,
          nit: row.nit || "",
          nrc: row.nrc || "",
          telefono: row.telefono || "",
          correo: row.correo || "",
          contacto: row.contacto || "",
          direccion: row.direccion || "",
          estado: (row.estado === 1 || row.estado === true || row.estado === "1" || row.estado === "Activo") ? 1 : 0,
        };
      } else {
        this.editRow = null;
        this.form = {
          nombre: "",
          nit: "",
          nrc: "",
          telefono: "",
          correo: "",
          contacto: "",
          direccion: "",
          estado: 1,
        };
      }
      this.modalOpen = true;
    },
    async saveRow() {
      const formValid = await this.$refs.proveedorForm.validate();
      if (!formValid) return;

      Alert("Guardando...", "Enviando datos del proveedor...", "info");
      useLoadingStore().startLoading();
      try {
        const payload = {
          ...this.form,
          estado: (this.form.estado === 1 || this.form.estado === true || this.form.estado === "1" || this.form.estado === "Activo") ? 1 : 0,
        };
        if (this.editRow) {
          await api.put(`/api/proveedores/${this.editRow.id}`, payload);
        } else {
          await api.post("/api/proveedores", payload);
        }
        this.modalOpen = false;
        Alert("Éxito", "Proveedor guardado correctamente", "success");
        this.fetchData();
      } catch (error) {
        Alert("Error", error.response?.data?.message || "Error al guardar el proveedor", "error");
      } finally {
        useLoadingStore().stopLoading();
      }
    },
    confirmDelete(row) {
      if (!this.authStore.hasPermission("proveedores.eliminar")) {
        Alert("Acceso denegado", "No tienes permiso para eliminar proveedores.", "warning");
        return;
      }
      this.rowToDelete = row.id;
      this.confirmOpen = true;
    },
    async deleteRow() {
      if (!this.authStore.hasPermission("proveedores.eliminar")) {
        Alert("Acceso denegado", "No tienes permiso para eliminar proveedores.", "warning");
        return;
      }
      useLoadingStore().startLoading();
      try {
        await api.delete(`/api/proveedores/${this.rowToDelete}`);
        this.confirmOpen = false;
        Alert("Éxito", "Proveedor eliminado correctamente", "success");
        this.fetchData();
      } catch (error) {
        Alert(
          "Error",
          "No se pudo eliminar el proveedor, posiblemente esté en uso",
          "error"
        );
      } finally {
        this.rowToDelete = null;
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
