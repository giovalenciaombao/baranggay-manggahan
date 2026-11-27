<template>
    <div class="p-4 sm:p-6 space-y-5">

      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <p class="text-sm text-gray-400 mt-0.5">Track your filed incident reports.</p>
        </div>
        <RouterLink to="/portal/incidents/new">
          <AppButton variant="primary" size="sm">
            <PlusIcon class="w-4 h-4" />
            File Report
          </AppButton>
        </RouterLink>
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

      <!-- List -->
      <div v-if="filtered.length === 0" class="card rounded-2xl p-10 text-center text-gray-400 text-sm">
        No incident reports found.
      </div>

      <div v-else class="space-y-3">
        <RouterLink
          v-for="item in filtered"
          :key="item.id"
          class="card rounded-2xl p-4 flex items-start gap-4 hover:shadow-sm transition-shadow cursor-pointer"
          :to="{ name: 'resident-incident-detail', params: { id: item.id } }"
        >
          <div :class="['w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0', item.iconBg]">
            <ExclamationCircleIcon :class="['w-5 h-5', item.iconColor]" />
          </div>
          <div class="flex-1 min-w-0">
            <div class="flex items-center justify-between gap-2">
              <p class="text-sm font-semibold text-gray-800 truncate">{{ item.title }}</p>
              <AppBadge :variant="item.status.variant" class="flex-shrink-0">
                {{ item.status.label }}
              </AppBadge>
            </div>
            <p class="text-xs text-gray-400 mt-0.5 line-clamp-2">{{ item.description }}</p>
            <p class="text-xs text-gray-300 mt-1">{{ item.date }}</p>
          </div>
        </RouterLink>
      </div>

    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import { PlusIcon, ExclamationCircleIcon } from '@heroicons/vue/24/outline'
import api from '@/services/api'
import AppButton from '@/components/common/AppButton.vue'
import AppBadge from '@/components/common/AppBadge.vue'

const activeFilter = ref('all')
const loading      = ref(true)
const incidents    = ref([])

const statusMap = {
  pending:   { variant: 'warning', label: 'Pending'      },
  review:    { variant: 'info',    label: 'Under Review' },
  resolved:  { variant: 'success', label: 'Resolved'     },
  dismissed: { variant: 'default', label: 'Dismissed'    },
}

const filters = [
  { label: 'All',          value: 'all'       },
  { label: 'Pending',      value: 'pending'   },
  { label: 'Under Review', value: 'review'    },
  { label: 'Resolved',     value: 'resolved'  },
]

const incidentsWithMeta = computed(() =>
  incidents.value.map(i => ({
    ...i,
    statusKey: i.status,
    status:    statusMap[i.status] ?? { variant: 'default', label: i.status },
    iconBg:    'bg-red-50',
    iconColor: 'text-red-400',
    date:      new Date(i.created_at).toLocaleDateString('en-PH', { year: 'numeric', month: 'long', day: 'numeric' }),
  }))
)

const filtered = computed(() =>
  activeFilter.value === 'all'
    ? incidentsWithMeta.value
    : incidentsWithMeta.value.filter(i => i.statusKey === activeFilter.value)
)

onMounted(async () => {
  try {
    const { data } = await api.get('/incidents')
    incidents.value = data.incidents
  } finally {
    loading.value = false
  }
})
</script>
