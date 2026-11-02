<template>
  <div class="p-4 sm:p-6 space-y-5">

    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <p class="text-sm text-gray-400 mt-0.5">Track your waste pickup schedule.</p>
      </div>
      <AppButton variant="primary" size="sm" @click="showModal = true">
        <PlusIcon class="w-4 h-4" />
        Register
      </AppButton>
    </div>

    <!-- Garbage truck announcements -->
    <div class="card rounded-2xl p-5">
      <div class="flex items-center gap-2 mb-4">
        <TruckIcon class="w-5 h-5 text-primary" />
        <h2 class="text-sm font-semibold text-primary-dark">Garbage Truck Announcements</h2>
      </div>

      <div v-if="announcements.length === 0" class="text-center py-6 text-gray-400 text-sm">
        No upcoming truck announcements.
      </div>

      <div v-else class="space-y-3">
        <div
          v-for="a in announcements"
          :key="a.id"
          class="flex items-start gap-3 rounded-xl border border-green-100 bg-green-50/50 p-3"
        >
          <div class="w-9 h-9 rounded-lg bg-green-100 flex items-center justify-center flex-shrink-0">
            <TruckIcon class="w-4 h-4 text-green-600" />
          </div>
          <div class="flex-1 min-w-0">
            <div class="flex items-center justify-between gap-2">
              <p class="text-sm font-semibold text-gray-800">{{ a.waste_type }}</p>
              <AppBadge :variant="a.dayVariant" class="flex-shrink-0">{{ a.dayLabel }}</AppBadge>
            </div>
            <p class="text-xs text-gray-600 mt-0.5">
              {{ a.dateText }}<span v-if="a.timeText"> · {{ a.timeText }}</span>
            </p>
            <p class="text-xs text-gray-400">{{ a.area || 'Whole barangay' }}</p>
            <p v-if="a.notes" class="text-xs text-gray-500 mt-1 leading-relaxed">{{ a.notes }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Schedule cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
      <div
        v-for="sched in schedule"
        :key="sched.day"
        :class="['card rounded-2xl p-4 flex items-center gap-3 border-l-4', sched.borderColor]"
      >
        <div :class="['w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0', sched.iconBg]">
          <TrashIcon :class="['w-5 h-5', sched.iconColor]" />
        </div>
        <div>
          <p class="text-sm font-semibold text-gray-800">{{ sched.type }}</p>
          <p class="text-xs text-gray-400">Every {{ sched.day }}</p>
        </div>
      </div>
    </div>

    <!-- Collection history -->
    <div class="card rounded-2xl p-5">
      <h2 class="text-sm font-semibold text-primary-dark mb-4">Collection History</h2>

      <div v-if="history.length === 0" class="text-center py-8 text-gray-400 text-sm">
        No records yet.
      </div>

      <div v-else class="space-y-3">
        <div
          v-for="item in history"
          :key="item.id"
          class="flex items-center gap-3"
        >
          <div :class="['w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0', item.iconBg]">
            <TrashIcon :class="['w-4 h-4', item.iconColor]" />
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-sm text-gray-700 font-medium">{{ item.type }}</p>
            <p class="text-xs text-gray-400">{{ item.date }}</p>
          </div>
          <AppBadge :variant="item.status.variant">
            {{ item.status.label }}
          </AppBadge>
        </div>
      </div>
    </div>

    <!-- Register modal -->
    <Teleport to="body">
      <div
        v-if="showModal"
        class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 px-4"
        @click.self="showModal = false"
      >
        <div class="bg-white rounded-2xl p-6 w-full max-w-sm space-y-4 shadow-xl">
          <div class="flex items-center justify-between">
            <h3 class="text-base font-semibold text-primary-dark">Register Waste Pickup</h3>
            <button @click="showModal = false" class="text-gray-400 hover:text-gray-600">
              <XMarkIcon class="w-5 h-5" />
            </button>
          </div>

          <!-- Waste type -->
          <div>
            <label class="block text-sm font-medium text-gray-600 mb-1">
              Waste type <span class="text-red-400">*</span>
            </label>
            <select
              v-model="form.type"
              class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-primary"
            >
              <option value="" disabled>Select type</option>
              <option v-for="t in wasteTypes" :key="t" :value="t">{{ t }}</option>
            </select>
            <p v-if="errors.type" class="text-xs text-red-500 mt-1">{{ errors.type }}</p>
          </div>

          <!-- Pickup date -->
          <AppInput
            v-model="form.date"
            label="Preferred pickup date"
            type="date"
            :min="today"
            :error="errors.date"
            required
          />

          <!-- Notes -->
          <div>
            <label class="block text-sm font-medium text-gray-600 mb-1">Notes (optional)</label>
            <textarea
              v-model="form.notes"
              rows="2"
              placeholder="e.g. Large appliance disposal..."
              class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-primary resize-none"
            />
          </div>

          <div class="flex gap-2 pt-1">
            <AppButton variant="outline" class="flex-1" @click="showModal = false">
              Cancel
            </AppButton>
            <AppButton variant="primary" class="flex-1" :loading="loading" @click="handleSubmit">
              Submit
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
import { PlusIcon, TrashIcon, XMarkIcon, TruckIcon } from '@heroicons/vue/24/outline'
import api from '@/services/api'
import AppButton from '@/components/common/AppButton.vue'
import AppBadge from '@/components/common/AppBadge.vue'
import AppInput from '@/components/common/AppInput.vue'

const toast         = useToast()
const showModal     = ref(false)
const loading       = ref(false)
const fetching      = ref(true)
const today         = new Date().toISOString().split('T')[0]
const history       = ref([])
const announcementsRaw = ref([])

const statusMap = {
  pending:   { variant: 'warning', label: 'Pending'   },
  scheduled: { variant: 'info',    label: 'Scheduled' },
  collected: { variant: 'success', label: 'Collected' },
  missed:    { variant: 'danger',  label: 'Missed'    },
}

const typeConfig = {
  'Biodegradable':      { iconBg: 'bg-green-50',  iconColor: 'text-green-600' },
  'Non-Biodegradable':  { iconBg: 'bg-blue-50',   iconColor: 'text-blue-500'  },
  'Residual':           { iconBg: 'bg-gray-50',   iconColor: 'text-gray-500'  },
  'Hazardous':          { iconBg: 'bg-red-50',    iconColor: 'text-red-500'   },
  'Bulk / Large Items': { iconBg: 'bg-yellow-50', iconColor: 'text-yellow-500'},
}

const schedule = [
  { type: 'Biodegradable',     day: 'Monday & Thursday',    iconBg: 'bg-green-50',  iconColor: 'text-green-600', borderColor: 'border-green-400' },
  { type: 'Non-Biodegradable', day: 'Tuesday & Friday',     iconBg: 'bg-blue-50',   iconColor: 'text-blue-500',  borderColor: 'border-blue-400'  },
  { type: 'Residual',          day: 'Wednesday & Saturday', iconBg: 'bg-gray-50',   iconColor: 'text-gray-500',  borderColor: 'border-gray-400'  },
]

const wasteTypes = ['Biodegradable', 'Non-Biodegradable', 'Residual', 'Hazardous', 'Bulk / Large Items']

const form   = reactive({ type: '', date: '', notes: '' })
const errors = reactive({ type: '', date: '' })

// ─── Truck announcements helpers ───────────────────────────
function localDateStr(d = new Date()) {
  const y = d.getFullYear()
  const m = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  return `${y}-${m}-${day}`
}

function parseDate(str) {
  const [y, m, d] = String(str).slice(0, 10).split('-').map(Number)
  return new Date(y, m - 1, d)
}

function formatTime(str) {
  const [h, m] = String(str).slice(0, 5).split(':').map(Number)
  const d = new Date()
  d.setHours(h, m, 0, 0)
  return d.toLocaleTimeString('en-PH', { hour: 'numeric', minute: '2-digit' })
}

const announcements = computed(() => {
  const base = parseDate(localDateStr())
  return announcementsRaw.value.map(a => {
    const n = Math.round((parseDate(a.collection_date) - base) / 86400000)
    return {
      ...a,
      dateText: parseDate(a.collection_date).toLocaleDateString('en-PH', {
        weekday: 'short', year: 'numeric', month: 'long', day: 'numeric',
      }),
      timeText:   a.collection_time ? formatTime(a.collection_time) : '',
      dayLabel:   n === 0 ? 'Today' : n === 1 ? 'Tomorrow' : `In ${n} days`,
      dayVariant: n <= 1 ? 'warning' : 'info',
    }
  })
})

// ─── Pickup request history ────────────────────────────────
const historyWithMeta = computed(() =>
  history.value.map(r => ({
    ...r,
    status:    statusMap[r.status] ?? { variant: 'default', label: r.status },
    ...(typeConfig[r.type] ?? { iconBg: 'bg-gray-50', iconColor: 'text-gray-500' }),
    date: new Date(r.date).toLocaleDateString('en-PH', {
      year: 'numeric', month: 'long', day: 'numeric'
    }),
  }))
)

function validate() {
  errors.type = !form.type ? 'Waste type is required.' : ''
  errors.date = !form.date ? 'Date is required.'       : ''
  return Object.values(errors).every(e => !e)
}

async function handleSubmit() {
  if (!validate()) return
  loading.value = true
  try {
    const { data } = await api.post('/waste', form)
    history.value.unshift(data.waste_request)
    toast.success('Pickup request submitted!')
    form.type  = ''
    form.date  = ''
    form.notes = ''
    showModal.value = false
  } catch (err) {
    toast.error(err.response?.data?.message || 'Failed to submit.')
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  try {
    const { data } = await api.get('/waste')
    history.value = data.waste_requests
  } finally {
    fetching.value = false
  }

  try {
    const { data } = await api.get('/waste-schedules')
    announcementsRaw.value = data.schedules
  } catch (_) {
    // announcements are optional; ignore errors
  }
})
</script>