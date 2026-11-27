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
          <p class="text-sm text-green-800">
            Upload a Barangay ID, billing statement, or HOA Certificate so a barangay admin can verify that you live in Barangay Manggahan before your account is activated.
          </p>
        </div>

        <!-- API error -->
        <div v-if="apiError" class="flex items-center gap-2 bg-red-50 border border-red-200 rounded-xl px-4 py-3 mb-5">
          <ExclamationCircleIcon class="w-4 h-4 text-red-500 flex-shrink-0" />
          <p class="text-sm text-red-700">{{ apiError }}</p>
        </div>

        <form @submit.prevent="handleRegister" novalidate>
          <p class="mb-4 text-xs leading-relaxed text-gray-600">
            Your typed details stay in this tab if you refresh. Re-enter your password, select your document, and confirm consent again after a refresh.
          </p>
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
            @update:model-value="clearEmailError"
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

          <!-- Proof of residency -->
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1.5">
              Proof of residency <span class="text-red-500">*</span>
            </label>

            <select
              v-model="form.proof_type"
              class="w-full rounded-xl border border-gray-200 px-3.5 py-2.5 text-sm text-gray-700 bg-white focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary mb-1"
            >
              <option value="">Select document type</option>
              <option value="barangay_id">Barangay ID</option>
              <option value="billing_statement">Billing statement</option>
              <option value="hoa_certificate">HOA Certificate</option>
            </select>
            <p v-if="errors.proof_type" class="text-xs text-red-500 mb-2">{{ errors.proof_type }}</p>

            <label
              class="mt-2 flex flex-col items-center justify-center gap-1 border-2 border-dashed rounded-xl px-4 py-5 cursor-pointer hover:bg-gray-50 transition-colors"
              :class="errors.proof ? 'border-red-300' : 'border-gray-200'"
            >
              <ArrowUpTrayIcon class="w-5 h-5 text-gray-400" />
              <span class="text-sm text-gray-600 text-center break-all">
                {{ proofFile ? proofFile.name : 'Tap to upload a photo or PDF' }}
              </span>
              <span class="text-xs text-gray-400">JPG, PNG or PDF, up to 5 MB</span>
              <input
                type="file"
                accept="image/jpeg,image/png,application/pdf"
                class="hidden"
                @change="onProofChange"
              />
            </label>

            <img
              v-if="proofPreview"
              :src="proofPreview"
              alt="Proof preview"
              class="mt-2 max-h-40 mx-auto rounded-lg border border-gray-200 object-contain"
            />
            <p v-if="errors.proof" class="text-xs text-red-500 mt-1">{{ errors.proof }}</p>
            <p class="text-xs text-gray-400 mt-1.5">
              Used only to verify residency. Only barangay admins can view it.
            </p>
          </div>

          <!-- Data privacy consent -->
          <PrivacyConsent v-model="consent" :error="errors.consent" />

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
import { ref, reactive, onBeforeUnmount } from 'vue'
import { useRouter, RouterLink } from 'vue-router'
import { useToast } from 'vue-toastification'
import api from '@/services/api'
import {
  BuildingOffice2Icon,
  EyeIcon,
  EyeSlashIcon,
  ExclamationCircleIcon,
  InformationCircleIcon,
  UserPlusIcon,
  ArrowUpTrayIcon,
} from '@heroicons/vue/24/outline'
import { useAuthStore } from '@/stores/auth'
import AppInput from '@/components/common/AppInput.vue'
import AppButton from '@/components/common/AppButton.vue'
import GoogleIcon from '@/components/common/GoogleIcon.vue'
import PrivacyConsent from '@/components/common/PrivacyConsent.vue'
import { useFormDraft } from '@/composables/useFormDraft'

const authStore = useAuthStore()
const router    = useRouter()
const toast     = useToast()

const MAX_PROOF_BYTES = 5 * 1024 * 1024
const ALLOWED_TYPES   = ['image/jpeg', 'image/png', 'application/pdf']

const loading      = ref(false)
const showPassword = ref(false)
const showConfirm  = ref(false)
const apiError     = ref('')

const proofFile    = ref(null)
const proofPreview = ref('')
const consent      = ref(false)

const { form, clearDraft } = useFormDraft('registration', {
  first_name:            '',
  last_name:             '',
  email:                 '',
  contact_number:        '',
  address:               '',
  proof_type:            '',
}, { guest: true })
form.password = ''
form.password_confirmation = ''

const errors = reactive({
  first_name:            '',
  last_name:             '',
  email:                 '',
  contact_number:        '',
  address:               '',
  proof_type:            '',
  proof:                 '',
  consent:               '',
  password:              '',
  password_confirmation: '',
})

function clearPreview() {
  if (proofPreview.value) URL.revokeObjectURL(proofPreview.value)
  proofPreview.value = ''
}

function onProofChange(e) {
  const file = e.target.files?.[0]
  errors.proof = ''
  clearPreview()
  proofFile.value = null

  if (!file) return

  if (!ALLOWED_TYPES.includes(file.type)) {
    errors.proof = 'Only JPG, PNG or PDF files are allowed.'
    e.target.value = ''
    return
  }
  if (file.size > MAX_PROOF_BYTES) {
    errors.proof = 'File is too large. Maximum is 5 MB.'
    e.target.value = ''
    return
  }

  proofFile.value = file
  if (file.type.startsWith('image/')) {
    proofPreview.value = URL.createObjectURL(file)
  }
}

onBeforeUnmount(clearPreview)

function validate() {
  errors.first_name            = !form.first_name            ? 'First name is required.'       : ''
  errors.last_name             = !form.last_name             ? 'Last name is required.'        : ''
  errors.email                 = !form.email                 ? 'Email is required.'            : ''
  errors.contact_number        = !form.contact_number        ? 'Contact number is required.'   : ''
  errors.address               = !form.address               ? 'Address is required.'          : ''
  errors.proof_type            = !form.proof_type            ? 'Select a document type.'       : ''
  if (!proofFile.value && !errors.proof) errors.proof = 'Please upload your proof of residency.'
  errors.consent               = !consent.value              ? 'Kailangan mong pumayag para magpatuloy.' : ''
  errors.password              = form.password.length < 8    ? 'At least 8 characters.'        : ''
  errors.password_confirmation = form.password !== form.password_confirmation
                                  ? 'Passwords do not match.' : ''
  return Object.values(errors).every(e => !e)
}

function clearEmailError() {
  if (apiError.value === errors.email) apiError.value = ''
  errors.email = ''
}

async function handleRegister() {
  if (loading.value || !validate()) return
  loading.value  = true
  apiError.value = ''
  try {
    const fd = new FormData()
    Object.entries(form).forEach(([key, value]) => fd.append(key, value))
    fd.append('proof', proofFile.value)
    fd.append('privacy_consent', consent.value ? '1' : '0')

    await authStore.register(fd)
    clearDraft()
    toast.success('Application submitted for admin approval.')
    await router.replace({
      name: 'pending-approval',
      state: { registrationSubmitted: true },
    })
  } catch (err) {
    const serverErrors = err.response?.data?.errors
    if (serverErrors) {
      Object.keys(serverErrors).forEach((key) => {
        if (key === 'privacy_consent') errors.consent = serverErrors[key][0]
        else if (key in errors) errors[key] = serverErrors[key][0]
      })
    }
    apiError.value = err.response?.data?.message || 'Registration failed. Please try again.'
  } finally {
    loading.value = false
  }
}

async function handleGoogle() {
  try {
    const { data } = await api.get('/auth/google/redirect')
    window.location.href = data.url
  } catch (_) {
    toast.error('Failed to connect to Google. Please try again.')
  }
}
</script>
