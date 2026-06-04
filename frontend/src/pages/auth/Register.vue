<template>
  <div class="min-h-[calc(100vh-4rem)] flex items-center justify-center px-4 py-12 bg-primary-light">
    <div class="w-full max-w-md">
      <div class="card rounded-2xl p-8 sm:p-9">

        <!-- Logo + heading -->
        <div class="flex flex-col items-center mb-6 gap-3">
          <div class="w-11 h-11 bg-primary-dark rounded-xl flex items-center justify-center">
            <BuildingOffice2Icon class="w-5 h-5 text-primary-light" />
          </div>
          <div class="text-center">
            <h2 class="font-display text-xl font-semibold text-primary-dark">Create an account</h2>
            <p class="text-sm text-gray-400 mt-0.5">Register as a Barangay Manggahan resident</p>
          </div>
        </div>

        <!-- Pending approval notice -->
        <div class="flex items-start gap-2 bg-green-50 border border-green-200 rounded-xl px-4 py-3 mb-5">
          <InformationCircleIcon class="w-4 h-4 text-green-600 flex-shrink-0 mt-0.5" />
          <p class="text-sm text-green-800">Your account will be reviewed by a barangay admin before activation.</p>
        </div>

        <!-- API error -->
        <div v-if="apiError" class="flex items-center gap-2 bg-red-50 border border-red-200 rounded-xl px-4 py-3 mb-5">
          <ExclamationCircleIcon class="w-4 h-4 text-red-500 flex-shrink-0" />
          <p class="text-sm text-red-700">{{ apiError }}</p>
        </div>

        <form @submit.prevent="handleRegister" novalidate>
          <!-- Name row -->
          <div class="grid grid-cols-2 gap-3">
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
            v-model="form.contact_number"
            label="Contact number"
            type="tel"
            placeholder="09xx-xxx-xxxx"
            :error="errors.contact_number"
            required
          />

          <AppInput
            v-model="form.address"
            label="Home address"
            placeholder="House no., street, purok..."
            :error="errors.address"
            required
          />

          <AppInput
            v-model="form.password"
            label="Password"
            :type="showPassword ? 'text' : 'password'"
            placeholder="••••••••"
            :error="errors.password"
            hint="At least 8 characters"
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

          <AppInput
            v-model="form.password_confirmation"
            label="Confirm password"
            :type="showConfirm ? 'text' : 'password'"
            placeholder="••••••••"
            :error="errors.password_confirmation"
            required
          >
            <template #suffix>
              <button type="button" @click="showConfirm = !showConfirm"
                class="text-gray-400 hover:text-gray-600 transition-colors"
                :aria-label="showConfirm ? 'Hide password' : 'Show password'">
                <EyeSlashIcon v-if="showConfirm" class="w-4 h-4" />
                <EyeIcon v-else class="w-4 h-4" />
              </button>
            </template>
          </AppInput>

          <AppButton type="submit" variant="primary" class="w-full mt-1" :loading="loading">
            <UserPlusIcon class="w-4 h-4" />
            Create account
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
          Already have an account?
          <RouterLink to="/login" class="text-primary font-medium hover:underline">
            Sign in
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
import {
  BuildingOffice2Icon,
  EyeIcon,
  EyeSlashIcon,
  ExclamationCircleIcon,
  InformationCircleIcon,
  UserPlusIcon,
} from '@heroicons/vue/24/outline'
import { useAuthStore } from '@/stores/auth'
import AppInput from '@/components/common/AppInput.vue'
import AppButton from '@/components/common/AppButton.vue'
import GoogleIcon from '@/components/common/GoogleIcon.vue'

const authStore = useAuthStore()
const router    = useRouter()
const toast     = useToast()

const loading     = ref(false)
const showPassword = ref(false)
const showConfirm  = ref(false)
const apiError    = ref('')

const form = reactive({
  first_name:            '',
  last_name:             '',
  email:                 '',
  contact_number:        '',
  address:               '',
  password:              '',
  password_confirmation: '',
})

const errors = reactive({
  first_name:            '',
  last_name:             '',
  email:                 '',
  contact_number:        '',
  address:               '',
  password:              '',
  password_confirmation: '',
})

function validate() {
  errors.first_name            = !form.first_name            ? 'First name is required.'       : ''
  errors.last_name             = !form.last_name             ? 'Last name is required.'        : ''
  errors.email                 = !form.email                 ? 'Email is required.'            : ''
  errors.contact_number        = !form.contact_number        ? 'Contact number is required.'   : ''
  errors.address               = !form.address               ? 'Address is required.'          : ''
  errors.password              = form.password.length < 8    ? 'At least 8 characters.'        : ''
  errors.password_confirmation = form.password !== form.password_confirmation
                                  ? 'Passwords do not match.' : ''
  return Object.values(errors).every(e => !e)
}

async function handleRegister() {
  if (!validate()) return
  loading.value  = true
  apiError.value = ''
  try {
    await authStore.register(form)
    router.push('/pending-approval')
  } catch (err) {
    apiError.value = err.response?.data?.message || 'Registration failed. Please try again.'
  } finally {
    loading.value = false
  }
}

function handleGoogle() {
  window.location.href = '/api/auth/google/redirect'
}
</script>