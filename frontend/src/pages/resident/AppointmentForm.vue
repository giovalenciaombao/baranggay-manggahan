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

    <form class="card rounded-2xl p-5 space-y-4" @submit.prevent="handleSubmit">

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
          required
          @change="file = null"
          class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-primary"
        >
          <option value="" disabled>Select a purpose</option>
          <option v-for="purpose in purposes" :key="purpose" :value="purpose">
            {{ purpose === 'Business Permit' ? 'Barangay Clearance for Business Permit' : purpose }}
          </option>
        </select>
      </div>

      <div v-if="template" class="space-y-3 border-t border-gray-200 pt-4">
        <p class="text-sm text-gray-600">Download the editable document, fill in your information in Word, and attach the saved file before booking.</p>
        <AppButton type="button" variant="outline" @click="downloadTemplate">
          Download Editable Document
        </AppButton>
        <div>
          <label for="appointment-document" class="block text-sm font-medium text-gray-700 mb-1">
            Completed document <span class="text-red-400">*</span>
          </label>
          <input
            :key="form.purpose"
            id="appointment-document"
            type="file"
            accept=".rtf,.docx"
            required
            aria-describedby="appointment-document-help"
            class="w-full text-sm"
            @change="file = $event.target.files[0] ?? null"
          >
          <p id="appointment-document-help" class="text-xs text-gray-500 mt-1">RTF or DOCX, up to 2 MB</p>
        </div>
      </div>

      <div v-if="form.purpose">
        <label for="appointment-details" class="block text-sm font-medium text-gray-600 mb-1">
          {{ template ? 'Additional notes (optional)' : 'Request details' }}
          <span v-if="!template" class="text-red-400">*</span>
        </label>
        <p v-if="requestPrompts[form.purpose]" id="appointment-details-help" class="text-sm text-gray-500 mb-2">
          {{ requestPrompts[form.purpose] }}
        </p>
        <textarea
          id="appointment-details"
          v-model="form.notes"
          rows="4"
          :required="!template"
          :aria-invalid="Boolean(notesError)"
          :aria-describedby="requestPrompts[form.purpose] ? 'appointment-details-help' : undefined"
          class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-primary resize-none"
        />
        <p v-if="notesError" class="text-xs text-red-500 mt-1">{{ notesError }}</p>
      </div>

      <AppButton
        type="submit"
        variant="primary"
        class="w-full"
        :loading="loading"
      >
        <CalendarDaysIcon class="w-4 h-4" />
        Submit Appointment
      </AppButton>

    </form>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
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
import { appointmentTemplate, downloadAppointmentFile } from '@/utils/appointmentDocument'
import { useFormDraft } from '@/composables/useFormDraft'

const router = useRouter()
const toast  = useToast()

const loading  = ref(false)
const apiError = ref('')

const requestPrompts = {
  'Barangay ID Renewal': 'Enter your current address and describe any information that needs to change on your ID.',
  'Certificate of Indigency': 'Enter your complete barangay address and describe what you need the certificate for.',
  'Complaint Filing': 'Briefly describe the complaint, including when and where the incident happened.',
  'Others': 'Specify the document or service you need and include the details staff need to prepare for your visit.',
}

const purposes = [
  'Barangay ID Renewal',
  'Certificate of Residency',
  'Certificate of Indigency',
  'Certificate of Low Income',
  'Certificate of Good Moral Character',
  'Business Permit',
  'Certification for Business Closure',
  'Barangay Clearance',
  'Complaint Filing',
  'Others',
]

const { form, clearDraft } = useFormDraft('appointment', { purpose: '', notes: '' })
const template = computed(() => appointmentTemplate(form.purpose))
const file = ref(null)
const notesError = ref('')

function downloadTemplate() {
  const { filename, contents } = template.value
  downloadAppointmentFile(new Blob([contents], { type: 'application/rtf' }), filename)
}

async function handleSubmit() {
  if (loading.value || !purposes.includes(form.purpose)) return

  apiError.value = ''
  notesError.value = ''
  const notes = form.notes.trim()
  let payload = { purpose: form.purpose, notes }

  if (template.value) {
    if (!file.value || !/\.(rtf|docx)$/i.test(file.value.name) || file.value.size > 2 * 1024 * 1024) {
      apiError.value = 'Attach a completed RTF or DOCX file up to 2 MB.'
      return
    }
    payload = new FormData()
    payload.append('purpose', form.purpose)
    payload.append('notes', notes)
    payload.append('document', file.value)
  } else if (!notes) {
    notesError.value = 'Enter the details for your request.'
    return
  }

  loading.value = true
  try {
    const config = template.value ? { headers: { 'Content-Type': 'multipart/form-data' } } : {}
    const { data } = await api.post('/appointments', payload, config)
    clearDraft()
    const { date, time } = data.appointment
    if (payload instanceof FormData && !data.document_attached) {
      toast.warning('Your appointment was booked, but the file was not attached. Contact the barangay office before visiting.')
    } else {
      toast.success('Scheduled for ' + formatDate(date) + ' at ' + time + '. Wait for staff approval before visiting.')
    }
    router.push({ name: 'resident-appointments' })
  } catch (err) {
    apiError.value = err.response?.data?.errors?.document?.[0] || err.response?.data?.message || 'Failed to book. Please try again.'
  } finally {
    loading.value = false
  }
}
</script>
