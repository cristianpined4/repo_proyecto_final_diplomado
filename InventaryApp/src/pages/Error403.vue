<template>
  <div class="fullscreen bg-dark text-white text-center q-pa-md flex flex-center">
    <div>
      <q-icon name="warning" color="warning" size="100px" />
      <div style="font-size: 10vh">
        403
      </div>

      <div class="text-h2" style="opacity:.4">
        Acceso Denegado
      </div>

      <div class="text-subtitle1 q-mt-md" style="opacity:.6">
        No tienes los permisos suficientes para ver esta página.
      </div>

      <q-btn
        class="q-mt-xl"
        color="white"
        text-color="dark"
        unelevated
        to="/"
        label="Regresar al inicio"
        no-caps
      />
    </div>
  </div>
</template>

<script>
import { useAuthStore } from "src/store/auth-store";
import { defineComponent } from 'vue'

export default defineComponent({
  name: 'Error403Page'
})
</script>
