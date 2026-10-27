<template>
  <q-page class="q-pa-md">
    <!-- Header Banner -->
    <q-card class="bg-gradient-brand text-white q-mb-md shadow-2 rounded-card overflow-hidden">
      <q-card-section class="q-py-md">
        <div class="row items-center justify-between no-wrap">
          <div class="row items-center">
            <q-avatar icon="receipt_long" size="48px" class="q-mr-md bg-white-translucent text-white" />
            <div>
              <div class="text-h5 text-weight-bold">Historial de Ventas</div>
              <div class="text-subtitle2 opacity-80">Consulta y administra las ventas realizadas en tu sucursal</div>
            </div>
          </div>
          <!-- Acciones Principales -->
          <q-btn
            v-if="authStore.hasPermission('ventas.crear')"
            color="white"
            text-color="primary"
            label="Nueva Venta"
            icon="add"
            unelevated
            class="rounded-btn q-px-md text-weight-bold"
            to="/ventas/realizar-venta"
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
            label="Buscar por cliente, correlativo..."
            outlined
            dense
            debounce="300"
            class="rounded-input"
            @update:model-value="fetchSales"
          >
            <template v-slot:prepend><q-icon name="search" color="grey-6" /></template>
            <template v-slot:append v-if="search">
              <q-icon name="close" class="cursor-pointer" @click="search = ''; fetchSales();" />
            </template>
          </q-input>
        </div>

        <!-- Filtro Vendedor -->
        <div class="col-12 col-md-6">
          <q-select
            v-model="selectedUser"
            :options="vendedoresFilterOptions"
            label="Vendedor"
            outlined
            dense
            emit-value
            map-options
            :display-value="selectedUser !== '' && selectedUser !== null ? undefined : 'Todos los Vendedores'"
            :clearable="selectedUser !== '' && selectedUser !== null"
            clear-value=""
            @clear="selectedUser = ''; fetchSales()"
            class="rounded-input"
            @update:model-value="fetchSales"
          >
            <template v-slot:prepend><q-icon name="person" color="primary" /></template>
          </q-select>
        </div>

        <!-- Filtro Método de Pago -->
        <div class="col-12 col-sm-6 col-md-6">
          <q-select
            v-model="metodoPagoFilter"
            :options="[
              { label: 'Todos los Métodos', value: '' },
              { label: 'Efectivo', value: 'efectivo' },
              { label: 'Tarjeta de Débito', value: 'tarjeta_debito' },
              { label: 'Tarjeta de Crédito', value: 'tarjeta_credito' }
            ]"
            label="Método Pago"
            outlined
            dense
            emit-value
            map-options
            :display-value="metodoPagoFilter !== '' && metodoPagoFilter !== null ? undefined : 'Todos los Métodos'"
            :clearable="metodoPagoFilter !== '' && metodoPagoFilter !== null"
            clear-value=""
            @clear="metodoPagoFilter = ''; fetchSales()"
            class="rounded-input"
            @update:model-value="fetchSales"
          >
            <template v-slot:prepend><q-icon name="payment" color="primary" /></template>
          </q-select>
        </div>

        <!-- Filtro Estado -->
        <div class="col-12 col-sm-6 col-md-6">
          <q-select
            v-model="statusFilter"
            :options="[
              { label: 'Todos los Estados', value: '' },
              { label: 'Pagada', value: 'pagada' },
              { label: 'Anulada', value: 'anulada' },
              { label: 'Devolución', value: 'devolucion' },
            ]"
            label="Estado"
            outlined
            dense
            emit-value
            map-options
            :display-value="statusFilter !== '' && statusFilter !== null ? undefined : 'Todos los Estados'"
            :clearable="statusFilter !== '' && statusFilter !== null"
            clear-value=""
            @clear="statusFilter = ''; fetchSales()"
            class="rounded-input"
            @update:model-value="fetchSales"
          >
            <template v-slot:prepend><q-icon name="flag" color="primary" /></template>
          </q-select>
        </div>

        <!-- Fecha Desde -->
        <div class="col-12 col-sm-6 col-md-3">
          <q-input
            v-model="fechaDesde"
            type="date"
            label="Fecha Desde"
            outlined
            dense
            stack-label
            class="rounded-input"
            @update:model-value="fetchSales"
          >
            <template v-slot:prepend><q-icon name="event" color="primary" /></template>
            <template v-slot:append v-if="fechaDesde">
              <q-icon name="close" class="cursor-pointer" @click="fechaDesde = ''; fetchSales();" />
            </template>
          </q-input>
        </div>

        <!-- Fecha Hasta -->
        <div class="col-12 col-sm-6 col-md-3">
          <q-input
            v-model="fechaHasta"
            type="date"
            label="Fecha Hasta"
            outlined
            dense
            stack-label
            class="rounded-input"
            @update:model-value="fetchSales"
          >
            <template v-slot:prepend><q-icon name="event" color="primary" /></template>
            <template v-slot:append v-if="fechaHasta">
              <q-icon name="close" class="cursor-pointer" @click="fechaHasta = ''; fetchSales();" />
            </template>
          </q-input>
        </div>

        <!-- Rango de Monto Total -->
        <div class="col-12 col-sm-6 col-md-2">
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
            @update:model-value="fetchSales"
          >
            <template v-slot:prepend><q-icon name="attach_money" color="primary" /></template>
          </q-input>
        </div>

        <div class="col-12 col-sm-6 col-md-2">
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
            @update:model-value="fetchSales"
          >
            <template v-slot:prepend><q-icon name="attach_money" color="primary" /></template>
          </q-input>
        </div>

        <!-- Limpiar Filtros -->
        <div class="col-12 col-sm-6 col-md-2 row justify-end">
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
        no-data-label="No se encontraron ventas"
      >
        <template v-slot:body-cell-id="props">
          <q-td align="left">
            <span class="text-weight-bold text-primary">
              {{ props.row.serie && props.row.correlativo ? `${props.row.serie}-${props.row.correlativo}` : `V${strPad(`${props.row.id}`, 8, '0', 'STR_PAD_LEFT')}` }}
            </span>
          </q-td>
        </template>

        <template v-slot:body-cell-metodo_pago="props">
          <q-td align="center">
            <q-chip
              :color="getMetodoPagoColor(props.row.metodo_pago)"
              :icon="getMetodoPagoIcon(props.row.metodo_pago)"
              text-color="white"
              :label="getMetodoPagoLabel(props.row.metodo_pago)"
              dense
              class="text-weight-bold"
            />
          </q-td>
        </template>

        <template v-slot:body-cell-total_venta="props">
          <q-td align="right">
            <span class="text-weight-bold text-subtitle2">
              ${{ formatCurrency(props.row.total_venta) }}
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
                  <q-item-section avatar><q-icon name="visibility" color="secondary" /></q-item-section>
                  <q-item-section> Detalles </q-item-section>
                </q-item>
                <q-item clickable v-close-popup @click="printTicket(props.row.id)">
                  <q-item-section avatar><q-icon name="print" color="primary" /></q-item-section>
                  <q-item-section> Imprimir Ticket </q-item-section>
                </q-item>
                <q-item
                  clickable
                  v-close-popup
                  v-if="props.row.status === 'pagada' && authStore.hasPermission('ventas.devolver')"
                  @click="confirmReturn(props.row)"
                >
                  <q-item-section avatar><q-icon name="undo" color="warning" /></q-item-section>
                  <q-item-section> Devolver </q-item-section>
                </q-item>
                <q-item
                  clickable
                  v-close-popup
                  v-if="props.row.status === 'pagada' && authStore.hasPermission('ventas.anular')"
                  @click="confirmCancel(props.row.id)"
                >
                  <q-item-section avatar><q-icon name="cancel" color="negative" /></q-item-section>
                  <q-item-section> Anular </q-item-section>
                </q-item>
              </q-list>
            </q-btn-dropdown>
          </q-td>
        </template>
      </q-table>
    </q-card>

    <!-- Modal para confirmar devolución -->
    <q-dialog v-model="returnOpen">
      <q-card style="width: 400px; max-width: 90vw; border-radius: 12px;">
        <q-card-section class="bg-warning text-white">
          <div class="text-h6 text-weight-bold">¿Confirmar Devolución?</div>
        </q-card-section>
        <q-card-section class="q-pa-md">
          <div class="text-subtitle1 text-weight-medium">Cliente: {{ selectedRow?.cliente }}</div>
          <div class="text-h6 text-weight-bolder text-warning q-mt-xs">Total: ${{ formatCurrency(selectedRow?.total_venta) }}</div>
          <div class="text-caption text-grey-7 q-mt-sm">Esta acción reintegrará las existencias al inventario de la sucursal.</div>
        </q-card-section>
        <q-card-actions align="right" class="q-pa-md bg-grey-1">
          <q-btn flat label="Cancelar" color="grey-7" v-close-popup class="rounded-btn" />
          <q-btn unelevated label="Devolver" color="warning" @click="processReturn" class="rounded-btn text-weight-bold" />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- Modal para confirmar anulación -->
    <q-dialog v-model="confirmOpen">
      <q-card style="width: 400px; max-width: 90vw; border-radius: 12px;">
        <q-card-section class="bg-negative text-white">
          <div class="text-h6 text-weight-bold">¿Seguro que deseas anular esta venta?</div>
        </q-card-section>
        <q-card-section class="q-pa-md">
          <div class="text-subtitle1 text-weight-medium">Cliente: {{ selectedRow?.cliente }}</div>
          <div class="text-h6 text-weight-bolder text-negative q-mt-xs">Total: ${{ formatCurrency(selectedRow?.total_venta) }}</div>
          <div class="text-caption text-grey-7 q-mt-sm">La venta pasará a estado anulada y su kardex será revertido.</div>
        </q-card-section>
        <q-card-actions align="right" class="q-pa-md bg-grey-1">
          <q-btn flat label="Cancelar" color="grey-7" v-close-popup class="rounded-btn" />
          <q-btn unelevated label="Anular Venta" color="negative" @click="processCancel" class="rounded-btn text-weight-bold" />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- Modal para mostrar detalles de la venta -->
    <q-dialog v-model="detailsOpen">
      <q-card style="width: 720px; max-width: 95vw; border-radius: 14px;" class="overflow-hidden">
        <q-card-section class="bg-gradient-brand text-white q-py-md">
          <div class="row items-center justify-between no-wrap">
            <div class="row items-center">
              <q-avatar icon="receipt" size="42px" class="q-mr-sm bg-white-translucent text-white" />
              <div>
                <div class="text-h6 text-weight-bold">Detalle de Venta</div>
                <div class="text-caption opacity-80">
                  Comprobante: {{ detailsRow?.serie ? `${detailsRow.serie}-${detailsRow.correlativo || detailsRow.id}` : `V${strPad(`${detailsRow?.id}`, 8, '0', 'STR_PAD_LEFT')}` }}
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
                <div class="text-caption text-grey-7">Cliente:</div>
                <div class="text-weight-bold text-dark">{{ detailsRow?.cliente || 'Consumidor Final / General' }}</div>
                <div class="text-caption text-grey-6" v-if="detailsRow?.dui">DUI: {{ detailsRow.dui }}</div>
              </div>
            </div>
            <div class="col-12 col-sm-6">
              <div class="bg-grey-1 q-pa-sm rounded-borders border-grey">
                <div class="text-caption text-grey-7">Atendido por:</div>
                <div class="text-weight-bold text-dark">{{ detailsRow?.vendedor || 'N/A' }}</div>
                <div class="text-caption text-grey-6">Fecha: {{ formatDate(detailsRow?.fecha) }}</div>
              </div>
            </div>
          </div>

          <!-- Información del Pago Realizado -->
          <div
            class="q-pa-sm rounded-borders q-mb-md"
            :class="detailsRow?.metodo_pago === 'efectivo' || !detailsRow?.metodo_pago ? 'bg-blue-1 border-blue' : 'bg-indigo-1 border-indigo'"
          >
            <div class="row items-center justify-between">
              <div class="row items-center">
                <q-icon
                  :name="getMetodoPagoIcon(detailsRow?.metodo_pago)"
                  size="28px"
                  :color="getMetodoPagoColor(detailsRow?.metodo_pago)"
                  class="q-mr-sm"
                />
                <div>
                  <div class="text-caption text-weight-bold text-grey-8">FORMA DE PAGO</div>
                  <div class="text-subtitle2 text-weight-bolder" :class="`text-${getMetodoPagoColor(detailsRow?.metodo_pago)}`">
                    {{ getMetodoPagoLabel(detailsRow?.metodo_pago) }}
                  </div>
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

            <!-- Detalle de Efectivo Recibido y Cambio -->
            <div v-if="detailsRow?.metodo_pago === 'efectivo' || !detailsRow?.metodo_pago" class="row q-col-gutter-sm q-mt-xs text-caption">
              <div class="col-6 col-sm-4">
                <span class="text-grey-7">Efectivo Recibido:</span>
                <div class="text-weight-bold text-dark">${{ formatCurrency(detailsRow?.monto_recibido || detailsRow?.total_venta) }}</div>
              </div>
              <div class="col-6 col-sm-4">
                <span class="text-grey-7">Cambio / Vuelto:</span>
                <div class="text-weight-bold text-green-9">${{ formatCurrency(detailsRow?.vuelto || 0) }}</div>
              </div>
              <div class="col-12 col-sm-4">
                <span class="text-grey-7">Modalidad:</span>
                <div class="text-weight-bold text-blue-9">Pago en Efectivo</div>
              </div>
            </div>

            <div v-else class="text-caption text-grey-8 q-mt-xs">
              Transacción procesada mediante terminal POS bancaria con {{ getMetodoPagoLabel(detailsRow?.metodo_pago) }}
            </div>
          </div>

          <!-- Tabla de Productos -->
          <div class="text-subtitle2 text-weight-bold text-grey-8 q-mb-xs">Productos Facturados</div>
          <div class="rounded-borders border-grey overflow-hidden q-mb-md">
            <q-markup-table dense flat separator="horizontal">
              <thead>
                <tr class="bg-grey-2 text-grey-9">
                  <th class="text-left">#</th>
                  <th class="text-left">Producto</th>
                  <th class="text-center">Cant.</th>
                  <th class="text-right">Precio Unit.</th>
                  <th class="text-right">Subtotal</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(detalle, index) in detailsRow?.detalles_venta" :key="detalle.id">
                  <td class="text-caption text-grey-7">{{ index + 1 }}</td>
                  <td>
                    <div class="text-weight-medium">{{ detalle.producto?.nombre || 'Producto' }}</div>
                    <div class="text-caption text-grey-6" v-if="detalle.producto?.sku">SKU: {{ detalle.producto.sku }}</div>
                  </td>
                  <td class="text-center text-weight-bold">{{ formatQuantity(detalle.cantidad) }}</td>
                  <td class="text-right">${{ formatCurrency(detalle.precio_venta) }}</td>
                  <td class="text-right text-weight-bold">${{ formatCurrency(Number(detalle.cantidad) * Number(detalle.precio_venta)) }}</td>
                </tr>
              </tbody>
            </q-markup-table>
          </div>

          <!-- Totales Desglosados -->
          <div class="row justify-end">
            <div style="width: 290px;" class="bg-grey-1 q-pa-sm rounded-borders border-grey">
              <div class="row justify-between text-caption text-grey-8 q-mb-xs" v-if="detailsRow?.subtotal">
                <span>Subtotal Neto:</span>
                <span class="text-weight-bold text-dark">${{ formatCurrency(detailsRow.subtotal) }}</span>
              </div>
              <div class="row justify-between text-caption text-grey-8 q-mb-xs" v-if="detailsRow?.monto_iva > 0">
                <span>IVA ({{ Number(detailsRow.porcentaje_iva_aplicado || 13) }}%):</span>
                <span class="text-weight-bold text-dark">${{ formatCurrency(detailsRow.monto_iva) }}</span>
              </div>
              <div class="row justify-between text-caption text-grey-8 q-mb-xs">
                <span>Total Unidades:</span>
                <span class="text-weight-bold text-dark">{{ detailsRow?.total_productos }}</span>
              </div>
              <q-separator class="q-my-xs" />
              <div class="row justify-between items-center text-subtitle1">
                <span class="text-weight-bold text-grey-9">Total Venta:</span>
                <span class="text-h6 text-weight-bolder text-primary">${{ formatCurrency(detailsRow?.total_venta) }}</span>
              </div>
            </div>
          </div>
        </q-card-section>

        <q-card-actions align="right" class="q-pa-md bg-grey-2">
          <q-btn flat label="Cerrar" color="grey-7" v-close-popup class="rounded-btn" />
          <q-btn
            unelevated
            color="primary"
            icon="print"
            label="Imprimir Ticket"
            class="rounded-btn q-px-md text-weight-bold"
            @click="printTicket(detailsRow?.id)"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script>
import { useAuthStore } from "src/store/auth-store";
import { api } from "boot/axios";
import Alert from "src/helpers/Alert";
import { useLoadingStore } from "src/store/loading-store";

export default {
  computed: {
    authStore() {
      return useAuthStore();
    },
    vendedoresFilterOptions() {
      return [
        { label: "Todos los Vendedores", value: "" },
        ...this.usuariosList.map((u) => ({
          label: u.name ? `${u.name} ${u.lastname || ''}`.trim() : u.username,
          value: u.id,
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
          label: "Comprobante",
          align: "left",
          field: "id",
          sortable: true,
          format: (val, row) =>
            row?.serie && row?.correlativo
              ? `${row.serie}-${row.correlativo}`
              : `V${this.strPad(`${val}`, 8, "0", "STR_PAD_LEFT")}`,
        },
        {
          name: "fecha",
          label: "Fecha",
          align: "left",
          field: "fecha",
          sortable: true,
          format: (val) => this.formatDate(val),
        },
        { name: "cliente", label: "Cliente", align: "left", field: "cliente", sortable: false },
        {
          name: "vendedor",
          label: "Vendedor",
          align: "left",
          field: "vendedor",
          sortable: false,
        },
        {
          name: "metodo_pago",
          label: "Método Pago",
          align: "center",
          field: "metodo_pago",
          sortable: true,
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
          field: "subtotal",
          sortable: true,
          format: (val) => `$${this.formatCurrency(val)}`,
        },
        {
          name: "monto_iva",
          label: "IVA ($)",
          align: "right",
          field: "monto_iva",
          sortable: true,
          format: (val) => `$${this.formatCurrency(val)}`,
        },
        {
          name: "total_venta",
          label: "Total ($)",
          align: "right",
          field: "total_venta",
          sortable: true,
          format: (val) => `$${this.formatCurrency(val)}`,
        },
        { name: "status", label: "Estado", align: "center", field: "status", sortable: true },
        { name: "options", label: "Acciones", align: "right" },
      ],
      selectedRow: null,
      returnOpen: false,
      confirmOpen: false,
      detailsOpen: false,
      detailsRow: null,
      metodoPagoFilter: "",
      statusFilter: "",
      selectedUser: "",
      usuariosList: [],
      fechaDesde: "",
      fechaHasta: "",
      montoMin: "",
      montoMax: "",
    };
  },
  created() {
    this.fetchSales();
    this.fetchUsuarios();
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
        this.fetchSales();
      }
    },
    async fetchUsuarios() {
      try {
        const response = await api.get("/api/usuarios", { params: { limit: -1 } });
        const resData = response.data?.data;
        this.usuariosList = Array.isArray(resData) ? resData : (resData?.data || []);
      } catch (e) {
        console.error("Error cargando usuarios", e);
      }
    },
    clearFilters() {
      this.search = "";
      this.metodoPagoFilter = "";
      this.statusFilter = "";
      this.selectedUser = "";
      this.fechaDesde = "";
      this.fechaHasta = "";
      this.montoMin = "";
      this.montoMax = "";
      this.fetchSales();
    },
    fetchSales() {
      this.onRequest({
        pagination: this.pagination,
        filter: this.search,
      });
    },
    async onRequest(props) {
      const { page, rowsPerPage, sortBy, descending } = props.pagination;
      this.loading = true;
      try {
        const response = await api.get("/api/ventas/historial", {
          params: {
            page: page,
            limit: rowsPerPage === 0 ? -1 : rowsPerPage,
            search: this.search,
            sortBy: sortBy,
            descending: descending,
            metodo_pago: this.metodoPagoFilter || undefined,
            status: this.statusFilter || undefined,
            user_id: this.selectedUser || undefined,
            fecha_desde: this.fechaDesde || undefined,
            fecha_hasta: this.fechaHasta || undefined,
            monto_min: this.montoMin !== null && this.montoMin !== "" ? this.montoMin : undefined,
            monto_max: this.montoMax !== null && this.montoMax !== "" ? this.montoMax : undefined,
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
        Alert("Error", error.response?.data?.message || error.message || "Error al obtener historial", "error");
      } finally {
        this.loading = false;
      }
    },
    formatCurrency(val) {
      if (!val && val !== 0) return "0.00";
      return String(Number(val).toFixed(2)).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    },
    formatQuantity(val) {
      if (val === null || val === undefined || val === "") return "0";
      return String(Math.round(Number(val) || 0)).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
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
    getMetodoPagoLabel(metodo) {
      switch (metodo) {
        case "efectivo":
          return "Efectivo";
        case "tarjeta_debito":
          return "T. Débito";
        case "tarjeta_credito":
          return "T. Crédito";
        default:
          return "Efectivo";
      }
    },
    getMetodoPagoColor(metodo) {
      switch (metodo) {
        case "efectivo":
          return "positive";
        case "tarjeta_debito":
          return "primary";
        case "tarjeta_credito":
          return "purple-8";
        default:
          return "positive";
      }
    },
    getMetodoPagoIcon(metodo) {
      switch (metodo) {
        case "efectivo":
          return "payments";
        case "tarjeta_debito":
          return "credit_card";
        case "tarjeta_credito":
          return "account_balance_wallet";
        default:
          return "payments";
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
    confirmReturn(row) {
      this.selectedRow = row;
      this.returnOpen = true;
    },
    confirmCancel(id) {
      this.selectedRow = this.rows.find((row) => row.id === id);
      this.confirmOpen = true;
    },
    viewDetails(row) {
      this.detailsRow = row;
      this.detailsOpen = true;
    },
    printTicket(ventaId) {
      if (!ventaId) return;
      const iframeId = 'print-ticket-iframe';
      let iframe = document.getElementById(iframeId);
      if (!iframe) {
        iframe = document.createElement('iframe');
        iframe.id = iframeId;
        iframe.style.position = 'fixed';
        iframe.style.right = '0';
        iframe.style.bottom = '0';
        iframe.style.width = '0';
        iframe.style.height = '0';
        iframe.style.border = '0';
        document.body.appendChild(iframe);
      }
      const baseUrl = api.defaults.baseURL || window.location.origin;
      iframe.src = `${baseUrl}/api/ventas/${ventaId}/ticket`;
    },
    async processReturn() {
      this.loading = true;
      try {
        await api.post(`/api/ventas/devolver/${this.selectedRow.id}`);
        this.fetchSales();
        this.returnOpen = false;
        Alert("Éxito", "Devolución realizada con éxito", "success");
      } catch (error) {
        Alert("Error", error.response?.data?.message || error.message || "Error al realizar devolución", "error");
      } finally {
        this.loading = false;
      }
    },
    async processCancel() {
      const loadingStore = useLoadingStore();
      loadingStore.startLoading();
      try {
        await api.post(`/api/ventas/anular/${this.selectedRow.id}`);
        this.fetchSales();
        this.confirmOpen = false;
        Alert("Éxito", "Venta anulada con éxito", "success");
      } catch (error) {
        Alert("Error", error.response?.data?.message || error.message || "Error al anular venta", "error");
      } finally {
        loadingStore.stopLoading();
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
.border-indigo {
  border: 1px solid #c5cae9;
}
</style>
