<template>
  <q-layout class="fullscreen-layout">
    <q-page-container class="fullscreen-container bg-gradient-brand">
      <q-page class="flex flex-center">
        <div class="login-wrapper">
          <q-card class="login-card">
            <img
              :src="companyLogo || defaultLogo"
              alt="Logo"
              class="q-mb-md"
              style="
                max-width: 140px;
                max-height: 140px;
                object-fit: contain;
                display: block;
                margin: 0 auto;
              "
            />
            <h5
              class="q-mb-md text-center text-weight-bold"
              style="margin-top: 0.5rem"
            >
              {{ companyName || "Inventary App" }}
            </h5>
            <q-spinner
              v-if="loading"
              color="primary"
              class="q-mb-md"
              style="
                display: block;
                width: 100px;
                height: 100px;
                margin: 2rem auto;
              "
            />
            <q-form
              v-if="!loading"
              ref="loginForm"
              @submit.prevent="handleLogin"
            >
              <q-input
                v-model="username"
                label="Usuario"
                filled
                class="q-mb-md login-input"
                :rules="[(val) => !!val || 'El usuario es obligatorio']"
              />
              <q-input
                v-model="password"
                type="password"
                label="Contraseña"
                filled
                class="q-mb-md login-input"
                :rules="[(val) => !!val || 'La contraseña es obligatoria']"
              />
              <q-btn
                type="submit"
                label="Ingresar"
                class="full-width btn-suu"
              />
            </q-form>
          </q-card>
        </div>
      </q-page>
    </q-page-container>
  </q-layout>
</template>

<script>
import { api } from "boot/axios";
import { useLoadingStore } from "src/store/loading-store";
import { useSettingsStore } from "src/store/settings-store";
import { useAuthStore } from "src/store/auth-store";
import defaultLogo from "../assets/logo.png";
import Alert from "src/helpers/Alert";

export default {
  data() {
    return {
      username: "",
      password: "",
      error: null,
      defaultLogo: defaultLogo,
    };
  },
  computed: {
    authStore() {
      return useAuthStore();
    },
    loading() {
      const loadingStore = useLoadingStore();
      return loadingStore.loading;
    },
    companyLogo() {
      const settingsStore = useSettingsStore();
      return settingsStore.companyLogo;
    },
    companyName() {
      const settingsStore = useSettingsStore();
      return settingsStore.companyName;
    },
  },
  async mounted() {
    const loadingStore = useLoadingStore();
    loadingStore.startLoading();

    // Check if user is already authenticated with cookie
    try {
      const authStore = useAuthStore();
      await authStore.fetchUser();

      if (authStore.isAuthenticated) {
        this.$router.push("/dashboard");
      }
    } catch (error) {
      // Not authenticated, stay on login
    } finally {
      loadingStore.stopLoading();
    }
  },
  methods: {
    async handleLogin() {
      const formValid = await this.$refs.loginForm.validate();
      if (!formValid) return;

      const loadingStore = useLoadingStore();
      Alert("Iniciando Sesión", "Validando credenciales...", "info");
      loadingStore.startLoading();
      this.error = null;
      try {
        // Fetch CSRF cookie for Sanctum SPA Auth
        await api.get("/sanctum/csrf-cookie");

        const response = await api.post("/api/login", {
          username: this.username,
          password: this.password,
        });

        if (response.data.status) {
          Alert("Acceso Concedido", "Bienvenido al sistema", "success");
          // Hidratar el auth store con los datos del usuario (NO se guarda token)
          const authStore = useAuthStore();
          authStore.user = response.data.data;
          authStore.roles = response.data.data.roles || [];
          authStore.permissions = response.data.data.permissions || [];

          if (response.data.data.sucursal_id !== null) {
            authStore.setSucursalActiva(response.data.data.sucursal_id);
          } else {
            authStore.setSucursalActiva(null);
          }

          this.$router.push("/dashboard");
        }
      } catch (error) {
        const errMsg =
          error.response?.data?.message || "Error al iniciar sesión";
        Alert("Error de Acceso", errMsg, "error");

        const authStore = useAuthStore();
        authStore.clearAuth();
      } finally {
        loadingStore.stopLoading();
      }
    },
  },
};
</script>

<style scoped>
.fullscreen-layout {
  height: 100vh;
  width: 100vw;
}

.fullscreen-container {
  height: 100%;
}

.login-wrapper {
  max-width: 420px;
  width: 90%;
  margin: 0 auto;
}

/* Beautiful animated gradient blending brand colors */
.bg-gradient-brand {
  background: linear-gradient(
    -45deg,
    var(--q-primary),
    var(--q-secondary),
    var(--q-accent),
    var(--q-primary)
  ) !important;
  background-size: 400% 400% !important;
  animation: gradientBG 15s ease infinite !important;
}

@keyframes gradientBG {
  0% {
    background-position: 0% 50%;
  }
  50% {
    background-position: 100% 50%;
  }
  100% {
    background-position: 0% 50%;
  }
}

.login-card {
  border-radius: 16px !important;
  box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2) !important;
  background: rgba(255, 255, 255, 0.95) !important;
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.3);
  padding: 3rem 2.5rem !important;
  transition: all 0.3s ease;
}

.login-card:hover {
  box-shadow: 0 20px 45px rgba(0, 0, 0, 0.28) !important;
}

.login-input :deep(.q-field__control) {
  border-radius: 10px !important;
}

.btn-suu {
  background: linear-gradient(
    135deg,
    var(--q-primary) 0%,
    var(--q-secondary) 100%
  ) !important;
  color: #fff !important;
  border-radius: 10px !important;
  font-weight: 600;
  padding: 0.8rem 0 !important;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15) !important;
  transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1) !important;
  text-transform: none !important;
  font-size: 1rem;
}

.btn-suu:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.22) !important;
}

.btn-suu:active {
  transform: translateY(0);
}
</style>
