import { store } from 'quasar/wrappers'
import { createPinia } from 'pinia'

// Creamos y exportamos la instancia de Pinia para poder importarla en otros archivos como el router
export const pinia = createPinia()

export default store((/* { ssrContext } */) => {
  // Retornamos la misma instancia global
  return pinia
})
