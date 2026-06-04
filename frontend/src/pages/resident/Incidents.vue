<template>
  <ResidentLayout>
    <div class="p-4 sm:p-6 space-y-5">

      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-xl font-semibold text-primary-dark">Incident Reports</h1>
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
        <div
          v-for="item in filtered"
          :key="item.id"
          class="card rounded-2xl p-4 flex items-start gap-4 hover:shadow-sm transition-shadow cursor-pointer"
          @click="router.push(`/portal/incidents/${item.id}`)"
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
        </div>
      </div>

    </div>
  </ResidentLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { PlusIcon, ExclamationCircleIcon } from '@heroicons/vue/24/outline'
import ResidentLayout from '@/layouts/ResidentLayout.vue'
import AppButton from '@/components/common/AppButton.vue'
import AppBadge from '@/components/common/AppBadge.vue'

const router = useRouter()
const activeFilter = ref('all')

const filters = [
  { label: 'All',        value: 'all'        },
  { label: 'Pending',    value: 'pending'    },
  { label: 'Under Review', value: 'review'  },
  { label: 'Resolved',   value: 'resolved'   },
]

const incidents = ref([
  {
    id: 1,
    title: 'Noise complaint — Purok 3',
    description: 'Loud music every night past 10PM near the basketball court.',
    date: 'June 2, 2026',
    statusKey: 'pending',
    status: { variant: 'warning', label: 'Pending' },
    iconBg: 'bg-red-50',
    iconColor: 'text-red-400',
  },
  {
    id: 2,
    title: 'Illegal dumping near creek',
    description: 'Someone has been dumping garbage near the creek at Purok 5.',
    date: 'May 28, 2026',
    statusKey: 'review',
    status: { variant: 'info', label: 'Under Review' },
    iconBg: 'bg-orange-50',
    iconColor: 'text-orange-400',
  },
  {
    id: 3,
    title: 'Broken streetlight',
    description: 'Streetlight at corner of Rizal St. has been out for 2 weeks.',
    date: 'May 20, 2026',
    statusKey: 'resolved',
    status: { variant: 'success', label: 'Resolved' },
    iconBg: 'bg-green-50',
    iconColor: 'text-green-500',
  },
])

const filtered = computed(() =>
  activeFilter.value === 'all'
    ? incidents.value
    : incidents.value.filter(i => i.statusKey === activeFilter.value)
)
</script>