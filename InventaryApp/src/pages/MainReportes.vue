<template>
  <q-page class="q-pa-md">
        <!-- Header Banner (no-print) -->
    <q-card class="bg-gradient-brand text-white q-mb-md shadow-2 rounded-card overflow-hidden no-print">
      <q-card-section class="q-py-md">
        <div class="row items-center justify-between no-wrap">
          <div class="row items-center">
            <q-avatar icon="analytics" size="48px" class="q-mr-md bg-white-translucent text-white" />
            <div>
              <div class="text-h5 text-weight-bold">Módulo de Reportes de Negocio</div>
              <div class="text-subtitle2 opacity-80 row items-center q-gutter-x-sm">
                <span>Genera informes fiscales, de ventas, compras y rotación de inventarios</span>
                <q-badge color="white" text-color="primary" class="text-weight-bold q-pa-xs">
                  <q-icon name="layers" size="14px" class="q-mr-xs" />
                  Método: {{ settingsStore.valuationMethodShort }}
                </q-badge>
              </div>
            </div>
          </div>
          <!-- CONTROLES DE FECHA Y EXPORTACIONES -->
          <div class="row q-gutter-sm items-center bg-white-translucent q-pa-sm rounded-borders">
            <q-input dark color="white" v-model="startDate" type="date" label="Desde" dense outlined style="width: 140px;" @update:model-value="fetchActiveReport" />
            <q-input dark color="white" v-model="endDate" type="date" label="Hasta" dense outlined style="width: 140px;" @update:model-value="fetchActiveReport" />
            
            <q-btn-dropdown color="white" text-color="primary" label="Exportar" icon="download" unelevated class="rounded-btn text-weight-bold q-ml-sm" :disable="activeTab === 'dashboard'">
              <q-list>
                <q-item clickable v-close-popup @click="exportReportCsv">
                  <q-item-section avatar><q-icon name="border_all" color="green" /></q-item-section>
                  <q-item-section>Excel / CSV</q-item-section>
                </q-item>
                <q-item clickable v-close-popup @click="printReportPdf">
                  <q-item-section avatar><q-icon name="picture_as_pdf" color="red" /></q-item-section>
                  <q-item-section>Imprimir PDF</q-item-section>
                </q-item>
              </q-list>
            </q-btn-dropdown>
          </div>
        </div>
      </q-card-section>
    </q-card>

    <!-- TABS DE REPORTES (no-print) -->
    <q-card class="q-mb-md no-print shadow-3 rounded-borders">
      <q-tabs
        v-model="activeTab"
        dense
        class="text-grey-8"
        active-color="primary"
        indicator-color="primary"
        align="left"
        narrow-indicator
        outside-arrows
        mobile-arrows
        @update:model-value="fetchActiveReport"
      >
        <q-tab name="dashboard" icon="dashboard" label="Resumen Kpis" />
        <q-tab name="ventas-sucursal" icon="store" label="Ventas Sucursal" />
        <q-tab name="ventas-vendedor" icon="badge" label="Ventas Vendedor" />
        <q-tab name="rentabilidad" icon="payments" label="Rentabilidad Productos" />
        <q-tab name="rotacion" icon="loop" label="Rotación Inventario" />
        <q-tab name="compras-proveedor" icon="local_shipping" label="Compras Proveedor" />
        <q-tab name="stock-bajo" icon="warning" label="Stock Crítico" />
        <q-tab name="iva" icon="gavel" label="Impuestos IVA" />
        <q-tab name="ventas-categoria" icon="category" label="Ventas Categoría" />
        <q-tab name="kardex-consolidado" icon="menu_book" label="Libro Kardex" />
      </q-tabs>
    </q-card>

    <!-- REPORT VIEWS CONTAINER -->
    <div>
      <div v-if="loading" class="row justify-center q-my-xl no-print">
        <q-spinner size="60px" color="primary" />
      </div>

      <div v-else>
        <!-- 1. RESUMEN KPIS VIEW -->
        <div v-if="activeTab === 'dashboard'" class="row q-col-gutter-md">
          <!-- KPI Cards -->
          <div class="col-12 col-sm-6 col-md-3">
            <q-card class="bg-primary text-white shadow-3 rounded-borders hover-scale">
              <q-card-section>
                <div class="text-subtitle2 opacity-80 uppercase">Ventas de Hoy</div>
                <div class="text-h4 text-weight-bold">${{ formatCurrency(metrics.ventas_hoy) }}</div>
                <q-icon name="today" class="absolute-bottom-right opacity-20 q-mr-sm q-mb-xs" size="64px" />
              </q-card-section>
            </q-card>
          </div>

          <div class="col-12 col-sm-6 col-md-3">
            <q-card class="bg-positive text-white shadow-3 rounded-borders hover-scale">
              <q-card-section>
                <div class="text-subtitle2 opacity-80 uppercase">Ventas del Mes</div>
                <div class="text-h4 text-weight-bold">${{ formatCurrency(metrics.ventas_mes) }}</div>
                <q-icon name="calendar_month" class="absolute-bottom-right opacity-20 q-mr-sm q-mb-xs" size="64px" />
              </q-card-section>
            </q-card>
          </div>

          <div class="col-12 col-sm-6 col-md-3">
            <q-card class="bg-info text-white shadow-3 rounded-borders hover-scale">
              <q-card-section>
                <div class="text-subtitle2 opacity-80 uppercase">Ventas del Año</div>
                <div class="text-h4 text-weight-bold">${{ formatCurrency(metrics.ventas_anio) }}</div>
                <q-icon name="trending_up" class="absolute-bottom-right opacity-20 q-mr-sm q-mb-xs" size="64px" />
              </q-card-section>
            </q-card>
          </div>

          <div class="col-12 col-sm-6 col-md-3">
            <q-card class="bg-amber-9 text-white shadow-3 rounded-borders hover-scale">
              <q-card-section>
                <div class="text-subtitle2 opacity-80 uppercase">Utilidad Bruta (Mes)</div>
                <div class="text-h4 text-weight-bold">${{ formatCurrency(metrics.utilidad_mes) }}</div>
                <q-icon name="savings" class="absolute-bottom-right opacity-20 q-mr-sm q-mb-xs" size="64px" />
              </q-card-section>
            </q-card>
          </div>

          <!-- Top Products -->
          <div class="col-12 col-md-6">
            <q-card class="shadow-3 rounded-borders">
              <q-card-section class="bg-grey-1 q-py-sm">
                <div class="text-h6 text-grey-8 row items-center">
                  <q-icon name="star" color="primary" class="q-mr-xs" />
                  Productos Más Vendidos (Mes)
                </div>
              </q-card-section>
              <q-separator />
              <q-list separator>
                <q-item v-for="(prod, index) in metrics.top_productos" :key="index">
                  <q-item-section avatar>
                    <q-avatar color="primary" text-color="white" size="30px">{{ index + 1 }}</q-avatar>
                  </q-item-section>
                  <q-item-section>
                    <q-item-label class="text-weight-bold">{{ prod.nombre }}</q-item-label>
                    <q-item-label caption>Unidades vendidas: {{ formatQuantity(prod.total_vendido) }}</q-item-label>
                  </q-item-section>
                </q-item>
                <q-item v-if="!metrics.top_productos || metrics.top_productos.length === 0">
                  <q-item-section class="text-grey text-center q-py-md">Sin datos comerciales este mes</q-item-section>
                </q-item>
              </q-list>
            </q-card>
          </div>

          <!-- Top Customers -->
          <div class="col-12 col-md-6">
            <q-card class="shadow-3 rounded-borders">
              <q-card-section class="bg-grey-1 q-py-sm">
                <div class="text-h6 text-grey-8 row items-center">
                  <q-icon name="favorite" color="negative" class="q-mr-xs" />
                  Mejores Clientes (Mes)
                </div>
              </q-card-section>
              <q-separator />
              <q-list separator>
                <q-item v-for="(client, index) in metrics.top_clientes" :key="index">
                  <q-item-section avatar>
                    <q-avatar color="secondary" text-color="white" size="30px">{{ index + 1 }}</q-avatar>
                  </q-item-section>
                  <q-item-section>
                    <q-item-label class="text-weight-bold">{{ client.nombre }}</q-item-label>
                    <q-item-label caption>Volumen comprado: ${{ formatCurrency(client.total_comprado) }}</q-item-label>
                  </q-item-section>
                </q-item>
                <q-item v-if="!metrics.top_clientes || metrics.top_clientes.length === 0">
                  <q-item-section class="text-grey text-center q-py-md">Sin datos de compras este mes</q-item-section>
                </q-item>
              </q-list>
            </q-card>
          </div>

          <!-- Inventory Alerts -->
          <div class="col-12">
            <q-card class="bg-red-1 border-red text-red-9 shadow-3 rounded-borders q-pa-sm">
              <q-card-section class="row items-center justify-between no-wrap">
                <div class="row items-center">
                  <q-icon name="warning" size="36px" class="q-mr-md" />
                  <div>
                    <div class="text-weight-bold text-subtitle1">Alertas de Stock Crítico</div>
                    <div>Hay <strong>{{ formatQuantity(metrics.stock_bajo) }}</strong> productos en almacén que están por debajo de la reserva mínima.</div>
                  </div>
                </div>
                <q-btn label="Ver Alerta" color="negative" unelevated class="rounded-btn" @click="activeTab = 'stock-bajo'; fetchActiveReport()" />
              </q-card-section>
            </q-card>
          </div>
        </div>

        <div v-else class="print-block">
          <q-card class="shadow-5 rounded-borders q-pa-md">
            <div class="text-h6 text-primary text-weight-bold q-mb-sm uppercase row justify-between items-center">
              <span>{{ reportTitle }}</span>
              <span class="text-caption text-grey-8 font-normal lowercase no-print">
                Rango: {{ startDate }} al {{ endDate }}
              </span>
            </div>
            <q-separator class="q-mb-md" />

            <!-- Filtros locales interactivos -->
            <div class="row q-col-gutter-sm items-center q-mb-md no-print">
              <div class="col-12 col-sm-6 col-md-4">
                <q-input
                  v-model="filterText"
                  placeholder="Buscar en el reporte..."
                  dense
                  filled
                  clearable
                >
                  <template v-slot:append>
                    <q-icon name="search" />
                  </template>
                </q-input>
              </div>
              <div v-if="activeTab === 'ventas-sucursal'" class="col-12 col-sm-6 col-md-3">
                <q-input
                  v-model="filterMinTotal"
                  type="text"
                  inputmode="decimal"
                  label="Venta Mínima ($)"
                  placeholder="0.00"
                  dense
                  filled
                  clearable
                  @blur="formatMinTotalOnBlur"
                  @keyup.enter="formatMinTotalOnBlur"
                />
              </div>
              <div v-if="activeTab === 'stock-bajo' || activeTab === 'rentabilidad'" class="col-12 col-sm-6 col-md-3">
                <q-select
                  v-model="filterCategory"
                  :options="categoriesList"
                  label="Filtrar por Categoría"
                  dense
                  filled
                  clearable
                />
              </div>
            </div>

            <q-table :loading="loading"
              :rows="filteredReportData"
              :columns="reportColumns"
              row-key="id"
              flat
              bordered
              v-model:pagination="tablePagination"
              binary-state-sort
              no-data-label="No se generaron registros para este reporte en el rango de fechas seleccionado."
            />
          </q-card>
        </div>
      </div>
    </div>
  </q-page>
</template>

<script>
import { useAuthStore } from "src/store/auth-store";
import { useSettingsStore } from "src/store/settings-store";
import { api } from "boot/axios";
import { useLoadingStore } from "src/store/loading-store";
import Alert from "src/helpers/Alert";
import { date } from 'quasar';

export default {
  name: "MainReportes",
  data() {
    return {
      activeTab: "dashboard",
      loading: false,
      startDate: date.formatDate(date.startOfDate(new Date(), 'month'), 'YYYY-MM-DD'),
      endDate: date.formatDate(new Date(), 'YYYY-MM-DD'),
      metrics: {
        ventas_hoy: 0,
        ventas_mes: 0,
        ventas_anio: 0,
        utilidad_mes: 0,
        total_productos: 0,
        stock_bajo: 0,
        top_clientes: [],
        top_productos: []
      },
      reportData: [],
      reportColumns: [],
      reportTitle: "",
      filterText: "",
      filterMinTotal: null,
      filterCategory: "",
      categoriesList: [],
      tablePagination: {
        sortBy: null,
        descending: false,
        page: 1,
        rowsPerPage: 25,
      },
    };
  },
  created() {
    this.fetchActiveReport();
    this.fetchCategories();
  },
  computed: {
    authStore() {
      return useAuthStore();
    },
    settingsStore() {
      return useSettingsStore();
    },
    filteredReportData() {
      let data = Array.isArray(this.reportData) ? [...this.reportData] : [];
      const text = (this.filterText || '').toLowerCase().trim();
      
      if (text) {
        data = data.filter(row => {
          return Object.entries(row).some(([key, val]) => {
            if (key === 'logo') return false;
            return String(val).toLowerCase().includes(text);
          });
        });
      }

      if (this.activeTab === 'ventas-sucursal' && this.filterMinTotal) {
        data = data.filter(row => Number(row.total) >= Number(this.filterMinTotal));
      }

      if ((this.activeTab === 'stock-bajo' || this.activeTab === 'rentabilidad') && this.filterCategory) {
        const cat = this.filterCategory.toLowerCase();
        data = data.filter(row => {
          const categoryField = row.categoria || '';
          return categoryField.toLowerCase().includes(cat);
        });
      }

      return data;
    }
  },
  methods: {
    formatMinTotalOnBlur() {
      if (this.filterMinTotal !== null && this.filterMinTotal !== undefined && String(this.filterMinTotal).trim() !== "") {
        const clean = String(this.filterMinTotal).replace(/[^0-9.]/g, "");
        const num = parseFloat(clean);
        if (!isNaN(num) && num >= 0) {
          this.filterMinTotal = num.toFixed(2);
        } else {
          this.filterMinTotal = "";
        }
      }
    },
    formatCurrency(val) {
      if (val === null || val === undefined || val === "") return "0.00";
      return String(Number(val).toFixed(2)).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    },
    formatQuantity(val) {
      if (val === null || val === undefined || val === "") return "0";
      return String(Math.round(Number(val) || 0)).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    },
    formatSignedQuantity(val) {
      if (val === null || val === undefined || val === "") return "0";
      const num = Math.round(Number(val) || 0);
      const formatted = String(Math.abs(num)).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
      return num > 0 ? `+${formatted}` : (num < 0 ? `-${formatted}` : "0");
    },
    async fetchActiveReport() {
      if (this.activeTab === 'dashboard') {
        this.fetchDashboardMetrics();
        return;
      }

      this.loading = true;
      try {
        let endpoint = "";
        let cols = [];
        let title = "";

        switch (this.activeTab) {
          case 'ventas-sucursal':
            endpoint = '/api/reportes/ventas-sucursal';
            title = "Reporte de Ventas por Sucursal";
            cols = [
              { name: "sucursal", label: "Sucursal", align: "left", field: "sucursal", sortable: true },
              { name: "cantidad_transacciones", label: "No. Ventas", align: "center", field: "cantidad_transacciones", format: val => this.formatQuantity(val), sortable: true },
              { name: "subtotal", label: "Subtotal", align: "right", field: "subtotal", format: val => `$${this.formatCurrency(val)}`, sortable: true },
              { name: "iva", label: "IVA", align: "right", field: "iva", format: val => `$${this.formatCurrency(val)}` },
              { name: "total", label: "Total", align: "right", field: "total", format: val => `$${this.formatCurrency(val)}`, sortable: true },
              { name: "promedio", label: "Ticket Prom.", align: "right", field: row => `$${this.formatCurrency(Number(row.total) / Number(row.cantidad_transacciones || 1))}` }
            ];
            break;
          case 'ventas-vendedor':
            endpoint = '/api/reportes/ventas-vendedor';
            title = "Reporte de Ventas por Vendedor";
            cols = [
              { name: "vendedor", label: "Vendedor", align: "left", field: "vendedor", sortable: true },
              { name: "transacciones", label: "Transacciones", align: "center", field: "transacciones", format: val => this.formatQuantity(val), sortable: true },
              { name: "total", label: "Total Vendido", align: "right", field: "total", format: val => `$${this.formatCurrency(val)}`, sortable: true },
              { name: "promedio", label: "Ticket Prom.", align: "right", field: row => `$${this.formatCurrency(Number(row.total) / Number(row.transacciones || 1))}` }
            ];
            break;
          case 'rentabilidad':
            endpoint = '/api/reportes/rentabilidad';
            title = "Reporte de Rentabilidad y Margen de Utilidad";
            cols = [
              { name: "sku", label: "SKU", align: "left", field: "sku" },
              { name: "producto", label: "Producto", align: "left", field: "producto", sortable: true },
              { name: "unidades_vendidas", label: "Uds. Vendidas", align: "center", field: "unidades_vendidas", format: val => this.formatQuantity(val), sortable: true },
              { name: "ingresos", label: "Ingresos Brutos", align: "right", field: "ingresos", format: val => `$${this.formatCurrency(val)}`, sortable: true },
              { name: "costo_total", label: "Costo de Venta (COGS)", align: "right", field: "costo_total", format: val => `$${this.formatCurrency(val)}` },
              { name: "utilidad", label: "Utilidad", align: "right", field: "utilidad", format: val => `$${this.formatCurrency(val)}`, sortable: true },
              { name: "margen_porcentaje", label: "Margen (%)", align: "center", field: "margen_porcentaje", format: val => `${val}%`, sortable: true },
              { name: "precio_prom", label: "Precio Prom. Venta", align: "right", field: row => `$${this.formatCurrency(Number(row.ingresos) / Number(row.unidades_vendidas || 1))}` },
              { name: "costo_prom", label: "Costo Unit. (" + this.settingsStore.valuationMethodShort + ")", align: "right", field: row => `$${this.formatCurrency(Number(row.costo_total) / Number(row.unidades_vendidas || 1))}` }
            ];
            break;
          case 'rotacion':
            endpoint = '/api/reportes/rotacion';
            title = "Reporte de Rotación de Inventario";
            cols = [
              { name: "sku", label: "SKU", align: "left", field: "sku" },
              { name: "producto", label: "Producto", align: "left", field: "producto", sortable: true },
              { name: "stock_actual", label: "Stock Actual", align: "center", field: "stock_actual", format: val => this.formatQuantity(val), sortable: true },
              { name: "costo_promedio", label: "Costo Unit. (" + this.settingsStore.valuationMethodShort + ")", align: "right", field: "costo_promedio", format: val => `$${this.formatCurrency(val)}` },
              { name: "unidades_vendidas", label: "Uds. Vendidas", align: "center", field: "unidades_vendidas", format: val => this.formatQuantity(val), sortable: true },
              { name: "ratio_rotacion", label: "Ratio de Rotación", align: "center", field: "ratio_rotacion", format: val => `${Number(val || 0).toFixed(2)}x`, sortable: true },
              { name: "valor_inv", label: "Valor Inventario", align: "right", field: row => `$${this.formatCurrency(Number(row.stock_actual) * Number(row.costo_promedio || 0))}` }
            ];
            break;
          case 'compras-proveedor':
            endpoint = '/api/reportes/compras-proveedor';
            title = "Reporte de Compras por Proveedor";
            cols = [
              { name: "proveedor", label: "Proveedor", align: "left", field: "proveedor", sortable: true },
              { name: "nit", label: "RFC / NIT", align: "left", field: "nit" },
              { name: "compras_realizadas", label: "Facturas", align: "center", field: "compras_realizadas", format: val => this.formatQuantity(val), sortable: true },
              { name: "subtotal", label: "Subtotal", align: "right", field: "subtotal", format: val => `$${this.formatCurrency(val)}` },
              { name: "total", label: "Total Compras", align: "right", field: "total", format: val => `$${this.formatCurrency(val)}`, sortable: true },
              { name: "promedio", label: "Costo Prom. Compra", align: "right", field: row => `$${this.formatCurrency(Number(row.total) / Number(row.compras_realizadas || 1))}` }
            ];
            break;
          case 'stock-bajo':
            endpoint = '/api/reportes/stock-bajo';
            title = "Alerta de Productos con Stock Crítico";
            cols = [
              { name: "sku", label: "SKU", align: "left", field: "sku" },
              { name: "producto", label: "Producto", align: "left", field: "producto", sortable: true },
              { name: "categoria", label: "Categoría", align: "left", field: "categoria" },
              { name: "stock", label: "Stock Actual", align: "center", field: "stock", format: val => this.formatQuantity(val), sortable: true, classes: 'bg-red-1 text-red text-weight-bold' },
              { name: "costo_promedio", label: "Costo Unit. (" + this.settingsStore.valuationMethodShort + ")", align: "right", field: "costo_promedio", format: val => `$${this.formatCurrency(val)}` },
              { name: "precio_venta", label: "Precio Venta", align: "right", field: "precio_venta", format: val => `$${this.formatCurrency(val)}` },
              { name: "valor_inv", label: "Valor Inventario", align: "right", field: row => `$${this.formatCurrency(Number(row.stock) * Number(row.costo_promedio || 0))}` },
              { name: "margen", label: "Margen Est. Unit.", align: "right", field: row => `$${this.formatCurrency(Number(row.precio_venta) - Number(row.costo_promedio || 0))}` }
            ];
            break;
          case 'iva':
            endpoint = '/api/reportes/iva';
            title = "Reporte de Impuestos del Periodo (Balance IVA)";
            cols = [
              { name: "concepto", label: "Concepto", align: "left", field: "concepto" },
              { name: "monto", label: "Monto", align: "right", field: "monto", format: val => `$${this.formatCurrency(val)}` }
            ];
            break;
          case 'ventas-categoria':
            endpoint = '/api/reportes/ventas-categoria';
            title = "Reporte de Ventas por Categoría";
            cols = [
              { name: "categoria", label: "Categoría", align: "left", field: "categoria", sortable: true },
              { name: "transacciones", label: "Transacciones", align: "center", field: "transacciones", format: val => this.formatQuantity(val), sortable: true },
              { name: "productos_vendidos", label: "Cant. Vendida", align: "center", field: "productos_vendidos", format: val => this.formatQuantity(val), sortable: true },
              { name: "total_venta", label: "Total Ventas", align: "right", field: "total_venta", format: val => `$${this.formatCurrency(val)}`, sortable: true },
              { name: "promedio", label: "Venta Prom. Transac.", align: "right", field: row => `$${this.formatCurrency(Number(row.total_venta) / Number(row.transacciones || 1))}` }
            ];
            break;
          case 'kardex-consolidado':
            endpoint = '/api/reportes/kardex-consolidado';
            title = "Libro Diario de Movimientos e Historial de Kardex";
            cols = [
              { name: "fecha", label: "Fecha/Hora", align: "left", field: "fecha", format: val => date.formatDate(val, 'DD/MM/YYYY HH:mm'), sortable: true },
              { name: "sku", label: "SKU", align: "left", field: "sku" },
              { name: "producto", label: "Producto", align: "left", field: "producto" },
              { name: "cantidad_antes", label: "Stock Ant.", align: "center", field: "cantidad_antes", format: val => this.formatQuantity(val) },
              { name: "cantidad_movimiento", label: "Movimiento", align: "center", field: "cantidad_movimiento", format: val => this.formatSignedQuantity(val) },
              { name: "costo_movimiento", label: "Costo Mov.", align: "right", field: "costo_movimiento", format: val => val ? `$${this.formatCurrency(val)}` : 'N/A' },
              { name: "cantidad_despues", label: "Stock Post.", align: "center", field: "cantidad_despues", format: val => this.formatQuantity(val) },
              { name: "costo_despues", label: "Costo Post.", align: "right", field: "costo_despues", format: val => `$${this.formatCurrency(val)}` },
              { name: "tipo", label: "Tipo Mov.", align: "center", field: row => Number(row.cantidad_movimiento) > 0 ? 'Entrada' : 'Salida' }
            ];
            break;
        }

        const response = await api.get(endpoint, {
          params: { start_date: this.startDate, end_date: this.endDate }
        });

        if (response.data.success) {
          this.reportTitle = title;
          this.reportColumns = cols;
          
          if (this.activeTab === 'rotacion') {
            this.tablePagination.sortBy = 'ratio_rotacion';
            this.tablePagination.descending = true;
          } else {
            this.tablePagination.sortBy = null;
            this.tablePagination.descending = false;
          }

          if (this.activeTab === 'iva') {
            const r = response.data.data;
            this.reportData = [
              { concepto: 'Base Imponible de Ventas', monto: r.base_ventas },
              { concepto: 'IVA Débito Fiscal (Cobrado en Ventas)', monto: r.iva_debito },
              { concepto: 'Base Imponible de Compras', monto: r.base_compras },
              { concepto: 'IVA Crédito Fiscal (Pagado en Compras)', monto: r.iva_credito },
              { concepto: 'Diferencia de Operaciones (Base Ventas - Base Compras)', monto: r.base_ventas - r.base_compras },
              { concepto: r.iva_por_pagar >= 0 ? 'IVA Neto a Pagar a Fisco' : 'Saldo a Favor del Período', monto: Math.abs(r.iva_por_pagar) }
            ];
          } else {
            this.reportData = response.data.data;
          }
        }
      } catch (err) {
        Alert("Error", "No se pudo generar el reporte.", "error");
      } finally {
        this.loading = false;
      }
    },
    async fetchCategories() {
      try {
        const res = await api.get('/api/categorias', { params: { limit: -1 } });
        const list = res.data.data.data || res.data.data || [];
        this.categoriesList = list.map(c => c.name || c.nombre);
      } catch (e) {
        console.error("No se pudieron cargar las categorías para los filtros", e);
      }
    },
    async fetchDashboardMetrics() {
      this.loading = true;
      try {
        const response = await api.get("/api/reportes/dashboard");
        if (response.data.success) {
          this.metrics = response.data.data;
        }
      } catch (error) {
        Alert("Error", "No se pudieron obtener las métricas resumidas.", "error");
      } finally {
        this.loading = false;
      }
    },
    async exportReportCsv() {
      useLoadingStore().startLoading();
      try {
        const response = await api.get(`/api/reportes/${this.activeTab}`, {
          params: {
            start_date: this.startDate,
            end_date: this.endDate,
            export: 'csv'
          },
          responseType: 'blob'
        });
        
        const blob = new Blob([response.data], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.setAttribute('download', `reporte_${this.activeTab}_${this.startDate}_${this.endDate}.csv`);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
      } catch (error) {
        Alert("Error", "Ocurrió un error al exportar el archivo CSV", "error");
      } finally {
        useLoadingStore().stopLoading();
      }
    },
    printReportPdf() {
      // Abre el cuadro de diálogo de impresión del navegador
      window.print();
    }
  }
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

.border-red {
  border: 1px solid #ffcdd2;
}

.bg-gradient {
  background: linear-gradient(135deg, var(--q-primary) 0%, var(--q-secondary) 100%);
}

.hover-scale {
  transition: transform 0.2s ease, box-shadow 0.2s ease;
  &:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12) !important;
  }
}
</style>
