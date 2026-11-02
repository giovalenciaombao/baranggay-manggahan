<template>
  <div class="min-h-screen flex items-center justify-center bg-primary-light">
    <div class="text-center">
      <div class="w-12 h-12 rounded-2xl bg-primary-dark flex items-center justify-center mx-auto mb-4">
        <svg class="w-6 h-6 text-white animate-spin" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
        </svg>
      </div>
      <p class="text-sm text-gray-500">Signing you in with Google...</p>
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useToast } from 'vue-toastification'
import api from '@/services/api'

const router    = useRouter()
const authStore = useAuthStore()
const toast     = useToast()

const errorMessages = {
  google_failed:      'Google sign-in failed. Please try again.',
  account_suspended:  'Your account has been suspended or rejected.',
  google_unverified:  'Your Google email is not verified. Please use a verified Google account.',
  admin_use_password: 'Staff accounts must sign in with email and password.',
}

onMounted(async () => {
  const params   = new URLSearchParams(window.location.search)
  const token    = params.get('token')
  const redirect = params.get('redirect')
  const error    = params.get('error')

  // Handle errors
  if (error) {
    toast.error(errorMessages[error] || 'Google sign-in failed. Please try again.')
    router.push('/login')
    return
  }

  if (!token) {
    router.push('/login')
    return
  }

  // Set token first
  localStorage.setItem('auth_token', token)

  // Fetch user data
  try {
    const { data } = await api.get('/auth/me')
    authStore.setAuth(token, data.user)
    toast.success(`Welcome, ${data.user.first_name}!`)
    router.push(redirect || (data.user.role === 'admin' ? '/admin' : '/portal'))
  } catch (_) {
    localStorage.removeItem('auth_token')
    toast.error('Something went wrong. Please try again.')
    router.push('/login')
  }
})
</script>