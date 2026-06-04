<template>
  <div class="p-4 sm:p-6 space-y-5">

    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <p class="text-sm text-gray-400 mt-0.5">Manage your barangay appointments.</p>
      </div>
      <RouterLink :to="{ name: 'resident-appointments-new' }">
        <AppButton variant="primary" size="sm">
          <PlusIcon class="w-4 h-4" />
          Book
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

    <!-- Empty -->
    <div v-if="filtered.length === 0" class="card rounded-2xl p-10 text-center text-gray-400 text-sm">
      No appointments found.
    </div>

    <!-- List -->
    <div v-else class="space-y-3">
      <div
        v-for="item in filtered"
        :key="item.id"
        class="card rounded-2xl p-4 flex items-start gap-4"
      >
        <div class="w-10 h-10 rounded-xl bg-green-50 flex items-center justify-center flex-shrink-0">
          <CalendarDaysIcon class="w-5 h-5 text-green-600" />
        </div>
        <div class="flex-1 min-w-0">
          <div class="flex items-center justify-between gap-2">
            <p class="text-sm font-semibold text-gray-800 truncate">{{ item.purpose }}</p>
            <AppBadge :variant="item.status.variant" class="flex-shrink-0">
              {{ item.status.label }}
            </AppBadge>
          </div>
          <p class="text-xs text-gray-500 mt-0.5">{{ item.date }} · {{ item.time }}</p>
          <p class="text-xs text-gray-400 mt-0.5">{{ item.notes }}</p>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { RouterLink } from 'vue-router'
import { PlusIcon, CalendarDaysIcon } from '@heroicons/vue/24/outline'
import AppButton from '@/components/common/AppButton.vue'
import AppBadge from '@/components/common/AppBadge.vue'

const activeFilter = ref('all')

const filters = [
  { label: 'All',       value: 'all'      },
  { label: 'Pending',   value: 'pending'  },
  { label: 'Approved',  value: 'approved' },
  { label: 'Cancelled', value: 'cancelled'},
]

const appointments = ref([
  {
    id: 1,
    purpose: 'Barangay ID Renewal',
    date: 'June 10, 2026',
    time: '9:00 AM',
    notes: 'Please bring 1 valid ID.',
    statusKey: 'approved',
    status: { variant: 'success', label: 'Approved' },
  },
  {
    id: 2,
    purpose: 'Certificate of Residency',
    date: 'June 15, 2026',
    time: '2:00 PM',
    notes: 'For employment purposes.',
    statusKey: 'pending',
    status: { variant: 'warning', label: 'Pending' },
  },
])

const filtered = computed(() =>
  activeFilter.value === 'all'
    ? appointments.value
    : appointments.value.filter(a => a.statusKey === activeFilter.value)
)
</script>