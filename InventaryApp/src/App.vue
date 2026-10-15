<template>
  <div v-if="!booted" :class="['boot-loading-screen', $q.dark.isActive ? 'bg-dark' : 'bg-light']">
    <div class="boot-logo-container">
      <q-avatar v-if="companyLogo" size="88px" class="bg-white q-pa-xs shadow-3 q-mb-md pulse-animation">
        <q-img :src="companyLogo" style="height:78px; width:78px; object-fit:contain;" />
      </q-avatar>
      <q-avatar v-else size="88px" color="primary" text-color="white" class="shadow-3 q-mb-md pulse-animation">
        <q-icon name="storefront" size="44px" />
      </q-avatar>
      <div :class="['text-h6 text-weight-bold', $q.dark.isActive ? 'text-white' : 'text-grey-9']">
        {{ companyName || 'Cargando Sistema...' }}
      </div>
      <q-spinner-tail size="48px" color="primary" class="q-mt-lg" />
    </div>
  </div>
  <router-view v-else />
</template>

<script setup>
import { ref, onMounted, watch, computed } from 'vue';
import { useQuasar } from 'quasar';
import { useRoute } from 'vue-router';
import { pinia } from 'src/store/index';
import { useAuthStore } from 'src/store/auth-store';
import { useSettingsStore } from 'src/store/settings-store';

defineOptions({
  name: 'App'
});

const $q = useQuasar();
const route = useRoute();
const booted = ref(false);

// 1. Sync check of localStorage to apply cached theme/dark mode immediately
const savedDarkMode = localStorage.getItem('dark_mode');
if (savedDarkMode !== null) {
  $q.dark.set(savedDarkMode === 'true');
} else {
  $q.dark.set(false);
}

// 2. Computed properties for reactive access without early Pinia error
const companyName = computed(() => {
  try {
    return useSettingsStore(pinia).companyName;
  } catch (e) {
    return 'Inventary App';
  }
});

const companyLogo = computed(() => {
  try {
    return useSettingsStore(pinia).companyLogo;
  } catch (e) {
    return '';
  }
});

// 3. Reactively watch route changes and companyName changes to update document.title
watch([() => route.path, companyName], () => {
  try {
    const settingsStore = useSettingsStore(pinia);
    const routeTitle = route.meta && route.meta.title ? route.meta.title : '';
    settingsStore.updateDocumentTitle(routeTitle);
  } catch (e) {
    console.error("Error updating title in watch:", e);
  }
});

onMounted(async () => {
  const authStore = useAuthStore(pinia);
  const settingsStore = useSettingsStore(pinia);
  
  // Set initial document title once Pinia is mounted
  const routeTitle = route.meta && route.meta.title ? route.meta.title : '';
  settingsStore.updateDocumentTitle(routeTitle);

  // 4. Fetch fresh settings
  await settingsStore.fetchPublicSettings();

  // 5. Verify user authentication status
  try {
    await authStore.fetchUser();
  } catch (e) {
    console.log("User not authenticated on boot.");
  } finally {
    // 6. Mark app as booted and transition smoothly
    booted.value = true;
  }
});
</script>

<style scoped>
.boot-loading-screen {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 99999;
  transition: background-color 0.3s ease;
}

.bg-dark {
  background-color: #121214 !important;
}

.bg-light {
  background-color: #f4f4f7 !important;
}

.boot-logo-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
}

.pulse-animation {
  animation: logoPulse 2s infinite ease-in-out;
}

@keyframes logoPulse {
  0% { transform: scale(1); opacity: 0.95; }
  50% { transform: scale(1.05); opacity: 1; }
  100% { transform: scale(1); opacity: 0.95; }
}
</style>
