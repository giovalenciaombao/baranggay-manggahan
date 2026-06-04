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
import { ref, computed } from 'vue'
import {
  ExclamationTriangleIcon,
  MegaphoneIcon,
  InformationCircleIcon,
  WrenchScrewdriverIcon,
} from '@heroicons/vue/24/outline'
import AppBadge from '@/components/common/AppBadge.vue'

const activeFilter = ref('all')

const filters = [
  { label: 'All',           value: 'all'          },
  { label: 'Emergency',     value: 'emergency'    },
  { label: 'Announcement',  value: 'announcement' },
  { label: 'Maintenance',   value: 'maintenance'  },
]

const alerts = ref([
  {
    id: 1,
    type: 'emergency',
    title: 'Typhoon Advisory',
    message: 'Signal No. 2 raised. Residents near low-lying areas are advised to evacuate to the barangay hall.',
    date: 'June 4, 2026',
    icon: ExclamationTriangleIcon,
    iconBg: 'bg-red-50',
    iconColor: 'text-red-500',
    borderColor: 'border-red-400',
    badge: { variant: 'danger', label: 'Emergency' },
  },
  {
    id: 2,
    type: 'announcement',
    title: 'Barangay Fiesta 2026',
    message: 'Join us on June 24 for the annual Barangay Manggahan Fiesta! Activities start at 8AM at the covered court.',
    date: 'June 3, 2026',
    icon: MegaphoneIcon,
    iconBg: 'bg-green-50',
    iconColor: 'text-green-600',
    borderColor: 'border-green-400',
    badge: { variant: 'success', label: 'Announcement' },
  },
  {
    id: 3,
    type: 'maintenance',
    title: 'Scheduled Water Interruption',
    message: 'June 5, 8AM–5PM. Purok 1 to 4 affected. Please store enough water in advance.',
    date: 'June 2, 2026',
    icon: WrenchScrewdriverIcon,
    iconBg: 'bg-yellow-50',
    iconColor: 'text-yellow-500',
    borderColor: 'border-yellow-400',
    badge: { variant: 'warning', label: 'Maintenance' },
  },
  {
    id: 4,
    type: 'announcement',
    title: 'Free Medical Mission',
    message: 'Free check-up, medicines, and dental services on June 12 at the barangay health center. Open to all residents.',
    date: 'June 1, 2026',
    icon: InformationCircleIcon,
    iconBg: 'bg-blue-50',
    iconColor: 'text-blue-500',
    borderColor: 'border-blue-400',
    badge: { variant: 'info', label: 'Announcement' },
  },
])

const filtered = computed(() =>
  activeFilter.value === 'all'
    ? alerts.value
    : alerts.value.filter(a => a.type === activeFilter.value)
)
</script>