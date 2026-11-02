<template>
   <!-- Aquí se muestra toda la pantalla cuando el usuario no tiene acceso -->
  <div class="fullscreen bg-dark text-white text-center q-pa-md flex flex-center">
    <div>
      <!-- Icono que indica que hay un problema con el acceso -->
      <q-icon name="warning" color="warning" size="100px" />

      <!-- Se muestra el número del error -->
      <div style="font-size: 10vh">
        403
      </div>
      <!-- Mensaje para avisar que el acceso no está permitido -->
      <div class="text-h2" style="opacity:.4">
        Acceso Denegado
      </div>

      <!-- Se le explica al usuario que no tiene permiso para entrar -->
      <div class="text-subtitle1 q-mt-md" style="opacity:.6">
        No tienes los permisos suficientes para ver esta página.
      </div>

      <!-- Botón para que el usuario pueda regresar al inicio -->
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
