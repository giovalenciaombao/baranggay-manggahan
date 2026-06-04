<template>
  <div class="p-4 sm:p-6 max-w-xl mx-auto space-y-5">

    <!-- Header -->
    <div>
      <p class="text-sm text-gray-400 mt-0.5">Manage your personal information.</p>
    </div>

    <!-- Avatar + name -->
    <div class="card rounded-2xl p-5 flex items-center gap-4">
      <div class="w-16 h-16 rounded-full bg-primary flex items-center justify-center flex-shrink-0">
        <span class="text-xl font-semibold text-primary-light">{{ initials }}</span>
      </div>
      <div>
        <p class="text-base font-semibold text-primary-dark">{{ user?.first_name }} {{ user?.last_name }}</p>
        <p class="text-sm text-gray-400">{{ user?.email }}</p>
        <AppBadge variant="success" class="mt-1">Active</AppBadge>
      </div>
    </div>

    <!-- Edit form -->
    <div class="card rounded-2xl p-5 space-y-4">
      <h2 class="text-sm font-semibold text-primary-dark">Personal Information</h2>

      <!-- Success message -->
      <div v-if="saved" class="flex items-center gap-2 bg-green-50 border border-green-200 rounded-xl px-4 py-3">
        <CheckCircleIcon class="w-4 h-4 text-green-600 flex-shrink-0" />
        <p class="text-sm text-green-700">Profile updated successfully.</p>
      </div>

      <!-- API error -->
      <div v-if="apiError" class="flex items-center gap-2 bg-red-50 border border-red-200 rounded-xl px-4 py-3">
        <ExclamationCircleIcon class="w-4 h-4 text-red-500 flex-shrink-0" />
        <p class="text-sm text-red-700">{{ apiError }}</p>
      </div>

      <div class="grid grid-cols-2 gap-3">
        <AppInput
          v-model="form.first_name"
          label="First name"
          :error="errors.first_name"
          required
        />
        <AppInput
          v-model="form.last_name"
          label="Last name"
          :error="errors.last_name"
          required
        />
      </div>

      <AppInput
        v-model="form.email"
        label="Email address"
        type="email"
        :error="errors.email"
        required
      />

      <AppInput
        v-model="form.contact_number"
        label="Contact number"
        type="tel"
        :error="errors.contact_number"
        required
      />

      <AppInput
        v-model="form.address"
        label="Home address"
        :error="errors.address"
        required
      />

      <AppButton
        variant="primary"
        class="w-full"
        :loading="loading"
        @click="handleSave"
      >
        <CheckCircleIcon class="w-4 h-4" />
        Save Changes
      </AppButton>
    </div>

    <!-- Change password -->
    <div class="card rounded-2xl p-5 space-y-4">
      <h2 class="text-sm font-semibold text-primary-dark">Change Password</h2>

      <AppInput
        v-model="pwForm.current_password"
        label="Current password"
        :type="showCurrent ? 'text' : 'password'"
        placeholder="••••••••"
        :error="pwErrors.current_password"
        required
      >
        <template #suffix>
          <button type="button" @click="showCurrent = !showCurrent"
            class="text-gray-400 hover:text-gray-600 transition-colors">
            <EyeSlashIcon v-if="showCurrent" class="w-4 h-4" />
            <EyeIcon v-else class="w-4 h-4" />
          </button>
        </template>
      </AppInput>

      <AppInput
        v-model="pwForm.password"
        label="New password"
        :type="showNew ? 'text' : 'password'"
        placeholder="••••••••"
        hint="At least 8 characters"
        :error="pwErrors.password"
        required
      >
        <template #suffix>
          <button type="button" @click="showNew = !showNew"
            class="text-gray-400 hover:text-gray-600 transition-colors">
            <EyeSlashIcon v-if="showNew" class="w-4 h-4" />
            <EyeIcon v-else class="w-4 h-4" />
          </button>
        </template>
      </AppInput>

      <AppInput
        v-model="pwForm.password_confirmation"
        label="Confirm new password"
        :type="showConfirm ? 'text' : 'password'"
        placeholder="••••••••"
        :error="pwErrors.password_confirmation"
        required
      >
        <template #suffix>
          <button type="button" @click="showConfirm = !showConfirm"
            class="text-gray-400 hover:text-gray-600 transition-colors">
            <EyeSlashIcon v-if="showConfirm" class="w-4 h-4" />
            <EyeIcon v-else class="w-4 h-4" />
          </button>
        </template>
      </AppInput>

      <AppButton
        variant="outline"
        class="w-full"
        :loading="pwLoading"
        @click="handlePasswordChange"
      >
        Update Password
      </AppButton>
    </div>

  </div>
</template>

<script setup>
import { ref, reactive, computed } from 'vue'
import { useToast } from 'vue-toastification'
import {
  CheckCircleIcon,
  ExclamationCircleIcon,
  EyeIcon,
  EyeSlashIcon,
} from '@heroicons/vue/24/outline'
import api from '@/services/api'
import { useAuthStore } from '@/stores/auth'
import AppInput from '@/components/common/AppInput.vue'
import AppButton from '@/components/common/AppButton.vue'
import AppBadge from '@/components/common/AppBadge.vue'

const authStore = useAuthStore()
const toast     = useToast()
const user      = computed(() => authStore.user)

const initials = computed(() => {
  if (!user.value) return '?'
  return `${user.value.first_name?.[0] ?? ''}${user.value.last_name?.[0] ?? ''}`.toUpperCase()
})

// Profile form
const loading  = ref(false)
const saved    = ref(false)
const apiError = ref('')

const form = reactive({
  first_name:     user.value?.first_name     ?? '',
  last_name:      user.value?.last_name      ?? '',
  email:          user.value?.email          ?? '',
  contact_number: user.value?.contact_number ?? '',
  address:        user.value?.address        ?? '',
})

const errors = reactive({
  first_name: '', last_name: '', email: '', contact_number: '', address: '',
})

function validateProfile() {
  errors.first_name     = !form.first_name     ? 'Required.' : ''
  errors.last_name      = !form.last_name      ? 'Required.' : ''
  errors.email          = !form.email          ? 'Required.' : ''
  errors.contact_number = !form.contact_number ? 'Required.' : ''
  errors.address        = !form.address        ? 'Required.' : ''
  return Object.values(errors).every(e => !e)
}

async function handleSave() {
  if (!validateProfile()) return
  loading.value  = true
  apiError.value = ''
  saved.value    = false
  try {
    await authStore.updateProfile(form)
    saved.value = true
    toast.success('Profile updated!')
  } catch (err) {
    apiError.value = err.response?.data?.message || 'Failed to update profile.'
  } finally {
    loading.value = false
  }
}

// Password form
const pwLoading   = ref(false)
const showCurrent = ref(false)
const showNew     = ref(false)
const showConfirm = ref(false)

const pwForm = reactive({
  current_password:      '',
  password:              '',
  password_confirmation: '',
})

const pwErrors = reactive({
  current_password:      '',
  password:              '',
  password_confirmation: '',
})

function validatePassword() {
  pwErrors.current_password      = !pwForm.current_password       ? 'Required.'              : ''
  pwErrors.password              = pwForm.password.length < 8     ? 'At least 8 characters.' : ''
  pwErrors.password_confirmation = pwForm.password !== pwForm.password_confirmation
                                    ? 'Passwords do not match.'   : ''
  return Object.values(pwErrors).every(e => !e)
}

async function handlePasswordChange() {
  if (!validatePassword()) return
  pwLoading.value = true
  try {
    await authStore.updatePassword(pwForm)
    toast.success('Password updated!')
    pwForm.current_password      = ''
    pwForm.password              = ''
    pwForm.password_confirmation = ''
  } catch (err) {
    toast.error(err.response?.data?.message || 'Failed to update password.')
  } finally {
    pwLoading.value = false
  }
}
</script>