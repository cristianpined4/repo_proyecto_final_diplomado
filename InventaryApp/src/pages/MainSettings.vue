<template>
  <q-page class="q-pa-md">
    <q-card class="my-card shadow-10 no-border">
      <q-card-section class="bg-gradient-brand text-white q-py-lg">
        <div class="row items-center no-wrap">
          <q-avatar
            icon="settings"
            size="48px"
            class="q-mr-md bg-white-translucent text-white"
          />
          <div>
            <div class="text-h5 text-weight-bold">Configuración Global</div>
            <div class="text-subtitle2 opacity-80">
              Administra los parámetros generales, impuestos y valuación del
              sistema
            </div>
          </div>
        </div>
      </q-card-section>

      <q-separator />

      <q-tabs
        v-model="tab"
        dense
        class="bg-grey-2 text-grey-7"
        active-color="primary"
        indicator-color="primary"
        align="justify"
      >
        <q-tab name="general" label="Configuración General" icon="tune" />
        <q-tab name="unidades" label="Unidades de Medida" icon="straighten" />
      </q-tabs>

      <q-separator />

      <q-tab-panels v-model="tab" animated class="bg-transparent">
        <q-tab-panel name="general" class="q-pa-none">
          <q-card-section class="q-pa-lg">
            <q-form
              ref="settingsForm"
              @submit.prevent="saveSettings"
              class="q-gutter-md"
            >
              <div class="row q-col-gutter-lg">
                <!-- GENERAL SETTINGS -->
                <div class="col-12 col-md-6">
                  <q-card bordered flat class="full-height rounded-card">
                    <q-card-section class="bg-grey-1 q-py-sm">
                      <div class="text-h6 text-grey-8 row items-center">
                        <q-avatar
                          icon="business"
                          size="28px"
                          color="primary"
                          text-color="white"
                          class="q-mr-xs"
                        />
                        Datos de la Empresa
                      </div>
                    </q-card-section>
                    <q-separator />
                    <q-card-section class="q-gutter-md q-py-md">
                      <q-input
                        v-model="form.empresa_nombre"
                        label="Nombre de la Empresa"
                        filled
                        class="rounded-input"
                        hint="Razón social o nombre comercial (mínimo 3 caracteres)"
                        :rules="[
                          (val) => (!!val && val.trim().length > 0) || 'El nombre de la empresa es obligatorio',
                          (val) => (val && val.trim().length >= 3) || 'Debe tener al menos 3 caracteres',
                          (val) => (val && val.trim().length <= 100) || 'Máximo 100 caracteres',
                          (val) => /^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑüÜ\s.,&'()-]+$/.test(val ? val.trim() : '') || 'Formato no válido (solo letras, números y signos básicos)',
                        ]"
                      />
                      <q-input
                        v-model="form.empresa_correo"
                        label="Correo Electrónico"
                        filled
                        type="email"
                        class="rounded-input"
                        hint="Ej: contacto@miempresa.com"
                        :rules="[
                          (val) => (!!val && val.trim().length > 0) || 'El correo electrónico es obligatorio',
                          (val) => /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/.test(val ? val.trim() : '') || 'Formato de correo no válido (ej: contacto@empresa.com)',
                          (val) => (val && val.trim().length <= 150) || 'Máximo 150 caracteres',
                        ]"
                      />
                      <q-input
                        v-model="form.empresa_telefono"
                        label="Teléfono de Contacto"
                        filled
                        mask="####-####"
                        placeholder="0000-0000"
                        class="rounded-input"
                        hint="Formato: 0000-0000"
                        @update:model-value="onPhoneInput"
                        :rules="[
                          (val) => (!!val && val.trim().length > 0) || 'El teléfono es obligatorio',
                          (val) => /^[0-9]{4}-[0-9]{4}$/.test(val ? val.trim() : '') || 'Formato no válido (debe ser 0000-0000)',
                        ]"
                      />
                      <q-input
                        v-model="form.empresa_direccion"
                        label="Dirección Principal"
                        filled
                        type="textarea"
                        autogrow
                        class="rounded-input"
                        hint="Ubicación física de la sede principal (mínimo 5 caracteres)"
                        :rules="[
                          (val) => (!!val && val.trim().length > 0) || 'La dirección es obligatoria',
                          (val) => (val && val.trim().length >= 5) || 'Debe contener al menos 5 caracteres',
                          (val) => (val && val.trim().length <= 250) || 'Máximo 250 caracteres',
                        ]"
                      />
                      <q-select
                        v-model="form.empresa_moneda"
                        :options="['USD', 'MXN', 'EUR', 'COP', 'CLP', 'ARS']"
                        label="Moneda del Sistema"
                        filled
                        class="rounded-input"
                        hint="Moneda de referencia para transacciones y reportes"
                        :rules="[(val) => !!val || 'La moneda es obligatoria']"
                      />

                      <!-- Logo de la Empresa -->
                      <q-separator class="q-my-md" />
                      <div class="text-subtitle2 text-grey-8 q-mb-sm">
                        Logo de la Empresa (Global)
                      </div>
                      <div class="row items-center q-gutter-md">
                        <q-avatar
                          size="80px"
                          square
                          class="bg-grey-3 rounded-borders shadow-1"
                        >
                          <q-img
                            v-if="globalLogoUrl"
                            :src="globalLogoUrl"
                            spinner-color="primary"
                          >
                            <template v-slot:error>
                              <q-icon
                                name="business"
                                color="grey-6"
                                size="40px"
                              />
                            </template>
                          </q-img>
                          <q-icon
                            v-else
                            name="business"
                            color="grey-6"
                            size="40px"
                          />
                        </q-avatar>
                        <div class="col">
                          <q-file
                            v-model="logoFile"
                            label="Subir nuevo logo"
                            filled
                            accept="image/*"
                            dense
                            class="rounded-input q-mb-xs"
                            @update:model-value="onLogoSelected"
                          >
                            <template v-slot:prepend>
                              <q-icon name="cloud_upload" />
                            </template>
                          </q-file>
                          <div class="text-caption text-grey-6">
                            Formatos: PNG, JPG, GIF (Max 2MB)
                          </div>
                        </div>
                      </div>
                    </q-card-section>
                  </q-card>
                </div>

                <!-- FISCAL AND INVENTORY VALUATION SETTINGS -->
                <div class="col-12 col-md-6">
                  <q-card bordered flat class="full-height rounded-card">
                    <q-card-section class="bg-grey-1 q-py-sm">
                      <div class="text-h6 text-grey-8 row items-center">
                        <q-avatar
                          icon="receipt_long"
                          size="28px"
                          color="secondary"
                          text-color="white"
                          class="q-mr-xs"
                        />
                        Fiscal & Inventario
                      </div>
                    </q-card-section>
                    <q-separator />
                    <q-card-section class="q-gutter-md q-py-md">
                      <div
                        class="row items-center justify-between bg-blue-1 q-pa-sm rounded-borders"
                      >
                        <div
                          class="text-subtitle1 text-weight-medium text-blue-9"
                        >
                          IVA Habilitado Globalmente
                        </div>
                        <q-toggle
                          v-model="form.iva_enabled"
                          color="primary"
                          keep-color
                        />
                      </div>

                      <q-input
                        v-model.number="form.iva_percentage"
                        label="Porcentaje de IVA (%)"
                        type="number"
                        step="0.01"
                        filled
                        :disable="!form.iva_enabled"
                        hint="Ejemplo: 13 para 13% o 16 para 16%"
                        class="rounded-input"
                        :rules="[
                          (val) =>
                            !form.iva_enabled ||
                            (val !== null && val !== '' && !isNaN(val)) ||
                            'El porcentaje de IVA es obligatorio',
                          (val) =>
                            !form.iva_enabled ||
                            (Number(val) >= 0 && Number(val) <= 100) ||
                            'El porcentaje debe estar entre 0% y 100%',
                          (val) =>
                            !form.iva_enabled ||
                            /^\d+(\.\d{1,2})?$/.test(String(val)) ||
                            'Formato numérico no válido (máximo 2 decimales)',
                        ]"
                      />

                      <!-- Serie de Facturación -->
                      <q-input
                        v-model="form.venta_serie"
                        label="Serie de Facturación Predeterminada"
                        filled
                        class="rounded-input"
                        hint="Prefijo de serie para comprobantes de venta (ej: F001, B001)"
                        :rules="[
                          (val) =>
                            (!!val && val.trim().length > 0) ||
                            'La serie de venta es obligatoria',
                          (val) =>
                            /^[A-Z0-9\-]{2,10}$/.test(val ? val.trim() : '') ||
                            'Formato no válido: 2 a 10 caracteres alfanuméricos en mayúsculas (ej: F001)',
                        ]"
                        @update:model-value="
                          (val) =>
                            (form.venta_serie = (val || '')
                              .toUpperCase()
                              .trim())
                        "
                      />

                      <q-separator class="q-my-sm" />

                      <!-- Selector de Valuación de Inventarios -->
                      <div class="text-subtitle2 text-grey-8 q-mb-xs">
                        Método de Valuación de Inventario
                      </div>
                      <q-select
                        v-model="form.inventory_valuation_method"
                        :options="[
                          {
                            label: 'Costo Promedio Ponderado',
                            value: 'promedio',
                          },
                          {
                            label:
                              'FIFO / PEPS (Primeras Entradas, Primeras Salidas)',
                            value: 'fifo',
                          },
                          {
                            label:
                              'LIFO / UEPS (Últimas Entradas, Primeras Salidas)',
                            value: 'lifo',
                          },
                        ]"
                        emit-value
                        map-options
                        filled
                        class="rounded-input"
                        hint="Establece cómo se calculará el costo de salida de los productos."
                      />

                      <div
                        class="q-mt-md text-caption text-grey-6 bg-grey-2 q-pa-sm rounded-borders"
                      >
                        <strong>Importante:</strong> El método seleccionado
                        afectará los reportes de rentabilidad y los cálculos
                        automáticos del costo de ventas al registrar
                        transacciones.
                      </div>

                      <!-- Color Branding Customization -->
                      <q-separator class="q-my-md" />
                      <div class="text-subtitle2 text-grey-8 q-mb-sm">
                        Personalización del Tema (Branding)
                      </div>
                      <div class="row q-col-gutter-sm">
                        <div class="col-12 col-sm-4">
                          <q-input
                            filled
                            v-model="form.color_primary"
                            label="Color Primario"
                            dense
                            class="rounded-input"
                            hint="Ej: #1976D2"
                            :rules="[
                              (val) =>
                                (!!val && val.trim().length > 0) ||
                                'El color es obligatorio',
                              (val) =>
                                /^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/.test(
                                  val ? val.trim() : ''
                                ) || 'Hex inválido (ej: #1976D2)',
                            ]"
                          >
                            <template v-slot:append>
                              <q-icon
                                name="colorize"
                                class="cursor-pointer"
                                :style="{ color: form.color_primary }"
                              >
                                <q-popup-proxy
                                  cover
                                  transition-show="scale"
                                  transition-hide="scale"
                                >
                                  <q-color v-model="form.color_primary" />
                                </q-popup-proxy>
                              </q-icon>
                            </template>
                          </q-input>
                        </div>
                        <div class="col-12 col-sm-4">
                          <q-input
                            filled
                            v-model="form.color_secondary"
                            label="Color Secundario"
                            dense
                            class="rounded-input"
                            hint="Ej: #26A69A"
                            :rules="[
                              (val) =>
                                (!!val && val.trim().length > 0) ||
                                'El color es obligatorio',
                              (val) =>
                                /^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/.test(
                                  val ? val.trim() : ''
                                ) || 'Hex inválido (ej: #26A69A)',
                            ]"
                          >
                            <template v-slot:append>
                              <q-icon
                                name="colorize"
                                class="cursor-pointer"
                                :style="{ color: form.color_secondary }"
                              >
                                <q-popup-proxy
                                  cover
                                  transition-show="scale"
                                  transition-hide="scale"
                                >
                                  <q-color v-model="form.color_secondary" />
                                </q-popup-proxy>
                              </q-icon>
                            </template>
                          </q-input>
                        </div>
                        <div class="col-12 col-sm-4">
                          <q-input
                            filled
                            v-model="form.color_accent"
                            label="Color Terciario"
                            dense
                            class="rounded-input"
                            hint="Ej: #9C27B0"
                            :rules="[
                              (val) =>
                                (!!val && val.trim().length > 0) ||
                                'El color es obligatorio',
                              (val) =>
                                /^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/.test(
                                  val ? val.trim() : ''
                                ) || 'Hex inválido (ej: #9C27B0)',
                            ]"
                          >
                            <template v-slot:append>
                              <q-icon
                                name="colorize"
                                class="cursor-pointer"
                                :style="{ color: form.color_accent }"
                              >
                                <q-popup-proxy
                                  cover
                                  transition-show="scale"
                                  transition-hide="scale"
                                >
                                  <q-color v-model="form.color_accent" />
                                </q-popup-proxy>
                              </q-icon>
                            </template>
                          </q-input>
                        </div>
                      </div>
                    </q-card-section>
                  </q-card>
                </div>
              </div>

              <div class="row justify-end q-mt-lg">
                <q-btn
                  v-if="authStore.hasPermission('configuracion.editar')"
                  label="Guardar Configuración"
                  type="submit"
                  color="primary"
                  icon="save"
                  class="q-px-lg rounded-btn"
                  size="md"
                  unelevated
                />
              </div>
            </q-form>
          </q-card-section>
        </q-tab-panel>

        <q-tab-panel name="unidades" class="q-pa-none">
          <q-card-section class="q-pa-md">
            <main-unidades />
          </q-card-section>
        </q-tab-panel>
      </q-tab-panels>
    </q-card>
  </q-page>
</template>

<script>
import { useAuthStore } from "src/store/auth-store";
import { useLoadingStore } from "src/store/loading-store";
import { useSettingsStore } from "src/store/settings-store";
import { api } from "boot/axios";
import Alert from "src/helpers/Alert";
import { setCssVar } from "quasar";
import MainUnidades from "src/pages/MainUnidades.vue";

export default {
  components: {
    MainUnidades,
  },
  computed: {
    authStore() {
      return useAuthStore();
    },
  },
  data() {
    return {
      loading: false,
      tab: "general",
      settingsRaw: [],
      logoFile: null,
      globalLogoUrl: "",
      form: {
        empresa_nombre: "",
        empresa_correo: "",
        empresa_telefono: "",
        empresa_direccion: "",
        empresa_moneda: "USD",
        venta_serie: "F001",
        iva_enabled: false,
        iva_percentage: 13,
        inventory_valuation_method: "promedio",
        color_primary: "#1976D2",
        color_secondary: "#26A69A",
        color_accent: "#9C27B0",
      },
    };
  },
  watch: {
    "form.color_primary"(val) {
      if (val && val.startsWith("#")) {
        setCssVar("primary", val);
      }
    },
    "form.color_secondary"(val) {
      if (val && val.startsWith("#")) {
        setCssVar("secondary", val);
      }
    },
    "form.color_accent"(val) {
      if (val && val.startsWith("#")) {
        setCssVar("accent", val);
      }
    },
  },
  mounted() {
    this.fetchSettings();
  },
  methods: {
    async fetchSettings() {
      useLoadingStore().startLoading();
      try {
        const response = await api.get("/api/settings");
        this.settingsRaw = response.data.data;

        // Map raw settings DB rows to form
        this.settingsRaw.forEach((setting) => {
          if (setting.key === "empresa_logo" && setting.value) {
            this.globalLogoUrl =
              "http://localhost:8000/storage/" + setting.value;
          }
          if (this.form.hasOwnProperty(setting.key)) {
            if (setting.key === "iva_enabled") {
              this.form[setting.key] =
                setting.value === "true" ||
                setting.value === "1" ||
                setting.value === true;
            } else if (setting.key === "iva_percentage") {
              this.form[setting.key] = Number(setting.value);
            } else if (setting.key === "empresa_telefono") {
              const clean = String(setting.value || "").replace(/\D/g, "");
              const digits = clean.length >= 8 ? clean.slice(-8) : clean;
              if (digits.length === 8) {
                this.form[setting.key] = `${digits.slice(0, 4)}-${digits.slice(4)}`;
              } else {
                this.form[setting.key] = setting.value || "";
              }
            } else {
              this.form[setting.key] = setting.value;
            }
          }
        });
      } catch (error) {
        Alert("Error", "No se pudo cargar la configuración", "error");
      } finally {
        useLoadingStore().stopLoading();
      }
    },
    async onLogoSelected(file) {
      if (!file) return;
      const validTypes = [
        "image/jpeg",
        "image/png",
        "image/jpg",
        "image/gif",
        "image/webp",
        "image/svg+xml",
      ];
      if (!validTypes.includes(file.type)) {
        Alert(
          "Formato no válido",
          "Por favor seleccione un archivo de imagen válido (JPEG, PNG, WEBP, GIF, SVG).",
          "warning"
        );
        this.logoFile = null;
        return;
      }
      if (file.size > 2 * 1024 * 1024) {
        Alert(
          "Tamaño excedido",
          "La imagen seleccionada no puede superar los 2MB de tamaño.",
          "warning"
        );
        this.logoFile = null;
        return;
      }
      const formData = new FormData();
      formData.append("logo", file);

      useLoadingStore().startLoading();
      try {
        const res = await api.post("/api/settings/logo", formData, {
          headers: { "Content-Type": "multipart/form-data" },
        });
        if (res.data.success) {
          Alert(
            "Éxito",
            "Logo de la empresa actualizado correctamente",
            "success"
          );
          this.globalLogoUrl =
            "http://localhost:8000/storage/" + res.data.logo_path;
          this.logoFile = null;

          // Refresh the public settings cache in settingsStore
          try {
            const settingsStore = useSettingsStore();
            await settingsStore.fetchPublicSettings();
          } catch (e) {
            console.error(
              "Failed to refresh cached settings on logo upload",
              e
            );
          }
        }
      } catch (error) {
        Alert(
          "Error",
          error.response?.data?.message || "No se pudo subir el logo",
          "error"
        );
      } finally {
        useLoadingStore().stopLoading();
      }
    },
    onPhoneInput(val) {
      if (!val) {
        this.form.empresa_telefono = "";
        return;
      }
      const digits = String(val).replace(/\D/g, "").slice(0, 8);
      if (digits.length > 4) {
        this.form.empresa_telefono = `${digits.slice(0, 4)}-${digits.slice(4)}`;
      } else {
        this.form.empresa_telefono = digits;
      }
    },
    async saveSettings() {
      if (!this.authStore.hasPermission("configuracion.editar")) {
        Alert("Acceso denegado", "No tienes permiso para modificar las configuraciones.", "warning");
        return;
      }
      const isValid = await this.$refs.settingsForm.validate();
      if (!isValid) return;

      Alert("Guardando...", "Guardando configuración global...", "info");
      useLoadingStore().startLoading();
      try {
        // Map form back to bulk payload
        const payload = Object.keys(this.form).map((key) => ({
          key: key,
          value:
            typeof this.form[key] === "boolean"
              ? this.form[key]
                ? "true"
                : "false"
              : String(this.form[key]),
        }));

        await api.post("/api/settings/bulk", { settings: payload });

        // Refresh the public settings cache in settingsStore
        try {
          const settingsStore = useSettingsStore();
          await settingsStore.fetchPublicSettings();
        } catch (e) {
          console.error("Failed to refresh cached settings on save", e);
        }

        Alert("Éxito", "Configuración guardada correctamente", "success");
      } catch (error) {
        Alert("Error", "Error al guardar la configuración", "error");
      } finally {
        useLoadingStore().stopLoading();
      }
    },
  },
};
</script>

<style scoped>
.my-card {
  border-radius: 12px;
  overflow: hidden;
}

.bg-white-translucent {
  background-color: rgba(255, 255, 255, 0.15);
}

.rounded-card {
  border-radius: 8px;
}

.rounded-borders {
  border-radius: 6px;
}

.rounded-btn {
  border-radius: 8px;
  text-transform: none;
}
</style>
