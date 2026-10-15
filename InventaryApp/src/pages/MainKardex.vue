<template>
  <q-page class="q-pa-md">
    <!-- Header Banner -->
    <q-card class="bg-gradient-brand text-white q-mb-md shadow-2 rounded-card overflow-hidden">
      <q-card-section class="q-py-md">
        <div class="row items-center justify-between no-wrap">
          <div class="row items-center">
            <q-avatar icon="history" size="48px" class="q-mr-md bg-white-translucent text-white" />
            <div>
              <div class="text-h5 text-weight-bold">Kardex de Inventario</div>
              <div class="text-subtitle2 opacity-80 row items-center q-gutter-x-sm">
                <span>Registra y visualiza los movimientos de entrada y salida de productos</span>
                <q-badge color="white" text-color="primary" class="text-weight-bold q-pa-xs">
                  <q-icon name="layers" size="14px" class="q-mr-xs" />
                  Método: {{ settingsStore.valuationMethodShort }}
                </q-badge>
              </div>
            </div>
          </div>
          <!-- Acciones Principales -->
          <q-btn v-if="authStore.hasPermission('inventario.kardex')" color="white" text-color="primary" label="Agregar Movimiento" icon="add" unelevated class="rounded-btn q-px-md text-weight-bold" @click="openModal()" />
        </div>
      </q-card-section>
    </q-card>

    <!-- Search / Filter Card -->
    <q-card flat bordered class="q-pa-md q-mb-md rounded-card bg-white shadow-1">
      <div class="row q-col-gutter-md items-center">
        <!-- Búsqueda por texto (SKU, Producto, Categoría) -->
        <!-- Búsqueda -->
        <div class="col-12 col-md-5">
          <q-input v-model="search" label="Buscar por SKU, producto o categoría..." outlined dense debounce="300" class="rounded-input">
            <template v-slot:prepend><q-icon name="search" color="grey-6" /></template>
            <template v-slot:append v-if="search">
              <q-icon name="close" class="cursor-pointer" @click="search = ''" />
            </template>
          </q-input>
        </div>

        <!-- Filtro Producto -->
        <div class="col-12 col-md-7">
          <q-select
            v-model="selectedProducto"
            :options="filterProductosList"
            :loading="loadingFilterProducts"
            label="Producto específico (escribe para buscar...)"
            outlined
            dense
            use-input
            emit-value
            map-options
            input-debounce="300"
            @filter="filterFnFilterProduct"
            :display-value="selectedProductoLabel"
            :clearable="selectedProducto !== '' && selectedProducto !== null"
            clear-value=""
            @clear="selectedProducto = ''; onDateFilterChange()"
            class="rounded-input"
            @update:model-value="onDateFilterChange"
          >
            <template v-slot:prepend><q-icon name="inventory_2" color="primary" /></template>
            <template v-slot:no-option>
              <q-item>
                <q-item-section class="text-grey">
                  {{ loadingFilterProducts ? 'Buscando productos...' : 'No se encontraron productos' }}
                </q-item-section>
              </q-item>
            </template>
          </q-select>
        </div>

        <!-- Filtro Tipo de Movimiento -->
        <div class="col-12 col-sm-6 col-md-6">
          <q-select
            v-model="tipoFilter"
            :options="[
              { label: 'Todos los Tipos', value: '' },
              { label: 'Entradas', value: 'entrada' },
              { label: 'Salidas', value: 'salida' },
            ]"
            label="Tipo de Movimiento"
            outlined
            dense
            emit-value
            map-options
            :display-value="tipoFilter !== '' && tipoFilter !== null ? undefined : 'Todos los Tipos'"
            :clearable="tipoFilter !== '' && tipoFilter !== null"
            clear-value=""
            @clear="tipoFilter = ''; onDateFilterChange()"
            class="rounded-input"
            @update:model-value="onDateFilterChange"
          >
            <template v-slot:prepend><q-icon name="swap_vert" color="primary" /></template>
          </q-select>
        </div>

        <!-- Filtro Razón -->
        <div class="col-12 col-sm-6 col-md-6">
          <q-select
            v-model="razonFilter"
            :options="filterRazonesList"
            :loading="loadingFilterRazones"
            label="Razón del movimiento (escribe para buscar...)"
            outlined
            dense
            use-input
            emit-value
            map-options
            input-debounce="300"
            @filter="filterFnFilterRazon"
            :display-value="razonFilter !== '' && razonFilter !== null ? razonFilter : 'Todas las razones'"
            :clearable="razonFilter !== '' && razonFilter !== null"
            clear-value=""
            @clear="razonFilter = ''; onDateFilterChange()"
            class="rounded-input"
            @update:model-value="onDateFilterChange"
          >
            <template v-slot:prepend><q-icon name="help_outline" color="primary" /></template>
            <template v-slot:no-option>
              <q-item>
                <q-item-section class="text-grey">
                  {{ loadingFilterRazones ? 'Buscando razones...' : 'No se encontraron razones' }}
                </q-item-section>
              </q-item>
            </template>
          </q-select>
        </div>

        <!-- Filtro Rango de Fechas: Desde -->
        <div class="col-12 col-sm-4 col-md-4">
          <q-input
            v-model="startDate"
            type="date"
            label="Fecha Desde"
            outlined
            dense
            stack-label
            class="rounded-input"
            @update:model-value="onDateFilterChange"
          >
            <template v-slot:prepend><q-icon name="event" color="primary" /></template>
            <template v-slot:append v-if="startDate">
              <q-icon name="close" class="cursor-pointer" @click="startDate = ''; onDateFilterChange()" />
            </template>
          </q-input>
        </div>

        <!-- Filtro Rango de Fechas: Hasta -->
        <div class="col-12 col-sm-4 col-md-4">
          <q-input
            v-model="endDate"
            type="date"
            label="Fecha Hasta"
            outlined
            dense
            stack-label
            class="rounded-input"
            @update:model-value="onDateFilterChange"
          >
            <template v-slot:prepend><q-icon name="event" color="primary" /></template>
            <template v-slot:append v-if="endDate">
              <q-icon name="close" class="cursor-pointer" @click="endDate = ''; onDateFilterChange()" />
            </template>
          </q-input>
        </div>

        <!-- Botón Limpiar Filtros -->
        <div class="col-12 col-sm-4 col-md-4 row justify-end">
          <q-btn flat color="primary" icon="filter_alt_off" label="Limpiar" @click="clearFilters" class="rounded-btn full-width" />
        </div>
      </div>
    </q-card>

    <!-- Table Card -->
    <q-card flat bordered class="rounded-card overflow-hidden bg-white">
      <q-table :rows="rows" :columns="columns" row-key="id" v-model:pagination="pagination" @request="onRequest" :filter="search" :loading="loading" flat class="no-shadow" binary-state-sort no-data-label="No se encontraron registros en el Kardex">
        <template v-slot:body-cell-fecha="props">
          <q-td :props="props">
            <div class="row items-center no-wrap justify-center q-gutter-x-xs">
              <q-icon name="schedule" size="14px" color="grey-6" />
              <span class="text-weight-medium">{{ formatDateTime(props.row.fecha) }}</span>
            </div>
          </q-td>
        </template>
        <template v-slot:body-cell-cantidad_movimiento="props">
          <q-td :props="props">
            <q-badge :color="props.row.tipo === 'salida' ? 'negative' : 'positive'" class="text-weight-bold">
              {{ props.row.tipo === 'salida' ? '-' : '+' }}{{ formatQuantity(Math.abs(props.row.cantidad_movimiento)) }}
            </q-badge>
          </q-td>
        </template>
      </q-table>
    </q-card>

      <!-- Modal para Agregar/Editar -->
      <q-dialog v-model="modalOpen">
        <q-card style="max-width: 800px; width: 90%">
          <q-card-section>
            <div class="text-h6">
              {{ editRow ? "Editar Registro" : "Agregar Registro" }}
            </div>
          </q-card-section>

          <q-card-section>
            <q-select
              v-model="form.producto_id"
              label="Buscar Producto por Nombre, SKU o Código..."
              :options="filteredProductosOptions"
              :loading="loadingProducts"
              use-input
              emit-value
              map-options
              input-debounce="300"
              @filter="filterFn"
              filled
              :rules="[(val) => !!val || 'Este campo es requerido']"
            >
              <template v-slot:no-option>
                <q-item>
                  <q-item-section class="text-grey">
                    {{ loadingProducts ? 'Buscando productos...' : 'No se encontraron productos' }}
                  </q-item-section>
                </q-item>
              </template>
            </q-select>

            <q-input
              v-model.number="form.stock"
              type="number"
              label="Cantidad / Stock"
              filled
              min="1"
              step="1"
              :rules="[val => !!val || 'Este campo es requerido', val => val > 0 || 'Debe ser mayor que 0']"
            />

            <q-select
              v-model="form.tipo"
              :options="tipoOptions"
              label="Tipo"
              filled
              :rules="[(val) => !!val || 'Este campo es requerido']"
            />

            <q-input
              v-model.number="form.costo_unitario"
              type="number"
              step="0.01"
              min="0"
              label="Costo Unitario"
              v-if="form.tipo && form.tipo.label === 'Entrada'"
              filled
              :rules="[val => val !== null && val !== undefined && val !== '' || 'Este campo es requerido', val => val >= 0 || 'Mínimo 0']"
            />

            <q-input
              v-model="form.razon"
              label="Razón del movimiento"
              filled
              maxlength="300"
              counter
              :rules="[
                (val) => !!val && val.trim().length > 0 || 'Este campo es requerido',
                (val) => !val || val.length <= 300 || 'Máximo 300 caracteres'
              ]"
              @update:model-value="formatRazonInput"
            />
          </q-card-section>

          <q-card-actions align="right">
            <q-btn flat label="Cancelar" color="negative" v-close-popup />
            <q-btn flat label="Guardar" color="primary" @click="saveRow" />
          </q-card-actions>
        </q-card>
      </q-dialog>
    </q-page>
</template>

<script>
import { useAuthStore } from "src/store/auth-store";
import { useSettingsStore } from "src/store/settings-store";
import { useLoadingStore } from "src/store/loading-store";
import { api } from "boot/axios";
import Alert from "src/helpers/Alert";

export default {
  computed: {
    authStore() {
      return useAuthStore();
    },
    settingsStore() {
      return useSettingsStore();
    },
    selectedProductoLabel() {
      if (!this.selectedProducto) return "Todos los productos";
      const p = this.productsCache[this.selectedProducto];
      if (p) return `${p.sku} - ${p.nombre}`;
      const found = this.filterProductsSearchOptions.find((o) => o.value === this.selectedProducto);
      return found ? found.label : "Producto seleccionado";
    },
    filterProductosList() {
      return [
        { label: "Todos los productos", value: "" },
        ...this.filterProductsSearchOptions,
      ];
    },
    filterRazonesList() {
      return [
        { label: "Todas las razones", value: "" },
        ...this.filterRazonesSearchOptions,
      ];
    },
    columns() {
      return [
        {
          name: "fecha",
          label: "Fecha y Hora",
          field: "fecha",
          align: "center",
          sortable: true,
          format: (val) => this.formatDateTime(val),
        },
        {
          name: "producto_sku",
          label: "SKU",
          field: "producto_sku",
          align: "left",
        },
        {
          name: "producto_name",
          label: "Producto",
          field: "producto_name",
          align: "left",
        },
        {
          name: "producto_categoria",
          label: "Categoria",
          field: "producto_categoria",
          align: "left",
        },
        {
          name: "cantidad_antes",
          label: "Stock Anterior",
          field: "cantidad_antes",
          align: "left",
          format: (val) => this.formatQuantity(val),
        },
        {
          name: "costo_antes",
          label: "Costo Anterior",
          field: "costo_antes",
          align: "left",
          format: (val) =>
            val !== undefined && val !== null
              ? `$${String(Number(val).toFixed(2)).replace(/\B(?=(\d{3})+(?!\d))/g, ",")}`
              : "$0.00",
        },
        {
          name: "cantidad_movimiento",
          label: "Cantidad del ultimo movimiento",
          field: "cantidad_movimiento",
          align: "left",
          format: (val) => this.formatQuantity(val),
        },
        {
          name: "costo_movimiento",
          label: "Costo Mov. (" + this.settingsStore.valuationMethodShort + ")",
          field: "costo_movimiento",
          align: "left",
          format: (val) =>
            val != null
              ? `$${String(Number(val).toFixed(2)).replace(/\B(?=(\d{3})+(?!\d))/g, ",")}`
              : "$0.00",
        },
        {
          name: "cantidad_despues",
          label: "Stock Actual",
          field: "cantidad_despues",
          align: "left",
          format: (val) => this.formatQuantity(val),
        },
        {
          name: "costo_despues",
          label: "Costo Stock (" + this.settingsStore.valuationMethodShort + ")",
          field: "costo_despues",
          align: "left",
          format: (val) =>
            val !== undefined && val !== null
              ? `$${String(Number(val).toFixed(2)).replace(/\B(?=(\d{3})+(?!\d))/g, ",")}`
              : "$0.00",
        },
        { name: 'razon', label: 'Razon', field: 'razon', align: 'left' },
      ];
    },
  },
  data() {
    return {
      search: "",
      startDate: "",
      endDate: "",
      tipoFilter: "",
      selectedProducto: "",
      razonFilter: "",
      rows: [],
      loading: false,
      pagination: {
        sortBy: 'fecha',
        descending: true,
        page: 1,
        rowsPerPage: 15,
        rowsNumber: 0
      },
      form: {
        producto_id: "",
        stock: "",
        costo_unitario: "",
        tipo: "",
        razon: "",
      },
      modalOpen: false,
      editRow: null,
      loadingProducts: false,
      productosOptions: [],
      filteredProductosOptions: [],
      loadingFilterProducts: false,
      filterProductsSearchOptions: [],
      productsCache: {},
      loadingFilterRazones: false,
      filterRazonesSearchOptions: [],
      tipoOptions: [
        { label: "Entrada", value: "entrada" },
        { label: "Salida", value: "salida" },
      ],
    };
  },
  created() {
    this.fetchItems();
    this.filterFnFilterProduct("", (cb) => cb && cb());
    this.filterFnFilterRazon("", (cb) => cb && cb());
  },

  methods: {
    formatQuantity(val) {
      if (val === null || val === undefined || val === "") return "0";
      return String(Math.round(Number(val) || 0)).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    },
    async fetchItems() {
      this.onRequest({
        pagination: this.pagination,
        filter: this.search
      });
    },
    async onRequest(props) {
      const { page, rowsPerPage, sortBy, descending } = props.pagination;
      const filter = props.filter;

      this.loading = true;
      try {
        const response = await api.get("/api/kardex", {
          params: {
            page: page,
            limit: rowsPerPage === 0 ? -1 : rowsPerPage,
            search: filter,
            start_date: this.startDate || undefined,
            end_date: this.endDate || undefined,
            tipo: this.tipoFilter || undefined,
            producto_id: this.selectedProducto || undefined,
            razon: this.razonFilter || undefined,
            sortBy: sortBy,
            descending: descending
          }
        });
        
        const data = response.data.data || [];
        if (data && data.data) {
          this.rows = data.data || [];
          this.pagination.rowsNumber = data.total || 0;
        } else {
          this.rows = Array.isArray(data) ? data : [];
          this.pagination.rowsNumber = this.rows.length;
        }
        
        const prodData = response.data.productos || [];
        this.productosOptions = prodData.map(
          ({ id, nombre }) => ({
            label: nombre,
            value: id,
          })
        );
        this.filteredProductosOptions = this.productosOptions;
        
        this.pagination.page = page;
        this.pagination.rowsPerPage = rowsPerPage;
        this.pagination.sortBy = sortBy;
        this.pagination.descending = descending;

      } catch (error) {
        Alert("Error", error.response?.data?.message || error.message || "Error", "error");
      } finally {
        this.loading = false;
      }
    },
    formatDateTime(val) {
      if (!val) return "N/A";
      const d = new Date(val);
      if (isNaN(d.getTime())) return val;
      const day = String(d.getDate()).padStart(2, "0");
      const month = String(d.getMonth() + 1).padStart(2, "0");
      const year = d.getFullYear();
      let hours = d.getHours();
      const minutes = String(d.getMinutes()).padStart(2, "0");
      const ampm = hours >= 12 ? "PM" : "AM";
      hours = hours % 12;
      hours = hours ? hours : 12;
      const strHours = String(hours).padStart(2, "0");
      return `${day}/${month}/${year} ${strHours}:${minutes} ${ampm}`;
    },
    onDateFilterChange() {
      this.pagination.page = 1;
      this.onRequest({
        pagination: this.pagination,
        filter: this.search,
      });
    },
    clearFilters() {
      this.search = "";
      this.startDate = "";
      this.endDate = "";
      this.tipoFilter = "";
      this.selectedProducto = "";
      this.razonFilter = "";
      this.filterFnFilterProduct("", (cb) => cb && cb());
      this.filterFnFilterRazon("", (cb) => cb && cb());
      this.pagination.page = 1;
      this.onRequest({
        pagination: this.pagination,
        filter: "",
      });
    },
    formatRazonInput(val) {
      if (!val) {
        this.form.razon = "";
        return;
      }
      let str = String(val);
      if (str.length > 300) {
        str = str.substring(0, 300);
      }
      this.form.razon = str.charAt(0).toUpperCase() + str.slice(1);
    },
    async filterFnFilterProduct(val, update, abort) {
      this.loadingFilterProducts = true;
      try {
        const response = await api.get("/api/productos/search", {
          params: { q: val, limit: 30 },
        });
        const prods = response.data.data || [];
        update(() => {
          this.filterProductsSearchOptions = prods.map((p) => {
            this.productsCache[p.id] = p;
            return {
              label: `${p.sku} - ${p.nombre}`,
              value: p.id,
            };
          });
        });
      } catch (err) {
        if (abort) abort();
      } finally {
        this.loadingFilterProducts = false;
      }
    },
    async filterFnFilterRazon(val, update, abort) {
      this.loadingFilterRazones = true;
      try {
        const response = await api.get("/api/kardex/razones", {
          params: { q: val },
        });
        const razones = response.data.data || [];
        update(() => {
          const opts = razones.map((r) => ({ label: r, value: r }));
          if (val && val.trim() !== "" && !razones.includes(val.trim())) {
            opts.unshift({ label: `Buscar: "${val.trim()}"`, value: val.trim() });
          }
          this.filterRazonesSearchOptions = opts;
        });
      } catch (err) {
        if (abort) abort();
      } finally {
        this.loadingFilterRazones = false;
      }
    },
    async filterFn(val, update, abort) {
      this.loadingProducts = true;
      try {
        const response = await api.get("/api/productos/search", {
          params: { q: val, limit: 30 },
        });
        const prods = response.data.data || [];
        update(() => {
          this.filteredProductosOptions = prods.map((p) => ({
            label: `${p.sku} - ${p.nombre}`,
            value: p.id,
          }));
        });
      } catch (err) {
        if (abort) abort();
      } finally {
        this.loadingProducts = false;
      }
    },
    openModal() {
      this.form = {
        producto_id: "",
        stock: "",
        costo_unitario: "",
        tipo: "",
        razon: "",
      };
      this.modalOpen = true;
    },
    async saveRow() {
      useLoadingStore().startLoading();
      try {
        const formData = {
          ...this.form,
          cantidad: parseInt(this.form.stock),
          costo_unitario: parseFloat(this.form.costo_unitario) || 0,
          producto_id: parseInt(this.form.producto_id),
          razon: typeof this.form.razon === "object" && this.form.razon !== null ? this.form.razon.value : (this.form.razon ? this.form.razon.trim() : null),
          tipo: typeof this.form.tipo === "object" && this.form.tipo !== null ? this.form.tipo.value : this.form.tipo,
        };

        const response = await api.post("/api/kardex", formData);
        this.fetchItems();
        this.modalOpen = false;
        this.filterFnFilterRazon("", (cb) => cb && cb());
        Alert("Éxito", "Stock actualizado con éxito", "success");
      } catch (error) {
        Alert("Error", error.response?.data?.message || error.message || "Error", "error");
      } finally {
        useLoadingStore().stopLoading();
      }
    },
  },
};
</script>
