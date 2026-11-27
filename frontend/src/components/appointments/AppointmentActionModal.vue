<template>
  <Teleport to="body">
    <div
      v-if="open"
      class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/40 p-4"
      @click.self="close"
    >
      <div class="bg-white rounded-2xl w-full max-w-md p-5 space-y-4 max-h-[90vh] overflow-y-auto">

        <!-- Header -->
        <div>
          <h2 class="text-lg font-semibold text-primary-dark">
            {{ isReschedule ? 'Reschedule Appointment' : 'Cancel Appointment' }}
          </h2>
          <p v-if="appointment" class="text-sm text-gray-400 mt-0.5">
            {{ appointment.purpose }} · currently
            {{ formatDate(appointment.date) }} at {{ appointment.time }}
          </p>
        </div>

        <!-- API error -->
        <div v-if="apiError" class="bg-red-50 border border-red-200 rounded-xl px-4 py-3">
          <p class="text-sm text-red-700">{{ apiError }}</p>
        </div>

        <!-- New date/time (reschedule only) -->
        <template v-if="isReschedule">
          <AppInput
            v-model="form.date"
            label="New date"
            type="date"
            :min="today()"
            :error="errors.date"
            required
          />

          <div>
            <label class="block text-sm font-medium text-gray-600 mb-1">
              New time <span class="text-red-400">*</span>
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
        </template>

        <!-- Reason / note -->
        <div>
          <label class="block text-sm font-medium text-gray-600 mb-1">
            Reason / note for the resident <span class="text-red-400">*</span>
          </label>
          <textarea
            v-model="form.admin_note"
            rows="3"
            :placeholder="isReschedule
              ? 'e.g. Walang staff sa araw na yan dahil may seminar.'
              : 'e.g. Duplicate request.'"
            class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-primary resize-none"
          />
          <p v-if="errors.admin_note" class="text-xs text-red-500 mt-1">{{ errors.admin_note }}</p>
        </div>

        <!-- Actions -->
        <div class="flex gap-2 pt-1">
          <button
            type="button"
            :disabled="loading"
            class="flex-1 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-600 hover:bg-gray-50 transition-colors disabled:opacity-50"
            @click="close"
          >
            Back
          </button>
          <button
            type="button"
            :disabled="loading"
            :class="[
              'flex-1 py-2.5 rounded-xl text-sm font-medium text-white transition-colors disabled:opacity-50',
              isReschedule ? 'bg-primary hover:opacity-90' : 'bg-red-600 hover:bg-red-700'
            ]"
            @click="submit"
          >
            {{ loading ? 'Saving...' : (isReschedule ? 'Confirm Reschedule' : 'Confirm Cancel') }}
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, reactive, computed, watch } from 'vue'
import { useToast } from 'vue-toastification'
import api from '@/services/api'
import AppInput from '@/components/common/AppInput.vue'
import { useFormDraft } from '@/composables/useFormDraft'

const props = defineProps({
  open:        { type: Boolean, default: false },
  appointment: { type: Object, default: null },
  // 'rescheduled' | 'cancelled'
  action:      { type: String, default: 'rescheduled' },
})

const emit = defineEmits(['close', 'updated'])

const toast = useToast()

const today = () => new Intl.DateTimeFormat('sv-SE', { timeZone: 'Asia/Manila' }).format(new Date())

const timeSlots = [
  '8:00 AM', '9:00 AM', '10:00 AM', '11:00 AM',
  '1:00 PM', '2:00 PM', '3:00 PM', '4:00 PM',
]

const loading  = ref(false)
const apiError = ref('')

const { form, restoreDraft, clearDraft } = useFormDraft(null, { date: '', time: '', admin_note: '' })
const errors = reactive({ date: '', time: '', admin_note: '' })

const isReschedule = computed(() => props.action === 'rescheduled')

watch(() => props.open, (isOpen) => {
  if (!isOpen || !props.appointment) return
  restoreDraft('appointment-action:' + props.appointment.id + ':' + props.action)
  errors.date = errors.time = errors.admin_note = ''
  apiError.value = ''
})

function formatDate(d) {
  if (!d) return ''
  return new Date(String(d).slice(0, 10) + 'T00:00:00').toLocaleDateString('en-PH', {
    month: 'short', day: 'numeric', year: 'numeric',
  })
}

function validate() {
  errors.admin_note = !form.admin_note.trim() ? 'Reason/note is required.' : ''
  errors.date = isReschedule.value && !form.date ? 'New date is required.' : ''
  errors.time = isReschedule.value && !form.time ? 'New time is required.' : ''
  if (isReschedule.value && form.date) {
    const day = new Date(form.date + 'T00:00:00Z').getUTCDay()
    if (day === 0 || day === 6) {
      errors.date = 'Appointments are available Monday through Friday.'
    }
  }
  return !errors.date && !errors.time && !errors.admin_note
}

function close() {
  if (loading.value) return
  emit('close')
}

async function submit() {
  if (!validate()) return
  loading.value  = true
  apiError.value = ''

  const payload = {
    status:     props.action,
    admin_note: form.admin_note.trim(),
  }
  if (isReschedule.value) {
    payload.date = form.date
    payload.time = form.time
  }

  try {
    const { data } = await api.put(`/appointments/${props.appointment.id}`, payload)
    clearDraft()
    toast.success(isReschedule.value ? 'Appointment rescheduled.' : 'Appointment cancelled.')
    emit('updated', data.appointment)
    emit('close')
  } catch (err) {
    const validation = err.response?.data?.errors
    apiError.value = validation
      ? Object.values(validation).flat()[0]
      : err.response?.data?.message || 'Something went wrong. Please try again.'
  } finally {
    loading.value = false
  }
}
</script>
