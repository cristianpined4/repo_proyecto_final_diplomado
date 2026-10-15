import { boot } from "quasar/wrappers";
import axios from "axios";

const api = axios.create({
  baseURL: "http://localhost:8000",
  withCredentials: true, // Enviar cookies HttpOnly automáticamente
  headers: {
    "Content-Type": "application/json",
    Accept: "application/json",
  },
});

// Inicializar cabecera de sucursal si existe en localStorage
const savedBranchId = localStorage.getItem('sucursal_activa_id');
if (savedBranchId) {
  api.defaults.headers.common['X-Sucursal-Id'] = savedBranchId;
}

// Interceptor para inyectar XSRF-TOKEN (Sanctum SPA) y asegurar multipart/form-data con boundary
api.interceptors.request.use((config) => {
  if (config.data instanceof FormData) {
    delete config.headers["Content-Type"];
  }

  // Leer XSRF-TOKEN de las cookies (esta cookie NO es HttpOnly, es legible por JS por diseño de Laravel)
  const match = document.cookie.match(
    new RegExp("(^|;\\s*)(XSRF-TOKEN)=([^;]*)")
  );
  if (match) {
    config.headers["X-XSRF-TOKEN"] = decodeURIComponent(match[3]);
  }
  return config;
});

// Interceptor para manejar errores 401 globalmente
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response && error.response.status === 401) {
      // Sesión expirada: redirigir al login si no estamos ya ahí
      if (
        window.location.hash !== "#/" &&
        window.location.hash !== "#/login"
      ) {
        window.location.hash = "#/";
      }
    }
    return Promise.reject(error);
  }
);

export default boot(({ app }) => {
  app.config.globalProperties.$axios = axios;
  app.config.globalProperties.$api = api;
});

export { api };
