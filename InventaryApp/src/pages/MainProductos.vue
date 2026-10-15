<template>
  <q-page class="q-pa-md productos-page">
    <!-- Header Banner -->
    <q-card class="bg-gradient-brand text-white q-mb-md shadow-2 rounded-card overflow-hidden">
      <q-card-section class="q-py-md">
        <div class="row items-center justify-between no-wrap">
          <div class="row items-center">
            <q-avatar icon="shopping_bag" size="48px" class="q-mr-md bg-white-translucent text-white" />
            <div>
              <div class="text-h5 text-weight-bold">Catálogo de Productos</div>
              <div class="text-subtitle2 opacity-80 row items-center q-gutter-x-sm">
                <span>Administra tu inventario, precios, imágenes, marcas y modelos</span>
                <q-badge color="white" text-color="primary" class="text-weight-bold q-pa-xs">
                  <q-icon name="layers" size="14px" class="q-mr-xs" />
                  Método: {{ settingsStore.valuationMethodShort }}
                </q-badge>
              </div>
            </div>
          </div>
          <!-- Acciones Principales -->
          <q-btn v-if="authStore.hasPermission('productos.crear')" color="white" text-color="primary" label="Agregar Producto" icon="add" unelevated class="rounded-btn q-px-md text-weight-bold" @click="openModal(null)" />
        </div>
      </q-card-section>
    </q-card>

    <!-- Search / Filter Card -->
    <q-card flat bordered class="q-pa-md q-mb-md rounded-card bg-white shadow-1">
      <div class="row q-col-gutter-md items-center">
        <!-- Búsqueda -->
        <!-- Búsqueda -->
        <div class="col-12 col-md-4">
          <q-input v-model="search" label="Buscar por nombre, SKU o código..." outlined dense debounce="300" class="rounded-input" @update:model-value="fetchItems">
            <template v-slot:prepend><q-icon name="search" color="grey-6" /></template>
            <template v-slot:append v-if="search">
              <q-icon name="close" class="cursor-pointer" @click="search = ''; fetchItems();" />
            </template>
          </q-input>
        </div>

        <!-- Filtro por Categoría -->
        <div class="col-12 col-sm-6 col-md-4">
          <q-select
            v-model="selectedCategoria"
            :options="categoriasFilterOptions"
            label="Categoría"
            outlined
            dense
            emit-value
            map-options
            :display-value="selectedCategoria !== '' && selectedCategoria !== null ? undefined : 'Todas las Categorías'"
            :clearable="selectedCategoria !== '' && selectedCategoria !== null"
            clear-value=""
            @clear="selectedCategoria = ''; fetchItems()"
            class="rounded-input"
            @update:model-value="fetchItems"
          >
            <template v-slot:prepend><q-icon name="category" color="primary" /></template>
          </q-select>
        </div>

        <!-- Filtro por Marca -->
        <div class="col-12 col-sm-6 col-md-4">
          <q-select
            v-model="selectedMarca"
            :options="marcasFilterOptions"
            label="Marca"
            outlined
            dense
            emit-value
            map-options
            :display-value="selectedMarca !== '' && selectedMarca !== null ? undefined : 'Todas las Marcas'"
            :clearable="selectedMarca !== '' && selectedMarca !== null"
            clear-value=""
            @clear="selectedMarca = ''; fetchItems()"
            class="rounded-input"
            @update:model-value="fetchItems"
          >
            <template v-slot:prepend><q-icon name="style" color="primary" /></template>
          </q-select>
        </div>

        <!-- Segunda fila de filtros: Características -->
        <!-- Filtro por Estado -->
        <div class="col-12 col-sm-4 col-md-4">
          <q-select
            v-model="selectedStatus"
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
            :display-value="selectedStatus !== '' && selectedStatus !== null ? undefined : 'Todos los Estados'"
            :clearable="selectedStatus !== '' && selectedStatus !== null"
            clear-value=""
            @clear="selectedStatus = ''; fetchItems()"
            class="rounded-input"
            @update:model-value="fetchItems"
          >
            <template v-slot:prepend><q-icon name="toggle_on" color="primary" /></template>
          </q-select>
        </div>

        <!-- Filtro por Stock -->
        <div class="col-12 col-sm-4 col-md-4">
          <q-select
            v-model="selectedStock"
            :options="[
              { label: 'Todo el Stock', value: '' },
              { label: 'Con Stock (>0)', value: 'con_stock' },
              { label: 'Sin Stock (0)', value: 'sin_stock' },
            ]"
            label="Disponibilidad"
            outlined
            dense
            emit-value
            map-options
            :display-value="selectedStock !== '' && selectedStock !== null ? undefined : 'Todo el Stock'"
            :clearable="selectedStock !== '' && selectedStock !== null"
            clear-value=""
            @clear="selectedStock = ''; fetchItems()"
            class="rounded-input"
            @update:model-value="fetchItems"
          >
            <template v-slot:prepend><q-icon name="inventory_2" color="primary" /></template>
          </q-select>
        </div>

        <!-- Filtro por Tipo de Producto -->
        <div class="col-12 col-sm-4 col-md-4">
          <q-select
            v-model="selectedTipo"
            :options="[
              { label: 'Todos los Tipos', value: '' },
              { label: 'No Perecedero', value: 'no perecedero' },
              { label: 'Perecedero', value: 'perecedero' },
            ]"
            label="Tipo de Producto"
            outlined
            dense
            emit-value
            map-options
            :display-value="selectedTipo !== '' && selectedTipo !== null ? undefined : 'Todos los Tipos'"
            :clearable="selectedTipo !== '' && selectedTipo !== null"
            clear-value=""
            @clear="selectedTipo = ''; fetchItems()"
            class="rounded-input"
            @update:model-value="fetchItems"
          >
            <template v-slot:prepend><q-icon name="schedule" color="primary" /></template>
          </q-select>
        </div>

        <!-- Tercera fila de filtros: Precios y Limpiar -->
        <div class="col-12 col-sm-4 col-md-4">
          <q-input
            v-model="precioMin"
            type="text"
            inputmode="decimal"
            label="Precio Mín ($)"
            placeholder="0.00"
            outlined
            dense
            clearable
            class="rounded-input"
            prefix="$"
            @blur="formatAmountOnBlur('precioMin')"
            @keyup.enter="formatAmountOnBlur('precioMin')"
            @update:model-value="fetchItems"
          />
        </div>

        <div class="col-12 col-sm-4 col-md-4">
          <q-input
            v-model="precioMax"
            type="text"
            inputmode="decimal"
            label="Precio Máx ($)"
            placeholder="0.00"
            outlined
            dense
            clearable
            class="rounded-input"
            prefix="$"
            @blur="formatAmountOnBlur('precioMax')"
            @keyup.enter="formatAmountOnBlur('precioMax')"
            @update:model-value="fetchItems"
          />
        </div>

        <div class="col-12 col-sm-4 col-md-4 row justify-end">
          <q-btn flat color="primary" icon="filter_alt_off" label="Limpiar" @click="clearFilters" class="rounded-btn full-width" />
        </div>
      </div>
    </q-card>

    <!-- DATA TABLE -->
    <q-card flat bordered class="rounded-card overflow-hidden bg-white">
      <q-table
        :rows="rows"
        :columns="columns"
        row-key="id"
        :pagination="pagination"
        @update:pagination="(val) => (pagination = val)"
        :loading="loading"
        flat
        bordered
        @request="onRequest"
        no-data-label="No se encontraron productos"
      >
        <!-- FIRST COLUMN: THUMBNAIL IMAGE -->
        <template v-slot:body-cell-imagen="props">
          <q-td align="center">
            <q-avatar
              size="45px"
              square
              class="bg-grey-3 rounded-borders shadow-1"
            >
              <q-img
                v-if="props.row.imagenes && getFirstImage(props.row.imagenes)"
                :src="
                  'http://localhost:8000/storage/' +
                  getFirstImage(props.row.imagenes)
                "
                loading="lazy"
                spinner-color="primary"
                style="height: 45px; width: 45px"
              >
                <template v-slot:error>
                  <q-icon name="image" color="grey-6" size="20px" />
                </template>
              </q-img>
              <q-icon v-else name="image" color="grey-6" size="24px" />
            </q-avatar>
          </q-td>
        </template>

        <!-- STATUS BADGE -->
        <template v-slot:body-cell-status="props">
          <q-td align="center">
            <q-badge
              :color="isStatusActive(props.row.status) ? 'green' : 'red'"
              :label="isStatusActive(props.row.status) ? 'Activo' : 'Inactivo'"
              class="text-weight-bold"
            />
          </q-td>
        </template>

        <!-- AVAILABILITY STOCK BADGE -->
        <template v-slot:body-cell-disponibilidad="props">
          <q-td align="center">
            <q-badge
              :color="
                props.row.stock == 0
                  ? 'red'
                  : props.row.stock > 15
                  ? 'green'
                  : 'orange'
              "
              :label="
                props.row.stock == 0
                  ? 'Agotado'
                  : props.row.stock > 15
                  ? 'Disponible (' + formatQuantity(props.row.stock) + ')'
                  : 'Stock Bajo (' + formatQuantity(props.row.stock) + ')'
              "
            />
          </q-td>
        </template>

        <!-- OPTIONS BUTTON -->
        <template v-slot:body-cell-options="props">
          <q-td align="right">
            <q-btn-dropdown
              v-if="authStore.hasPermission('productos.editar') || authStore.hasPermission('productos.eliminar')"
              flat
              color="dark"
              icon="more_vert"
              size="md"
            >
              <q-list>
                <q-item
                  clickable
                  v-close-popup
                  v-if="authStore.hasPermission('productos.editar')"
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
                  v-if="authStore.hasPermission('productos.eliminar')"
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

    <!-- FORM DIALOG (CREATE/EDIT) -->
    <q-dialog v-model="modalOpen" persistent max-width="850px" width="95%" class="modal-producto">
      <q-card style="border-radius: 12px; overflow: hidden">
        <q-form ref="productForm" @submit.prevent="saveRow">
          <q-card-section class="bg-primary text-white q-py-md">
            <div class="text-h6 text-weight-bold row items-center">
              <q-icon name="mode_edit" class="q-mr-xs" />
              {{ editRow ? "Editar Producto" : "Registrar Producto" }}
            </div>
            <div class="text-caption opacity-80">
              Rellena el formulario técnico comercial del artículo
            </div>
          </q-card-section>

          <q-card-section
            class="q-pa-md scroll-container"
            style="max-height: 550px; overflow-y: auto"
          >
            <div class="row q-col-gutter-md">
              <!-- LEFT SECTION -->
              <div class="col-12 col-md-6 q-gutter-y-sm">
                <q-input
                  v-model="form.sku"
                  label="SKU / Código de Fábrica"
                  name="sku"
                  filled
                  @keypress="toUppercase"
                  @keyup="toUppercase"
                  :rules="[(val) => !!val || 'El SKU es obligatorio']"
                />
                <q-input
                  v-model="form.codigo_interno"
                  label="Código de Barras / Interno"
                  filled
                />
                <q-input
                  v-model="form.nombre"
                  label="Nombre del Producto"
                  name="nombre"
                  filled
                  :rules="[(val) => !!val || 'El nombre es obligatorio']"
                />

                <q-select
                  v-model="form.categoria_id"
                  :options="categoriasOptions"
                  label="Categoría"
                  filled
                  emit-value
                  map-options
                  :rules="[(val) => !!val || 'La categoría es obligatoria']"
                  @update:model-value="onCategoryChange"
                />

                <q-select
                  v-model="form.subcategoria_id"
                  :options="filteredSubcategoriasOptions"
                  label="Subcategoría"
                  filled
                  emit-value
                  map-options
                  :disable="!form.categoria_id"
                />

                <q-select
                  v-model="form.unidad_id"
                  :options="unidadesOptions"
                  label="Unidad de Medida"
                  filled
                  emit-value
                  map-options
                />
              </div>

              <!-- RIGHT SECTION -->
              <div class="col-12 col-md-6 q-gutter-y-sm">
                <div class="row q-col-gutter-sm">
                  <div class="col-6">
                    <q-input
                      v-model.number="form.stock"
                      type="number"
                      label="Stock Inicial"
                      filled
                      :disable="!!editRow"
                      :rules="[(val) => val >= 0 || 'Mínimo 0']"
                    />
                  </div>
                  <div class="col-6">
                    <q-select
                      v-model="form.status"
                      :options="estadoOptions"
                      label="Estado"
                      filled
                      emit-value
                      map-options
                      class="rounded-input"
                    />
                  </div>
                </div>

                <div class="row q-col-gutter-sm">
                  <div class="col-6">
                    <q-input
                      v-model.number="form.costo_promedio"
                      type="number"
                      step="0.01"
                      :label="'Costo Unitario ($) [' + settingsStore.valuationMethodShort + ']'"
                      :hint="'Valuación: ' + settingsStore.valuationMethodLabel"
                      filled
                      :rules="[(val) => val >= 0 || 'Mínimo 0']"
                    />
                  </div>
                  <div class="col-6">
                    <q-input
                      v-model.number="form.precio_venta"
                      type="number"
                      step="0.01"
                      label="Precio Venta ($)"
                      filled
                      :rules="[(val) => val >= 0 || 'Mínimo 0']"
                    />
                  </div>
                </div>

                <div
                  class="row items-center justify-between bg-blue-1 q-pa-sm rounded-borders q-mb-xs"
                >
                  <div class="text-subtitle2 text-blue-9">
                    Aplica Impuesto (IVA)
                  </div>
                  <q-toggle v-model="form.aplica_iva" color="primary" />
                </div>

                <q-select
                  v-model="form.marca_id"
                  :options="marcasOptions"
                  label="Marca"
                  filled
                  emit-value
                  map-options
                  @update:model-value="onMarcaChange"
                />

                <q-select
                  v-model="form.modelo_id"
                  :options="filteredModelosOptions"
                  label="Modelo"
                  filled
                  emit-value
                  map-options
                  :disable="!form.marca_id"
                />

                <q-select
                  v-model="form.tipo"
                  :options="[
                    { label: 'No Perecedero', value: 'no perecedero' },
                    { label: 'Perecedero', value: 'perecedero' },
                  ]"
                  label="Tipo de Producto"
                  filled
                  emit-value
                  map-options
                />

                <q-input
                  v-if="form.tipo === 'perecedero'"
                  v-model="form.fecha_vencimiento"
                  type="date"
                  label="Fecha de Vencimiento"
                  filled
                />
              </div>

              <!-- GALERÍA / SUBIR IMÁGENES (SOLO EDITANDO PRODUCTO EXISTENTE) -->
              <div class="col-12 border-top q-pt-md">
                <div
                  class="text-subtitle1 text-weight-bold text-grey-8 q-mb-sm"
                >
                  Imágenes del Producto
                </div>

                <div>
                  <!-- Grid de imágenes existentes (solo cuando se está editando) -->
                  <div
                    v-if="editRow && currentImages.length > 0"
                    class="row q-col-gutter-sm q-mb-md"
                  >
                    <div
                      v-for="(img, idx) in currentImages"
                      :key="idx"
                      class="col-4 col-sm-3 relative-position"
                    >
                      <q-card
                        bordered
                        flat
                        class="rounded-borders overflow-hidden"
                      >
                        <q-img
                          :src="'http://localhost:8000/storage/' + img"
                          ratio="1"
                        />
                        <q-card-actions
                          align="center"
                          class="bg-grey-2 q-py-xs"
                        >
                          <q-btn
                            type="button"
                            flat
                            round
                            dense
                            color="negative"
                            icon="delete"
                            size="sm"
                            @click="deleteProductImage(img)"
                          >
                            <q-tooltip>Eliminar Imagen</q-tooltip>
                          </q-btn>
                        </q-card-actions>
                      </q-card>
                    </div>
                  </div>

                  <!-- Input para subir nuevas imágenes -->
                  <q-file
                    v-model="newImageFile"
                    label="Selecciona una imagen (.jpg, .png, .webp)"
                    filled
                    accept="image/*"
                    class="rounded-input"
                  >
                    <template v-slot:append>
                      <q-btn
                        v-if="editRow"
                        type="button"
                        round
                        dense
                        flat
                        icon="cloud_upload"
                        color="primary"
                        :disable="!newImageFile"
                        @click.stop="uploadProductImage"
                      >
                        <q-tooltip>Subir imagen ahora</q-tooltip>
                      </q-btn>
                    </template>
                  </q-file>
                  <div v-if="!editRow && newImageFile" class="text-caption text-primary q-mt-xs">
                    <q-icon name="info" size="xs" /> La imagen seleccionada se subirá automáticamente al guardar el producto.
                  </div>
                </div>
              </div>
            </div>
          </q-card-section>

          <q-card-actions align="right" class="q-pa-md bg-grey-2">
            <q-btn flat label="Cancelar" color="grey-7" v-close-popup />
            <q-btn
              type="submit"
              label="Guardar Producto"
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
      <q-card>
        <q-card-section class="row items-center q-pb-none">
          <q-avatar icon="warning" color="warning" text-color="white" />
          <span class="q-ml-sm text-h6 text-weight-bold"
            >Eliminar Producto</span
          >
        </q-card-section>
        <q-card-section class="q-pt-md">
          ¿Estás seguro de que deseas eliminar permanentemente el producto
          <strong>{{ rowToDeleteName }}</strong
          >? Esta acción no se puede deshacer.
        </q-card-section>
        <q-card-actions align="right">
          <q-btn flat label="Cancelar" color="grey-7" v-close-popup />
          <q-btn
            label="Eliminar"
            color="negative"
            unelevated
            class="rounded-btn"
            @click="deleteRow"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script>
import { useAuthStore } from "src/store/auth-store";
import { useSettingsStore } from "src/store/settings-store";
import { api } from "boot/axios";
import { useLoadingStore } from "src/store/loading-store";
import Alert from "src/helpers/Alert";

export default {
  data() {
    return {
      search: "",
      rows: [],
      loading: false,
      pagination: {
        page: 1,
        rowsPerPage: 15,
        rowsNumber: 0,
      },
      form: {
        sku: "",
        codigo_interno: "",
        nombre: "",
        categoria_id: null,
        subcategoria_id: null,
        unidad_id: null,
        stock: 0,
        costo_promedio: 0,
        precio_venta: 0,
        aplica_iva: true,
        marca_id: null,
        modelo_id: null,
        tipo: "no perecedero",
        fecha_vencimiento: "",
        status: true,
        imagenes: "[]",
      },
      estadoOptions: [
        { label: "Activo", value: true },
        { label: "Inactivo", value: false },
      ],
      categoriasOptions: [],
      allSubcategorias: [],
      filteredSubcategoriasOptions: [],
      marcasOptions: [],
      allModelos: [],
      filteredModelosOptions: [],
      unidadesOptions: [],
      modalOpen: false,
      confirmOpen: false,
      editRow: null,
      rowToDelete: null,
      rowToDeleteName: "",
      newImageFile: null,
      selectedCategoria: "",
      selectedMarca: "",
      selectedStatus: "",
      selectedStock: "",
      selectedTipo: "",
      precioMin: "",
      precioMax: "",
    };
  },
  computed: {
    categoriasFilterOptions() {
      return [
        { label: "Todas las Categorías", value: "" },
        ...this.categoriasOptions,
      ];
    },
    marcasFilterOptions() {
      return [
        { label: "Todas las Marcas", value: "" },
        ...this.marcasOptions,
      ];
    },
    authStore() {
      return useAuthStore();
    },
    settingsStore() {
      return useSettingsStore();
    },
    columns() {
      return [
        { name: "imagen", label: "Imagen", align: "center" },
        {
          name: "status",
          label: "Estado",
          align: "center",
          field: "status",
          sortable: true,
        },
        { name: "sku", label: "SKU", align: "left", field: "sku" },
        {
          name: "nombre",
          label: "Nombre del Producto",
          align: "left",
          field: "nombre",
        },
        {
          name: "disponibilidad",
          label: "Stock",
          align: "center",
          field: "stock",
        },
        {
          name: "costo_promedio",
          label: this.settingsStore.costColumnLabel,
          align: "right",
          field: (row) =>
            row.costo_actual !== undefined && row.costo_actual !== null
              ? row.costo_actual
              : row.costo_promedio,
          format: (val) => this.formatCurrency(val),
        },
        {
          name: "precio_venta",
          label: "P. Venta",
          align: "right",
          field: "precio_venta",
          format: (val) => this.formatCurrency(val),
        },
        { name: "options", label: "Acciones", align: "right" },
      ];
    },
    currentImages() {
      if (!this.form.imagenes) return [];
      try {
        const arr = JSON.parse(this.form.imagenes);
        return Array.isArray(arr) ? arr : [];
      } catch (e) {
        return [];
      }
    },
  },
  created() {
    this.fetchCatalogos();
    this.fetchItems();
  },
  methods: {
    formatCurrency(val) {
      if (val === null || val === undefined || val === "") return "$0.00";
      return `$${String(Number(val).toFixed(2)).replace(/\B(?=(\d{3})+(?!\d))/g, ",")}`;
    },
    formatQuantity(val) {
      if (val === null || val === undefined || val === "") return "0";
      return String(Math.round(Number(val) || 0)).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    },
    isStatusActive(val) {
      return (
        val === true ||
        val === 1 ||
        val === "1" ||
        val === "true" ||
        val === "Activo"
      );
    },
    getFirstImage(imgsJson) {
      try {
        const arr = JSON.parse(imgsJson);
        return Array.isArray(arr) && arr.length > 0 ? arr[0] : null;
      } catch (e) {
        return null;
      }
    },
    toUppercase(event) {
      const name = event.target.name;
      if (name) {
        this.form[name] = event.target.value.toUpperCase();
      }
    },
    async fetchCatalogos() {
      try {
        const response = await api.get("/api/productos/catalogos");
        const catalogos = response.data.catalogos;

        this.categoriasOptions = catalogos.categorias.map((cat) => ({
          label: cat.name,
          value: cat.id,
        }));

        this.allSubcategorias = catalogos.subcategorias;
        this.marcasOptions = catalogos.marcas.map((m) => ({
          label: m.nombre,
          value: m.id,
        }));

        this.allModelos = catalogos.modelos;
        this.unidadesOptions = catalogos.unidades.map((u) => ({
          label: `${u.nombre} (${u.abreviatura})`,
          value: u.id,
        }));
      } catch (err) {
        console.error("No se pudieron cargar los catálogos", err);
      }
    },
    onCategoryChange(catId) {
      this.form.subcategoria_id = null;
      if (catId) {
        this.filteredSubcategoriasOptions = this.allSubcategorias
          .filter((sc) => sc.categoria_id === catId)
          .map((sc) => ({ label: sc.nombre, value: sc.id }));
      } else {
        this.filteredSubcategoriasOptions = [];
      }
    },
    onMarcaChange(marcaId) {
      this.form.modelo_id = null;
      if (marcaId) {
        this.filteredModelosOptions = this.allModelos
          .filter((m) => m.marca_id === marcaId)
          .map((m) => ({ label: m.nombre, value: m.id }));
      } else {
        this.filteredModelosOptions = [];
      }
    },
    formatAmountOnBlur(field) {
      if (this[field] !== null && this[field] !== undefined && String(this[field]).trim() !== "") {
        const clean = String(this[field]).replace(/[^0-9.]/g, "");
        const num = parseFloat(clean);
        if (!isNaN(num) && num >= 0) {
          this[field] = num.toFixed(2);
        } else {
          this[field] = "";
        }
        this.fetchItems();
      }
    },
    clearFilters() {
      this.search = "";
      this.selectedCategoria = "";
      this.selectedMarca = "";
      this.selectedStatus = "";
      this.selectedStock = "";
      this.selectedTipo = "";
      this.precioMin = "";
      this.precioMax = "";
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
      this.loading = true;
      try {
        const response = await api.get("/api/productos", {
          params: {
            page: page,
            limit: rowsPerPage,
            search: this.search,
            sortBy: sortBy,
            descending: descending,
            categoria_id: this.selectedCategoria || undefined,
            marca_id: this.selectedMarca || undefined,
            status: this.selectedStatus !== "" ? this.selectedStatus : undefined,
            stock_status: this.selectedStock || undefined,
            tipo: this.selectedTipo || undefined,
            precio_min: this.precioMin !== null && this.precioMin !== "" ? this.precioMin : undefined,
            precio_max: this.precioMax !== null && this.precioMax !== "" ? this.precioMax : undefined,
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
        Alert("Error", "Error al obtener productos", "error");
      } finally {
        this.loading = false;
      }
    },
    openModal(row) {
      if (row && !this.authStore.hasPermission("productos.editar")) {
        Alert("Acceso denegado", "No tienes permiso para editar productos.", "warning");
        return;
      }
      if (!row && !this.authStore.hasPermission("productos.crear")) {
        Alert("Acceso denegado", "No tienes permiso para agregar productos.", "warning");
        return;
      }
      this.newImageFile = null;
      if (row) {
        this.editRow = row;
        const isActivo = this.isStatusActive(row.status);
        this.form = {
          ...row,
          status: isActivo,
          aplica_iva:
            row.aplica_iva === 1 ||
            row.aplica_iva === true ||
            row.aplica_iva === "1",
        };

        // Cargar opciones filtradas de subcategoría y modelo
        if (row.categoria_id) {
          this.filteredSubcategoriasOptions = this.allSubcategorias
            .filter((sc) => sc.categoria_id === row.categoria_id)
            .map((sc) => ({ label: sc.nombre, value: sc.id }));
        }
        if (row.marca_id) {
          this.filteredModelosOptions = this.allModelos
            .filter((m) => m.marca_id === row.marca_id)
            .map((m) => ({ label: m.nombre, value: m.id }));
        }
      } else {
        this.editRow = null;
        this.form = {
          sku: "",
          codigo_interno: "",
          nombre: "",
          categoria_id: null,
          subcategoria_id: null,
          unidad_id: null,
          stock: 0,
          costo_promedio: 0,
          precio_venta: 0,
          aplica_iva: true,
          marca_id: null,
          modelo_id: null,
          tipo: "no perecedero",
          fecha_vencimiento: "",
          status: true,
          imagenes: "[]",
        };
        this.filteredSubcategoriasOptions = [];
        this.filteredModelosOptions = [];
      }
      this.modalOpen = true;
    },
    async saveRow() {
      const formValid = await this.$refs.productForm.validate();
      if (!formValid) return;

      Alert("Guardando...", "Enviando datos del producto...", "info");
      this.loading = true;
      try {
        const isActivo = this.isStatusActive(this.form.status);
        const payload = {
          ...this.form,
          status: isActivo,
          aplica_iva: !!this.form.aplica_iva,
        };

        let savedProductId = null;
        if (this.editRow) {
          await api.put(`/api/productos/${this.editRow.id}`, payload);
          savedProductId = this.editRow.id;
          Alert("Éxito", "Producto actualizado correctamente", "success");
        } else {
          const res = await api.post("/api/productos", payload);
          savedProductId = res.data?.data?.id;
          Alert("Éxito", "Producto registrado correctamente", "success");
        }

        // Si se seleccionó una imagen pendiente, subirla automáticamente
        if (this.newImageFile && savedProductId) {
          try {
            const imgFormData = new FormData();
            imgFormData.append("image", this.newImageFile);
            await api.post(`/api/productos/${savedProductId}/upload-image`, imgFormData);
            this.newImageFile = null;
          } catch (imgErr) {
            console.error("Error al subir imagen tras guardar producto:", imgErr);
          }
        }

        this.modalOpen = false;
        this.fetchItems();
      } catch (error) {
        Alert(
          "Error",
          error.response?.data?.message || "No se pudo guardar el producto",
          "error"
        );
      } finally {
        this.loading = false;
      }
    },
    confirmDelete(row) {
      if (!this.authStore.hasPermission("productos.eliminar")) {
        Alert("Acceso denegado", "No tienes permiso para eliminar productos.", "warning");
        return;
      }
      this.rowToDelete = row.id;
      this.rowToDeleteName = row.nombre;
      this.confirmOpen = true;
    },
    async deleteRow() {
      if (!this.authStore.hasPermission("productos.eliminar")) {
        Alert("Acceso denegado", "No tienes permiso para eliminar productos.", "warning");
        return;
      }
      useLoadingStore().startLoading();
      try {
        await api.delete(`/api/productos/${this.rowToDelete}`);
        this.confirmOpen = false;
        this.rowToDelete = null;
        this.rowToDeleteName = "";
        Alert("Éxito", "Producto eliminado correctamente", "success");
        this.fetchItems();
      } catch (error) {
        Alert(
          "Error",
          error.response?.data?.message || "No se pudo eliminar el producto",
          "error"
        );
      } finally {
        useLoadingStore().stopLoading();
      }
    },
    async uploadProductImage() {
      if (!this.newImageFile || !this.editRow) return;
      const formData = new FormData();
      formData.append("image", this.newImageFile);

      useLoadingStore().startLoading();
      try {
        const response = await api.post(
          `/api/productos/${this.editRow.id}/upload-image`,
          formData
        );
        if (response.data.success) {
          Alert("Éxito", "Imagen subida correctamente", "success");
          this.newImageFile = null;
          const updatedImgs = response.data.imagenes;
          this.form.imagenes = JSON.stringify(updatedImgs);
          this.editRow.imagenes = JSON.stringify(updatedImgs);
          this.fetchItems();
        }
      } catch (err) {
        Alert(
          "Error",
          err.response?.data?.message || "No se pudo subir la imagen",
          "error"
        );
      } finally {
        useLoadingStore().stopLoading();
      }
    },
    async deleteProductImage(path) {
      if (!this.editRow) return;
      useLoadingStore().startLoading();
      try {
        const response = await api.post(
          `/api/productos/${this.editRow.id}/delete-image`,
          {
            image_path: path,
          }
        );
        if (response.data.success) {
          Alert("Éxito", "Imagen eliminada correctamente", "success");
          const updatedImgs = response.data.imagenes;
          this.form.imagenes = JSON.stringify(updatedImgs);
          this.editRow.imagenes = JSON.stringify(updatedImgs);
          this.fetchItems();
        }
      } catch (err) {
        Alert(
          "Error",
          err.response?.data?.message || "No se pudo eliminar la imagen",
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
.rounded-btn {
  border-radius: 8px;
  text-transform: none;
}
.rounded-borders {
  border-radius: 12px;
}
.border-top {
  border-top: 1px solid #e0e0e0;
}
</style>

<style>
.productos-page .q-field__bottom,
.modal-producto .q-field__bottom {
  width: 260px !important;
  max-width: 260px !important;
}

.productos-page .q-field__messages,
.modal-producto .q-field__messages {
  width: 100% !important;
  min-width: 0 !important;
  max-width: 100% !important;
}

.productos-page .q-field__messages > div,
.modal-producto .q-field__messages > div {
  width: 100% !important;
  max-width: 100% !important;
  white-space: nowrap;
  overflow-x: auto;
  overflow-y: hidden;
  box-sizing: border-box;
  scrollbar-width: thin;
}

.modal-producto .q-card__section {
  overflow-x: hidden;
}
</style>
