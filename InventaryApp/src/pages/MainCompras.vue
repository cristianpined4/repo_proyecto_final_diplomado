<template>
  <q-page class="q-pa-md">
    <!-- Header Banner -->
    <q-card class="bg-gradient-brand text-white q-mb-md shadow-2 rounded-card overflow-hidden">
      <q-card-section class="q-py-md">
        <div class="row items-center justify-between no-wrap">
          <div class="row items-center">
            <q-avatar icon="shopping_cart" size="48px" class="q-mr-md bg-white-translucent text-white" />
            <div>
              <div class="text-h5 text-weight-bold">Registrar Compra</div>
              <div class="text-subtitle2 opacity-80">Agrega productos al inventario ingresando los datos del proveedor y factura</div>
            </div>
          </div>
          <!-- Acciones Principales -->
          <q-btn v-if="authStore.hasPermission('compras.crear')" color="white" text-color="primary" label="Finalizar Compra" icon="done" unelevated class="rounded-btn q-px-md text-weight-bold" @click="openCloseModal" :disable="addedProducts.length === 0" />
        </div>
      </q-card-section>
    </q-card>

    <q-card flat bordered class="rounded-card bg-white">


      <!-- Selector de Proveedor -->
      <q-card-section class="q-mt-md">
        <q-select
          v-model="proveedorId"
          :options="proveedoresOptions"
          :loading="loadingProveedores"
          use-input
          emit-value
          map-options
          input-debounce="300"
          @filter="filterFnProveedores"
          option-value="id"
          option-label="nombre"
          label="Seleccionar Proveedor (Escribe para buscar...)"
          filled
          style="width: 100%"
        >
          <template v-slot:no-option>
            <q-item>
              <q-item-section class="text-grey">
                {{ loadingProveedores ? "Buscando proveedores..." : "No se encontraron proveedores" }}
              </q-item-section>
            </q-item>
          </template>
        </q-select>
        <q-input
          v-model="numeroFactura"
          label="Número de Factura"
          filled
          class="q-mt-md"
          style="width: 100%"
        />
      </q-card-section>

      <!-- Selector de Producto, Precio Unitario y Cantidad -->
      <q-card-section
        class="cliclo"
        style="
          display: flex;
          flex-direction: row;
          justify-content: space-between;
          align-items: center;
          gap: 1rem;
        "
      >
        <div style="display: flex; flex-direction: row; width: 100%; gap: 1rem">
          <q-select
            v-model="selectedProduct"
            label="Buscar Producto por Nombre, SKU o Código..."
            :options="filteredProductosOptions"
            :loading="loadingProducts"
            use-input
            emit-value
            map-options
            input-debounce="300"
            @filter="filterFnProduct"
            @update:model-value="onProductSelected"
            filled
            class="q-mb-md"
            style="width: 100%; max-width: 400px"
          >
            <template v-slot:no-option>
              <q-item>
                <q-item-section class="text-grey">
                  {{ loadingProducts ? "Buscando productos..." : "No se encontraron productos" }}
                </q-item-section>
              </q-item>
            </template>
          </q-select>
          <q-input
            v-model.number="unitPrice"
            type="number"
            step="0.01"
            label="Precio Unitario"
            filled
            class="q-mb-md"
            style="width: 100%; max-width: 150px"
          />
          <q-input
            v-model.number="quantity"
            type="number"
            label="Cantidad"
            filled
            class="q-mb-md"
            style="width: 100%; max-width: 100px"
          />
        </div>
        <q-btn
          label="Agregar Producto"
          color="primary"
          style="margin-top: -1rem"
          @click="addProduct"
          :disable="!selectedProduct || quantity <= 0 || unitPrice <= 0"
        />
      </q-card-section>



      <!-- Tabla de Productos Agregados -->
      <q-table :loading="loading"
        :rows="addedProducts"
        :columns="tableColumns"
        row-key="id"
        :pagination="{ rowsPerPage: 15 }"
        no-data-label="No hay productos agregados a la compra."
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

        <template v-slot:body-cell-options="props">
          <q-td align="right">
            <q-btn
              flat
              color="negative"
              icon="delete"
              @click="removeProduct(props.row.id)"
            />
          </q-td>
        </template>
      </q-table>

      <!-- Total de Compra -->
      <q-card-section class="text-right">
        <div class="text-subtitle1 text-grey-8">
          Items agregados: {{ formatQuantity(addedProducts.reduce((acc, p) => acc + p.quantity, 0)) }} uds
        </div>
        <div class="text-subtitle1 text-grey-8" v-if="ivaEnabled">
          Subtotal: ${{ formatCurrency(subtotal) }}
        </div>
        <div class="text-subtitle1 text-grey-8" v-if="ivaEnabled">
          IVA ({{ ivaPercentage }}%): ${{ formatCurrency(ivaAmount) }}
        </div>
        <div class="text-h6 text-primary text-weight-bold q-mt-sm">
          Total: ${{ formatCurrency(totalCompra) }}
        </div>
      </q-card-section>

      <!-- Modal para Confirmar Compra -->
      </q-card>

      <q-dialog v-model="closeModal">
        <q-card style="max-width: 500px; width: 90%">
          <q-card-section>
            <div class="text-h6">¿Confirmar Registro de Compra?</div>
            <div>Proveedor ID: {{ proveedorId }}</div>
            <div v-if="ivaEnabled">
              Subtotal: ${{ formatCurrency(subtotal) }}<br>
              IVA ({{ ivaPercentage }}%): ${{ formatCurrency(ivaAmount) }}
            </div>
            <div class="text-h6 text-primary q-mt-sm">
              Total a Pagar: ${{ formatCurrency(totalCompra) }}
            </div>
          </q-card-section>
          <q-card-actions align="right">
            <q-btn flat label="Cancelar" color="primary" v-close-popup />
            <q-btn
              flat
              label="Confirmar"
              color="positive"
              @click="confirmPurchase"
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
    tableColumns() {
      if (!this.ivaEnabled) {
        return [
          { name: "name", label: "Producto", align: "left", field: "name" },
          {
            name: "unitPrice",
            label: "Precio Unitario",
            align: "right",
            field: "unitPrice",
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
          { name: "options", label: "Opciones", align: "right" },
        ];
      }
      return [
        { name: "name", label: "Producto", align: "left", field: "name" },
        {
          name: "unitPrice",
          label: "Precio Unit.",
          align: "right",
          field: "unitPrice",
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
        { name: "options", label: "Opciones", align: "right" },
      ];
    },
  },
  data() {
    return {
      loading: false,
      selectedProduct: null,
      unitPrice: 0,
      quantity: 1,
      proveedorId: null,
      numeroFactura: "",
      loadingProveedores: false,
      loadingProducts: false,
      proveedoresCache: {},
      productsCache: {},
      productOptions: [],
      proveedoresOptions: [],
      filteredProductosOptions: [],
      addedProducts: [],
      subtotal: 0,
      ivaAmount: 0,
      totalCompra: 0,
      ivaEnabled: false,
      ivaPercentage: 0,
      closeModal: false,
      columns: [
        { name: "name", label: "Producto", align: "left", field: "name" },
        {
          name: "unitPrice",
          label: "Precio Unitario",
          align: "right",
          field: "unitPrice",
          format: (val) =>
            `$${String(Number(val).toFixed(2)).replace(
              /\B(?=(\d{3})+(?!\d))/g,
              ","
            )}`,
        },
        {
          name: "quantity",
          label: "Cantidad",
          align: "center",
          field: "quantity",
          format: (val) => this.formatQuantity(val),
        },
        { name: "iva_icon", label: "Aplica IVA", align: "center", field: "aplica_iva" },
        {
          name: "total",
          label: "Subtotal",
          align: "right",
          field: "total",
          format: (val) =>
            `$${String(Number(val).toFixed(2)).replace(
              /\B(?=(\d{3})+(?!\d))/g,
              ","
            )}`,
        },
        { name: "options", label: "Opciones", align: "right" },
      ],
    };
  },
  created() {
    this.fetchSettings();
    this.filterFnProveedores("", (callback) => callback && callback());
    this.filterFnProduct("", (callback) => callback && callback());
  },
  methods: {
    async fetchSettings() {
      try {
        const response = await api.get('/api/settings');
        const settings = response.data.data;
        const enabled = settings.find(s => s.key === 'iva_enabled');
        const pct = settings.find(s => s.key === 'iva_percentage');
        
        this.ivaEnabled = enabled ? (enabled.value === 'true' || enabled.value === '1') : false;
        this.ivaPercentage = pct ? Number(pct.value) : 0;
      } catch(e) {
        console.error("Error fetching settings", e);
      }
    },
    async filterFnProveedores(val, update, abort) {
      this.loadingProveedores = true;
      try {
        const response = await api.get("/api/proveedores/search", {
          params: { q: val, limit: 30 },
        });
        const proveedores = response.data.data || [];
        update(() => {
          this.proveedoresOptions = proveedores.map((p) => {
            this.proveedoresCache[p.id] = p;
            return {
              id: p.id,
              nombre: `${p.nombre} (Contacto: ${p.contacto || "N/A"}${p.telefono ? " - Tel: " + p.telefono : ""})`,
            };
          });
        });
      } catch (err) {
        if (abort) abort();
      } finally {
        this.loadingProveedores = false;
      }
    },
    async filterFnProduct(val, update, abort) {
      this.loadingProducts = true;
      try {
        const response = await api.get("/api/productos/search", {
          params: { q: val, limit: 30 },
        });
        const prods = response.data.data || [];
        update(() => {
          this.filteredProductosOptions = prods.map((p) => {
            this.productsCache[p.id] = p;
            return {
              label: `${p.sku} - ${p.nombre}`,
              value: p.id,
              costo_promedio: Number(p.costo_promedio) || 0,
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
    onProductSelected(prodId) {
      if (prodId && this.productsCache[prodId]) {
        const prod = this.productsCache[prodId];
        this.unitPrice = Number(prod.costo_promedio) || 0;
      }
    },
    formatCurrency(val) {
      if (!val) return "0.00";
      return String(Number(val).toFixed(2)).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    },
    formatQuantity(val) {
      if (val === null || val === undefined || val === "") return "0";
      return String(Math.round(Number(val) || 0)).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    },
    getItemBreakdown(prod) {
      const qty = Number(prod.quantity) || 0;
      const price = Number(prod.unitPrice) || 0;
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
      let ivaAmount = 0;
      let totalCompra = 0;

      this.addedProducts.forEach((prod) => {
        const b = this.getItemBreakdown(prod);
        subtotal += b.subtotal;
        ivaAmount += b.iva;
        totalCompra += b.total;
      });

      this.subtotal = subtotal;
      this.ivaAmount = ivaAmount;
      this.totalCompra = totalCompra;
    },
    addProduct() {
      const selected =
        this.filteredProductosOptions.find((p) => p.value === this.selectedProduct) ||
        (this.productsCache[this.selectedProduct]
          ? {
              value: this.selectedProduct,
              label: `${this.productsCache[this.selectedProduct].sku} - ${this.productsCache[this.selectedProduct].nombre}`,
              aplica_iva:
                this.productsCache[this.selectedProduct].aplica_iva === 1 ||
                this.productsCache[this.selectedProduct].aplica_iva === true,
            }
          : null);

      if (!selected) return;

      const existingProduct = this.addedProducts.find(
        (p) => p.id === selected.value
      );

      if (existingProduct) {
        existingProduct.quantity += this.quantity;
      } else {
        this.addedProducts.push({
          id: selected.value,
          name: selected.label,
          unitPrice: this.unitPrice,
          quantity: this.quantity,
          aplica_iva: selected.aplica_iva
        });
      }

      this.updateTotal();
      this.selectedProduct = null;
      this.unitPrice = 0;
      this.quantity = 1;
    },
    removeProduct(id) {
      this.addedProducts = this.addedProducts.filter((p) => p.id !== id);
      this.updateTotal();
    },
    openCloseModal() {
      if (!this.proveedorId || !this.numeroFactura) {
        Alert(
          "Advertencia",
          "Complete los datos de proveedor y factura",
          "warning"
        );
        return;
      }
      if (this.addedProducts.length === 0) {
        Alert("Advertencia", "No hay productos en la compra", "warning");
        return;
      }
      this.closeModal = true;
    },
    async confirmPurchase() {
      Alert("Procesando...", "Registrando compra en el inventario...", "info");
      useLoadingStore().startLoading();
      try {
        const data = {
          proveedor_id: this.proveedorId,
          numero_factura: this.numeroFactura,
          productos: this.addedProducts.map((product) => ({
            producto_id: product.id,
            cantidad: product.quantity,
            precio_unitario: product.unitPrice,
          }))
          // El backend recalcula el total
        };
        await api.post("/api/compras", data);
        Alert("Éxito", "Compra registrada con éxito", "success");
        this.resetForm();
      } catch (error) {
        Alert("Error", error.response?.data?.message || "Error", "error");
      } finally {
        useLoadingStore().stopLoading();
      }
    },
    resetForm() {
      this.addedProducts = [];
      this.totalCompra = 0;
      this.subtotal = 0;
      this.ivaAmount = 0;
      this.proveedorId = null;
      this.numeroFactura = "";
      this.closeModal = false;
    },
  },
};
</script>
