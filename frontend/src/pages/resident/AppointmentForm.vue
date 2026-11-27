<template>
  <div class="p-4 sm:p-6 max-w-xl mx-auto space-y-5">

    <!-- Header -->
    <div class="flex items-center gap-3">
      <button @click="router.back()" class="p-2 rounded-lg hover:bg-primary-light transition-colors">
        <ArrowLeftIcon class="w-4 h-4 text-primary-dark" />
      </button>
      <div>
        <h1 class="text-xl font-semibold text-primary-dark">Book an Appointment</h1>
        <p class="text-sm text-gray-500 mt-0.5">Send your details so barangay staff can prepare your document or review your request. The system will assign the next available date and time. Wait for staff approval before visiting.</p>
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
        <label for="appointment-purpose" class="block text-sm font-medium text-gray-600 mb-1">
          Purpose <span class="text-red-400">*</span>
        </label>
        <select
          id="appointment-purpose"
          v-model="form.purpose"
          class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-primary"
        >
          <option value="" disabled>Select a purpose</option>
          <option v-for="(_, purpose) in purposes" :key="purpose" :value="purpose">{{ purpose }}</option>
        </select>
        <p v-if="errors.purpose" class="text-xs text-red-500 mt-1">{{ errors.purpose }}</p>
      </div>

      <div v-if="form.purpose">
        <label for="appointment-details" class="block text-sm font-medium text-gray-600 mb-1">
          Request details <span class="text-red-400">*</span>
        </label>
        <p id="appointment-details-help" class="text-sm text-gray-500 mb-2">{{ purposes[form.purpose] }}</p>
        <textarea
          id="appointment-details"
          v-model="form.notes"
          rows="4"
          required
          :aria-invalid="Boolean(errors.notes)"
          :aria-describedby="errors.notes ? 'appointment-details-help appointment-details-error' : 'appointment-details-help'"
          class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-primary resize-none"
        />
        <p v-if="errors.notes" id="appointment-details-error" class="text-xs text-red-500 mt-1">{{ errors.notes }}</p>
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

const purposes = {
  'Barangay ID Renewal': 'Enter your current address and describe any information that needs to change on your ID.',
  'Certificate of Residency': 'Enter your complete barangay address, how long you have lived there, and what you need the certificate for.',
  'Certificate of Indigency': 'Enter your complete barangay address and describe what you need the certificate for.',
  'Business Permit': 'Enter the business name, address, and activity. Indicate whether this is a new application or a renewal.',
  'Barangay Clearance': 'Enter your complete barangay address and what you need the clearance for.',
  'Complaint Filing': 'Briefly describe the complaint, including when and where the incident happened.',
  'Others': 'Specify the document or service you need and include the details staff need to prepare for your visit.',
}

const { form, clearDraft } = useFormDraft('appointment', {
  purpose: '',
  notes:   '',
})

const errors = reactive({
  purpose: '',
  notes: '',
})

function validate() {
  errors.purpose = !form.purpose ? 'Purpose is required.' : ''
  errors.notes = form.purpose && !form.notes.trim() ? 'Enter the details for your request.' : ''
  return Object.values(errors).every(e => !e)
}

async function handleSubmit() {
  if (loading.value || !validate()) return
  loading.value  = true
  apiError.value = ''
  try {
    const { data } = await api.post('/appointments', {
      purpose: form.purpose,
      notes: form.notes.trim(),
    })
    clearDraft()
    const { date, time } = data.appointment
    toast.success('Scheduled for ' + formatDate(date) + ' at ' + time + '. Wait for staff approval before visiting.')
    router.push({ name: 'resident-appointments' })
  } catch (err) {
    apiError.value = err.response?.data?.message || 'Failed to book. Please try again.'
  } finally {
    loading.value = false
  }
}
</script>
