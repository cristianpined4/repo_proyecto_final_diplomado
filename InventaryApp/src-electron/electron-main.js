import { app, BrowserWindow, session } from 'electron'
import path from 'path'
import os from 'os'

// needed in case process is undefined under Linux
const platform = process.platform || os.platform()

let mainWindow
let xsrfToken = null

function setupNetworkInterceptors () {
  const backendUrl = (process.env.BACKEND_URL || '').replace(/\/$/, '')
  if (!backendUrl) return
  const filter = { urls: [`${backendUrl}/*`] }

  // 1. Inyectar Origin y Referer de Hostinger para que Sanctum lo acepte como SPA y no aplique restricciones de CORS
  session.defaultSession.webRequest.onBeforeSendHeaders(filter, (details, callback) => {
    details.requestHeaders['Origin'] = backendUrl
    details.requestHeaders['Referer'] = `${backendUrl}/`
    if (xsrfToken && !details.requestHeaders['X-XSRF-TOKEN']) {
      details.requestHeaders['X-XSRF-TOKEN'] = xsrfToken
    }
    callback({ requestHeaders: details.requestHeaders })
  })

  // 2. Capturar XSRF-TOKEN y reescribir Set-Cookie para permitir cookies en esquemas file:// de Electron
  session.defaultSession.webRequest.onHeadersReceived(filter, (details, callback) => {
    const responseHeaders = { ...details.responseHeaders }
    const setCookieKey = Object.keys(responseHeaders).find(
      (k) => k.toLowerCase() === 'set-cookie'
    )

    if (setCookieKey && responseHeaders[setCookieKey]) {
      for (const cookieStr of responseHeaders[setCookieKey]) {
        const match = cookieStr.match(/XSRF-TOKEN=([^;]+)/i)
        if (match) {
          xsrfToken = decodeURIComponent(match[1])
        }
      }

      // Convertir a SameSite=None; Secure para que Chromium guarde y envíe cookies sin bloquearlas
      responseHeaders[setCookieKey] = responseHeaders[setCookieKey].map((cookie) => {
        let c = cookie.replace(/SameSite=(Lax|Strict)/gi, 'SameSite=None')
        if (!/SameSite=/i.test(c)) {
          c += '; SameSite=None'
        }
        if (!/Secure/i.test(c)) {
          c += '; Secure'
        }
        return c
      })
    }

    callback({ responseHeaders })
  })
}

function createWindow () {
  /**
   * Initial window options
   */
  mainWindow = new BrowserWindow({
    icon: path.resolve(__dirname, 'icons/favicon-128x128.png'), // tray / window icon
    width: 1000,
    height: 600,
    useContentSize: true,
    webPreferences: {
      contextIsolation: true,
      webSecurity: false, // Permite peticiones y cookies cross-origin en Electron
      // More info: https://v2.quasar.dev/quasar-cli-vite/developing-electron-apps/electron-preload-script
      preload: path.resolve(__dirname, process.env.QUASAR_ELECTRON_PRELOAD)
    }
  })

  mainWindow.loadURL(process.env.APP_URL)

  if (process.env.DEBUGGING) {
    // if on DEV or Production with debug enabled
    mainWindow.webContents.openDevTools()
  } else {
    // we're on production; no access to devtools pls
    mainWindow.webContents.on('devtools-opened', () => {
      mainWindow.webContents.closeDevTools()
    })
  }

  mainWindow.on('closed', () => {
    mainWindow = null
  })
}

app.whenReady().then(() => {
  setupNetworkInterceptors()
  createWindow()
})

app.on('window-all-closed', () => {
  if (platform !== 'darwin') {
    app.quit()
  }
})

app.on('activate', () => {
  if (mainWindow === null) {
    createWindow()
  }
})
