<template>
  <div class="p-4 sm:p-6 max-w-xl mx-auto space-y-5">

    <!-- Header -->
    <div class="flex items-center gap-3">
      <button @click="router.back()" class="p-2 rounded-lg hover:bg-primary-light transition-colors">
        <ArrowLeftIcon class="w-4 h-4 text-primary-dark" />
      </button>
      <div>
        <h1 class="text-xl font-semibold text-primary-dark">Book an Appointment</h1>
        <p class="text-sm text-gray-400 mt-0.5">Submit your request and the system will assign the next available date and time.</p>
      </div>
    </div>

    <div class="card rounded-2xl p-5 space-y-4">

      <!-- API error -->
      <div v-if="apiError" class="flex items-center gap-2 bg-red-50 border border-red-200 rounded-xl px-4 py-3">
        <ExclamationCircleIcon class="w-4 h-4 text-red-500 flex-shrink-0" />
        <p class="text-sm text-red-700">{{ apiError }}</p>
      </div>

      <!-- Purpose -->
      <div>
        <label class="block text-sm font-medium text-gray-600 mb-1">
          Purpose <span class="text-red-400">*</span>
        </label>
        <select
          v-model="form.purpose"
          class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-primary"
        >
          <option value="" disabled>Select a purpose</option>
          <option v-for="p in purposes" :key="p" :value="p">{{ p }}</option>
        </select>
        <p v-if="errors.purpose" class="text-xs text-red-500 mt-1">{{ errors.purpose }}</p>
      </div>

      <!-- Notes -->
      <div>
        <label class="block text-sm font-medium text-gray-600 mb-1">Additional notes (optional)</label>
        <textarea
          v-model="form.notes"
          rows="3"
          placeholder="Any additional info for the barangay staff..."
          class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-primary resize-none"
        />
      </div>

      <AppButton
        type="button"
        variant="primary"
        class="w-full"
        :loading="loading"
        @click="handleSubmit"
      >
        <CalendarDaysIcon class="w-4 h-4" />
        Submit Appointment
      </AppButton>

    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'vue-toastification'
import {
  ArrowLeftIcon,
  ExclamationCircleIcon,
  CalendarDaysIcon,
} from '@heroicons/vue/24/outline'
import api from '@/services/api'
import { formatDate } from '@/utils/formatters'
import AppButton from '@/components/common/AppButton.vue'
import { useFormDraft } from '@/composables/useFormDraft'

const router = useRouter()
const toast  = useToast()

const loading  = ref(false)
const apiError = ref('')

const purposes = [
  'Barangay ID Renewal',
  'Certificate of Residency',
  'Certificate of Indigency',
  'Business Permit',
  'Barangay Clearance',
  'Complaint Filing',
  'Others',
]

const { form, clearDraft } = useFormDraft('appointment', {
  purpose: '',
  notes:   '',
})

const errors = reactive({
  purpose: '',
})

function validate() {
  errors.purpose = !form.purpose ? 'Purpose is required.' : ''
  return Object.values(errors).every(e => !e)
}

async function handleSubmit() {
  if (loading.value || !validate()) return
  loading.value  = true
  apiError.value = ''
  try {
    const { data } = await api.post('/appointments', form)
    clearDraft()
    const { date, time } = data.appointment
    toast.success('Assigned ' + formatDate(date) + ' at ' + time + '. Awaiting approval.')
    router.push({ name: 'resident-appointments' })
  } catch (err) {
    apiError.value = err.response?.data?.message || 'Failed to book. Please try again.'
  } finally {
    loading.value = false
  }
}
</script>
