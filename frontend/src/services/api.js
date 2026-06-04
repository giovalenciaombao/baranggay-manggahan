import axios from 'axios'
import { useAuthStore } from '@/stores/auth'
import router from '@/router'

const api = axios.create({
  baseURL: '/api',
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json'
  },
  withCredentials: true // needed for Sanctum cookies
})

// ─── Request interceptor — attach token ──────────────────────────────────────
api.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('auth_token')
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }
    return config
  },
  (error) => Promise.reject(error)
)

// ─── Response interceptor — handle global errors ─────────────────────────────
api.interceptors.response.use(
  (response) => response,
  async (error) => {
    const status = error.response?.status

    if (status === 401) {
      // Token expired / not authenticated
      const authStore = useAuthStore()
      authStore.logout()
      router.push({ name: 'login' })
    }

    if (status === 403) {
      router.push({ name: 'not-found' })
    }

    return Promise.reject(error)
  }
)

export default api
