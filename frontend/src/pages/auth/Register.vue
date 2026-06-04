<template>
  <div class="min-h-[calc(100vh-4rem)] flex items-center justify-center px-4 py-12">
    <div class="w-full max-w-lg">

      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
        <div class="mb-7 text-center">
          <h2 class="font-display text-2xl font-semibold text-primary-dark">Create an account</h2>
          <p class="text-sm text-gray-500 mt-1">Register as a Brgy. Manggahan resident</p>
        </div>

        <form @submit.prevent="handleRegister" novalidate>
          <!-- Row: first + last name -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-4">
            <AppInput
              v-model="form.first_name"
              label="First name"
              placeholder="Juan"
              :error="errors.first_name"
              required
            />
            <AppInput
              v-model="form.last_name"
              label="Last name"
              placeholder="dela Cruz"
              :error="errors.last_name"
              required
            />
          </div>

          <AppInput
            v-model="form.email"
            label="Email address"
            type="email"
            placeholder="you@example.com"
            :error="errors.email"
            required
          />

          <AppInput
            v-model="form.phone"
            label="Mobile number"
            type="tel"
            placeholder="09XXXXXXXXX"
            :error="errors.phone"
          />

          <AppInput
            v-model="form.address"
            label="Home address in Brgy. Manggahan"
            placeholder="Block, Lot, Street"
            :error="errors.address"
            required
          />

          <AppInput
            v-model="form.password"
            label="Password"
            :type="showPassword ? 'text' : 'password'"
            placeholder="At least 8 characters"
            :error="errors.password"
            required
          >
            <template #suffix>
              <button type="button" @click="showPassword = !showPassword" class="text-gray-400">
                <EyeIcon v-if="!showPassword" class="w-4 h-4" />
                <EyeSlashIcon v-else class="w-4 h-4" />
              </button>
            </template>
          </AppInput>

          <AppInput
            v-model="form.password_confirmation"
            label="Confirm password"
            :type="showPassword ? 'text' : 'password'"
            placeholder="Repeat password"
            :error="errors.password_confirmation"
            required
          />

          <!-- Note -->
          <div class="mb-5 px-4 py-3 bg-primary-50 border border-primary/20 rounded-xl text-sm text-primary-dark/70">
            📋 Your account will be reviewed by a barangay admin before activation.
          </div>

          <!-- API error -->
          <div v-if="apiError" class="mb-4 px-4 py-3 bg-red-50 border border-red-200 rounded-xl">
            <p class="text-sm text-red-600">{{ apiError }}</p>
          </div>

          <AppButton type="submit" variant="primary" class="w-full" :loading="loading">
            Submit Registration
          </AppButton>
        </form>

        <p class="mt-6 text-center text-sm text-gray-500">
          Already registered?
          <RouterLink to="/login" class="text-primary font-medium hover:underline">Sign in</RouterLink>
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

const form = reactive({
  first_name: '', last_name: '', email: '',
  phone: '', address: '', password: '', password_confirmation: ''
})

const errors = reactive({
  first_name: '', last_name: '', email: '',
  phone: '', address: '', password: '', password_confirmation: ''
})

function validate() {
  let ok = true
  const required = ['first_name', 'last_name', 'email', 'address', 'password', 'password_confirmation']
  required.forEach(f => {
    errors[f] = !form[f] ? 'This field is required.' : ''
    if (errors[f]) ok = false
  })
  if (form.password && form.password.length < 8) {
    errors.password = 'Password must be at least 8 characters.'; ok = false
  }
  if (form.password !== form.password_confirmation) {
    errors.password_confirmation = 'Passwords do not match.'; ok = false
  }
  return ok
}

async function handleRegister() {
  if (!validate()) return
  loading.value  = true
  apiError.value = ''

  try {
    await authStore.register({
      name: `${form.first_name} ${form.last_name}`,
      ...form
    })
    toast.success('Registration submitted! Please check your email to verify.')
    router.push({ name: 'verify-email' })
  } catch (err) {
    const errs = err.response?.data?.errors
    if (errs) {
      Object.keys(errs).forEach(k => { if (errors[k] !== undefined) errors[k] = errs[k][0] })
    } else {
      apiError.value = err.response?.data?.message || 'Registration failed.'
    }
  } finally {
    loading.value = false
  }
}
</script>
