import { createApp } from 'vue'
import { createPinia } from 'pinia'
import Toast, { POSITION } from 'vue-toastification'
import 'vue-toastification/dist/index.css'
import { Capacitor } from '@capacitor/core'
import { App as CapApp } from '@capacitor/app'
import { Browser } from '@capacitor/browser'

import App from './App.vue'
import router from './router'
import './assets/main.css'
import { useAuthStore } from '@/stores/auth'

const app = createApp(App)

app.use(createPinia())
app.use(router)
app.use(Toast, {
  position: POSITION.TOP_RIGHT,
  timeout: 3500,
  closeOnClick: true,
  pauseOnHover: true,
  draggable: true,
  hideProgressBar: false,
  toastClassName: 'brgy-toast',
})

app.mount('#app')

// Handle deep link redirect back from system browser after Google OAuth
if (Capacitor.isNativePlatform()) {
  CapApp.addListener('appUrlOpen', async (event) => {
    const url = new URL(event.url)
    const token = url.searchParams.get('token')
    const redirect = url.searchParams.get('redirect') || '/portal'

    await Browser.close()

    if (token) {
      const authStore = useAuthStore()
      await authStore.loginWithToken(token)
      router.push(redirect)
    }
  })
}