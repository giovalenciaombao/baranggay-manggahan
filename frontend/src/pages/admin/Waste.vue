<template>
  <div class="p-4 sm:p-6 space-y-5">

    <!-- Header -->
    <div>
      <h1 class="text-xl font-semibold text-primary-dark">Waste Management</h1>
      <p class="text-sm text-gray-400 mt-0.5">Monitor and manage waste pickup requests.</p>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
      <div v-for="stat in stats" :key="stat.label"
        class="card rounded-2xl p-4"
      >
        <p class="text-xs text-gray-400 mb-1">{{ stat.label }}</p>
        <p class="text-2xl font-semibold text-primary-dark">{{ stat.value }}</p>
      </div>
    </div>

    <!-- Filters -->
    <div class="flex flex-wrap gap-2">
      <button
        v-for="f in filters"
        :key="f.value"
        @click="activeFilter = f.value"
        :class="[
          'px-3 py-1.5 rounded-lg text-xs font-medium transition-colors',
          activeFilter === f.value
            ? 'bg-primary text-white'
            : 'bg-white border border-gray-200 text-gray-500 hover:border-primary'
        ]"
      >
        {{ f.label }}
      </button>
    </div>

    <!-- Search -->
    <div class="relative">
      <MagnifyingGlassIcon class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" />
      <input
        v-model="search"
        type="text"
        placeholder="Search by resident..."
        class="w-full pl-9 pr-4 py-2.5 border border-gray-200 rounded-xl text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-primary"
      />
    </div>

    <!-- Empty -->
    <div v-if="filtered.length === 0" class="card rounded-2xl p-10 text-center text-gray-400 text-sm">
      No records found.
    </div>

    <!-- List -->
    <div v-else class="space-y-3">
      <div
        v-for="item in filtered"
        :key="item.id"
        class="card rounded-2xl p-4 space-y-3"
      >
        <div class="flex items-start gap-3">
          <div :class="['w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0', item.iconBg]">
            <TrashIcon :class="['w-5 h-5', item.iconColor]" />
          </div>
          <div class="flex-1 min-w-0">
            <div class="flex items-center justify-between gap-2">
              <p class="text-sm font-semibold text-gray-800 truncate">{{ item.type }}</p>
              <AppBadge :variant="item.status.variant" class="flex-shrink-0">
                {{ item.status.label }}
              </AppBadge>
            </div>
            <p class="text-xs text-gray-400 mt-0.5">{{ item.resident }}</p>
            <p class="text-xs text-gray-500 mt-0.5 flex items-center gap-1">
              <CalendarDaysIcon class="w-3.5 h-3.5" />
              {{ item.date }}
            </p>
            <p v-if="item.notes" class="text-xs text-gray-400 mt-0.5 italic">
              "{{ item.notes }}"
            </p>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex gap-2 pt-1 border-t border-gray-100">
          <select
            v-model="item.statusKey"
            @change="updateStatus(item)"
            class="flex-1 border border-gray-200 rounded-lg px-3 py-1.5 text-xs text-gray-700 focus:outline-none focus:ring-2 focus:ring-primary"
          >
            <option value="pending">Pending</option>
            <option value="scheduled">Scheduled</option>
            <option value="collected">Collected</option>
            <option value="missed">Missed</option>
          </select>
          <button
            @click="deleteRecord(item.id)"
            class="px-3 py-1.5 rounded-lg bg-red-50 hover:bg-red-100 text-xs text-red-600 transition-colors"
          >
            <TrashIcon class="w-3.5 h-3.5" />
          </button>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useToast } from 'vue-toastification'
import {
  MagnifyingGlassIcon,
  TrashIcon,
} from '@heroicons/vue/24/outline'
import api from '@/services/api'
import AppBadge from '@/components/common/AppBadge.vue'

const toast        = useToast()
const activeFilter = ref('all')
const search       = ref('')
const loading      = ref(true)
const waste        = ref([])

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

const filters = [
  { label: 'All',       value: 'all'       },
  { label: 'Pending',   value: 'pending'   },
  { label: 'Scheduled', value: 'scheduled' },
  { label: 'Collected', value: 'collected' },
  { label: 'Missed',    value: 'missed'    },
]

const wasteWithMeta = computed(() =>
  waste.value.map(w => ({
    ...w,
    statusKey: w.status,
    resident:  w.user ? `${w.user.first_name} ${w.user.last_name}` : '—',
    status:    statusMap[w.status] ?? { variant: 'default', label: w.status },
    ...(typeConfig[w.type] ?? { iconBg: 'bg-gray-50', iconColor: 'text-gray-500' }),
    date: new Date(w.date).toLocaleDateString('en-PH', {
      year: 'numeric', month: 'long', day: 'numeric'
    }),
  }))
)

const filtered = computed(() => {
  let list = wasteWithMeta.value
  if (activeFilter.value !== 'all') {
    list = list.filter(w => w.statusKey === activeFilter.value)
  }
  if (search.value.trim()) {
    const q = search.value.toLowerCase()
    list = list.filter(w =>
      w.resident.toLowerCase().includes(q) ||
      w.type.toLowerCase().includes(q)
    )
  }
  return list
})

async function updateStatus(item, newStatus) {
  try {
    await api.put(`/waste/${item.id}`, { status: newStatus })
    const original = waste.value.find(w => w.id === item.id)
    if (original) original.status = newStatus
    item.statusKey = newStatus
    item.status    = statusMap[newStatus]
    toast.success('Waste request status updated!')
  } catch (err) {
    toast.error(err.response?.data?.message || 'Failed to update status.')
  }
}

async function deleteRequest(id) {
  try {
    await api.delete(`/waste/${id}`)
    waste.value = waste.value.filter(w => w.id !== id)
    toast.success('Record deleted.')
  } catch (err) {
    toast.error(err.response?.data?.message || 'Failed to delete.')
  }
}

onMounted(async () => {
  try {
    const { data } = await api.get('/admin/waste')
    waste.value = data.waste_requests
  } finally {
    loading.value = false
  }
})
</script>