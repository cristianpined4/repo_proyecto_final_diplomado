<template>
  <q-page class="q-pa-md">
    <!-- Header Banner -->
    <q-card class="bg-gradient-brand text-white q-mb-md shadow-2 rounded-card overflow-hidden">
      <q-card-section class="q-py-md">
        <div class="row items-center justify-between no-wrap">
          <div class="row items-center">
            <q-avatar icon="shopping_cart" size="48px" class="q-mr-md bg-white-translucent text-white" />
            <div>
              <div class="text-h5 text-weight-bold">Historial de Compras</div>
              <div class="text-subtitle2 opacity-80">Consulta y administra las compras realizadas a proveedores</div>
            </div>
          </div>
          <!-- Acciones Principales -->
          <q-btn
            v-if="authStore.hasPermission('compras.crear')"
            color="white"
            text-color="primary"
            label="Nueva Compra"
            icon="add"
            unelevated
            class="rounded-btn q-px-md text-weight-bold"
            to="/compras/registrar-compra"
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
            label="Buscar por factura, proveedor o código..."
            outlined
            dense
            debounce="300"
            class="rounded-input"
            @update:model-value="fetchPurchases"
          >
            <template v-slot:prepend>
              <q-icon name="search" color="grey-6" />
            </template>
            <template v-slot:append v-if="search">
              <q-icon name="close" class="cursor-pointer" @click="search = ''; fetchPurchases();" />
            </template>
          </q-input>
        </div>

        <!-- Filtro Proveedor -->
        <div class="col-12 col-md-6">
          <q-select
            v-model="selectedProveedor"
            :options="proveedoresFilterOptions"
            label="Proveedor"
            outlined
            dense
            emit-value
            map-options
            :display-value="selectedProveedor !== '' && selectedProveedor !== null ? undefined : 'Todos los Proveedores'"
            :clearable="selectedProveedor !== '' && selectedProveedor !== null"
            clear-value=""
            @clear="selectedProveedor = ''; fetchPurchases()"
            class="rounded-input"
            @update:model-value="fetchPurchases"
          >
            <template v-slot:prepend><q-icon name="local_shipping" color="primary" /></template>
          </q-select>
        </div>

        <!-- Filtro Estado -->
        <div class="col-12 col-sm-6 col-md-4">
          <q-select
            v-model="statusFilter"
            :options="[
              { label: 'Todos los Estados', value: '' },
              { label: 'Pagado', value: 'pagado' },
              { label: 'Cancelado', value: 'cancelado' },
            ]"
            label="Estado"
            outlined
            dense
            emit-value
            map-options
            :display-value="statusFilter !== '' && statusFilter !== null ? undefined : 'Todos los Estados'"
            :clearable="statusFilter !== '' && statusFilter !== null"
            clear-value=""
            @clear="statusFilter = ''; fetchPurchases()"
            class="rounded-input"
            @update:model-value="fetchPurchases"
          >
            <template v-slot:prepend><q-icon name="flag" color="primary" /></template>
          </q-select>
        </div>

        <!-- Fecha Desde -->
        <div class="col-12 col-sm-6 col-md-4">
          <q-input
            v-model="fechaDesde"
            type="date"
            label="Fecha Desde"
            outlined
            dense
            stack-label
            class="rounded-input"
            @update:model-value="fetchPurchases"
          >
            <template v-slot:prepend><q-icon name="event" color="primary" /></template>
            <template v-slot:append v-if="fechaDesde">
              <q-icon name="close" class="cursor-pointer" @click="fechaDesde = ''; fetchPurchases();" />
            </template>
          </q-input>
        </div>

        <!-- Fecha Hasta -->
        <div class="col-12 col-sm-6 col-md-4">
          <q-input
            v-model="fechaHasta"
            type="date"
            label="Fecha Hasta"
            outlined
            dense
            stack-label
            class="rounded-input"
            @update:model-value="fetchPurchases"
          >
            <template v-slot:prepend><q-icon name="event" color="primary" /></template>
            <template v-slot:append v-if="fechaHasta">
              <q-icon name="close" class="cursor-pointer" @click="fechaHasta = ''; fetchPurchases();" />
            </template>
          </q-input>
        </div>

        <!-- Rango de Monto de Compra -->
        <div class="col-12 col-sm-4 col-md-4">
          <q-input
            v-model="montoMin"
            type="text"
            inputmode="decimal"
            label="Monto Mín ($)"
            placeholder="0.00"
            outlined
            dense
            clearable
            class="rounded-input"
            @blur="formatAmountOnBlur('montoMin')"
            @keyup.enter="formatAmountOnBlur('montoMin')"
            @update:model-value="fetchPurchases"
          >
            <template v-slot:prepend><q-icon name="attach_money" color="primary" /></template>
          </q-input>
        </div>

        <div class="col-12 col-sm-4 col-md-4">
          <q-input
            v-model="montoMax"
            type="text"
            inputmode="decimal"
            label="Monto Máx ($)"
            placeholder="0.00"
            outlined
            dense
            clearable
            class="rounded-input"
            @blur="formatAmountOnBlur('montoMax')"
            @keyup.enter="formatAmountOnBlur('montoMax')"
            @update:model-value="fetchPurchases"
          >
            <template v-slot:prepend><q-icon name="attach_money" color="primary" /></template>
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
        :rows="rows"
        :columns="columns"
        row-key="id"
        v-model:pagination="pagination"
        @request="onRequest"
        :filter="search"
        :loading="loading"
        flat
        class="no-shadow"
        binary-state-sort
        no-data-label="No se encontraron compras"
      >
        <template v-slot:body-cell-id="props">
          <q-td align="left">
            <span class="text-weight-bold text-primary">
              C{{ strPad(`${props.row.id}`, 8, '0', 'STR_PAD_LEFT') }}
            </span>
          </q-td>
        </template>

        <template v-slot:body-cell-numero_factura="props">
          <q-td align="left">
            <q-badge color="blue-1" text-color="primary" class="q-pa-xs text-weight-bold">
              <q-icon name="tag" size="14px" class="q-mr-xs" />
              {{ props.row.numero_factura || 'S/N' }}
            </q-badge>
          </q-td>
        </template>

        <template v-slot:body-cell-subtotal="props">
          <q-td align="right">
            <span class="text-weight-medium text-grey-9">
              ${{ formatCurrency(props.row.subtotal != null ? props.row.subtotal : (props.row.total_compra ? Number(props.row.total_compra) / 1.13 : 0)) }}
            </span>
          </q-td>
        </template>

        <template v-slot:body-cell-monto_iva="props">
          <q-td align="right">
            <span class="text-weight-medium text-grey-8">
              ${{ formatCurrency(props.row.monto_iva != null ? props.row.monto_iva : (props.row.total_compra ? Number(props.row.total_compra) - (Number(props.row.total_compra) / 1.13) : 0)) }}
            </span>
          </q-td>
        </template>

        <template v-slot:body-cell-total_compra="props">
          <q-td align="right">
            <span class="text-weight-bold text-dark text-subtitle2">
              ${{ formatCurrency(props.row.total_compra) }}
            </span>
          </q-td>
        </template>

        <template v-slot:body-cell-status="props">
          <q-td align="center">
            <q-chip
              :color="getStatusColor(props.row.status)"
              text-color="white"
              :label="getStatusLabel(props.row.status)"
              dense
              class="text-weight-bold"
            />
          </q-td>
        </template>

        <template v-slot:body-cell-options="props">
          <q-td align="right">
            <q-btn-dropdown flat color="dark" icon="more_vert" size="md">
              <q-list>
                <q-item clickable v-close-popup @click="viewDetails(props.row)">
                  <q-item-section avatar>
                    <q-icon name="visibility" color="secondary" />
                  </q-item-section>
                  <q-item-section> Detalles </q-item-section>
                </q-item>

                <q-item
                  clickable
                  v-close-popup
                  v-if="props.row.status !== 'cancelada' && authStore.hasPermission('compras.crear')"
                  @click="confirmCancel(props.row.id)"
                >
                  <q-item-section avatar>
                    <q-icon name="cancel" color="negative" />
                  </q-item-section>
                  <q-item-section> Cancelar </q-item-section>
                </q-item>
              </q-list>
            </q-btn-dropdown>
          </q-td>
        </template>
      </q-table>
    </q-card>

    <!-- Modal para confirmar cancelación -->
    <q-dialog v-model="confirmOpen" persistent>
      <q-card style="width: 420px; max-width: 90vw; border-radius: 12px;">
        <q-card-section class="bg-negative text-white">
          <div class="text-h6 text-weight-bold">¿Cancelar Compra?</div>
        </q-card-section>
        <q-card-section class="q-pa-md">
          <div class="text-subtitle1 text-weight-medium">Proveedor: {{ selectedRow?.nombre_proveedor }}</div>
          <div class="text-caption text-grey-7">Factura N°: {{ selectedRow?.numero_factura || 'S/N' }}</div>
          <div class="text-h6 text-weight-bolder text-negative q-mt-xs">Total: ${{ formatCurrency(selectedRow?.total_compra) }}</div>
          <div class="text-caption text-grey-8 q-mt-sm">
            Esta acción cancelará la compra y revertirá el inventario ingresado a la sucursal.
          </div>
        </q-card-section>
        <q-card-actions align="right" class="q-pa-md bg-grey-1">
          <q-btn flat label="Cerrar" color="grey-7" v-close-popup class="rounded-btn" />
          <q-btn unelevated label="Sí, Cancelar Compra" color="negative" class="rounded-btn q-px-md text-weight-bold" @click="processCancel" />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- Modal para mostrar detalles de la compra -->
    <q-dialog v-model="detailsOpen">
      <q-card style="width: 720px; max-width: 95vw; border-radius: 14px;" class="overflow-hidden">
        <q-card-section class="bg-gradient-brand text-white q-py-md">
          <div class="row items-center justify-between no-wrap">
            <div class="row items-center">
              <q-avatar icon="receipt_long" size="42px" class="q-mr-sm bg-white-translucent text-white" />
              <div>
                <div class="text-h6 text-weight-bold">Detalle de Compra</div>
                <div class="text-caption opacity-80">
                  Código: C{{ strPad(`${detailsRow?.id}`, 8, '0', 'STR_PAD_LEFT') }} • Factura: {{ detailsRow?.numero_factura || 'S/N' }}
                </div>
              </div>
            </div>
            <q-btn flat round dense icon="close" color="white" v-close-popup />
          </div>
        </q-card-section>

        <q-card-section class="q-pa-md">
          <!-- Datos Generales -->
          <div class="row q-col-gutter-sm q-mb-md">
            <div class="col-12 col-sm-6">
              <div class="bg-grey-1 q-pa-sm rounded-borders border-grey">
                <div class="text-caption text-grey-7">Proveedor:</div>
                <div class="text-weight-bold text-dark">{{ detailsRow?.nombre_proveedor || 'Proveedor General' }}</div>
                <div class="text-caption text-grey-6" v-if="detailsRow?.proveedor_contacto">Contacto: {{ detailsRow.proveedor_contacto }}</div>
                <div class="text-caption text-grey-6" v-if="detailsRow?.proveedor_telefono">Tel: {{ detailsRow.proveedor_telefono }}</div>
              </div>
            </div>
            <div class="col-12 col-sm-6">
              <div class="bg-grey-1 q-pa-sm rounded-borders border-grey">
                <div class="text-caption text-grey-7">Registrado por:</div>
                <div class="text-weight-bold text-dark">{{ detailsRow?.usuario || 'N/A' }}</div>
                <div class="text-caption text-grey-6">Fecha: {{ formatDate(detailsRow?.fecha) }}</div>
                <div class="text-caption text-grey-6">N° Factura: <strong>{{ detailsRow?.numero_factura || 'S/N' }}</strong></div>
              </div>
            </div>
          </div>

          <!-- Estado de la Compra -->
          <div class="bg-blue-1 q-pa-sm rounded-borders border-blue q-mb-md">
            <div class="row items-center justify-between">
              <div class="row items-center">
                <q-icon name="local_shipping" size="26px" color="primary" class="q-mr-sm" />
                <div>
                  <div class="text-caption text-weight-bold text-grey-8">ESTADO DE TRANSACCIÓN</div>
                  <div class="text-subtitle2 text-weight-bolder text-primary">Ingreso de Inventario por Compra</div>
                </div>
              </div>
              <q-chip
                :color="getStatusColor(detailsRow?.status)"
                text-color="white"
                :label="getStatusLabel(detailsRow?.status)"
                dense
                class="text-weight-bold"
              />
            </div>
          </div>

          <!-- Tabla de Productos -->
          <div class="text-subtitle2 text-weight-bold text-grey-8 q-mb-xs">Productos Adquiridos</div>
          <div class="rounded-borders border-grey overflow-hidden q-mb-md">
            <q-markup-table dense flat separator="horizontal">
              <thead>
                <tr class="bg-grey-2 text-grey-9">
                  <th class="text-left">#</th>
                  <th class="text-left">Producto</th>
                  <th class="text-center">Cant.</th>
                  <th class="text-right">Costo Unit.</th>
                  <th class="text-right">Subtotal</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(detalle, index) in (detailsRow?.detalles_compra || detailsRow?.detalles || [])" :key="detalle.id">
                  <td class="text-caption text-grey-7">{{ index + 1 }}</td>
                  <td>
                    <div class="text-weight-medium">{{ detalle.producto?.nombre || 'Producto' }}</div>
                    <div class="text-caption text-grey-6" v-if="detalle.producto?.sku">SKU: {{ detalle.producto.sku }}</div>
                  </td>
                  <td class="text-center text-weight-bold">{{ formatQuantity(detalle.cantidad) }}</td>
                  <td class="text-right">${{ formatCurrency(detalle.precio_compra) }}</td>
                  <td class="text-right text-weight-bold">
                    ${{ formatCurrency(Number(detalle.cantidad) * Number(detalle.precio_compra)) }}
                  </td>
                </tr>
              </tbody>
            </q-markup-table>
          </div>

          <!-- Totales Desglosados -->
          <div class="row justify-end">
            <div style="width: 290px;" class="bg-grey-1 q-pa-sm rounded-borders border-grey">
              <div class="row justify-between text-caption text-grey-8 q-mb-xs">
                <span>Subtotal Neto:</span>
                <span class="text-weight-bold text-dark">
                  ${{ formatCurrency(detailsRow?.subtotal != null ? detailsRow.subtotal : (detailsRow?.total_compra ? Number(detailsRow.total_compra) / 1.13 : 0)) }}
                </span>
              </div>
              <div class="row justify-between text-caption text-grey-8 q-mb-xs">
                <span>IVA ({{ Number(detailsRow?.porcentaje_iva_aplicado != null ? detailsRow.porcentaje_iva_aplicado : 13) }}%):</span>
                <span class="text-weight-bold text-dark">
                  ${{ formatCurrency(detailsRow?.monto_iva != null ? detailsRow.monto_iva : (detailsRow?.total_compra ? Number(detailsRow.total_compra) - (Number(detailsRow.total_compra) / 1.13) : 0)) }}
                </span>
              </div>
              <div class="row justify-between text-caption text-grey-8 q-mb-xs">
                <span>Total Unidades:</span>
                <span class="text-weight-bold text-dark">{{ detailsRow?.total_productos }}</span>
              </div>
              <q-separator class="q-my-xs" />
              <div class="row justify-between items-center text-subtitle1">
                <span class="text-weight-bold text-grey-9">Total Compra:</span>
                <span class="text-h6 text-weight-bolder text-primary">${{ formatCurrency(detailsRow?.total_compra) }}</span>
              </div>
            </div>
          </div>
        </q-card-section>

        <q-card-actions align="right" class="q-pa-md bg-grey-2">
          <q-btn flat label="Cerrar" color="grey-7" v-close-popup class="rounded-btn" />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script>
import { useAuthStore } from "src/store/auth-store";
import { api } from "boot/axios";
import Alert from "src/helpers/Alert";

export default {
  computed: {
    authStore() {
      return useAuthStore();
    },
    proveedoresFilterOptions() {
      return [
        { label: "Todos los proveedores", value: "" },
        ...this.proveedoresList.map((p) => ({
          label: p.nombre,
          value: p.id,
        })),
      ];
    },
  },
  data() {
    return {
      search: "",
      rows: [],
      loading: false,
      pagination: {
        sortBy: "fecha",
        descending: true,
        page: 1,
        rowsPerPage: 15,
        rowsNumber: 0,
      },
      columns: [
        {
          name: "id",
          label: "Código",
          align: "left",
          field: "id",
          sortable: true,
          format: (val) => `C${this.strPad(`${val}`, 8, "0", "STR_PAD_LEFT")}`,
        },
        {
          name: "numero_factura",
          label: "N° Factura",
          align: "left",
          field: "numero_factura",
          sortable: true,
        },
        {
          name: "fecha",
          label: "Fecha",
          align: "left",
          field: "fecha",
          sortable: true,
          format: (val) => this.formatDate(val),
        },
        {
          name: "proveedor",
          label: "Proveedor",
          align: "left",
          field: "nombre_proveedor",
          sortable: true,
        },
        {
          name: "usuario",
          label: "Comprador",
          align: "left",
          field: "usuario",
          sortable: false,
        },
        {
          name: "total_productos",
          label: "Cant.",
          align: "center",
          field: "total_productos",
          sortable: false,
          format: (val) => this.formatQuantity(val),
        },
        {
          name: "subtotal",
          label: "Subtotal ($)",
          align: "right",
          field: (row) => row.subtotal != null ? row.subtotal : (row.total_compra ? Number(row.total_compra) / 1.13 : 0),
          sortable: true,
          format: (val) => `$${this.formatCurrency(val)}`,
        },
        {
          name: "monto_iva",
          label: "IVA ($)",
          align: "right",
          field: (row) => row.monto_iva != null ? row.monto_iva : (row.total_compra ? Number(row.total_compra) - (Number(row.total_compra) / 1.13) : 0),
          sortable: true,
          format: (val) => `$${this.formatCurrency(val)}`,
        },
        {
          name: "total_compra",
          label: "Total ($)",
          align: "right",
          field: "total_compra",
          sortable: true,
          format: (val) => `$${this.formatCurrency(val)}`,
        },
        { name: "status", label: "Estado", align: "center", field: "status", sortable: true },
        { name: "options", label: "Acciones", align: "right" },
      ],
      selectedRow: null,
      confirmOpen: false,
      detailsOpen: false,
      detailsRow: null,
      statusFilter: "",
      fechaDesde: "",
      fechaHasta: "",
      selectedProveedor: "",
      montoMin: "",
      montoMax: "",
      proveedoresList: [],
    };
  },
  created() {
    this.fetchPurchases();
    this.fetchProveedores();
  },
  methods: {
    formatAmountOnBlur(field) {
      if (this[field] !== null && this[field] !== undefined && String(this[field]).trim() !== "") {
        const clean = String(this[field]).replace(/[^0-9.]/g, "");
        const num = parseFloat(clean);
        if (!isNaN(num) && num >= 0) {
          this[field] = num.toFixed(2);
        } else {
          this[field] = "";
        }
        this.fetchPurchases();
      }
    },
    async fetchProveedores() {
      try {
        const response = await api.get("/api/proveedores", { params: { limit: -1 } });
        const resData = response.data?.data;
        this.proveedoresList = Array.isArray(resData) ? resData : (resData?.data || []);
      } catch (e) {
        console.error("Error cargando proveedores", e);
      }
    },
    clearFilters() {
      this.search = "";
      this.statusFilter = "";
      this.fechaDesde = "";
      this.fechaHasta = "";
      this.selectedProveedor = "";
      this.montoMin = "";
      this.montoMax = "";
      this.fetchPurchases();
    },
    fetchPurchases() {
      this.onRequest({
        pagination: this.pagination,
        filter: this.search,
      });
    },
    async onRequest(props) {
      const { page, rowsPerPage, sortBy, descending } = props.pagination;
      this.loading = true;
      try {
        const response = await api.get("/api/compras/historial", {
          params: {
            page: page,
            limit: rowsPerPage === 0 ? -1 : rowsPerPage,
            search: this.search,
            sortBy: sortBy,
            descending: descending,
            status: this.statusFilter || undefined,
            fecha_desde: this.fechaDesde || undefined,
            fecha_hasta: this.fechaHasta || undefined,
            proveedor_id: this.selectedProveedor || undefined,
            monto_min: (this.montoMin !== null && this.montoMin !== "") ? this.montoMin : undefined,
            monto_max: (this.montoMax !== null && this.montoMax !== "") ? this.montoMax : undefined,
          },
        });
        const resData = response.data.data;
        if (resData.data) {
          this.rows = resData.data;
          this.pagination.rowsNumber = resData.total;
        } else {
          this.rows = resData;
          this.pagination.rowsNumber = resData.length;
        }
        this.pagination.page = page;
        this.pagination.rowsPerPage = rowsPerPage;
        this.pagination.sortBy = sortBy;
        this.pagination.descending = descending;
      } catch (error) {
        Alert(
          "Error",
          error.response?.data?.message || error.message || "Error al obtener historial",
          "error"
        );
      } finally {
        this.loading = false;
      }
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
          const halfPad = pad.substring(0, Math.ceil(padLength / 2));
          return halfPad + input + halfPad;
        case "STR_PAD_RIGHT":
        default:
          return input + pad;
      }
    },
    formatCurrency(value) {
      const val = parseFloat(value);
      if (isNaN(val)) return "0.00";
      return val.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    },
    formatQuantity(value) {
      if (value === null || value === undefined || value === "") return "0";
      return String(Math.round(Number(value) || 0)).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    },
    formatDate(val) {
      if (!val) return "N/A";
      return new Date(val).toLocaleDateString("es-ES", {
        year: "numeric",
        month: "numeric",
        day: "numeric",
        hour: "numeric",
        minute: "numeric",
        hour12: true,
      });
    },
    getStatusColor(status) {
      switch (status) {
        case "pendiente":
          return "orange";
        case "pagado":
          return "green";
        case "cancelada":
          return "red";
        default:
          return "grey";
      }
    },
    getStatusLabel(status) {
      switch (status) {
        case "pendiente":
          return "Pendiente";
        case "pagado":
          return "Pagado";
        case "cancelada":
          return "Cancelada";
        default:
          return "Desconocido";
      }
    },
    async viewDetails(row) {
      this.detailsRow = { ...row };
      this.detailsOpen = true;
      try {
        const response = await api.get(`/api/compras/${row.id}`);
        if (response.data?.status && response.data?.data) {
          const comp = response.data.data;
          this.detailsRow = {
            ...this.detailsRow,
            ...comp,
            nombre_proveedor: comp.proveedor?.nombre || this.detailsRow.nombre_proveedor,
            proveedor_contacto: comp.proveedor?.contacto || this.detailsRow.proveedor_contacto,
            proveedor_telefono: comp.proveedor?.telefono || this.detailsRow.proveedor_telefono,
            usuario: comp.user ? `${comp.user.name} ${comp.user.lastname || ''}`.trim() : this.detailsRow.usuario,
            fecha: comp.created_at || this.detailsRow.fecha,
            subtotal: comp.subtotal != null ? comp.subtotal : this.detailsRow.subtotal,
            monto_iva: comp.monto_iva != null ? comp.monto_iva : this.detailsRow.monto_iva,
            porcentaje_iva_aplicado: comp.porcentaje_iva_aplicado != null ? comp.porcentaje_iva_aplicado : this.detailsRow.porcentaje_iva_aplicado,
            total_productos: comp.detalles_compra ? comp.detalles_compra.reduce((sum, d) => sum + Number(d.cantidad || 0), 0) : this.detailsRow.total_productos
          };
        }
      } catch (e) {
        // En caso de fallo de red, conserva los datos ya disponibles en memoria
      }
    },
    confirmCancel(id) {
      this.selectedRow = this.rows.find((row) => row.id === id);
      this.confirmOpen = true;
    },
    async processCancel() {
      this.loading = true;
      try {
        await api.post(`/api/compras/cancelar/${this.selectedRow.id}`);
        this.fetchPurchases();
        this.confirmOpen = false;
        Alert("Éxito", "Compra cancelada con éxito", "success");
      } catch (error) {
        Alert(
          "Error",
          error.response?.data?.message || error.message || "Error al cancelar compra",
          "error"
        );
      } finally {
        this.loading = false;
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
.rounded-borders {
  border-radius: 10px;
}
.border-grey {
  border: 1px solid #e0e0e0;
}
.border-blue {
  border: 1px solid #bbdefb;
}
</style>
