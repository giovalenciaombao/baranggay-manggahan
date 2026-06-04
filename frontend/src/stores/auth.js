import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/services/api'

export const useAuthStore = defineStore('auth', () => {
  const user    = ref(null)
  const token   = ref(localStorage.getItem('auth_token') || null)
  const checked = ref(false)

  // ─── Getters ───────────────────────────────────────────────────────────────
  const isAuthenticated = computed(() => !!token.value && !!user.value)
  const isAdmin         = computed(() => user.value?.role === 'admin')
  const isResident      = computed(() => user.value?.role === 'resident')
  const isApproved      = computed(() => user.value?.status === 'approved')

  // ─── Actions ───────────────────────────────────────────────────────────────
  async function login(credentials) {
    // mock lang muna
    if (credentials.email === 'admin@manggahan.ph' && credentials.password === 'password') {
      this.user = { first_name: 'Jonas', last_name: 'Dev', role: 'admin' }
      this.token = 'mock-token'
      return { user: this.user }
    }
    if (credentials.email === 'resident@manggahan.ph' && credentials.password === 'password') {
      this.user = { first_name: 'Juan', last_name: 'dela Cruz', role: 'resident' }
      this.token = 'mock-token'
      return { user: this.user }
    }
    throw { response: { data: { message: 'Invalid credentials.' } } }
  }

  async function register(payload) {
    const { data } = await api.post('/auth/register', payload)
    return data
  }

  async function logout() {
    try {
      await api.post('/auth/logout')
    } catch (_) {
      // still clear local state even if request fails
    } finally {
      clearAuth()
    }
  }

  async function checkAuth() {
    checked.value = true
    if (!token.value) return

    try {
      const { data } = await api.get('/auth/me')
      user.value = data.user
    } catch (_) {
      clearAuth()
    }
  }

  async function updateProfile(payload) {
    const { data } = await api.put('/auth/profile', payload)
    user.value = data.user
    return data
  }

  // ─── Helpers ───────────────────────────────────────────────────────────────
  function setAuth(newToken, newUser) {
    token.value = newToken
    user.value  = newUser
    localStorage.setItem('auth_token', newToken)
  }

  function clearAuth() {
    token.value = null
    user.value  = null
    localStorage.removeItem('auth_token')
  }

  return {
    user, token, checked,
    isAuthenticated, isAdmin, isResident, isApproved,
    login, register, logout, checkAuth, updateProfile
  }
})
