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
              icon="storefront"
              size="48px"
              class="q-mr-md bg-white-translucent text-white"
            />
            <div>
              <div class="text-h5 text-weight-bold">Gestión de Sucursales</div>
              <div class="text-subtitle2 opacity-80">
                Administra los locales físicos de la empresa, su información de
                contacto y logotipos para comprobantes
              </div>
            </div>
          </div>
          <q-btn
            v-if="authStore.hasPermission('sucursales.crear')"
            color="white"
            text-color="primary"
            label="Agregar Sucursal"
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
            label="Buscar por nombre, dirección, teléfono o correo..."
            outlined
            dense
            debounce="300"
            class="rounded-input"
            @update:model-value="fetchSucursales"
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
                  fetchSucursales();
                "
              />
            </template>
          </q-input>
        </div>

        <!-- Filtro Ciudad -->
        <div class="col-12 col-md-6">
          <q-input
            v-model="ciudadFilter"
            label="Filtrar por Ciudad"
            outlined
            dense
            debounce="300"
            class="rounded-input"
            @update:model-value="fetchSucursales"
            clearable
          >
            <template v-slot:prepend>
              <q-icon name="location_city" color="primary" />
            </template>
          </q-input>
        </div>

        <!-- Filtro Estado -->
        <div class="col-12 col-sm-6 col-md-6">
          <q-select
            v-model="activaFilter"
            :options="[
              { label: 'Todas las Sucursales', value: '' },
              { label: 'Activas', value: '1' },
              { label: 'Inactivas', value: '0' },
            ]"
            label="Estado"
            outlined
            dense
            emit-value
            map-options
            :display-value="activaFilter !== '' && activaFilter !== null ? undefined : 'Todas las Sucursales'"
            :clearable="activaFilter !== '' && activaFilter !== null"
            clear-value=""
            @clear="activaFilter = ''; fetchSucursales()"
            class="rounded-input"
            @update:model-value="fetchSucursales"
          >
            <template v-slot:prepend>
              <q-icon name="toggle_on" color="primary" />
            </template>
          </q-select>
        </div>

        <!-- Limpiar Filtros -->
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

    <!-- TABLA DE SUCURSALES -->
    <q-card flat bordered class="rounded-card overflow-hidden bg-white">
      <q-table
        :rows="sucursales"
        :columns="columns"
        row-key="id"
        flat
        class="no-shadow"
        v-model:pagination="pagination"
        @request="onRequest"
        :filter="search"
        :loading="loading"
        binary-state-sort
        no-data-label="No hay sucursales registradas"
      >
        <!-- FIRST COLUMN: LOGO -->
        <template v-slot:body-cell-logo="props">
          <q-td align="center">
            <q-avatar
              size="40px"
              square
              class="bg-grey-3 rounded-borders shadow-1"
            >
              <q-img
                v-if="props.row.logo"
                :src="'http://localhost:8000/storage/' + props.row.logo"
                loading="lazy"
                spinner-color="primary"
                style="height: 40px; width: 40px; object-fit: contain"
              >
                <template v-slot:error>
                  <q-icon name="storefront" color="grey-6" size="20px" />
                </template>
              </q-img>
              <q-icon v-else name="storefront" color="grey-6" size="24px" />
            </q-avatar>
          </q-td>
        </template>

        <!-- STATUS BADGE -->
        <template v-slot:body-cell-activa="props">
          <q-td align="center">
            <q-chip
              :color="props.row.activa ? 'green-2' : 'red-2'"
              :text-color="props.row.activa ? 'green-9' : 'red-9'"
              :label="props.row.activa ? 'Activa' : 'Inactiva'"
              dense
              class="text-weight-bold"
            />
          </q-td>
        </template>

        <!-- OPTIONS BUTTON -->
        <template v-slot:body-cell-options="props">
          <q-td align="right">
            <q-btn-dropdown
              v-if="authStore.hasPermission('sucursales.editar') || authStore.hasPermission('sucursales.eliminar')"
              flat
              color="dark"
              icon="more_vert"
              size="md"
            >
              <q-list>
                <q-item
                  clickable
                  v-close-popup
                  v-if="authStore.hasPermission('sucursales.editar')"
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
                  v-if="authStore.hasPermission('sucursales.eliminar')"
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

    <!-- FORM DIALOG -->
    <q-dialog v-model="modalOpen" persistent>
      <q-card
        style="
          max-width: 600px;
          width: 90%;
          border-radius: 12px;
          overflow: hidden;
        "
      >
        <q-form ref="sucursalForm" @submit.prevent="saveRow">
          <q-card-section class="bg-primary text-white q-py-md">
            <div class="text-h6 text-weight-bold row items-center">
              <q-icon name="store" class="q-mr-xs" />
              {{ editRow ? "Editar Sucursal" : "Agregar Sucursal" }}
            </div>
            <div class="text-caption opacity-80">
              Rellena la información de contacto y de dirección de la sucursal
            </div>
          </q-card-section>

          <q-card-section class="q-pa-md q-gutter-y-sm">
            <q-input
              v-model="form.nombre"
              label="Nombre de la Sucursal"
              filled
              class="rounded-input"
              hint="Nombre comercial de la sucursal (mínimo 3 caracteres)"
              :rules="[
                (val) => (!!val && val.trim().length > 0) || 'El nombre es obligatorio',
                (val) => (val && val.trim().length >= 3) || 'Debe tener al menos 3 caracteres',
                (val) => (val && val.trim().length <= 100) || 'Máximo 100 caracteres',
                (val) => /^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑüÜ\s.,&'()-]+$/.test(val ? val.trim() : '') || 'Formato no válido (solo letras, números y signos básicos)',
              ]"
            />

            <div class="row q-col-gutter-sm">
              <div class="col-6">
                <q-input
                  v-model="form.telefono"
                  label="Teléfono"
                  filled
                  class="rounded-input"
                  mask="####-####"
                  placeholder="0000-0000"
                  hint="Formato: 0000-0000"
                  @update:model-value="onPhoneInput"
                  :rules="[
                    (val) =>
                      !val ||
                      /^[0-9]{4}-[0-9]{4}$/.test(val ? val.trim() : '') ||
                      'Formato no válido (debe ser 0000-0000)',
                  ]"
                />
              </div>
              <div class="col-6">
                <q-input
                  v-model="form.correo"
                  label="Correo Electrónico"
                  type="email"
                  filled
                  class="rounded-input"
                  hint="Ej: sucursal@empresa.com"
                  :rules="[
                    (val) =>
                      !val ||
                      /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/.test(val ? val.trim() : '') ||
                      'Formato de correo no válido',
                    (val) => !val || val.trim().length <= 150 || 'Máximo 150 caracteres',
                  ]"
                />
              </div>
            </div>

            <q-input
              v-model="form.direccion"
              label="Dirección / Calle"
              filled
              class="rounded-input"
              type="textarea"
              autogrow
              hint="Ubicación física (mínimo 5 caracteres)"
              :rules="[
                (val) => !val || val.trim().length >= 5 || 'Debe tener al menos 5 caracteres',
                (val) => !val || val.trim().length <= 250 || 'Máximo 250 caracteres',
              ]"
            />

            <div class="row q-col-gutter-sm">
              <div class="col-4">
                <q-input
                  v-model="form.ciudad"
                  label="Ciudad"
                  filled
                  class="rounded-input"
                  hint="Ej: San Salvador"
                  :rules="[
                    (val) => !val || val.trim().length >= 2 || 'Mínimo 2 caracteres',
                    (val) => !val || val.trim().length <= 100 || 'Máximo 100 caracteres',
                    (val) => !val || /^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s.-]+$/.test(val ? val.trim() : '') || 'Solo letras y puntos',
                  ]"
                />
              </div>
              <div class="col-4">
                <q-input
                  v-model="form.estado"
                  label="Estado / Provincia"
                  filled
                  class="rounded-input"
                  hint="Ej: San Salvador"
                  :rules="[
                    (val) => !val || val.trim().length >= 2 || 'Mínimo 2 caracteres',
                    (val) => !val || val.trim().length <= 100 || 'Máximo 100 caracteres',
                    (val) => !val || /^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s.-]+$/.test(val ? val.trim() : '') || 'Solo letras y puntos',
                  ]"
                />
              </div>
              <div class="col-4">
                <q-input
                  v-model="form.pais"
                  label="País"
                  filled
                  class="rounded-input"
                  hint="Ej: El Salvador"
                  :rules="[
                    (val) => !val || val.trim().length >= 2 || 'Mínimo 2 caracteres',
                    (val) => !val || val.trim().length <= 100 || 'Máximo 100 caracteres',
                    (val) => !val || /^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s.-]+$/.test(val ? val.trim() : '') || 'Solo letras y puntos',
                  ]"
                />
              </div>
            </div>

            <div class="q-py-xs">
              <q-toggle
                v-model="form.activa"
                label="Sucursal Activa"
                color="green"
              />
            </div>

            <!-- LOGO DE SUCURSAL -->
            <div class="border-top q-pt-md q-mt-md">
              <div class="text-subtitle2 text-weight-bold text-grey-8 q-mb-sm">
                Logo de la Sucursal
              </div>
              <div class="row items-center q-col-gutter-md">
                <div class="col-4 text-center">
                  <q-avatar
                    size="80px"
                    square
                    class="bg-grey-3 rounded-borders shadow-2"
                  >
                    <q-img
                      v-if="editRow && editRow.logo && !logoFile"
                      :src="'http://localhost:8000/storage/' + editRow.logo"
                    >
                      <template v-slot:error
                        ><q-icon name="storefront" size="48px" color="grey-6"
                      /></template>
                    </q-img>
                    <q-icon
                      v-else-if="!logoFile"
                      name="storefront"
                      size="48px"
                      color="grey-6"
                    />
                    <div
                      v-else
                      class="text-caption text-weight-bold text-primary q-pa-xs"
                    >
                      Logo Seleccionado
                    </div>
                  </q-avatar>
                </div>
                <div class="col-8">
                  <q-file
                    v-model="logoFile"
                    label="Seleccionar Logotipo"
                    filled
                    accept="image/jpeg,image/png,image/jpg,image/gif,image/svg+xml,image/webp"
                    max-file-size="2097152"
                    @rejected="onLogoRejected"
                    class="rounded-input"
                    clearable
                  >
                    <template v-slot:prepend>
                      <q-icon name="cloud_upload" />
                    </template>
                  </q-file>
                </div>
              </div>
            </div>
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

    <!-- CONFIRMACIÓN DE ELIMINACIÓN -->
    <q-dialog v-model="confirmOpen" persistent>
      <q-card style="border-radius: 12px">
        <q-card-section class="row items-center q-pb-none">
          <q-avatar
            icon="warning"
            color="warning"
            text-color="white"
            class="q-mr-sm"
          />
          <span class="text-h6 text-weight-bold">Eliminar Sucursal</span>
        </q-card-section>
        <q-card-section class="q-pt-md">
          ¿Estás seguro de que deseas eliminar permanentemente la sucursal
          <strong>{{ rowToDeleteName }}</strong
          >? Las sucursales con transacciones o usuarios vinculados no se pueden
          borrar.
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
      sucursales: [],
      search: "",
      pagination: {
        sortBy: "nombre",
        descending: false,
        page: 1,
        rowsPerPage: 15,
        rowsNumber: 0,
      },
      loading: false,
      columns: [
        { name: "logo", label: "Logo", align: "center" },
        {
          name: "nombre",
          label: "Nombre",
          align: "left",
          field: "nombre",
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
          name: "correo",
          label: "Correo",
          align: "left",
          field: "correo",
          sortable: true,
        },
        {
          name: "direccion",
          label: "Dirección",
          align: "left",
          field: "direccion",
        },
        {
          name: "activa",
          label: "Estado",
          align: "center",
          field: "activa",
          sortable: true,
        },
        { name: "options", label: "Acciones", align: "right" },
      ],
      modalOpen: false,
      confirmOpen: false,
      editRow: null,
      rowToDelete: null,
      rowToDeleteName: "",
      logoFile: null,
      form: {
        nombre: "",
        telefono: "",
        correo: "",
        direccion: "",
        ciudad: "",
        estado: "",
        pais: "",
        activa: true,
      },
      activaFilter: "",
      ciudadFilter: "",
    };
  },
  created() {
    this.fetchSucursales();
  },
  methods: {
    clearFilters() {
      this.search = "";
      this.activaFilter = "";
      this.ciudadFilter = "";
      this.fetchSucursales();
    },
    async fetchSucursales() {
      this.onRequest({
        pagination: this.pagination,
        filter: this.search,
      });
    },
    async onRequest(props) {
      const { page, rowsPerPage, sortBy, descending } = props.pagination;
      this.loading = true;
      try {
        const response = await api.get("/api/sucursales", {
          params: {
            page: page,
            limit: rowsPerPage === 0 ? -1 : rowsPerPage,
            search: this.search,
            activa: this.activaFilter !== "" ? this.activaFilter : undefined,
            ciudad: this.ciudadFilter || undefined,
            sortBy: sortBy,
            descending: descending,
          },
        });
        const resData = response.data.data;
        if (resData.data) {
          this.sucursales = resData.data;
          this.pagination.rowsNumber = resData.total;
        } else {
          this.sucursales = resData;
          this.pagination.rowsNumber = resData.length;
        }
        this.pagination.page = page;
        this.pagination.rowsPerPage = rowsPerPage;
        this.pagination.sortBy = sortBy;
        this.pagination.descending = descending;
      } catch (error) {
        Alert("Error", "No se pudieron obtener las sucursales", "error");
      } finally {
        this.loading = false;
      }
    },
    onPhoneInput(val) {
      if (!val) {
        this.form.telefono = "";
        return;
      }
      const digits = String(val).replace(/\D/g, "").slice(0, 8);
      if (digits.length > 4) {
        this.form.telefono = `${digits.slice(0, 4)}-${digits.slice(4)}`;
      } else {
        this.form.telefono = digits;
      }
    },
    onLogoRejected() {
      Alert("Error", "El logo debe ser una imagen válida (JPEG, PNG, JPG, GIF, SVG, WEBP) y pesar menos de 2MB", "error");
    },
    openModal(row) {
      if (row && !this.authStore.hasPermission("sucursales.editar")) {
        Alert("Acceso denegado", "No tienes permiso para editar sucursales.", "warning");
        return;
      }
      if (!row && !this.authStore.hasPermission("sucursales.crear")) {
        Alert("Acceso denegado", "No tienes permiso para crear sucursales.", "warning");
        return;
      }
      this.logoFile = null;
      if (row) {
        this.editRow = row;
        let tel = row.telefono || "";
        const clean = String(tel).replace(/\D/g, "");
        if (clean.length === 8) {
          tel = `${clean.slice(0, 4)}-${clean.slice(4)}`;
        }
        this.form = {
          ...row,
          telefono: tel,
          activa: row.activa === 1 || row.activa === true,
        };
      } else {
        this.editRow = null;
        this.form = {
          nombre: "",
          telefono: "",
          correo: "",
          direccion: "",
          ciudad: "",
          estado: "",
          pais: "",
          activa: true,
        };
      }
      this.modalOpen = true;
    },
    async saveRow() {
      const formValid = await this.$refs.sucursalForm.validate();
      if (!formValid) return;

      if (this.logoFile) {
        const validTypes = ["image/jpeg", "image/png", "image/jpg", "image/gif", "image/svg+xml", "image/webp"];
        if (!validTypes.includes(this.logoFile.type)) {
          Alert("Error", "El logo debe ser una imagen válida (JPEG, PNG, JPG, GIF, SVG, WEBP)", "error");
          return;
        }
        if (this.logoFile.size > 2 * 1024 * 1024) {
          Alert("Error", "El logo no debe exceder 2MB de tamaño", "error");
          return;
        }
      }

      Alert("Guardando...", "Enviando datos de la sucursal...", "info");
      this.loading = true;
      try {
        const formData = new FormData();
        formData.append("nombre", this.form.nombre ? this.form.nombre.trim() : "");
        formData.append("telefono", this.form.telefono ? this.form.telefono.trim() : "");
        formData.append("correo", this.form.correo ? this.form.correo.trim() : "");
        formData.append("direccion", this.form.direccion ? this.form.direccion.trim() : "");
        formData.append("ciudad", this.form.ciudad ? this.form.ciudad.trim() : "");
        formData.append("estado", this.form.estado ? this.form.estado.trim() : "");
        formData.append("pais", this.form.pais ? this.form.pais.trim() : "");
        formData.append("activa", this.form.activa ? "1" : "0");

        if (this.logoFile) {
          formData.append("logo", this.logoFile);
        }

        if (this.editRow) {
          formData.append("_method", "PUT");
          await api.post(`/api/sucursales/${this.editRow.id}`, formData, {
            headers: { "Content-Type": "multipart/form-data" },
          });
          Alert("Éxito", "Sucursal actualizada correctamente", "success");
        } else {
          await api.post("/api/sucursales", formData, {
            headers: { "Content-Type": "multipart/form-data" },
          });
          Alert("Éxito", "Sucursal creada correctamente", "success");
        }
        this.modalOpen = false;
        this.fetchSucursales();

        // Disparar recarga parcial de sucursales en la sesión (Layout) reactivamente
        window.dispatchEvent(new CustomEvent("branch-updated"));
      } catch (error) {
        Alert(
          "Error",
          error.response?.data?.message || "No se pudo guardar la sucursal",
          "error"
        );
      } finally {
        this.loading = false;
      }
    },
    confirmDelete(row) {
      if (!this.authStore.hasPermission("sucursales.eliminar")) {
        Alert("Acceso denegado", "No tienes permiso para eliminar sucursales.", "warning");
        return;
      }
      this.rowToDelete = row.id;
      this.rowToDeleteName = row.nombre;
      this.confirmOpen = true;
    },
    async deleteRow() {
      if (!this.authStore.hasPermission("sucursales.eliminar")) {
        Alert("Acceso denegado", "No tienes permiso para eliminar sucursales.", "warning");
        return;
      }
      useLoadingStore().startLoading();
      try {
        const response = await api.delete(
          `/api/sucursales/${this.rowToDelete}`
        );
        Alert(
          "Éxito",
          response.data.message || "Sucursal eliminada correctamente",
          "success"
        );
        this.confirmOpen = false;
        this.rowToDelete = null;
        this.rowToDeleteName = "";
        this.fetchSucursales();
      } catch (error) {
        Alert(
          "Error",
          error.response?.data?.message || "No se pudo eliminar la sucursal",
          "error"
        );
        this.confirmOpen = false;
        this.rowToDelete = null;
        this.rowToDeleteName = "";
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

.border-top {
  border-top: 1px solid #e0e0e0;
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
