<template>
  <q-page :class="$route.path.includes('unidades') ? 'q-pa-md' : 'q-pa-none'">
    <!-- Header Banner (Only if standalone page) -->
    <q-card
      v-if="$route.path.includes('unidades')"
      class="bg-gradient-brand text-white q-mb-md shadow-2 rounded-card overflow-hidden"
    >
      <q-card-section class="q-py-md">
        <div class="row items-center justify-between no-wrap">
          <div class="row items-center">
            <q-avatar
              icon="straighten"
              size="48px"
              class="q-mr-md bg-white-translucent text-white"
            />
            <div>
              <div class="text-h5 text-weight-bold">Unidades de Medida</div>
              <div class="text-subtitle2 opacity-80">
                Define las magnitudes físicas (kilogramos, piezas, litros, etc.)
                para controlar el stock de los productos
              </div>
            </div>
          </div>
          <q-btn
            v-if="authStore.hasPermission('categorias.crear')"
            color="white"
            text-color="primary"
            label="Agregar Unidad"
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
        <div class="col-12 col-md-5">
          <q-input
            v-model="search"
            label="Buscar unidad por nombre o abreviatura..."
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

        <div class="col-12 col-sm-6 col-md-3">
          <q-select
            v-model="estadoFilter"
            :options="[
              { label: 'Todos los Estados', value: '' },
              { label: 'Activas', value: '1' },
              { label: 'Inactivas', value: '0' },
            ]"
            label="Estado"
            outlined
            dense
            emit-value
            map-options
            :display-value="estadoFilter !== '' && estadoFilter !== null ? undefined : 'Todos los Estados'"
            :clearable="estadoFilter !== '' && estadoFilter !== null"
            clear-value=""
            @clear="estadoFilter = ''; fetchItems()"
            class="rounded-input"
            @update:model-value="fetchItems"
          >
            <template v-slot:prepend>
              <q-icon name="toggle_on" color="primary" />
            </template>
          </q-select>
        </div>

        <div
          class="col-12 col-sm-6 col-md-4 row justify-end items-center no-wrap q-gutter-x-sm"
        >
          <q-btn
            flat
            color="primary"
            label="Limpiar"
            icon="filter_alt_off"
            @click="clearFilters"
            class="rounded-btn"
          />
          <q-btn
            v-if="!$route.path.includes('unidades') && authStore.hasPermission('categorias.crear')"
            color="primary"
            label="Nueva Unidad"
            icon="add"
            unelevated
            class="rounded-btn text-weight-bold"
            @click="openModal(null)"
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
        no-data-label="No se encontraron unidades de medida registradas"
      >
        <!-- Slot para estado -->
        <template v-slot:body-cell-estado="props">
          <q-td align="center">
            <q-chip
              :color="
                props.row.estado === 1 || props.row.estado === true
                  ? 'green-2'
                  : 'red-2'
              "
              :text-color="
                props.row.estado === 1 || props.row.estado === true
                  ? 'green-9'
                  : 'red-9'
              "
              :label="
                props.row.estado === 1 || props.row.estado === true
                  ? 'Activa'
                  : 'Inactiva'
              "
              dense
              class="text-weight-bold"
            />
          </q-td>
        </template>

        <!-- Slot para opciones -->
        <template v-slot:body-cell-options="props">
          <q-td align="right">
            <q-btn-dropdown
              v-if="authStore.hasPermission('categorias.editar') || authStore.hasPermission('categorias.eliminar')"
              flat
              color="dark"
              icon="more_vert"
              size="md"
            >
              <q-list>
                <q-item
                  clickable
                  v-close-popup
                  v-if="authStore.hasPermission('categorias.editar')"
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
                  v-if="authStore.hasPermission('categorias.eliminar')"
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
          max-width: 500px;
          width: 90%;
          border-radius: 12px;
          overflow: hidden;
        "
      >
        <q-form ref="unidadForm" @submit.prevent="saveRow">
          <q-card-section class="bg-primary text-white q-py-md">
            <div class="text-h6 text-weight-bold row items-center">
              <q-icon name="straighten" class="q-mr-xs" />
              {{
                editRow ? "Editar Unidad de Medida" : "Agregar Unidad de Medida"
              }}
            </div>
            <div class="text-caption opacity-80">
              Rellena la información básica de la unidad de medida
            </div>
          </q-card-section>

          <q-card-section class="q-pa-md q-gutter-y-sm">
            <q-input
              v-model="form.nombre"
              label="Nombre de la Unidad (Ej: Kilogramos, Unidades)"
              filled
              class="rounded-input"
              hint="Nombre completo de la magnitud física (mínimo 2 caracteres)"
              :rules="[
                (val) => (!!val && val.trim().length > 0) || 'El nombre es obligatorio',
                (val) => (val && val.trim().length >= 2) || 'Debe tener al menos 2 caracteres',
                (val) => (val && val.trim().length <= 50) || 'Máximo 50 caracteres',
                (val) => /^[a-zA-ZáéíóúÁÉÍÓÚñÑ0-9\s.,-]+$/.test(val ? val.trim() : '') || 'Formato no válido (solo letras, números y caracteres básicos)',
              ]"
            />
            <q-input
              v-model="form.abreviatura"
              label="Abreviatura o Símbolo (Ej: kg, pza, l)"
              filled
              class="rounded-input"
              hint="Ejemplo: kg, pza, ltr, m, un"
              :rules="[
                (val) => (!!val && val.trim().length > 0) || 'La abreviatura es obligatoria',
                (val) => (val && val.trim().length <= 10) || 'Máximo 10 caracteres',
                (val) => /^[a-zA-ZáéíóúÁÉÍÓÚñÑ0-9/.-]+$/.test(val ? val.trim() : '') || 'Formato no válido (ej: kg, pza, l, m)',
              ]"
            />
            <q-select
              v-model="form.estado"
              :options="estadoOptions"
              label="Estado"
              filled
              class="rounded-input"
              :rules="[(val) => !!val || 'El estado es requerido']"
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
          <span class="text-h6 text-weight-bold">Confirmar Eliminación</span>
        </q-card-section>
        <q-card-section class="q-pt-none">
          <div>
            ¿Estás seguro de que deseas eliminar la unidad de medida
            <strong>{{ rowToDeleteName }}</strong
            >?
          </div>
          <div class="text-caption text-grey-7 q-mt-sm">
            Si la unidad está vinculada a algún producto en inventario, se
            desactivará en su lugar para mantener la integridad de los datos.
          </div>
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
            label="Confirmar"
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
          label: "Nombre Unidad",
          align: "left",
          field: "nombre",
          sortable: true,
        },
        {
          name: "abreviatura",
          label: "Abreviatura",
          align: "center",
          field: "abreviatura",
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
        abreviatura: "",
        estado: "",
      },
      estadoOptions: ["Activo", "Inactivo"],
      modalOpen: false,
      confirmOpen: false,
      editRow: null,
      rowToDelete: null,
      rowToDeleteName: "",
      estadoFilter: "",
    };
  },
  created() {
    this.fetchItems();
  },
  methods: {
    clearFilters() {
      this.search = "";
      this.estadoFilter = "";
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
        const response = await api.get("/api/unidades", {
          params: {
            page: page,
            limit: rowsPerPage === 0 ? -1 : rowsPerPage,
            search: filter,
            estado: this.estadoFilter !== "" ? this.estadoFilter : undefined,
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
          error.response?.data?.message || error.message || "Error",
          "error"
        );
      } finally {
        this.loading = false;
      }
    },
    openModal(row) {
      if (row && !this.authStore.hasPermission("categorias.editar")) {
        Alert("Acceso denegado", "No tienes permiso para editar unidades de medida.", "warning");
        return;
      }
      if (!row && !this.authStore.hasPermission("categorias.crear")) {
        Alert("Acceso denegado", "No tienes permiso para registrar unidades de medida.", "warning");
        return;
      }
      if (row) {
        this.editRow = row;
        this.form = {
          nombre: row.nombre,
          abreviatura: row.abreviatura || "",
          estado:
            row.estado === 1 || row.estado === true ? "Activo" : "Inactivo",
        };
      } else {
        this.editRow = null;
        this.form = { nombre: "", abreviatura: "", estado: "Activo" };
      }
      this.modalOpen = true;
    },
    async saveRow() {
      const formValid = await this.$refs.unidadForm.validate();
      if (!formValid) return;

      Alert("Guardando...", "Enviando datos...", "info");
      useLoadingStore().startLoading();
      try {
        const formData = {
          nombre: this.form.nombre,
          abreviatura: this.form.abreviatura,
          estado: this.form.estado === "Activo" ? 1 : 0,
        };

        if (this.editRow) {
          await api.put(`/api/unidades/${this.editRow.id}`, formData);
        } else {
          await api.post("/api/unidades", formData);
        }
        this.modalOpen = false;
        this.fetchItems();

        Alert(
          "Éxito",
          this.editRow
            ? "El registro ha sido actualizado exitosamente."
            : "El nuevo registro ha sido creado exitosamente.",
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
      if (!this.authStore.hasPermission("categorias.eliminar")) {
        Alert("Acceso denegado", "No tienes permiso para eliminar unidades de medida.", "warning");
        return;
      }
      this.rowToDelete = row.id;
      this.rowToDeleteName = row.nombre;
      this.confirmOpen = true;
    },
    async deleteRow() {
      if (!this.authStore.hasPermission("categorias.eliminar")) {
        Alert("Acceso denegado", "No tienes permiso para eliminar unidades de medida.", "warning");
        return;
      }
      useLoadingStore().startLoading();
      try {
        const response = await api.delete(`/api/unidades/${this.rowToDelete}`);
        this.confirmOpen = false;
        this.rowToDelete = null;
        this.rowToDeleteName = "";
        this.fetchItems();

        Alert(
          "Éxito",
          response.data.message ||
            "El registro ha sido eliminado exitosamente.",
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
