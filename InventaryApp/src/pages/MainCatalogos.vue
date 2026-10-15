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
              icon="style"
              size="48px"
              class="q-mr-md bg-white-translucent text-white"
            />
            <div>
              <div class="text-h5 text-weight-bold">Marcas y Modelos</div>
              <div class="text-subtitle2 opacity-80">
                Administra las marcas de los artículos y sus modelos específicos
                para organizar el catálogo de productos
              </div>
            </div>
          </div>
          <q-btn
            v-if="authStore.hasPermission('categorias.crear')"
            color="white"
            text-color="primary"
            label="Nueva Marca"
            icon="add"
            unelevated
            class="rounded-btn q-px-md text-weight-bold"
            @click="openMarcaModal(null)"
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
            v-model="searchMarca"
            label="Buscar por nombre de marca..."
            outlined
            dense
            debounce="300"
            class="rounded-input"
            @update:model-value="fetchMarcas"
          >
            <template v-slot:prepend>
              <q-icon name="search" color="grey-6" />
            </template>
            <template v-slot:append v-if="searchMarca">
              <q-icon
                name="close"
                class="cursor-pointer"
                @click="
                  searchMarca = '';
                  fetchMarcas();
                "
              />
            </template>
          </q-input>
        </div>

        <!-- Filtro Estado -->
        <div class="col-12 col-sm-6 col-md-4">
          <q-select
            v-model="estadoFilterMarca"
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
            :display-value="estadoFilterMarca !== '' && estadoFilterMarca !== null ? undefined : 'Todos los Estados'"
            :clearable="estadoFilterMarca !== '' && estadoFilterMarca !== null"
            clear-value=""
            @clear="estadoFilterMarca = ''; fetchMarcas()"
            class="rounded-input"
            @update:model-value="fetchMarcas"
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
            @click="clearFiltersMarca"
            class="rounded-btn full-width"
          />
        </div>
      </div>
    </q-card>

    <!-- MAIN CARD FOR BRANDS -->
    <q-card flat bordered class="rounded-card overflow-hidden bg-white">
      <q-table
        :loading="loading"
        :rows="marcas"
        :columns="columnsMarca"
        row-key="id"
        flat
        class="no-shadow"
        v-model:pagination="paginationMarca"
        @request="onRequestMarca"
        no-data-label="No hay marcas registradas"
        binary-state-sort
      >
        <template v-slot:body-cell-estado="props">
          <q-td :props="props" align="center">
            <q-chip
              :color="props.row.estado ? 'green-2' : 'red-2'"
              :text-color="props.row.estado ? 'green-9' : 'red-9'"
              dense
              class="text-weight-bold"
            >
              {{ props.row.estado ? "Activa" : "Inactiva" }}
            </q-chip>
          </q-td>
        </template>

        <template v-slot:body-cell-options="props">
          <q-td :props="props" align="right">
            <q-btn-dropdown flat color="dark" icon="more_vert" size="md">
              <q-list>
                <q-item
                  clickable
                  v-close-popup
                  @click="openModelsManagementModal(props.row)"
                >
                  <q-item-section avatar>
                    <q-icon name="visibility" color="secondary" />
                  </q-item-section>
                  <q-item-section>Ver Modelos</q-item-section>
                </q-item>
                <q-item
                  clickable
                  v-close-popup
                  v-if="authStore.hasPermission('categorias.editar')"
                  @click="openMarcaModal(props.row)"
                >
                  <q-item-section avatar>
                    <q-icon name="edit" color="primary" />
                  </q-item-section>
                  <q-item-section>Editar</q-item-section>
                </q-item>
                <q-item
                  clickable
                  v-close-popup
                  v-if="authStore.hasPermission('categorias.eliminar')"
                  @click="confirmDeleteMarca(props.row)"
                >
                  <q-item-section avatar>
                    <q-icon name="delete" color="negative" />
                  </q-item-section>
                  <q-item-section>Eliminar</q-item-section>
                </q-item>
              </q-list>
            </q-btn-dropdown>
          </q-td>
        </template>
      </q-table>
    </q-card>

    <!-- MODAL DE MARCA -->
    <q-dialog v-model="marcaModalOpen" persistent>
      <q-card
        style="
          min-width: 400px;
          max-width: 500px;
          width: 90%;
          border-radius: 12px;
          overflow: hidden;
        "
      >
        <q-form ref="marcaForm" @submit.prevent="saveMarca">
          <q-card-section class="bg-primary text-white q-py-md">
            <div class="text-h6 text-weight-bold">
              {{ editMarca ? "Editar Marca" : "Nueva Marca" }}
            </div>
            <div class="text-caption opacity-80">
              Ingresa la información de la marca de los productos
            </div>
          </q-card-section>

          <q-card-section class="q-pa-md q-gutter-y-sm">
            <q-input
              v-model="marcaForm.nombre"
              label="Nombre de la Marca"
              filled
              class="rounded-input"
              :rules="[(val) => !!val || 'El nombre es obligatorio']"
            />
            <q-toggle v-model="marcaForm.estado" label="Activa" color="green" />
          </q-card-section>

          <q-card-actions align="right" class="bg-grey-2 q-pa-md">
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

    <!-- MODAL DE GESTIÓN DE MODELOS POR MARCA -->
    <q-dialog v-model="modelsModalOpen" persistent>
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
              <q-icon name="widgets" class="q-mr-xs" />
              Modelos de:
              {{ selectedBrand ? selectedBrand.nombre : "" }}
            </div>
            <div class="text-caption opacity-80">
              Administra los modelos asociados a esta marca
            </div>
          </div>
          <q-btn
            icon="close"
            flat
            round
            dense
            v-close-popup
            @click="closeModelsModal"
          />
        </q-card-section>

        <q-card-section class="q-pa-md">
          <!-- Formulario rápido para agregar/editar modelo -->
          <q-form
            v-if="authStore.hasPermission('categorias.crear') || authStore.hasPermission('categorias.editar')"
            ref="modeloForm"
            @submit.prevent="saveModelo"
            class="q-mb-md"
          >
            <div class="row q-col-gutter-sm items-center">
              <div class="col-grow">
                <q-input
                  v-model="modeloForm.nombre"
                  label="Nombre del Modelo"
                  filled
                  dense
                  lazy-rules
                  class="rounded-input"
                  :rules="[(val) => !!val || 'El nombre es obligatorio']"
                />
              </div>
              <div class="col-auto">
                <q-select
                  v-model="modeloForm.estado"
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
                  :color="editModelo ? 'warning' : 'primary'"
                  :label="editModelo ? 'Actualizar' : 'Agregar'"
                  unelevated
                  class="rounded-btn"
                />
                <q-btn
                  v-if="editModelo"
                  flat
                  label="Cancelar"
                  color="grey"
                  class="q-ml-xs rounded-btn"
                  @click="cancelModelForm"
                />
              </div>
            </div>
          </q-form>

          <!-- Tabla de Modelos -->
          <q-table
            :loading="loadingModelos"
            :rows="modelos"
            :columns="columnsModeloModal"
            row-key="id"
            v-model:pagination="paginationModelo"
            @request="onRequestModelo"
            flat
            bordered
            dense
            binary-state-sort
            no-data-label="No hay modelos registrados para esta marca"
          >
            <!-- Slot para estado -->
            <template v-slot:body-cell-estado="props">
              <q-td align="center">
                <q-chip
                  :color="
                    props.row.estado === 1 || props.row.estado === true || props.row.estado === '1' || props.row.estado === 'true'
                      ? 'green-2'
                      : 'red-2'
                  "
                  :text-color="
                    props.row.estado === 1 || props.row.estado === true || props.row.estado === '1' || props.row.estado === 'true'
                      ? 'green-9'
                      : 'red-9'
                  "
                  :label="
                    props.row.estado === 1 || props.row.estado === true || props.row.estado === '1' || props.row.estado === 'true'
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
                  @click="openModeloForm(props.row)"
                />
                <q-btn
                  v-if="authStore.hasPermission('categorias.eliminar')"
                  flat
                  round
                  color="negative"
                  icon="delete"
                  size="sm"
                  @click="confirmDeleteModelo(props.row)"
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
            @click="closeModelsModal"
            class="rounded-btn"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- CONFIRMAR ELIMINAR MARCA -->
    <q-dialog v-model="deleteMarcaOpen">
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
        <q-card-section>
          ¿Deseas eliminar la marca <strong>{{ marcaToDelete?.nombre }}</strong
          >? Si tiene dependencias de productos o modelos, será desactivada en
          su lugar.
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
            @click="deleteMarca"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- CONFIRMAR ELIMINAR MODELO -->
    <q-dialog v-model="deleteModeloOpen">
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
        <q-card-section>
          ¿Deseas eliminar el modelo
          <strong>{{ modeloToDelete?.nombre }}</strong
          >? Si tiene dependencias de productos, será desactivado en su lugar.
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
            @click="deleteModelo"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script>
import { useAuthStore } from "src/store/auth-store";
import { api } from "boot/axios";
import { useLoadingStore } from "src/store/loading-store";
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
      marcas: [],
      modelos: [],
      searchMarca: "",
      searchModelo: "",
      selectedBrand: null,

      columnsMarca: [
        {
          name: "nombre",
          label: "Marca",
          align: "left",
          field: "nombre",
          sortable: true,
        },
        { name: "estado", label: "Estado", align: "center", sortable: true },
        { name: "options", label: "Acciones", align: "right" },
      ],
      columnsModeloModal: [
        {
          name: "nombre",
          label: "Modelo",
          align: "left",
          field: "nombre",
          sortable: true,
        },
        { name: "estado", label: "Estado", align: "center", sortable: true },
        { name: "options", label: "Acciones", align: "right" },
      ],

      paginationMarca: {
        sortBy: "nombre",
        descending: false,
        page: 1,
        rowsPerPage: 10,
        rowsNumber: 0,
      },
      paginationModelo: {
        sortBy: "nombre",
        descending: false,
        page: 1,
        rowsPerPage: 5,
        rowsNumber: 0,
      },

      marcaModalOpen: false,
      modelsModalOpen: false,
      loadingModelos: false,

      deleteMarcaOpen: false,
      deleteModeloOpen: false,

      editMarca: null,
      editModelo: null,

      marcaToDelete: null,
      modeloToDelete: null,

      marcaForm: {
        nombre: "",
        estado: true,
      },
      modeloForm: {
        nombre: "",
        estado: "Activo",
      },
      estadoFilterMarca: "",
    };
  },
  created() {
    this.fetchMarcas();
  },
  methods: {
    clearFiltersMarca() {
      this.searchMarca = "";
      this.estadoFilterMarca = "";
      this.fetchMarcas();
    },
    async fetchMarcas() {
      this.paginationMarca.page = 1;
      this.onRequestMarca({
        pagination: this.paginationMarca,
        filter: this.searchMarca,
      });
    },
    async onRequestMarca(props) {
      const { page, rowsPerPage, sortBy, descending } = props.pagination;
      const filter = props.filter;

      this.loading = true;
      try {
        const response = await api.get("/api/marcas", {
          params: {
            page: page,
            limit: rowsPerPage === 0 ? -1 : rowsPerPage,
            search: filter,
            estado: this.estadoFilterMarca !== "" ? this.estadoFilterMarca : undefined,
            sortBy: sortBy,
            descending: descending,
          },
        });

        const data = response.data.data;
        if (data.data) {
          this.marcas = data.data;
          this.paginationMarca.rowsNumber = data.total;
        } else {
          this.marcas = data;
          this.paginationMarca.rowsNumber = data.length;
        }

        this.paginationMarca.page = page;
        this.paginationMarca.rowsPerPage = rowsPerPage;
        this.paginationMarca.sortBy = sortBy;
        this.paginationMarca.descending = descending;
      } catch (err) {
        console.error(err);
        Alert(
          "Error",
          err.response?.data?.message || "No se pudieron cargar las marcas",
          "error"
        );
      } finally {
        this.loading = false;
      }
    },
    fetchModelosForSelectedBrand() {
      this.onRequestModelo({
        pagination: this.paginationModelo,
        filter: this.searchModelo,
      });
    },
    async onRequestModelo(props) {
      if (!this.selectedBrand) return;
      const { page, rowsPerPage, sortBy, descending } = props.pagination;
      const filter = props.filter;
      this.loadingModelos = true;
      try {
        const res = await api.get("/api/modelos", {
          params: {
            page: page,
            limit: rowsPerPage === 0 ? -1 : rowsPerPage,
            search: filter,
            marca_id: this.selectedBrand.id,
            sortBy: sortBy,
            descending: descending,
          },
        });
        const data = res.data.data;
        if (data.data) {
          this.modelos = data.data;
          this.paginationModelo.rowsNumber = data.total;
        } else {
          this.modelos = data;
          this.paginationModelo.rowsNumber = data.length;
        }
        this.paginationModelo.page = page;
        this.paginationModelo.rowsPerPage = rowsPerPage;
        this.paginationModelo.sortBy = sortBy;
        this.paginationModelo.descending = descending;
      } catch (err) {
        console.error(err);
        Alert(
          "Error",
          err.response?.data?.message || "No se pudieron cargar los modelos",
          "error"
        );
      } finally {
        this.loadingModelos = false;
      }
    },
    openMarcaModal(row) {
      if (row && !this.authStore.hasPermission("categorias.editar")) {
        Alert("Acceso denegado", "No tienes permiso para editar marcas.", "warning");
        return;
      }
      if (!row && !this.authStore.hasPermission("categorias.crear")) {
        Alert("Acceso denegado", "No tienes permiso para agregar marcas.", "warning");
        return;
      }
      if (row) {
        this.editMarca = row;
        this.marcaForm = {
          nombre: row.nombre,
          estado: !!row.estado,
        };
      } else {
        this.editMarca = null;
        this.marcaForm = {
          nombre: "",
          estado: true,
        };
      }
      this.marcaModalOpen = true;
    },
    async saveMarca() {
      const isValid = await this.$refs.marcaForm.validate();
      if (!isValid) return;

      Alert("Guardando...", "Guardando la marca...", "info");
      useLoadingStore().startLoading();
      try {
        if (this.editMarca) {
          await api.put(`/api/marcas/${this.editMarca.id}`, this.marcaForm);
          Alert("Éxito", "Marca actualizada correctamente", "success");
        } else {
          await api.post("/api/marcas", this.marcaForm);
          Alert("Éxito", "Marca creada correctamente", "success");
        }
        this.marcaModalOpen = false;
        this.fetchMarcas();
      } catch (err) {
        Alert(
          "Error",
          err.response?.data?.message || "No se pudo guardar la marca",
          "error"
        );
      } finally {
        useLoadingStore().stopLoading();
      }
    },
    confirmDeleteMarca(row) {
      this.marcaToDelete = row;
      this.deleteMarcaOpen = true;
    },
    async deleteMarca() {
      useLoadingStore().startLoading();
      try {
        const res = await api.delete(`/api/marcas/${this.marcaToDelete.id}`);
        Alert(
          "Éxito",
          res.data.message || "Marca eliminada correctamente",
          "success"
        );
        this.deleteMarcaOpen = false;
        this.fetchMarcas();
      } catch (err) {
        Alert(
          "Error",
          err.response?.data?.message || "No se pudo eliminar la marca",
          "error"
        );
      } finally {
        useLoadingStore().stopLoading();
      }
    },

    // MODEL MANAGEMENT METHODS
    openModelsManagementModal(brand) {
      this.selectedBrand = brand;
      this.searchModelo = "";
      this.editModelo = null;
      this.modeloForm = { nombre: "", estado: "Activo" };
      this.paginationModelo.page = 1;
      this.modelsModalOpen = true;
      this.$nextTick(() => {
        if (this.$refs.modeloForm) {
          this.$refs.modeloForm.resetValidation();
        }
      });
      this.fetchModelosForSelectedBrand();
    },
    closeModelsModal() {
      this.modelsModalOpen = false;
      this.selectedBrand = null;
      this.editModelo = null;
      this.modeloForm = { nombre: "", estado: "Activo" };
      this.modelos = [];
    },
    openModeloForm(row) {
      if (row) {
        this.editModelo = row;
        this.modeloForm = {
          nombre: row.nombre,
          estado:
            row.estado === true ||
            row.estado === 1 ||
            row.estado === "1" ||
            row.estado === "true" ||
            row.estado === "Activo"
              ? "Activo"
              : "Inactivo",
        };
      } else {
        this.cancelModelForm();
      }
    },
    cancelModelForm() {
      this.editModelo = null;
      this.modeloForm = { nombre: "", estado: "Activo" };
      this.$nextTick(() => {
        if (this.$refs.modeloForm) {
          this.$refs.modeloForm.resetValidation();
        }
      });
    },
    async saveModelo() {
      const isValid = await this.$refs.modeloForm.validate();
      if (!isValid) return;

      useLoadingStore().startLoading();
      try {
        const isActivo =
          this.modeloForm.estado === "Activo" ||
          this.modeloForm.estado === true ||
          this.modeloForm.estado === 1;

        const payload = {
          nombre: this.modeloForm.nombre,
          estado: isActivo,
          marca_id: this.selectedBrand.id,
        };

        if (this.editModelo) {
          await api.put(`/api/modelos/${this.editModelo.id}`, payload);
          Alert("Éxito", "Modelo actualizado correctamente", "success");
        } else {
          await api.post("/api/modelos", payload);
          Alert("Éxito", "Modelo creado correctamente", "success");
        }
        this.cancelModelForm();
        this.fetchModelosForSelectedBrand();
      } catch (err) {
        Alert(
          "Error",
          err.response?.data?.message || "No se pudo guardar el modelo",
          "error"
        );
      } finally {
        useLoadingStore().stopLoading();
      }
    },
    confirmDeleteModelo(row) {
      this.modeloToDelete = row;
      this.deleteModeloOpen = true;
    },
    async deleteModelo() {
      useLoadingStore().startLoading();
      try {
        const res = await api.delete(`/api/modelos/${this.modeloToDelete.id}`);
        Alert(
          "Éxito",
          res.data.message || "Modelo eliminado correctamente",
          "success"
        );
        this.deleteModeloOpen = false;
        if (this.editModelo && this.editModelo.id === this.modeloToDelete?.id) {
          this.cancelModelForm();
        }
        this.modeloToDelete = null;
        this.fetchModelosForSelectedBrand();
      } catch (err) {
        Alert(
          "Error",
          err.response?.data?.message || "No se pudo eliminar el modelo",
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
