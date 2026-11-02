<template>
  <div class="min-h-[60vh] flex flex-col items-center justify-center px-4 py-10 text-center gap-4">

    <!-- Loading (habang kinukuha ang account galing Google) -->
    <template v-if="booting">
      <p class="text-sm text-gray-400">Loading your account...</p>
    </template>

    <!-- Kailangan pang mag-upload ng proof (Google sign-ups) -->
    <template v-else-if="needsProof">
      <div class="w-16 h-16 rounded-2xl bg-yellow-50 flex items-center justify-center">
        <ArrowUpTrayIcon class="w-8 h-8 text-yellow-500" />
      </div>
      <h2 class="font-display text-xl font-semibold text-primary-dark">One more step</h2>
      <p class="text-sm text-gray-500 max-w-sm">
        To protect the system, we need proof that you live in Barangay Manggahan.
        Upload a Barangay ID or a billing statement. A barangay admin will review it.
      </p>

      <form @submit.prevent="submitProof" class="w-full max-w-sm text-left space-y-3 mt-2">
        <div v-if="apiError" class="flex items-center gap-2 bg-red-50 border border-red-200 rounded-xl px-4 py-3">
          <ExclamationCircleIcon class="w-4 h-4 text-red-500 flex-shrink-0" />
          <p class="text-sm text-red-700">{{ apiError }}</p>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Document type</label>
          <select
            v-model="proofType"
            class="w-full rounded-xl border border-gray-200 px-3.5 py-2.5 text-sm text-gray-700 bg-white focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary"
          >
            <option value="">Select document type</option>
            <option value="barangay_id">Barangay ID</option>
            <option value="billing_statement">Billing statement</option>
          </select>
          <p v-if="errors.proof_type" class="text-xs text-red-500 mt-1">{{ errors.proof_type }}</p>
        </div>

        <div>
          <label
            class="flex flex-col items-center justify-center gap-1 border-2 border-dashed rounded-xl px-4 py-5 cursor-pointer hover:bg-gray-50 transition-colors"
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
        </div>

        <AppButton type="submit" variant="primary" class="w-full" :loading="loading">
          Submit proof
        </AppButton>
      </form>
    </template>

    <!-- Under review -->
    <template v-else>
      <div class="w-16 h-16 rounded-2xl bg-yellow-50 flex items-center justify-center">
        <svg class="w-8 h-8 text-yellow-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
      </div>
      <h2 class="font-display text-xl font-semibold text-primary-dark">Account Pending Approval</h2>
      <p class="text-sm text-gray-500 max-w-sm">
        Your proof of residency is under review by the barangay admin.
        Once your account is approved, you can sign in.
      </p>
    </template>

    <button @click="backToLogin" class="btn-secondary text-sm">
      Back to Login
    </button>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onBeforeUnmount } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'vue-toastification'
import { ArrowUpTrayIcon, ExclamationCircleIcon } from '@heroicons/vue/24/outline'
import { useAuthStore } from '@/stores/auth'
import AppButton from '@/components/common/AppButton.vue'

const authStore = useAuthStore()
const route     = useRoute()
const router    = useRouter()
const toast     = useToast()

const MAX_PROOF_BYTES = 5 * 1024 * 1024
const ALLOWED_TYPES   = ['image/jpeg', 'image/png', 'application/pdf']

const booting  = ref(true)
const loading  = ref(false)
const apiError = ref('')

const proofType    = ref('')
const proofFile    = ref(null)
const proofPreview = ref('')
const errors       = reactive({ proof_type: '', proof: '' })

// Logged in (Google) + pending + wala pang proof
const needsProof = computed(() =>
  authStore.isAuthenticated &&
  authStore.user?.status === 'pending' &&
  !authStore.user?.has_proof
)

onMounted(async () => {
  // Galing sa Google redirect: /pending-approval?token=...
  const queryToken = route.query.token
  if (queryToken) {
    await authStore.loginWithToken(String(queryToken))
    // Tanggalin ang token sa URL
    router.replace({ name: 'pending-approval' })
  }

  // Kung na-approve na pala, diretso na sa portal
  if (authStore.isAuthenticated && authStore.user?.status === 'active') {
    router.replace(authStore.user.role === 'admin' ? '/admin' : '/portal')
    return
  }

  booting.value = false
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

async function submitProof() {
  errors.proof_type = proofType.value ? '' : 'Select a document type.'
  if (!proofFile.value && !errors.proof) errors.proof = 'Please upload your proof of residency.'
  if (errors.proof_type || errors.proof) return

  loading.value  = true
  apiError.value = ''
  try {
    const fd = new FormData()
    fd.append('proof_type', proofType.value)
    fd.append('proof', proofFile.value)

    await authStore.uploadProof(fd)
    clearPreview()
    toast.success('Proof submitted! Please wait for admin review.')
  } catch (err) {
    const serverErrors = err.response?.data?.errors
    if (serverErrors?.proof)      errors.proof      = serverErrors.proof[0]
    if (serverErrors?.proof_type) errors.proof_type = serverErrors.proof_type[0]
    apiError.value = err.response?.data?.message || 'Upload failed. Please try again.'
  } finally {
    loading.value = false
  }
}

async function backToLogin() {
  await authStore.logout()
  router.push('/login')
}
</script>