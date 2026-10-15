import { boot } from "quasar/wrappers";
import axios from "axios";

// URL del backend configurada exclusivamente en quasar.config.js
const BACKEND_URL = (process.env.BACKEND_URL || "").replace(/\/$/, "");

// Detectar si estamos en Electron (sea dev o compilado para escritorio)
const isElectron =
  process.env.MODE === "electron" ||
  (typeof window !== "undefined" &&
    (window.process?.type === "renderer" ||
      navigator.userAgent.toLowerCase().includes("electron")));

// En desarrollo web (navegador) se usa '' para pasar por el proxy de Vite.
// En Electron y en producción se usa SIEMPRE la URL completa del backend.
const resolvedBaseURL = process.env.DEV && !isElectron ? "" : BACKEND_URL;

const api = axios.create({
  baseURL: resolvedBaseURL,
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
