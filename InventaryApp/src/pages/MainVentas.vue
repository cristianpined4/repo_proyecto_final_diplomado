<template>
  <q-page class="q-pa-md">
    <!-- Header Banner -->
    <q-card class="bg-gradient-brand text-white q-mb-md shadow-2 rounded-card overflow-hidden">
      <q-card-section class="q-py-md">
        <div class="row items-center justify-between no-wrap">
          <div class="row items-center">
            <q-avatar icon="point_of_sale" size="48px" class="q-mr-md bg-white-translucent text-white" />
            <div>
              <div class="text-h5 text-weight-bold">Registrar Venta (POS)</div>
              <div class="text-subtitle2 opacity-80">Genera una venta rápida y emite el ticket de compra térmico automáticamente</div>
            </div>
          </div>
          <!-- Acciones Principales -->
          <q-btn v-if="authStore.hasPermission('ventas.crear')" color="white" text-color="primary" label="Finalizar Venta" icon="done" unelevated class="rounded-btn q-px-md text-weight-bold" @click="openCloseModal" :disable="addedProducts.length === 0" />
        </div>
      </q-card-section>
    </q-card>

    <q-card flat bordered class="rounded-card bg-white">
      <!-- Selector de Cliente -->
      <q-card-section class="q-py-md">
        <div class="text-subtitle2 text-weight-bold text-grey-8 q-mb-xs">1. Seleccione el Cliente</div>
        <q-select
          v-model="selectedClient"
          placeholder="Buscar cliente por Nombre, DUI, NIT o Teléfono..."
          :options="filteredClientOptions"
          :loading="loadingClients"
          use-input
          emit-value
          map-options
          input-debounce="300"
          @filter="filterFnClient"
          filled
          class="rounded-input"
        >
          <template v-slot:no-option>
            <q-item>
              <q-item-section class="text-grey">
                {{ loadingClients ? 'Buscando clientes...' : 'No se encontraron clientes' }}
              </q-item-section>
            </q-item>
          </template>
        </q-select>
      </q-card-section>

      <!-- Agregar Productos -->
      <q-card-section class="q-py-md">
        <div class="text-subtitle2 text-weight-bold text-grey-8 q-mb-xs">2. Agregar Productos al Carrito</div>
        <div class="row q-col-gutter-sm items-center">
          <div class="col-12 col-sm-8">
            <q-select
              v-model="selectedProduct"
              placeholder="Buscar por SKU, Nombre o Código de barras..."
              :options="filteredProductosOptions"
              :loading="loadingProducts"
              use-input
              emit-value
              map-options
              input-debounce="300"
              @filter="filterFnProduct"
              filled
              class="rounded-input"
              @update:model-value="onProductSelected"
            >
              <template v-slot:no-option>
                <q-item>
                  <q-item-section class="text-grey">
                    {{ loadingProducts ? 'Buscando productos...' : 'No se encontraron productos disponibles' }}
                  </q-item-section>
                </q-item>
              </template>
            </q-select>
          </div>
          
          <div class="col-6 col-sm-2">
            <q-input
              v-model.number="quantity"
              type="number"
              label="Cantidad"
              filled
              :disable="!selectedProduct"
              @update:model-value="validateStock"
              class="rounded-input"
            />
          </div>

          <div class="col-6 col-sm-2 text-right">
            <q-btn
              label="Agregar"
              icon="add_shopping_cart"
              color="primary"
              unelevated
              class="full-width rounded-btn q-py-sm"
              @click="addProduct"
              :disable="!selectedProduct || quantity <= 0"
            />
          </div>
        </div>
      </q-card-section>

      <!-- Tabla de Artículos Agregados -->
      <q-table :loading="loading"
        :rows="addedProducts"
        :columns="tableColumns"
        row-key="id"
        flat
        :pagination="{ rowsPerPage: 10 }"
        no-data-label="El carrito de compras está vacío."
      >
        <!-- Condición IVA -->
        <template v-slot:body-cell-iva_condicion="props">
          <q-td align="center">
            <q-chip
              dense
              size="sm"
              :color="props.row.aplica_iva ? 'blue-1' : 'purple-1'"
              :text-color="props.row.aplica_iva ? 'blue-9' : 'purple-9'"
              class="text-weight-bold"
            >
              {{ props.row.aplica_iva ? 'Incluido' : '+ IVA' }}
            </q-chip>
          </q-td>
        </template>

        <!-- Subtotal neto por línea -->
        <template v-slot:body-cell-subtotal="props">
          <q-td align="right">
            ${{ formatCurrency(getItemBreakdown(props.row).subtotal) }}
          </q-td>
        </template>

        <!-- Monto IVA por línea -->
        <template v-slot:body-cell-iva="props">
          <q-td align="right" class="text-grey-8">
            ${{ formatCurrency(getItemBreakdown(props.row).iva) }}
          </q-td>
        </template>

        <!-- Celda total por item -->
        <template v-slot:body-cell-total="props">
          <q-td align="right" class="text-weight-bold text-primary">
            ${{ formatCurrency(getItemBreakdown(props.row).total) }}
          </q-td>
        </template>

        <!-- Acción eliminar -->
        <template v-slot:body-cell-options="props">
          <q-td align="right">
            <q-btn flat round color="negative" icon="delete" size="sm" @click="removeProduct(props.row.id)" />
          </q-td>
        </template>
      </q-table>

      <!-- Resumen de Totales -->
      <q-card-section class="text-right">
        <div class="text-subtitle1 text-grey-8">
          Items agregados: {{ formatQuantity(addedProducts.reduce((acc, p) => acc + p.quantity, 0)) }} uds
        </div>
        <div class="text-subtitle1 text-grey-8" v-if="ivaEnabled">
          Subtotal: ${{ formatCurrency(subtotal) }}
        </div>
        <div class="text-subtitle1 text-grey-8" v-if="ivaEnabled">
          IVA ({{ ivaPercentage }}%): ${{ formatCurrency(totalIva) }}
        </div>
        <div class="text-h5 text-primary text-weight-bold q-mt-sm">
          Total a Cobrar: ${{ formatCurrency(total) }}
        </div>
      </q-card-section>
    </q-card>

    <!-- MODAL CONFIRMAR COBRO Y PAGO -->
    <q-dialog v-model="closeModal" persistent>
      <q-card style="max-width: 500px; width: 95%; border-radius: 14px;">
        <q-card-section class="bg-gradient-brand text-white text-center q-py-md">
          <q-icon name="point_of_sale" size="48px" class="q-mb-xs" />
          <div class="text-h6 text-weight-bold">Finalizar y Cobrar Venta</div>
          <div class="text-caption opacity-80">Seleccione el método de pago e ingrese el efectivo si aplica</div>
        </q-card-section>

        <q-card-section class="q-pa-md">
          <!-- Resumen de Cliente y Totales -->
          <div class="bg-grey-1 q-pa-sm rounded-borders q-mb-md border-blue">
            <div class="row justify-between items-center text-caption text-grey-8 q-mb-xs">
              <span>Cliente:</span>
              <span class="text-weight-bold text-dark">{{ selectedClientName }}</span>
            </div>
            <div class="row justify-between items-center text-caption text-grey-8" v-if="ivaEnabled">
              <span>Subtotal Neto:</span>
              <span class="text-weight-bold text-dark">${{ formatCurrency(subtotal) }}</span>
            </div>
            <div class="row justify-between items-center text-caption text-grey-8" v-if="ivaEnabled">
              <span>IVA ({{ ivaPercentage }}%):</span>
              <span class="text-weight-bold text-dark">${{ formatCurrency(totalIva) }}</span>
            </div>
            <q-separator class="q-my-xs" />
            <div class="row justify-between items-center">
              <span class="text-subtitle1 text-weight-bold text-grey-9">Total a Cobrar:</span>
              <span class="text-h5 text-weight-bolder text-primary">${{ formatCurrency(total) }}</span>
            </div>
          </div>

          <!-- Selector de Método de Pago -->
          <div class="text-subtitle2 text-weight-bold text-grey-8 q-mb-xs">Forma de Pago</div>
          <q-btn-toggle
            v-model="metodoPago"
            spread
            no-caps
            rounded
            unelevated
            toggle-color="primary"
            color="grey-2"
            text-color="grey-8"
            class="q-mb-md shadow-1"
            :options="[
              { label: 'Efectivo', value: 'efectivo', icon: 'payments' },
              { label: 'T. Débito', value: 'tarjeta_debito', icon: 'credit_card' },
              { label: 'T. Crédito', value: 'tarjeta_credito', icon: 'account_balance_wallet' }
            ]"
          />

          <!-- Detalle de Efectivo -->
          <div v-if="metodoPago === 'efectivo'" class="bg-blue-1 q-pa-md rounded-borders border-blue">
            <div class="row items-center justify-between q-mb-xs">
              <span class="text-subtitle2 text-weight-bold text-blue-9">Efectivo Recibido</span>
              <span class="text-caption text-blue-8">Paga con:</span>
            </div>
            <q-input
              v-model="montoRecibido"
              type="text"
              inputmode="decimal"
              filled
              dense
              prefix="$"
              class="text-h6 text-weight-bold bg-white rounded-input"
              placeholder="0.00"
              autofocus
              @blur="formatCashOnBlur"
              @keyup.enter="formatCashOnBlur"
            >
              <template v-slot:append>
                <q-btn
                  flat
                  dense
                  size="sm"
                  color="primary"
                  label="Exacto"
                  class="rounded-btn text-weight-bold"
                  @click="setExactCash"
                />
              </template>
            </q-input>

            <!-- Botones de incremento rápido -->
            <div class="row q-gutter-xs q-mt-xs justify-center">
              <q-btn outline dense size="sm" color="primary" label="+$1" @click="addQuickCash(1)" />
              <q-btn outline dense size="sm" color="primary" label="+$5" @click="addQuickCash(5)" />
              <q-btn outline dense size="sm" color="primary" label="+$10" @click="addQuickCash(10)" />
              <q-btn outline dense size="sm" color="primary" label="+$20" @click="addQuickCash(20)" />
              <q-btn outline dense size="sm" color="primary" label="+$50" @click="addQuickCash(50)" />
              <q-btn outline dense size="sm" color="primary" label="+$100" @click="addQuickCash(100)" />
            </div>

            <!-- Cambio / Vuelto -->
            <div
              class="q-mt-sm q-pa-sm rounded-borders text-center"
              :class="isCashSufficient ? 'bg-green-2 text-green-9' : 'bg-red-2 text-red-9'"
            >
              <div class="text-caption text-weight-bold">
                {{ isCashSufficient ? 'CAMBIO / VUELTO A ENTREGAR' : 'EFECTIVO INSUFICIENTE' }}
              </div>
              <div class="text-h5 text-weight-bolder">
                {{ isCashSufficient ? '$' + formatCurrency(vueltoCalculado) : 'Faltan $' + formatCurrency(efectivoFaltante) }}
              </div>
            </div>
          </div>

          <!-- Detalle de Tarjeta -->
          <div v-else class="bg-grey-1 q-pa-md rounded-borders border-grey text-center">
            <q-icon name="contactless" size="40px" color="primary" class="q-mb-xs" />
            <div class="text-subtitle1 text-weight-bold text-dark">
              {{ metodoPago === 'tarjeta_debito' ? 'Pago con Tarjeta de Débito' : 'Pago con Tarjeta de Crédito' }}
            </div>
            <div class="text-caption text-grey-7">Procese la transacción en la terminal POS bancaria por:</div>
            <div class="text-h5 text-primary text-weight-bolder q-mt-xs">${{ formatCurrency(total) }}</div>
          </div>
        </q-card-section>

        <q-card-actions align="right" class="q-pa-md bg-grey-2">
          <q-btn flat label="Cancelar" color="grey-7" v-close-popup class="rounded-btn" />
          <q-btn
            label="Confirmar y Emitir Ticket"
            color="primary"
            unelevated
            class="rounded-btn q-px-md text-weight-bold"
            :disable="metodoPago === 'efectivo' && !isCashSufficient"
            @click="confirmSale"
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
  data() {
    return {
      loading: false,
      loadingClients: false,
      loadingProducts: false,
      clientsCache: {},
      productsCache: {},
      selectedClient: null,
      selectedProduct: null,
      quantity: 1,
      clientOptions: [],
      filteredClientOptions: [],
      productOptions: [],
      filteredProductosOptions: [],
      addedProducts: [],
      total: 0,
      subtotal: 0,
      totalIva: 0,
      closeModal: false,
      metodoPago: "efectivo",
      montoRecibido: "",
      ivaEnabled: false,
      ivaPercentage: 0,
      columns: [
        { name: "name", label: "Producto", align: "left", field: "name" },
        {
          name: "price",
          label: "Precio Unitario",
          align: "right",
          field: "price",
          format: (val) => `$${this.formatCurrency(val)}`,
        },
        {
          name: "quantity",
          label: "Cantidad",
          align: "center",
          field: "quantity",
          format: (val) => this.formatQuantity(val),
        },
        {
          name: "total",
          label: "Subtotal",
          align: "right",
          field: "total",
        },
        { name: "options", label: "Acciones", align: "right" },
      ],
    };
  },
  computed: {
    authStore() {
      return useAuthStore();
    },
    tableColumns() {
      if (!this.ivaEnabled) {
        return [
          { name: "name", label: "Producto", align: "left", field: "name" },
          {
            name: "price",
            label: "Precio Unitario",
            align: "right",
            field: "price",
            format: (val) => `$${this.formatCurrency(val)}`,
          },
          {
            name: "quantity",
            label: "Cantidad",
            align: "center",
            field: "quantity",
            format: (val) => this.formatQuantity(val),
          },
          {
            name: "total",
            label: "Total",
            align: "right",
          },
          { name: "options", label: "Acciones", align: "right" },
        ];
      }
      return [
        { name: "name", label: "Producto", align: "left", field: "name" },
        {
          name: "price",
          label: "Precio Unit.",
          align: "right",
          field: "price",
          format: (val) => `$${this.formatCurrency(val)}`,
        },
        {
          name: "quantity",
          label: "Cant.",
          align: "center",
          field: "quantity",
          format: (val) => this.formatQuantity(val),
        },
        {
          name: "iva_condicion",
          label: "Condición IVA",
          align: "center",
        },
        {
          name: "subtotal",
          label: "Subtotal (Neto)",
          align: "right",
        },
        {
          name: "iva",
          label: `IVA (${this.ivaPercentage}%)`,
          align: "right",
        },
        {
          name: "total",
          label: "Total Línea",
          align: "right",
        },
        { name: "options", label: "Acciones", align: "right" },
      ];
    },
    selectedClientName() {
      if (this.selectedClient && this.clientsCache[this.selectedClient]) {
        return this.clientsCache[this.selectedClient].nombre;
      }
      const client = this.filteredClientOptions.find(
        (c) => c.value === this.selectedClient
      );
      return client ? client.label : "Consumidor Final / General";
    },
    vueltoCalculado() {
      if (this.metodoPago !== "efectivo") return 0;
      const clean = String(this.montoRecibido || "").replace(/[^0-9.]/g, "");
      const recibido = parseFloat(clean) || 0;
      return Math.max(0, Number((recibido - this.total).toFixed(2)));
    },
    isCashSufficient() {
      if (this.metodoPago !== "efectivo") return true;
      if (!this.montoRecibido || String(this.montoRecibido).trim() === "") return false;
      const clean = String(this.montoRecibido || "").replace(/[^0-9.]/g, "");
      const recibido = parseFloat(clean) || 0;
      return recibido >= Number(this.total.toFixed(2));
    },
    efectivoFaltante() {
      if (this.metodoPago !== "efectivo") return 0;
      const clean = String(this.montoRecibido || "").replace(/[^0-9.]/g, "");
      const recibido = parseFloat(clean) || 0;
      return Math.max(0, Number((this.total - recibido).toFixed(2)));
    },
  },
  created() {
    this.fetchIvaSettings();
    this.filterFnClient("", (callback) => callback && callback());
    this.filterFnProduct("", (callback) => callback && callback());
  },
  methods: {
    formatCurrency(val) {
      if (!val && val !== 0) return "0.00";
      return String(Number(val).toFixed(2)).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    },
    formatQuantity(val) {
      if (val === null || val === undefined || val === "") return "0";
      return String(Math.round(Number(val) || 0)).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    },
    async fetchIvaSettings() {
      try {
        const response = await api.get('/api/settings');
        const settings = response.data.data;
        const ivaEnabledRow = settings.find(s => s.key === 'iva_enabled');
        const ivaPercentageRow = settings.find(s => s.key === 'iva_percentage');
        
        this.ivaEnabled = ivaEnabledRow?.value === 'true';
        this.ivaPercentage = ivaEnabledRow?.value === 'true' ? Number(ivaPercentageRow?.value || 0) : 0;
      } catch (err) {
        console.error("No se pudo cargar la configuración de IVA", err);
      }
    },
    async filterFnClient(val, update, abort) {
      this.loadingClients = true;
      try {
        const response = await api.get("/api/clientes/search", {
          params: { q: val, limit: 30 },
        });
        const clientes = response.data.data || [];
        update(() => {
          this.filteredClientOptions = clientes.map((c) => {
            this.clientsCache[c.id] = c;
            return {
              label: `${c.nombre} (DUI: ${c.dui || "N/A"}${c.telefono ? " - Tel: " + c.telefono : ""})`,
              value: c.id,
            };
          });
        });
      } catch (err) {
        if (abort) abort();
      } finally {
        this.loadingClients = false;
      }
    },
    async filterFnProduct(val, update, abort) {
      this.loadingProducts = true;
      try {
        const response = await api.get("/api/productos/search", {
          params: { q: val, limit: 30, for_sale: true },
        });
        const prods = response.data.data || [];
        update(() => {
          this.filteredProductosOptions = prods.map((p) => {
            this.productsCache[p.id] = p;
            return {
              label: `${p.sku} - ${p.nombre} (Stock: ${this.formatQuantity(p.stock)} - $${this.formatCurrency(p.precio_venta)})`,
              label2: `${p.sku} - ${p.nombre}`,
              value: p.id,
              stock: p.stock,
              price: Number(p.precio_venta) || 0,
              aplica_iva: p.aplica_iva === 1 || p.aplica_iva === true || p.aplica_iva === "1",
            };
          });
        });
      } catch (err) {
        if (abort) abort();
      } finally {
        this.loadingProducts = false;
      }
    },
    onProductSelected() {
      this.quantity = 1;
    },
    validateStock() {
      const selected =
        this.filteredProductosOptions.find((p) => p.value === this.selectedProduct) ||
        this.productsCache[this.selectedProduct];
      if (!selected) return;

      const existingProduct = this.addedProducts.find(
        (p) => p.id === selected.value || p.id === selected.id
      );
      const totalQuantity = existingProduct
        ? existingProduct.quantity + this.quantity
        : this.quantity;

      if (totalQuantity > selected.stock) {
        Alert(
          "Advertencia",
          `La cantidad total (${totalQuantity}) supera el stock disponible (${selected.stock})`,
          "warning"
        );
        this.quantity =
          selected.stock - (existingProduct ? existingProduct.quantity : 0);
      }
    },
    getItemBreakdown(prod) {
      const qty = Number(prod.quantity) || 0;
      const price = Number(prod.price) || 0;
      const rawTotal = qty * price;

      if (!this.ivaEnabled) {
        return {
          subtotal: rawTotal,
          iva: 0,
          total: rawTotal,
          tipo: "Sin IVA",
        };
      }

      const pct = Number(this.ivaPercentage) || 0;
      if (prod.aplica_iva) {
        // TRUE: El precio incluye IVA -> Desglosar
        const subtotal = rawTotal / (1 + (pct / 100));
        const iva = rawTotal - subtotal;
        return {
          subtotal,
          iva,
          total: rawTotal,
          tipo: "Incluido",
        };
      } else {
        // FALSE: El precio no incluye IVA -> Sumar
        const subtotal = rawTotal;
        const iva = subtotal * (pct / 100);
        const total = subtotal + iva;
        return {
          subtotal,
          iva,
          total,
          tipo: "+ IVA",
        };
      }
    },
    updateTotal() {
      let subtotal = 0;
      let totalIva = 0;
      let total = 0;

      this.addedProducts.forEach((prod) => {
        const b = this.getItemBreakdown(prod);
        subtotal += b.subtotal;
        totalIva += b.iva;
        total += b.total;
      });

      this.subtotal = subtotal;
      this.totalIva = totalIva;
      this.total = total;
    },
    addProduct() {
      const selected =
        this.filteredProductosOptions.find((p) => p.value === this.selectedProduct) ||
        (this.productsCache[this.selectedProduct]
          ? {
              value: this.selectedProduct,
              label2: `${this.productsCache[this.selectedProduct].sku} - ${this.productsCache[this.selectedProduct].nombre}`,
              stock: this.productsCache[this.selectedProduct].stock,
              price: Number(this.productsCache[this.selectedProduct].precio_venta) || 0,
              aplica_iva:
                this.productsCache[this.selectedProduct].aplica_iva === 1 ||
                this.productsCache[this.selectedProduct].aplica_iva === true ||
                this.productsCache[this.selectedProduct].aplica_iva === "1",
            }
          : null);

      if (!selected) return;

      const existingProduct = this.addedProducts.find(
        (p) => p.id === selected.value
      );

      const totalQuantity = existingProduct
        ? existingProduct.quantity + this.quantity
        : this.quantity;

      if (totalQuantity > selected.stock) {
        Alert(
          "Advertencia",
          `La cantidad total (${totalQuantity}) supera el stock disponible (${selected.stock})`,
          "warning"
        );
        return;
      }

      if (existingProduct) {
        existingProduct.quantity += this.quantity;
      } else {
        this.addedProducts.push({
          id: selected.value,
          name: selected.label2,
          price: selected.price,
          quantity: this.quantity,
          aplica_iva: selected.aplica_iva,
        });
      }

      this.updateTotal();
      this.selectedProduct = null;
      this.quantity = 1;
    },
    removeProduct(id) {
      this.addedProducts = this.addedProducts.filter((p) => p.id !== id);
      this.updateTotal();
    },
    formatCashOnBlur() {
      if (this.montoRecibido !== null && this.montoRecibido !== undefined && String(this.montoRecibido).trim() !== "") {
        const clean = String(this.montoRecibido).replace(/[^0-9.]/g, "");
        const num = parseFloat(clean);
        if (!isNaN(num) && num >= 0) {
          this.montoRecibido = num.toFixed(2);
        } else {
          this.montoRecibido = "";
        }
      }
    },
    setExactCash() {
      this.montoRecibido = Number(this.total || 0).toFixed(2);
    },
    addQuickCash(amount) {
      const clean = String(this.montoRecibido || "").replace(/[^0-9.]/g, "");
      const current = parseFloat(clean) || 0;
      this.montoRecibido = (current + amount).toFixed(2);
    },
    openCloseModal() {
      if (!this.selectedClient) {
        Alert("Advertencia", "Debe seleccionar un cliente antes de cobrar", "warning");
        return;
      }
      if (this.addedProducts.length === 0) {
        Alert("Advertencia", "El carrito de compras está vacío", "warning");
        return;
      }
      this.metodoPago = "efectivo";
      this.montoRecibido = "";
      this.closeModal = true;
    },
    async confirmSale() {
      const loadingStore = useLoadingStore();
      Alert("Procesando...", "Registrando venta y generando ticket...", "info");
      loadingStore.startLoading();
      try {
        const cleanRecibido = String(this.montoRecibido || "").replace(/[^0-9.]/g, "");
        const recibido = parseFloat(cleanRecibido) || 0;
        const data = {
          cliente_id: this.selectedClient,
          metodo_pago: this.metodoPago,
          monto_recibido: this.metodoPago === 'efectivo' ? recibido : Number(this.total),
          vuelto: this.vueltoCalculado,
          productos: this.addedProducts.map((product) => ({
            producto_id: product.id,
            cantidad: product.quantity,
            precio_venta: product.price,
          }))
        };
        const response = await api.post("/api/ventas", data);
        
        Alert("Éxito", "Venta confirmada con éxito. Emitiendo ticket...", "success");
        
        // Auto-imprimir ticket
        if (response.data && response.data.venta_id) {
          this.printTicket(response.data.venta_id);
        }

        this.resetSaleForm();
        this.productsCache = {};
        this.filterFnProduct("", (callback) => callback && callback());
      } catch (error) {
        Alert("Error", error.response?.data?.message || error.message || "Ocurrió un error al registrar la venta", "error");
      } finally {
        loadingStore.stopLoading();
      }
    },
    resetSaleForm() {
      this.addedProducts = [];
      this.updateTotal();
      this.selectedClient = null;
      this.selectedProduct = null;
      this.quantity = 1;
      this.metodoPago = "efectivo";
      this.montoRecibido = "";
      this.closeModal = false;
    },
    printTicket(ventaId) {
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
    }
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
.border-blue {
  border: 1px solid #bbdefb;
}
</style>
