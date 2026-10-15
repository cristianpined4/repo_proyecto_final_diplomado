import { defineStore } from 'pinia';
import { api } from 'boot/axios';
import { setCssVar } from 'quasar';

export const useSettingsStore = defineStore('settings', {
  state: () => {
    let companyName = 'Inventary App';
    let companyLogo = '';
    let valuationMethod = 'promedio';
    let settings = [];
    try {
      const cached = localStorage.getItem('company_settings');
      if (cached) {
        settings = JSON.parse(cached);
        const nameSetting = settings.find(s => s.key === 'empresa_nombre');
        const logoSetting = settings.find(s => s.key === 'empresa_logo');
        const methodSetting = settings.find(s => s.key === 'inventory_valuation_method');
        if (nameSetting && nameSetting.value) {
          companyName = nameSetting.value;
        }
        if (logoSetting && logoSetting.value) {
          companyLogo = (process.env.BACKEND_URL || "").replace(/\/$/, "") + "/storage/" + logoSetting.value;
        }
        if (methodSetting && methodSetting.value) {
          valuationMethod = methodSetting.value;
        }
      }
    } catch (e) {
      console.error('Error parsing cached settings in store', e);
    }
    return {
      companyName,
      companyLogo,
      valuationMethod,
      settings
    };
  },
  getters: {
    valuationMethodLabel: (state) => {
      const m = String(state.valuationMethod || 'promedio').toLowerCase();
      if (m === 'fifo') return 'FIFO / PEPS (Primeras Entradas, Primeras Salidas)';
      if (m === 'lifo') return 'LIFO / UEPS (Últimas Entradas, Primeras Salidas)';
      return 'Costo Promedio Ponderado';
    },
    valuationMethodShort: (state) => {
      const m = String(state.valuationMethod || 'promedio').toLowerCase();
      if (m === 'fifo') return 'PEPS (FIFO)';
      if (m === 'lifo') return 'UEPS (LIFO)';
      return 'Promedio';
    },
    costColumnLabel: (state) => {
      const m = String(state.valuationMethod || 'promedio').toLowerCase();
      if (m === 'fifo') return 'Costo PEPS';
      if (m === 'lifo') return 'Costo UEPS';
      return 'Costo Prom.';
    },
  },
  actions: {
    async fetchPublicSettings() {
      try {
        const response = await api.get('/api/settings/public');
        const settings = response.data.data;
        this.settings = settings;
        localStorage.setItem('company_settings', JSON.stringify(settings));

        const nameSetting = settings.find(s => s.key === 'empresa_nombre');
        const logoSetting = settings.find(s => s.key === 'empresa_logo');
        const methodSetting = settings.find(s => s.key === 'inventory_valuation_method');
        
        if (nameSetting && nameSetting.value) {
          this.companyName = nameSetting.value;
        } else {
          this.companyName = 'Inventary App';
        }
        if (logoSetting && logoSetting.value) {
          this.companyLogo = (process.env.BACKEND_URL || "").replace(/\/$/, "") + "/storage/" + logoSetting.value;
        } else {
          this.companyLogo = '';
        }
        if (methodSetting && methodSetting.value) {
          this.valuationMethod = methodSetting.value;
        }

        // Apply colors
        const primarySetting = settings.find(s => s.key === 'color_primary');
        const secondarySetting = settings.find(s => s.key === 'color_secondary');
        const accentSetting = settings.find(s => s.key === 'color_accent');
        
        if (primarySetting && primarySetting.value) setCssVar('primary', primarySetting.value);
        if (secondarySetting && secondarySetting.value) setCssVar('secondary', secondarySetting.value);
        if (accentSetting && accentSetting.value) setCssVar('accent', accentSetting.value);
      } catch (e) {
        console.error('Failed to fetch public settings in store', e);
      }
    },
    updateDocumentTitle(routeTitle = '') {
      const name = this.companyName || 'Inventary App';
      document.title = routeTitle ? `${routeTitle} | ${name}` : name;
    }
  }
});
