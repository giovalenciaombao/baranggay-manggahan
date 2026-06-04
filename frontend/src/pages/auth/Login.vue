<template>
  <div class="min-h-[calc(100vh-4rem)] flex items-center justify-center px-4 py-12">
    <div class="w-full max-w-md">

      <!-- Card -->
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
        <div class="mb-7 text-center">
          <h2 class="font-display text-2xl font-semibold text-primary-dark">Welcome back</h2>
          <p class="text-sm text-gray-500 mt-1">Sign in to your resident account</p>
        </div>

        <form @submit.prevent="handleLogin" novalidate>
          <AppInput
            v-model="form.email"
            id="email"
            label="Email address"
            type="email"
            placeholder="you@example.com"
            :error="errors.email"
            required
          />

          <AppInput
            v-model="form.password"
            id="password"
            label="Password"
            :type="showPassword ? 'text' : 'password'"
            placeholder="••••••••"
            :error="errors.password"
            required
          >
            <template #suffix>
              <button type="button" @click="showPassword = !showPassword" class="text-gray-400 hover:text-gray-600">
                <EyeIcon v-if="!showPassword" class="w-4 h-4" />
                <EyeSlashIcon v-else class="w-4 h-4" />
              </button>
            </template>
          </AppInput>

          <!-- Error message -->
          <div v-if="apiError" class="mb-4 px-4 py-3 bg-red-50 border border-red-200 rounded-xl">
            <p class="text-sm text-red-600">{{ apiError }}</p>
          </div>

          <AppButton type="submit" variant="primary" class="w-full mt-2" :loading="loading">
            Sign in
          </AppButton>
        </form>

        <p class="mt-6 text-center text-sm text-gray-500">
          Don't have an account?
          <RouterLink to="/register" class="text-primary font-medium hover:underline">Register here</RouterLink>
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useRouter, RouterLink } from 'vue-router'
import { useToast } from 'vue-toastification'
import { EyeIcon, EyeSlashIcon } from '@heroicons/vue/24/outline'
import { useAuthStore } from '@/stores/auth'
import AppInput from '@/components/common/AppInput.vue'
import AppButton from '@/components/common/AppButton.vue'

const authStore = useAuthStore()
const router    = useRouter()
const toast     = useToast()

const loading      = ref(false)
const showPassword = ref(false)
const apiError     = ref('')

const form = reactive({ email: '', password: '' })
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
    toast.success(`Welcome, ${data.user.name}!`)
    const redirect = new URLSearchParams(window.location.search).get('redirect')
    router.push(redirect || (data.user.role === 'admin' ? '/admin' : '/portal'))
  } catch (err) {
    apiError.value = err.response?.data?.message || 'Login failed. Please try again.'
  } finally {
    loading.value = false
  }
}
</script>
