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
              icon="dashboard"
              size="48px"
              class="q-mr-md bg-white-translucent text-white"
            />
            <div>
              <div class="text-h5 text-weight-bold row items-center q-gutter-x-sm">
                <span>Escritorio</span>
                <q-badge
                  v-if="isAdmin"
                  color="white"
                  text-color="primary"
                  class="text-weight-bold q-pa-xs"
                >
                  <q-icon name="public" size="14px" class="q-mr-xs" />
                  Consolidado Global (Todas las Sucursales)
                </q-badge>
                <q-badge
                  v-else-if="sucursalNombre"
                  color="white"
                  text-color="primary"
                  class="text-weight-bold q-pa-xs"
                >
                  <q-icon name="storefront" size="14px" class="q-mr-xs" />
                  Sucursal: {{ sucursalNombre }}
                </q-badge>
              </div>
              <div class="text-subtitle2 opacity-80">
                Resumen y métricas principales de tu negocio
              </div>
            </div>
          </div>
        </div>
      </q-card-section>
    </q-card>

    <!-- Indicadores de ventas -->
    <div class="row q-mb-md" style="justify-content: space-between">
      <div class="col-12 col-md-6 col-lg-4 q-pa-md">
        <q-card
          class="q-pa-md text-center text-white"
          style="background-color: var(--q-primary)"
        >
          <div class="text-h6 text-weight-bold">Total ventas úúltimo día</div>
          <q-badge
            style="
              border-radius: 20px;
              margin-top: 0.5rem;
              color: var(--q-primary);
            "
            class="bg-white text-subtitle1"
          >
            ${{
              String(Number(salesLastDay).toFixed(2)).replace(
                /\B(?=(\d{3})+(?!\d))/g,
                ","
              )
            }}
          </q-badge>
        </q-card>
      </div>
      <div class="col-12 col-md-6 col-lg-4 q-pa-md">
        <q-card
          class="q-pa-md text-center text-white"
          style="background-color: var(--q-secondary)"
        >
          <div class="text-h6 text-weight-bold">
            Total ventas últimos 7 días
          </div>
          <q-badge
            style="
              border-radius: 20px;
              margin-top: 0.5rem;
              color: var(--q-secondary);
            "
            class="bg-white text-subtitle1"
          >
            ${{
              String(Number(salesLast7Days).toFixed(2)).replace(
                /\B(?=(\d{3})+(?!\d))/g,
                ","
              )
            }}
          </q-badge>
        </q-card>
      </div>
      <div class="col-12 col-md-6 col-lg-4 q-pa-md">
        <q-card
          class="q-pa-md text-center text-white"
          style="background-color: var(--q-accent)"
        >
          <div class="text-h6 text-weight-bold">Total ventas último mes</div>
          <q-badge
            style="
              border-radius: 20px;
              margin-top: 0.5rem;
              color: var(--q-accent);
            "
            class="bg-white text-subtitle1"
          >
            ${{
              String(Number(salesLastMonth).toFixed(2)).replace(
                /\B(?=(\d{3})+(?!\d))/g,
                ","
              )
            }}
          </q-badge>
        </q-card>
      </div>
    </div>

    <!-- Indicadores de compras -->
    <div class="row q-mb-md" style="justify-content: space-between">
      <div class="col-12 col-md-6 col-lg-4 q-pa-md">
        <q-card class="q-pa-md text-center purchases-card primary-border">
          <div class="text-h6 text-weight-bold">Total compras último día</div>
          <q-badge
            style="border-radius: 20px; margin-top: 0.5rem"
            class="bg-primary text-white text-subtitle1"
          >
            ${{
              String(Number(purchasesLastDay).toFixed(2)).replace(
                /\B(?=(\d{3})+(?!\d))/g,
                ","
              )
            }}
          </q-badge>
        </q-card>
      </div>
      <div class="col-12 col-md-6 col-lg-4 q-pa-md">
        <q-card class="q-pa-md text-center purchases-card secondary-border">
          <div class="text-h6 text-weight-bold">
            Total compras últimos 7 días
          </div>
          <q-badge
            style="border-radius: 20px; margin-top: 0.5rem"
            class="bg-secondary text-white text-subtitle1"
          >
            ${{
              String(Number(purchasesLast7Days).toFixed(2)).replace(
                /\B(?=(\d{3})+(?!\d))/g,
                ","
              )
            }}
          </q-badge>
        </q-card>
      </div>
      <div class="col-12 col-md-6 col-lg-4 q-pa-md">
        <q-card class="q-pa-md text-center purchases-card accent-border">
          <div class="text-h6 text-weight-bold">Total compras último mes</div>
          <q-badge
            style="border-radius: 20px; margin-top: 0.5rem"
            class="bg-accent text-white text-subtitle1"
          >
            ${{
              String(Number(purchasesLastMonth).toFixed(2)).replace(
                /\B(?=(\d{3})+(?!\d))/g,
                ","
              )
            }}
          </q-badge>
        </q-card>
      </div>
    </div>

    <!-- Tabla de últimas ventas -->
    <q-card class="q-pa-md q-mb-md q-mt-md">
      <q-table
        :loading="loading"
        :rows="lastSales"
        :columns="salesColumns"
        row-key="id"
        title="Últimas 10 ventas"
        :pagination="{ rowsPerPage: 10 }"
      >
        <template v-slot:body-cell-status="props">
          <q-td align="left">
            <q-badge
              :color="getStatusColor(props.row.status)"
              :label="getStatusLabel(props.row.status)"
              class="q-mr-sm"
            />
          </q-td>
        </template>
      </q-table>
    </q-card>

    <!-- Tabla de productos agotándose -->
    <q-card class="q-pa-md q-mt-md q-mb-lg">
      <q-table
        :loading="loadingLowStock"
        :rows="lowStockProducts"
        :columns="productColumns"
        row-key="id"
        v-model:pagination="lowStockPagination"
        @request="onRequestLowStock"
        :rows-per-page-options="[5, 10, 15, 25, 50]"
        binary-state-sort
        no-data-label="No hay productos agotados o por agotarse"
      >
        <template v-slot:top>
          <div class="row items-center justify-between full-width">
            <div class="text-h6 text-weight-bold">Productos Agotándose y Agotados</div>
            <q-badge color="primary" class="q-pa-xs text-weight-medium">
              <q-icon name="layers" size="14px" class="q-mr-xs" />
              Valuación: {{ settingsStore.valuationMethodShort }}
            </q-badge>
          </div>
        </template>
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
                  ? 'Disponible'
                  : 'Por Agotarse'
              "
            />
          </q-td>
        </template>
      </q-table>
    </q-card>
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
    productColumns() {
      return [
        { name: "sku", label: "SKU", align: "left", field: "sku", sortable: true },
        { name: "name", label: "Nombre", align: "left", field: "nombre", sortable: true },
        {
          name: "stock",
          label: "Stock",
          align: "right",
          field: "stock",
          sortable: true,
          format: (val) => this.formatQuantity(val),
        },
        {
          name: "disponibilidad",
          label: "Disponibilidad",
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
          sortable: true,
          format: (val) => this.formatCurrency(val),
        },
        {
          name: "precio_venta",
          label: "Precio Venta",
          align: "right",
          field: "precio_venta",
          sortable: true,
          format: (val) => this.formatCurrency(val),
        },
        {
          name: "categoria_id",
          label: "Categoria",
          align: "left",
          field: "categoria",
          format: (val) => (val ? val.name : ""),
        },
        {
          name: "subcategoria_id",
          label: "Subcategoria",
          align: "left",
          field: "subcategoria",
          format: (val) => (val ? (val.nombre || val.name) : ""),
        },
      ];
    },
  },
  data() {
    return {
      loading: false,
      loadingLowStock: false,
      isAdmin: false,
      sucursalNombre: "",
      salesLastDay: 0,
      salesLast7Days: 0,
      salesLastMonth: 0,
      purchasesLastDay: 0,
      purchasesLast7Days: 0,
      purchasesLastMonth: 0,
      lastSales: [],
      lowStockProducts: [],
      lowStockPagination: {
        sortBy: "stock",
        descending: false,
        page: 1,
        rowsPerPage: 10,
        rowsNumber: 0,
      },
      totalAgotados: 0,
      totalPorAgotarse: 0,
      salesColumns: [
        {
          name: "id",
          label: "Código",
          align: "left",
          field: "id",
          format: (val) =>
            val ? `V${this.strPad(`${val}`, 8, "0", "STR_PAD_LEFT")}` : "",
        },
        {
          name: "fecha",
          label: "Fecha",
          align: "left",
          field: "fecha",
          format: (val) =>
            val
              ? new Date(val)
                  .toLocaleDateString("es-ES", {
                    year: "numeric",
                    month: "numeric",
                    day: "numeric",
                    hour: "numeric",
                    minute: "numeric",
                    second: "numeric",
                    hour12: true,
                    timeZone: "America/Mexico_City",
                  })
                  .replace(",", "")
              : "",
        },
        { name: "cliente", label: "Cliente", align: "left", field: "cliente" },
        { name: "dui", label: "DUI", align: "left", field: "dui" },
        {
          name: "vendedor",
          label: "Vendedor",
          align: "left",
          field: "vendedor",
        },
        {
          name: "total_productos",
          label: "Total Productos",
          align: "right",
          field: "total_productos",
          format: (val) => this.formatQuantity(val),
        },
        {
          name: "total_venta",
          label: "Total Venta ($)",
          align: "right",
          field: "total_venta",
          format: (val) => this.formatCurrency(val),
        },
        { name: "status", label: "Estado", align: "left", field: "status" },
      ],
    };
  },
  async created() {
    useLoadingStore().startLoading();
    await Promise.all([
      this.fetchAll(),
      this.onRequestLowStock({ pagination: this.lowStockPagination })
    ]);
    useLoadingStore().stopLoading();
    this.alertas();
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
    strPad(input, length, padString = " ", type = "STR_PAD_RIGHT") {
      const strLength = input.length;

      if (strLength >= length) {
        return input;
      }

      const padLength = length - strLength;
      let pad = "";

      while (pad.length < padLength) {
        pad += padString;
      }

      pad = pad.substring(0, padLength);

      switch (type) {
        case "STR_PAD_LEFT":
          return pad + input;
        case "STR_PAD_BOTH":
          const halfPadLength = Math.floor(padLength / 2);
          return (
            pad.substring(0, halfPadLength) +
            input +
            pad.substring(0, padLength - halfPadLength)
          );
        case "STR_PAD_RIGHT":
        default:
          return input + pad;
      }
    },
    getStatusColor(status) {
      switch (status) {
        case "pendiente":
          return "orange";
        case "pagada":
          return "green";
        case "anulada":
          return "red";
        case "devolucion":
          return "blue";
        default:
          return "grey";
      }
    },
    getStatusLabel(status) {
      switch (status) {
        case "pendiente":
          return "Pendiente";
        case "pagada":
          return "Pagada";
        case "anulada":
          return "Anulada";
        case "devolucion":
          return "Devolución";
        default:
          return "Desconocido";
      }
    },
    async fetchAll() {
      try {
        const { data } = await api.get("/api/dashboard");
        this.salesLastDay = data.salesLastDay || 0;
        this.salesLast7Days = data.salesLast7Days || 0;
        this.salesLastMonth = data.salesLastMonth || 0;
        this.purchasesLastDay = data.purchasesLastDay || 0;
        this.purchasesLast7Days = data.purchasesLast7Days || 0;
        this.purchasesLastMonth = data.purchasesLastMonth || 0;
        this.lastSales = data.lastSales || [];
        this.totalAgotados = data.totalAgotados || 0;
        this.totalPorAgotarse = data.totalPorAgotarse || 0;
        this.isAdmin = !!data.isAdmin;
        this.sucursalNombre = data.sucursalNombre || "";
      } catch (error) {
        Alert(
          "Error",
          error.response?.data?.message || error.message || "Error",
          "error"
        );
      }
    },
    async onRequestLowStock(props) {
      const { page, rowsPerPage, sortBy, descending } = props.pagination;
      this.loadingLowStock = true;
      try {
        const res = await api.get("/api/dashboard/productos-agotandose", {
          params: {
            page: page,
            limit: rowsPerPage === 0 ? -1 : rowsPerPage,
            sortBy: sortBy,
            descending: descending,
          },
        });

        const data = res.data.data;
        if (data.data) {
          this.lowStockProducts = data.data;
          this.lowStockPagination.rowsNumber = data.total;
        } else {
          this.lowStockProducts = data;
          this.lowStockPagination.rowsNumber = data.length;
        }

        this.lowStockPagination.page = page;
        this.lowStockPagination.rowsPerPage = rowsPerPage;
        this.lowStockPagination.sortBy = sortBy;
        this.lowStockPagination.descending = descending;
      } catch (error) {
        Alert("Error", "Error al cargar productos agotándose", "error");
      } finally {
        this.loadingLowStock = false;
      }
    },
    alertas() {
      const agotados = this.totalAgotados;
      const porAgotarse = this.totalPorAgotarse;

      if (agotados > 0) {
        Alert(
          "Advertencia",
          `Existen ${this.formatQuantity(agotados)} productos agotados`,
          "productAgotado"
        );
      }

      if (porAgotarse > 0) {
        Alert(
          "Advertencia",
          `Existen ${this.formatQuantity(porAgotarse)} productos por agotarse`,
          "productPorAgotar"
        );
      }
    },
  },
};
</script>

<style scoped>
.purchases-card {
  transition: all 0.3s ease;
}

/* Light Theme: Transparent background, colored border, and colored text */
.body--light .purchases-card {
  background-color: transparent !important;
}
.body--light .purchases-card.primary-border {
  border: 2px solid var(--q-primary) !important;
  color: var(--q-primary) !important;
}
.body--light .purchases-card.secondary-border {
  border: 2px solid var(--q-secondary) !important;
  color: var(--q-secondary) !important;
}
.body--light .purchases-card.accent-border {
  border: 2px solid var(--q-accent) !important;
  color: var(--q-accent) !important;
}

/* Dark Theme: Dark slate background, colored border, and white text for high legibility */
.body--dark .purchases-card {
  background-color: #1e1e24 !important;
}
.body--dark .purchases-card.primary-border {
  border: 2px solid var(--q-primary) !important;
  color: #ffffff !important;
}
.body--dark .purchases-card.secondary-border {
  border: 2px solid var(--q-secondary) !important;
  color: #ffffff !important;
}
.body--dark .purchases-card.accent-border {
  border: 2px solid var(--q-accent) !important;
  color: #ffffff !important;
}
</style>
