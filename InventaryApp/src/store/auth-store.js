import { defineStore } from 'pinia';
import { api } from 'boot/axios';

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    roles: [],
    permissions: [],
    sucursalActiva: localStorage.getItem('sucursal_activa_id') ? parseInt(localStorage.getItem('sucursal_activa_id')) : null,
  }),
  getters: {
    isAuthenticated: (state) => !!state.user,
    hasRole: (state) => (role) => {
      return state.roles.some(r => (typeof r === 'string' ? r : r.name) === role);
    },
    hasPermission: (state) => (permission) => {
      if (state.roles.some(r => (typeof r === 'string' ? r : r.name) === 'admin')) {
        return true;
      }
      return state.permissions.some(p => (typeof p === 'string' ? p : p.name) === permission);
    },
  },
  actions: {
    async fetchUser() {
      try {
        const response = await api.get('/api/me');
        if (response.data && response.data.status) {
          this.user = response.data.user;
          this.roles = response.data.user.roles || [];
          this.permissions = response.data.user.permissions || [];
          
          if (this.user.sucursal_id !== null && !this.hasRole('admin') && !this.hasRole('gerente')) {
            this.setSucursalActiva(this.user.sucursal_id);
          }
        } else {
          this.clearAuth();
        }
      } catch (error) {
        this.clearAuth();
      }
    },
    async logout() {
      try {
        await api.post('/api/logout');
      } catch (error) {
        console.error('Error on logout', error);
      } finally {
        this.clearAuth();
      }
    },
    clearAuth() {
      this.user = null;
      this.roles = [];
      this.permissions = [];
      this.sucursalActiva = null;
      localStorage.removeItem('sucursal_activa_id');
      delete api.defaults.headers.common['X-Sucursal-Id'];
    },
    setSucursalActiva(sucursalId) {
      this.sucursalActiva = sucursalId;
      if (sucursalId) {
        localStorage.setItem('sucursal_activa_id', sucursalId);
        api.defaults.headers.common['X-Sucursal-Id'] = sucursalId;
      } else {
        localStorage.removeItem('sucursal_activa_id');
        delete api.defaults.headers.common['X-Sucursal-Id'];
      }
    }
  }
});
