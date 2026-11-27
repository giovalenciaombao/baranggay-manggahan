<template>
  <div class="min-h-[calc(100vh-4rem)] flex items-center justify-center px-4 py-12 bg-primary-light">
    <div class="w-full max-w-sm">
      <div class="card rounded-2xl p-8 sm:p-9">

        <!-- Logo + heading -->
        <div class="flex flex-col items-center mb-8 gap-3">
          <div class="w-11 h-11 bg-primary-dark rounded-xl flex items-center justify-center">
            <HomeIcon class="w-5 h-5 text-primary-light" />
          </div>
          <div class="text-center">
            <h2 class="font-display text-xl font-semibold text-primary-dark">Welcome back</h2>
            <p class="text-sm text-gray-400 mt-0.5">Sign in to your resident account</p>
          </div>
        </div>

        <!-- API error -->
        <div v-if="apiError" class="flex items-center gap-2 bg-red-50 border border-red-200 rounded-xl px-4 py-3 mb-5">
          <ExclamationCircleIcon class="w-4 h-4 text-red-500 flex-shrink-0" />
          <p class="text-sm text-red-700">{{ apiError }}</p>
        </div>

        <form @submit.prevent="handleLogin" novalidate>
          <AppInput
            v-model="form.email"
            label="Email address"
            type="email"
            placeholder="you@example.com"
            :error="errors.email"
            required
          />

          <AppInput
            v-model="form.password"
            label="Password"
            :type="showPassword ? 'text' : 'password'"
            placeholder="••••••••"
            :error="errors.password"
            required
          >
            <template #suffix>
              <button type="button" @click="showPassword = !showPassword"
                class="text-gray-400 hover:text-gray-600 transition-colors"
                :aria-label="showPassword ? 'Hide password' : 'Show password'">
                <EyeSlashIcon v-if="showPassword" class="w-4 h-4" />
                <EyeIcon v-else class="w-4 h-4" />
              </button>
            </template>
          </AppInput>

          <AppButton type="submit" variant="primary" class="w-full mt-1" :loading="loading">
            <ArrowRightEndOnRectangleIcon class="w-4 h-4" />
            Sign in
          </AppButton>
        </form>

        <!-- Divider -->
        <div class="flex items-center gap-3 my-5">
          <hr class="flex-1 border-gray-200" />
          <span class="text-xs text-gray-400">or</span>
          <hr class="flex-1 border-gray-200" />
        </div>

        <!-- Google -->
        <button
          @click="handleGoogle"
          :disabled="loading"
          class="w-full flex items-center justify-center gap-2.5 px-4 py-2.5 rounded-xl border border-gray-200 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors disabled:opacity-50"
        >
          <GoogleIcon />
          Continue with Google
        </button>

        <p class="mt-6 text-center text-sm text-gray-500">
          Don't have an account?
          <RouterLink to="/register" class="text-primary font-medium hover:underline">
            Register here
          </RouterLink>
        </p>

      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useRouter, RouterLink } from 'vue-router'
import { useToast } from 'vue-toastification'
import api from '@/services/api'
import { Capacitor } from '@capacitor/core'
import { Browser } from '@capacitor/browser'
import {
  HomeIcon,
  EyeIcon,
  EyeSlashIcon,
  ExclamationCircleIcon,
  ArrowRightEndOnRectangleIcon,
} from '@heroicons/vue/24/outline'
import { useAuthStore } from '@/stores/auth'
import AppInput from '@/components/common/AppInput.vue'
import AppButton from '@/components/common/AppButton.vue'
import GoogleIcon from '@/components/common/GoogleIcon.vue'

const authStore = useAuthStore()
const router    = useRouter()
const toast     = useToast()

const loading      = ref(false)
const showPassword = ref(false)
const apiError     = ref('')

const form   = reactive({ email: '', password: '' })
const errors = reactive({ email: '', password: '' })

function validate() {
  errors.email    = !form.email    ? 'Email is required.'    : ''
  errors.password = !form.password ? 'Password is required.' : ''
  return !errors.email && !errors.password
}

async function handleLogin() {
  if (!validate()) return
  loading.value  = true
  apiError.value = ''
  try {
    const data = await authStore.login(form)
    if (data.user.status === 'pending') {
      await router.push({ name: 'pending-approval' })
      return
    }
    toast.success(`Welcome, ${data.user.first_name}!`)
    const redirect = new URLSearchParams(window.location.search).get('redirect')
    router.push(redirect || (data.user.role === 'admin' ? '/admin' : '/portal'))
  } catch (err) {
    apiError.value = err.response?.data?.message || 'Unable to reach the server. Please try again.'
  } finally {
    loading.value = false
  }
}

async function handleGoogle() {
  try {
    const platform = Capacitor.isNativePlatform() ? 'native' : 'web'
    const { data } = await api.get('/auth/google/redirect', { params: { platform } })

    if (Capacitor.isNativePlatform()) {
      await Browser.open({ url: data.url })
    } else {
      window.location.href = data.url
    }
  } catch (_) {
    toast.error('Failed to connect to Google. Please try again.')
  }
}
</script>
