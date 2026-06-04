<template>
  <div class="p-4 sm:p-6 max-w-xl mx-auto space-y-5">

    <!-- Header -->
    <div class="flex items-center gap-3">
      <button @click="router.back()" class="p-2 rounded-lg hover:bg-primary-light transition-colors">
        <ArrowLeftIcon class="w-4 h-4 text-primary-dark" />
      </button>
      <div>
        <h1 class="text-xl font-semibold text-primary-dark">Book an Appointment</h1>
        <p class="text-sm text-gray-400 mt-0.5">Fill in the details below.</p>
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

      <!-- Date -->
      <AppInput
        v-model="form.date"
        label="Preferred date"
        type="date"
        :min="today"
        :error="errors.date"
        required
      />

      <!-- Time -->
      <div>
        <label class="block text-sm font-medium text-gray-600 mb-1">
          Preferred time <span class="text-red-400">*</span>
        </label>
        <select
          v-model="form.time"
          class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-primary"
        >
          <option value="" disabled>Select a time slot</option>
          <option v-for="t in timeSlots" :key="t" :value="t">{{ t }}</option>
        </select>
        <p v-if="errors.time" class="text-xs text-red-500 mt-1">{{ errors.time }}</p>
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
import AppInput from '@/components/common/AppInput.vue'
import AppButton from '@/components/common/AppButton.vue'

const router = useRouter()
const toast  = useToast()

const loading  = ref(false)
const apiError = ref('')
const today    = new Date().toISOString().split('T')[0]

const purposes = [
  'Barangay ID Renewal',
  'Certificate of Residency',
  'Certificate of Indigency',
  'Business Permit',
  'Barangay Clearance',
  'Complaint Filing',
  'Others',
]

const timeSlots = [
  '8:00 AM', '9:00 AM', '10:00 AM', '11:00 AM',
  '1:00 PM', '2:00 PM', '3:00 PM',  '4:00 PM',
]

const form = reactive({
  purpose: '',
  date:    '',
  time:    '',
  notes:   '',
})

const errors = reactive({
  purpose: '',
  date:    '',
  time:    '',
})

function validate() {
  errors.purpose = !form.purpose ? 'Purpose is required.' : ''
  errors.date    = !form.date    ? 'Date is required.'    : ''
  errors.time    = !form.time    ? 'Time is required.'    : ''
  return Object.values(errors).every(e => !e)
}

async function handleSubmit() {
  if (!validate()) return
  loading.value  = true
  apiError.value = ''
  try {
    await api.post('/appointments', form)
    toast.success('Appointment booked! Awaiting confirmation.')
    router.push({ name: 'resident-appointments' })
  } catch (err) {
    apiError.value = err.response?.data?.message || 'Failed to book. Please try again.'
  } finally {
    loading.value = false
  }
}
</script>