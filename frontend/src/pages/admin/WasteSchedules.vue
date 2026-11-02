<template>
  <div class="p-4 sm:p-6 space-y-5">

    <!-- Header -->
    <div class="flex items-center justify-between gap-3">
      <div>
        <h1 class="text-xl font-semibold text-primary-dark">Garbage Truck Schedule</h1>
        <p class="text-sm text-gray-400 mt-0.5">
          Announce when the garbage truck will arrive. Residents are notified automatically.
        </p>
      </div>
      <AppButton variant="primary" size="sm" @click="openModal()">
        <PlusIcon class="w-4 h-4" />
        Post Schedule
      </AppButton>
    </div>

    <!-- Loading -->
    <div v-if="fetching" class="card rounded-2xl p-10 text-center text-gray-400 text-sm">
      Loading schedules...
    </div>

    <template v-else>
      <!-- Upcoming -->
      <div>
        <h2 class="text-sm font-semibold text-primary-dark mb-3">Upcoming</h2>

        <div v-if="upcoming.length === 0" class="card rounded-2xl p-8 text-center text-gray-400 text-sm">
          No upcoming schedules. Tap "Post Schedule" to announce one.
        </div>

        <div v-else class="space-y-3">
          <div
            v-for="item in upcoming"
            :key="item.id"
            class="card rounded-2xl p-4 space-y-3 border-l-4 border-green-400"
          >
            <div class="flex items-start gap-3">
              <div class="w-10 h-10 rounded-xl bg-green-50 flex items-center justify-center flex-shrink-0">
                <TruckIcon class="w-5 h-5 text-green-600" />
              </div>
              <div class="flex-1 min-w-0">
                <div class="flex items-center justify-between gap-2">
                  <p class="text-sm font-semibold text-gray-800">{{ item.waste_type }}</p>
                  <AppBadge :variant="dayVariant(item)" class="flex-shrink-0">
                    {{ dayLabel(item) }}
                  </AppBadge>
                </div>
                <p class="text-xs text-gray-600 mt-1">
                  {{ formatDate(item.collection_date) }}<span v-if="item.collection_time"> · {{ formatTime(item.collection_time) }}</span>
                </p>
                <p class="text-xs text-gray-400 mt-0.5">{{ item.area || 'Whole barangay' }}</p>
                <p v-if="item.notes" class="text-xs text-gray-500 mt-1 leading-relaxed">{{ item.notes }}</p>
              </div>
            </div>

            <div class="flex gap-2 pt-1 border-t border-gray-100">
              <button
                @click="openModal(item)"
                class="flex-1 flex items-center justify-center gap-1.5 py-1.5 rounded-lg border border-gray-200 text-xs text-gray-600 hover:bg-gray-50 transition-colors"
              >
                <PencilIcon class="w-3.5 h-3.5" />
                Edit
              </button>
              <button
                @click="removeSchedule(item.id)"
                class="flex-1 flex items-center justify-center gap-1.5 py-1.5 rounded-lg bg-red-50 hover:bg-red-100 text-xs text-red-600 transition-colors"
              >
                <TrashIcon class="w-3.5 h-3.5" />
                Cancel
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Past -->
      <div v-if="past.length">
        <h2 class="text-sm font-semibold text-gray-400 mb-3">Past</h2>
        <div class="space-y-2">
          <div
            v-for="item in past"
            :key="item.id"
            class="card rounded-2xl p-3 flex items-center gap-3 opacity-70"
          >
            <div class="w-8 h-8 rounded-lg bg-gray-50 flex items-center justify-center flex-shrink-0">
              <TruckIcon class="w-4 h-4 text-gray-400" />
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm text-gray-600 font-medium truncate">{{ item.waste_type }}</p>
              <p class="text-xs text-gray-400">
                {{ formatDate(item.collection_date) }} · {{ item.area || 'Whole barangay' }}
              </p>
            </div>
          </div>
        </div>
      </div>
    </template>

    <!-- Create / Edit modal -->
    <Teleport to="body">
      <div
        v-if="showModal"
        class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 px-4"
        @click.self="closeModal"
      >
        <div class="bg-white rounded-2xl p-6 w-full max-w-md space-y-4 shadow-xl max-h-[92vh] overflow-y-auto">
          <div class="flex items-center justify-between">
            <h3 class="text-base font-semibold text-primary-dark">
              {{ editingId ? 'Edit Schedule' : 'Post Truck Schedule' }}
            </h3>
            <button @click="closeModal" class="text-gray-400 hover:text-gray-600">
              <XMarkIcon class="w-5 h-5" />
            </button>
          </div>

          <!-- Waste type -->
          <div>
            <label class="block text-sm font-medium text-gray-600 mb-1">
              Waste type <span class="text-red-400">*</span>
            </label>
            <select
              v-model="form.waste_type"
              class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-primary"
            >
              <option value="" disabled>Select type</option>
              <option v-for="t in wasteTypes" :key="t" :value="t">{{ t }}</option>
            </select>
            <p v-if="errors.waste_type" class="text-xs text-red-500 mt-1">{{ errors.waste_type }}</p>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <AppInput
              v-model="form.collection_date"
              label="Date"
              type="date"
              :min="editingId ? undefined : today"
              :error="errors.collection_date"
              required
            />
            <AppInput
              v-model="form.collection_time"
              label="Time (optional)"
              type="time"
              :error="errors.collection_time"
            />
          </div>

          <AppInput
            v-model="form.area"
            label="Area / Purok (optional)"
            placeholder="Leave blank for the whole barangay"
            :error="errors.area"
          />

          <div>
            <label class="block text-sm font-medium text-gray-600 mb-1">Notes (optional)</label>
            <textarea
              v-model="form.notes"
              rows="2"
              placeholder="e.g. Please have your trash outside by 6:00 AM"
              class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-primary resize-none"
            />
          </div>

          <p class="text-xs text-gray-400">
            All active residents will receive a notification.
          </p>

          <div class="flex gap-2 pt-1">
            <AppButton variant="outline" class="flex-1" @click="closeModal">
              Cancel
            </AppButton>
            <AppButton variant="primary" class="flex-1" :loading="loading" @click="handleSubmit">
              {{ editingId ? 'Save & Notify' : 'Post & Notify' }}
            </AppButton>
          </div>
        </div>
      </div>
    </Teleport>

  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useToast } from 'vue-toastification'
import {
  PlusIcon,
  PencilIcon,
  TrashIcon,
  XMarkIcon,
  TruckIcon,
} from '@heroicons/vue/24/outline'
import api from '@/services/api'
import AppButton from '@/components/common/AppButton.vue'
import AppBadge from '@/components/common/AppBadge.vue'
import AppInput from '@/components/common/AppInput.vue'

const toast     = useToast()
const showModal = ref(false)
const editingId = ref(null)
const loading   = ref(false)
const fetching  = ref(true)
const schedules = ref([])

const wasteTypes = ['Biodegradable', 'Non-Biodegradable', 'Residual', 'Hazardous', 'Bulk / Large Items']

// Local date as YYYY-MM-DD (hindi UTC, para tama sa Manila)
function localDateStr(d = new Date()) {
  const y = d.getFullYear()
  const m = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  return `${y}-${m}-${day}`
}

const today = localDateStr()

function parseDate(str) {
  const [y, m, d] = String(str).slice(0, 10).split('-').map(Number)
  return new Date(y, m - 1, d)
}

function formatDate(str) {
  return parseDate(str).toLocaleDateString('en-PH', {
    weekday: 'short', year: 'numeric', month: 'long', day: 'numeric',
  })
}

function formatTime(str) {
  const [h, m] = String(str).slice(0, 5).split(':').map(Number)
  const d = new Date()
  d.setHours(h, m, 0, 0)
  return d.toLocaleTimeString('en-PH', { hour: 'numeric', minute: '2-digit' })
}

function daysUntil(item) {
  const target = parseDate(item.collection_date)
  const base   = parseDate(today)
  return Math.round((target - base) / 86400000)
}

function dayLabel(item) {
  const n = daysUntil(item)
  if (n === 0) return 'Today'
  if (n === 1) return 'Tomorrow'
  return `In ${n} days`
}

function dayVariant(item) {
  return daysUntil(item) <= 1 ? 'warning' : 'info'
}

const upcoming = computed(() =>
  schedules.value
    .filter(s => String(s.collection_date).slice(0, 10) >= today)
    .sort((a, b) =>
      String(a.collection_date).localeCompare(String(b.collection_date)) ||
      String(a.collection_time || '').localeCompare(String(b.collection_time || ''))
    )
)

const past = computed(() =>
  schedules.value
    .filter(s => String(s.collection_date).slice(0, 10) < today)
    .sort((a, b) => String(b.collection_date).localeCompare(String(a.collection_date)))
    .slice(0, 10)
)

const form = reactive({
  waste_type: '', collection_date: '', collection_time: '', area: '', notes: '',
})
const errors = reactive({
  waste_type: '', collection_date: '', collection_time: '', area: '',
})

function openModal(item = null) {
  if (item) {
    editingId.value        = item.id
    form.waste_type        = item.waste_type
    form.collection_date   = String(item.collection_date).slice(0, 10)
    form.collection_time   = item.collection_time ? String(item.collection_time).slice(0, 5) : ''
    form.area              = item.area  || ''
    form.notes             = item.notes || ''
  } else {
    editingId.value        = null
    form.waste_type        = ''
    form.collection_date   = ''
    form.collection_time   = ''
    form.area              = ''
    form.notes             = ''
  }
  Object.keys(errors).forEach(k => (errors[k] = ''))
  showModal.value = true
}

function closeModal() {
  showModal.value = false
}

function validate() {
  errors.waste_type      = !form.waste_type      ? 'Waste type is required.' : ''
  errors.collection_date = !form.collection_date ? 'Date is required.'       : ''
  return !errors.waste_type && !errors.collection_date
}

async function handleSubmit() {
  if (!validate()) return
  loading.value = true
  try {
    const payload = {
      waste_type:      form.waste_type,
      collection_date: form.collection_date,
      collection_time: form.collection_time || null,
      area:            form.area  || null,
      notes:           form.notes || null,
    }

    if (editingId.value) {
      const { data } = await api.put(`/waste-schedules/${editingId.value}`, payload)
      const idx = schedules.value.findIndex(s => s.id === editingId.value)
      if (idx !== -1) schedules.value[idx] = data.schedule
      toast.success('Schedule updated. Residents notified.')
    } else {
      const { data } = await api.post('/waste-schedules', payload)
      schedules.value.unshift(data.schedule)
      toast.success('Schedule posted. Residents notified.')
    }
    closeModal()
  } catch (err) {
    const serverErrors = err.response?.data?.errors
    if (serverErrors) {
      Object.keys(serverErrors).forEach(k => { if (k in errors) errors[k] = serverErrors[k][0] })
    }
    toast.error(err.response?.data?.message || 'Failed to save schedule.')
  } finally {
    loading.value = false
  }
}

async function removeSchedule(id) {
  if (!window.confirm('Cancel this schedule? Residents will be notified.')) return
  try {
    await api.delete(`/waste-schedules/${id}`)
    schedules.value = schedules.value.filter(s => s.id !== id)
    toast.success('Schedule cancelled. Residents notified.')
  } catch (err) {
    toast.error(err.response?.data?.message || 'Failed to cancel schedule.')
  }
}

onMounted(async () => {
  try {
    const { data } = await api.get('/admin/waste-schedules')
    schedules.value = data.schedules
  } catch (_) {
    toast.error('Failed to load schedules.')
  } finally {
    fetching.value = false
  }
})
</script>