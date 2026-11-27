import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/services/api'

const multipart = { headers: { 'Content-Type': 'multipart/form-data' } }

export const useAuthStore = defineStore('auth', () => {
  const user    = ref(null)
  const token   = ref(localStorage.getItem('auth_token') || null)
  const checked = ref(false)
  const profilePhotoUrl = ref('')
  const profilePhotoLoading = ref(false)
  const profilePhotoLoadError = ref('')

  const isAuthenticated = computed(() => !!token.value && !!user.value)
  const isAdmin         = computed(() => user.value?.role === 'admin')
  const isResident      = computed(() => user.value?.role === 'resident')
  const isApproved      = computed(() => user.value?.status === 'active')

  async function login(credentials) {
    const { data } = await api.post('/auth/login', credentials)
    setAuth(data.token, data.user)
    return data
  }

  async function loginWithToken(newToken) {
    token.value = newToken
    localStorage.setItem('auth_token', newToken)
    return checkAuth()
  }

  // payload is FormData (includes the proof file)
  async function register(payload) {
    const { data } = await api.post('/auth/register', payload, multipart)
    return data
  }

  // For pending (Google) users who still need to submit proof
  async function uploadProof(payload) {
    const { data } = await api.post('/auth/proof', payload, multipart)
    user.value = data.user
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

  function setProfilePhoto(blob = null) {
    if (profilePhotoUrl.value) URL.revokeObjectURL(profilePhotoUrl.value)
    profilePhotoUrl.value = blob ? URL.createObjectURL(blob) : ''
  }

  async function loadProfilePhoto() {
    if (profilePhotoLoading.value) return
    const sessionToken = token.value
    profilePhotoLoading.value = true
    profilePhotoLoadError.value = ''
    try {
      const response = await api.get('/auth/profile/photo', { responseType: 'blob' })
      if (token.value === sessionToken && response.status === 200) setProfilePhoto(response.data)
    } catch {
      if (token.value === sessionToken) profilePhotoLoadError.value = 'Could not load your profile photo.'
    } finally {
      if (token.value === sessionToken) profilePhotoLoading.value = false
    }
  }

  async function updateProfilePhoto(file) {
    const sessionToken = token.value
    const formData = new FormData()
    formData.append('photo', file)
    await api.post('/auth/profile/photo', formData, multipart)
    if (token.value === sessionToken) {
      setProfilePhoto(file)
      profilePhotoLoadError.value = ''
    }
  }

  function setAuth(newToken, newUser) {
    token.value = newToken
    user.value  = newUser
    localStorage.setItem('auth_token', newToken)
  }

  function clearAuth() {
    setProfilePhoto()
    profilePhotoLoading.value = false
    profilePhotoLoadError.value = ''
    token.value = null
    user.value  = null
    localStorage.removeItem('auth_token')
  }

  return {
    user, token, checked,
    profilePhotoUrl, profilePhotoLoading, profilePhotoLoadError,
    loadProfilePhoto, updateProfilePhoto,
    isAuthenticated, isAdmin, isResident, isApproved,
    login, loginWithToken, register, uploadProof, logout, checkAuth, updateProfile, updatePassword,
    setAuth, clearAuth,
  }
})
