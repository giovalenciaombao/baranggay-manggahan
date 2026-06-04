import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/services/api'

export const useAuthStore = defineStore('auth', () => {
  const user    = ref(null)
  const token   = ref(localStorage.getItem('auth_token') || null)
  const checked = ref(false)

  const isAuthenticated = computed(() => !!token.value && !!user.value)
  const isAdmin         = computed(() => user.value?.role === 'admin')
  const isResident      = computed(() => user.value?.role === 'resident')
  const isApproved      = computed(() => user.value?.status === 'active')

  async function login(credentials) {
    const { data } = await api.post('/auth/login', credentials)
    setAuth(data.token, data.user)
    return data
  }

  async function register(payload) {
    const { data } = await api.post('/auth/register', payload)
    return data
  }

  async function logout() {
    try {
      await api.post('/auth/logout')
    } catch (_) {
      // still clear even if request fails
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

  async function updatePassword(payload) {
    const { data } = await api.put('/auth/password', payload)
    return data
  }

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
    login, register, logout, checkAuth, updateProfile, updatePassword,
    setAuth, clearAuth,
  }
})