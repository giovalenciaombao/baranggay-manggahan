<template>
  <div class="p-4 sm:p-6 space-y-5">

    <!-- Header -->
    <div>
      <p class="text-sm text-gray-400 mt-0.5">Stay updated with barangay news.</p>
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

    <!-- Empty -->
    <div v-if="filtered.length === 0" class="card rounded-2xl p-10 text-center text-gray-400 text-sm">
      No alerts found.
    </div>

    <!-- List -->
    <div v-else class="space-y-3">
      <div
        v-for="item in filtered"
        :key="item.id"
        :class="['card rounded-2xl p-4 flex items-start gap-4 border-l-4', item.borderColor]"
      >
        <div :class="['w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0', item.iconBg]">
          <component :is="item.icon" :class="['w-5 h-5', item.iconColor]" />
        </div>
        <div class="flex-1 min-w-0">
          <div class="flex items-center justify-between gap-2">
            <p class="text-sm font-semibold text-gray-800">{{ item.title }}</p>
            <AppBadge :variant="item.badge.variant" class="flex-shrink-0">
              {{ item.badge.label }}
            </AppBadge>
          </div>
          <p class="text-xs text-gray-500 mt-1 leading-relaxed">{{ item.message }}</p>
          <p class="text-xs text-gray-300 mt-2">{{ item.date }}</p>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import {
  ExclamationTriangleIcon,
  MegaphoneIcon,
  InformationCircleIcon,
  WrenchScrewdriverIcon,
} from '@heroicons/vue/24/outline'
import api from '@/services/api'
import AppBadge from '@/components/common/AppBadge.vue'

const activeFilter = ref('all')
const loading      = ref(true)
const alerts       = ref([])

const typeConfig = {
  emergency:    {
    icon: ExclamationTriangleIcon,
    iconBg: 'bg-red-50',    iconColor: 'text-red-500',
    borderColor: 'border-red-400',
    badge: { variant: 'danger',  label: 'Emergency'    },
  },
  announcement: {
    icon: MegaphoneIcon,
    iconBg: 'bg-green-50',  iconColor: 'text-green-600',
    borderColor: 'border-green-400',
    badge: { variant: 'success', label: 'Announcement' },
  },
  maintenance:  {
    icon: WrenchScrewdriverIcon,
    iconBg: 'bg-yellow-50', iconColor: 'text-yellow-500',
    borderColor: 'border-yellow-400',
    badge: { variant: 'warning', label: 'Maintenance'  },
  },
}

const filters = [
  { label: 'All',          value: 'all'          },
  { label: 'Emergency',    value: 'emergency'    },
  { label: 'Announcement', value: 'announcement' },
  { label: 'Maintenance',  value: 'maintenance'  },
]

const alertsWithConfig = computed(() =>
  alerts.value.map(a => ({
    ...a,
    ...(typeConfig[a.type] ?? typeConfig.announcement),
    date: new Date(a.created_at).toLocaleDateString('en-PH', {
      year: 'numeric', month: 'long', day: 'numeric'
    }),
  }))
)

const filtered = computed(() =>
  activeFilter.value === 'all'
    ? alertsWithConfig.value
    : alertsWithConfig.value.filter(a => a.type === activeFilter.value)
)

onMounted(async () => {
  try {
    const { data } = await api.get('/alerts')
    alerts.value = data.alerts
  } finally {
    loading.value = false
  }
})
</script>