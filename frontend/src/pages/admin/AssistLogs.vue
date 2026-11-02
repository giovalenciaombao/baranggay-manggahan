<template>
  <div class="p-4 sm:p-6 space-y-5">

    <p class="text-sm text-gray-400">
      Record kung sino ang staff na nag-asikaso sa request ng bawat residente. Super admin lang ang may access dito.
    </p>

    <!-- Filters -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
      <select
        v-model="staffFilter"
        class="border border-gray-200 rounded-xl px-3 py-2.5 text-sm text-gray-700 bg-white focus:outline-none focus:ring-2 focus:ring-primary"
      >
        <option value="">All staff</option>
        <option v-for="s in staffOptions" :key="s.id" :value="s.id">{{ s.name }}</option>
      </select>

      <select
        v-model="typeFilter"
        class="border border-gray-200 rounded-xl px-3 py-2.5 text-sm text-gray-700 bg-white focus:outline-none focus:ring-2 focus:ring-primary"
      >
        <option value="">All records</option>
        <option value="appointment">Appointments</option>
        <option value="incident">Incidents</option>
        <option value="waste">Waste requests</option>
      </select>

      <div class="relative">
        <MagnifyingGlassIcon class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" />
        <input
          v-model="search"
          type="text"
          placeholder="Search resident..."
          class="w-full pl-9 pr-4 py-2.5 border border-gray-200 rounded-xl text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-primary"
        />
      </div>
    </div>

    <!-- Loading / empty -->
    <div v-if="fetching" class="card rounded-2xl p-10 text-center text-gray-400 text-sm">
      Loading records...
    </div>
    <div v-else-if="filtered.length === 0" class="card rounded-2xl p-10 text-center text-gray-400 text-sm">
      No records found.
    </div>

    <!-- List -->
    <div v-else class="space-y-3">
      <div
        v-for="l in filtered"
        :key="l.id"
        class="card rounded-2xl p-4 flex items-start gap-3"
      >
        <div class="w-10 h-10 rounded-full bg-primary-light flex items-center justify-center flex-shrink-0">
          <span class="text-sm font-semibold text-primary-dark">{{ initials(l.staff_name) }}</span>
        </div>

        <div class="flex-1 min-w-0">
          <p class="text-sm text-gray-800">
            <span class="font-semibold">{{ l.staff_name }}</span>
            <span class="text-gray-400"> assisted </span>
            <span class="font-semibold">{{ l.resident_name }}</span>
          </p>
          <p class="text-xs text-gray-500 mt-0.5 truncate">
            {{ typeLabels[l.record_type] ?? l.record_type }}: {{ l.record_label }}
          </p>
          <p class="text-xs text-gray-300 mt-1">{{ formatDateTime(l.created_at) }}</p>
        </div>

        <AppBadge :variant="actionVariant(l.action)" class="flex-shrink-0">
          {{ capitalize(l.action) }}
        </AppBadge>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useToast } from 'vue-toastification'
import { MagnifyingGlassIcon } from '@heroicons/vue/24/outline'
import api from '@/services/api'
import AppBadge from '@/components/common/AppBadge.vue'

const toast   = useToast()
const fetching = ref(true)
const logs     = ref([])

const staffFilter = ref('')
const typeFilter  = ref('')
const search      = ref('')

const typeLabels = {
  appointment: 'Appointment',
  incident:    'Incident report',
  waste:       'Waste pickup',
}

const staffOptions = computed(() => {
  const map = new Map()
  logs.value.forEach(l => map.set(l.staff_id, l.staff_name))
  return Array.from(map, ([id, name]) => ({ id, name })).sort((a, b) => a.name.localeCompare(b.name))
})

const filtered = computed(() => {
  const q = search.value.trim().toLowerCase()
  return logs.value.filter(l =>
    (staffFilter.value === '' || l.staff_id === staffFilter.value) &&
    (typeFilter.value === '' || l.record_type === typeFilter.value) &&
    (!q || l.resident_name.toLowerCase().includes(q))
  )
})

function initials(name) {
  return (name || '?').split(' ').map(n => n[0]).join('').slice(0, 2).toUpperCase()
}

function capitalize(s) {
  return s ? s.charAt(0).toUpperCase() + s.slice(1) : ''
}

function actionVariant(action) {
  if (['approved', 'resolved', 'collected', 'completed'].includes(action)) return 'success'
  if (['cancelled', 'dismissed', 'missed'].includes(action)) return 'danger'
  return 'warning'
}

function formatDateTime(value) {
  return new Date(value).toLocaleString('en-PH', {
    year: 'numeric', month: 'short', day: 'numeric',
    hour: 'numeric', minute: '2-digit',
  })
}

onMounted(async () => {
  try {
    const { data } = await api.get('/admin/assist-logs')
    logs.value = data.logs
  } catch (_) {
    toast.error('Failed to load assist logs.')
  } finally {
    fetching.value = false
  }
})
</script>