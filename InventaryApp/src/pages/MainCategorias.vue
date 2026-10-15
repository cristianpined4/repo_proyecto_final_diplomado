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
              icon="category"
              size="48px"
              class="q-mr-md bg-white-translucent text-white"
            />
            <div>
              <div class="text-h5 text-weight-bold">
                Categorías de Productos
              </div>
              <div class="text-subtitle2 opacity-80">
                Organiza y clasifica tus artículos del inventario en categorías
                lógicas
              </div>
            </div>
          </div>
          <q-btn
            v-if="authStore.hasPermission('categorias.crear')"
            color="white"
            text-color="primary"
            label="Agregar Categoría"
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
            label="Buscar por nombre de categoría..."
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

        <!-- Filtro Estado -->
        <div class="col-12 col-sm-6 col-md-4">
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
            @clear="statusFilter = ''; fetchItems()"
            class="rounded-input"
            @update:model-value="fetchItems"
          >
            <template v-slot:prepend>
              <q-icon name="toggle_on" color="primary" />
            </template>
          </q-select>
        </div>

        <!-- Limpiar Filtros -->
        <div class="col-12 col-sm-6 col-md-2 row justify-end">
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
        no-data-label="No se encontraron categorías registradas"
      >
        <!-- Slot para status -->
        <template v-slot:body-cell-status="props">
          <q-td align="center">
            <q-chip
              :color="(props.row.status === true || props.row.status === 1 || props.row.status === 'true' || props.row.status === '1') ? 'green-2' : 'red-2'"
              :text-color="(props.row.status === true || props.row.status === 1 || props.row.status === 'true' || props.row.status === '1') ? 'green-9' : 'red-9'"
              :label="(props.row.status === true || props.row.status === 1 || props.row.status === 'true' || props.row.status === '1') ? 'Activo' : 'Inactivo'"
              dense
              class="text-weight-bold"
            />
          </q-td>
        </template>

        <template v-slot:body-cell-options="props">
          <q-td align="right">
            <q-btn-dropdown flat color="dark" icon="more_vert" size="md">
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
                  @click="openSubcategoriesModal(props.row)"
                >
                  <q-item-section avatar>
                    <q-icon name="list" color="secondary" />
                  </q-item-section>
                  <q-item-section> Subcategorías </q-item-section>
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
        <q-form ref="categoriaForm" @submit.prevent="saveRow">
          <q-card-section class="bg-primary text-white q-py-md">
            <div class="text-h6 text-weight-bold row items-center">
              <q-icon name="category" class="q-mr-xs" />
              {{ editRow ? "Editar Categoría" : "Agregar Categoría" }}
            </div>
            <div class="text-caption opacity-80">
              Rellena la información básica de la categoría
            </div>
          </q-card-section>

          <q-card-section class="q-pa-md q-gutter-y-sm">
            <q-input
              v-model="form.name"
              label="Nombre de Categoría"
              filled
              class="rounded-input"
              :rules="[(val) => !!val || 'El nombre es requerido']"
            />
            <q-select
              v-model="form.status"
              :options="estadoOptions"
              label="Estado"
              filled
              emit-value
              map-options
              class="rounded-input"
              :rules="[(val) => val !== null && val !== undefined || 'El estado es requerido']"
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
          <span class="text-h6 text-weight-bold">¿Eliminar categoría?</span>
        </q-card-section>
        <q-card-section class="q-pt-none">
          ¿Estás seguro de que deseas eliminar permanentemente la categoría
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
            label="Eliminar Categoría"
            color="negative"
            unelevated
            class="rounded-btn q-px-md"
            @click="deleteRow"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- Modal para gestionar subcategorías -->
    <q-dialog v-model="subcategoriesModalOpen" persistent>
      <q-card
        style="
          width: 750px;
          max-width: 90vw;
          border-radius: 12px;
          overflow: hidden;
        "
      >
        <q-card-section
          class="bg-secondary text-white q-py-md row items-center justify-between"
        >
          <div>
            <div class="text-h6 text-weight-bold row items-center">
              <q-icon name="list" class="q-mr-xs" />
              Subcategorías de:
              {{ selectedCategory ? selectedCategory.name : "" }}
            </div>
            <div class="text-caption opacity-80">
              Administra las subclasificaciones de esta categoría
            </div>
          </div>
          <q-btn icon="close" flat round dense v-close-popup />
        </q-card-section>

        <q-card-section class="q-pa-md">
          <!-- Formulario rápido para agregar/editar subcategoría -->
          <q-form
            v-if="authStore.hasPermission('categorias.crear') || authStore.hasPermission('categorias.editar')"
            ref="subForm"
            @submit.prevent="saveSubcategory"
            class="q-mb-md"
          >
            <div class="row q-col-gutter-sm items-center">
              <div class="col-grow">
                <q-input
                  v-model="subForm.nombre"
                  label="Nombre de Subcategoría"
                  filled
                  dense
                  lazy-rules
                  class="rounded-input"
                  :rules="[(val) => !!val || 'El nombre es obligatorio']"
                />
              </div>
              <div class="col-auto">
                <q-select
                  v-model="subForm.estado"
                  :options="['Activo', 'Inactivo']"
                  label="Estado"
                  filled
                  dense
                  class="rounded-input"
                  style="width: 130px; margin-top: -20px"
                />
              </div>
              <div class="col-auto" style="margin-top: -20px">
                <q-btn
                  type="submit"
                  :color="subEditingRow ? 'warning' : 'primary'"
                  :label="subEditingRow ? 'Actualizar' : 'Agregar'"
                  unelevated
                  class="rounded-btn"
                />
                <q-btn
                  v-if="subEditingRow"
                  flat
                  label="Cancelar"
                  color="grey"
                  class="q-ml-xs rounded-btn"
                  @click="cancelSubEdit"
                />
              </div>
            </div>
          </q-form>

          <!-- Tabla de Subcategorías -->
          <q-table
            :loading="loading"
            :rows="subcategories"
            :columns="subColumns"
            row-key="id"
            flat
            bordered
            dense
            :pagination="{ rowsPerPage: 5 }"
            no-data-label="No hay subcategorías en esta categoría"
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
                      ? 'Activo'
                      : 'Inactivo'
                  "
                  dense
                  class="text-weight-bold"
                />
              </q-td>
            </template>

            <!-- Acciones -->
            <template v-slot:body-cell-options="props">
              <q-td align="right">
                <q-btn
                  v-if="authStore.hasPermission('categorias.editar')"
                  flat
                  round
                  color="primary"
                  icon="edit"
                  size="sm"
                  @click="editSubcategory(props.row)"
                />
                <q-btn
                  v-if="authStore.hasPermission('categorias.eliminar')"
                  flat
                  round
                  color="negative"
                  icon="delete"
                  size="sm"
                  @click="deleteSubcategory(props.row)"
                />
                <span v-if="!authStore.hasPermission('categorias.editar') && !authStore.hasPermission('categorias.eliminar')" class="text-caption text-grey-5">—</span>
              </q-td>
            </template>
          </q-table>
        </q-card-section>

        <q-card-actions align="right" class="q-pa-md bg-grey-2">
          <q-btn
            flat
            label="Cerrar"
            color="grey-7"
            v-close-popup
            class="rounded-btn"
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
        sortBy: "name",
        descending: false,
        page: 1,
        rowsPerPage: 15,
        rowsNumber: 0,
      },
      columns: [
        {
          name: "name",
          label: "Categoría",
          align: "left",
          field: "name",
          sortable: true,
        },
        {
          name: "status",
          label: "Estado",
          align: "center",
          field: "status",
          sortable: true,
        },
        { name: "options", label: "Acciones", align: "right" },
      ],
      form: {
        name: "",
        status: "",
      },
      estadoOptions: [
        { label: "Activo", value: true },
        { label: "Inactivo", value: false },
      ],
      modalOpen: false,
      confirmOpen: false,
      editRow: null,
      rowToDelete: null,
      rowToDeleteName: "",
      // Subcategories Management
      subcategoriesModalOpen: false,
      selectedCategory: null,
      subcategories: [],
      subEditingRow: null,
      subForm: {
        nombre: "",
        estado: "Activo",
      },
      subColumns: [
        {
          name: "nombre",
          label: "Subcategoría",
          align: "left",
          field: "nombre",
          sortable: true,
        },
        { name: "estado", label: "Estado", align: "center", field: "estado" },
        { name: "options", label: "Acciones", align: "right" },
      ],
      statusFilter: "",
    };
  },
  created() {
    this.fetchItems();
  },
  methods: {
    clearFilters() {
      this.search = "";
      this.statusFilter = "";
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
        const response = await api.get("/api/categorias", {
          params: {
            page: page,
            limit: rowsPerPage === 0 ? -1 : rowsPerPage,
            search: filter,
            status: this.statusFilter !== "" ? this.statusFilter : undefined,
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
        Alert("Acceso denegado", "No tienes permiso para editar categorías.", "warning");
        return;
      }
      if (!row && !this.authStore.hasPermission("categorias.crear")) {
        Alert("Acceso denegado", "No tienes permiso para agregar categorías.", "warning");
        return;
      }
      if (row) {
        this.editRow = row;
        const isActivo =
          row.status === true ||
          row.status === 1 ||
          row.status === "1" ||
          row.status === "true" ||
          row.status === "Activo";
        this.form = {
          ...row,
          status: isActivo,
        };
      } else {
        this.editRow = null;
        this.form = { name: "", status: true };
      }
      this.modalOpen = true;
    },
    async saveRow() {
      const formValid = await this.$refs.categoriaForm.validate();
      if (!formValid) return;

      Alert("Guardando...", "Enviando datos de la categoría...", "info");
      useLoadingStore().startLoading();
      try {
        const isActivo =
          this.form.status === true ||
          this.form.status === 1 ||
          this.form.status === "1" ||
          this.form.status === "true" ||
          this.form.status === "Activo";
        const formData = {
          ...this.form,
          status: isActivo,
        };

        if (this.editRow) {
          await api.put(`/api/categorias/${this.editRow.id}`, formData);
          this.fetchItems();
        } else {
          await api.post("/api/categorias", formData);
          this.fetchItems();
        }
        this.modalOpen = false;

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
        Alert("Acceso denegado", "No tienes permiso para eliminar categorías.", "warning");
        return;
      }
      this.rowToDelete = row.id;
      this.rowToDeleteName = row.name;
      this.confirmOpen = true;
    },
    async deleteRow() {
      if (!this.authStore.hasPermission("categorias.eliminar")) {
        Alert("Acceso denegado", "No tienes permiso para eliminar categorías.", "warning");
        return;
      }
      useLoadingStore().startLoading();
      try {
        await api.delete(`/api/categorias/${this.rowToDelete}`);
        this.confirmOpen = false;
        this.rowToDelete = null;
        this.rowToDeleteName = "";
        this.fetchItems();
        Alert(
          "Éxito",
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
    async openSubcategoriesModal(row) {
      this.selectedCategory = row;
      this.subcategoriesModalOpen = true;
      this.cancelSubEdit();
      this.fetchSubcategories();
    },
    async fetchSubcategories() {
      if (!this.selectedCategory) return;
      useLoadingStore().startLoading();
      try {
        const response = await api.get("/api/subcategorias", {
          params: {
            categoria_id: this.selectedCategory.id,
            limit: -1,
          },
        });
        const resData = response.data.data;
        this.subcategories = Array.isArray(resData)
          ? resData
          : resData.data || [];
      } catch (error) {
        Alert("Error", "Error al cargar subcategorías", "error");
      } finally {
        useLoadingStore().stopLoading();
      }
    },
    cancelSubEdit() {
      this.subEditingRow = null;
      this.subForm = {
        nombre: "",
        estado: "Activo",
      };
      this.$nextTick(() => {
        if (this.$refs.subForm) {
          this.$refs.subForm.resetValidation();
        }
      });
    },
    editSubcategory(row) {
      this.subEditingRow = row;
      this.subForm = {
        nombre: row.nombre,
        estado:
          row.estado === 1 || row.estado === true || row.estado === "1"
            ? "Activo"
            : "Inactivo",
      };
    },
    async saveSubcategory() {
      const formValid = await this.$refs.subForm.validate();
      if (!formValid) return;

      useLoadingStore().startLoading();
      try {
        const payload = {
          nombre: this.subForm.nombre,
          categoria_id: this.selectedCategory.id,
          estado: this.subForm.estado === "Activo" ? 1 : 0,
        };

        if (this.subEditingRow) {
          await api.put(`/api/subcategorias/${this.subEditingRow.id}`, payload);
          Alert("Éxito", "Subcategoría actualizada correctamente", "success");
        } else {
          await api.post("/api/subcategorias", payload);
          Alert("Éxito", "Subcategoría creada correctamente", "success");
        }
        this.cancelSubEdit();
        this.fetchSubcategories();
      } catch (error) {
        Alert(
          "Error",
          error.response?.data?.message || "Error al guardar subcategoría",
          "error"
        );
      } finally {
        useLoadingStore().stopLoading();
      }
    },
    async deleteSubcategory(row) {
      useLoadingStore().startLoading();
      try {
        const response = await api.delete(`/api/subcategorias/${row.id}`);
        Alert(
          "Éxito",
          response.data.message || "Subcategoría eliminada",
          "success"
        );
        this.cancelSubEdit();
        this.fetchSubcategories();
      } catch (error) {
        Alert("Error", "Error al eliminar subcategoría", "error");
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
