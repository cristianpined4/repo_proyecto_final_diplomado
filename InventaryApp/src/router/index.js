import { route } from 'quasar/wrappers'
import { createRouter, createMemoryHistory, createWebHistory, createWebHashHistory } from 'vue-router'
import routes from './routes'
import { useAuthStore } from 'src/store/auth-store'

export default route(function ({ store }) {
  const createHistory = process.env.SERVER
    ? createMemoryHistory
    : (process.env.VUE_ROUTER_MODE === 'history' ? createWebHistory : createWebHashHistory)

  const Router = createRouter({
    scrollBehavior: () => ({ left: 0, top: 0 }),
    routes,
    history: createHistory(process.env.VUE_ROUTER_BASE)
  })

  Router.beforeEach(async (to, from, next) => {
    const { pinia } = await import('src/store/index')
    const authStore = useAuthStore(pinia)

    // Páginas públicas (login, 403)
    const publicPages = ['UserLogin', 'UserLogin2', 'Error403']
    const isPublic = publicPages.includes(to.name)

    // Si no estamos autenticados, intentar obtener usuario
    if (!authStore.isAuthenticated) {
      try {
        await authStore.fetchUser()
      } catch (e) {
        // fetchUser falló = no hay sesión activa
      }
    }

    // Redirigir a login si no autenticado y no es página pública
    if (!isPublic && !authStore.isAuthenticated) {
      return next({ name: 'UserLogin' })
    }

    // Redirigir a dashboard si ya autenticado y va al login
    if ((to.name === 'UserLogin' || to.name === 'UserLogin2') && authStore.isAuthenticated) {
      return next('/dashboard')
    }

    // Verificar permisos si la ruta tiene meta.permissions
    if (to.meta && to.meta.permissions && to.meta.permissions.length > 0) {
      const userPermissions = authStore.permissions || []
      const hasAccess = to.meta.permissions.some(permission =>
        userPermissions.includes(permission) || userPermissions.some(p => p.name === permission)
      )

      if (!hasAccess) {
        return next({ name: 'Error403' })
      }
    }

    next()
  })

  Router.afterEach(async (to) => {
    const { pinia } = await import('src/store/index')
    const { useSettingsStore } = await import('src/store/settings-store')
    const settingsStore = useSettingsStore(pinia)
    const routeTitle = to.meta && to.meta.title ? to.meta.title : ''
    settingsStore.updateDocumentTitle(routeTitle)
  })

  return Router
})
