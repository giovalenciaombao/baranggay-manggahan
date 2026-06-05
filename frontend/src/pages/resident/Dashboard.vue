<template>
    <div class="p-4 sm:p-6 space-y-6">

      <!-- Header -->
      <div>
        <h1 class="text-xl font-semibold text-primary-dark">Good {{ timeOfDay }}, {{ user?.first_name }}!</h1>
        <p class="text-sm text-gray-400 mt-0.5">Here's what's happening in Barangay Manggahan.</p>
      </div>

      <!-- Stat cards -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <StatCard
          v-for="stat in statCards"
          :key="stat.label"
          :label="stat.label"
          :value="stat.value"
          :icon="stat.icon"
          :color="stat.color"
        />
      </div>

      <!-- Recent activity + Quick actions -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">

        <!-- Recent activity -->
        <div class="sm:col-span-2 card rounded-2xl p-5">
          <h2 class="text-sm font-semibold text-primary-dark mb-4">Recent Activity</h2>

          <div v-if="activity.length === 0" class="text-center py-8 text-gray-400 text-sm">
            No recent activity yet.
          </div>

          <ul v-else class="space-y-3">
            <li
              v-for="item in activityWithMeta"
              :key="item.id"
              class="flex items-start gap-3"
            >
              <div :class="['w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0', item.iconBg]">
                <component :is="item.icon" class="w-4 h-4" :class="item.iconColor" />
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm text-gray-700 font-medium truncate">{{ item.title }}</p>
                <p class="text-xs text-gray-400">{{ item.subtitle }} · {{ item.date }}</p>
              </div>
              <AppBadge :variant="item.status.variant" class="flex-shrink-0">
                {{ item.status.label }}
              </AppBadge>
            </li>
          </ul>

          <RouterLink
            to="/portal/incidents"
            class="mt-4 block text-center text-xs text-primary font-medium hover:underline"
          >
            View all activity →
          </RouterLink>
        </div>

        <!-- Quick actions -->
        <div class="card rounded-2xl p-5">
          <h2 class="text-sm font-semibold text-primary-dark mb-4">Quick Actions</h2>
          <div class="space-y-2">
            <RouterLink
              v-for="action in quickActions"
              :key="action.label"
              :to="action.to"
              class="flex items-center gap-3 p-3 rounded-xl hover:bg-primary-light transition-colors group"
            >
              <div class="w-8 h-8 bg-primary-light rounded-lg flex items-center justify-center group-hover:bg-white transition-colors">
                <component :is="action.icon" class="w-4 h-4 text-primary" />
              </div>
              <span class="text-sm text-gray-700 font-medium">{{ action.label }}</span>
              <ChevronRightIcon class="w-4 h-4 text-gray-300 ml-auto" />
            </RouterLink>
          </div>
        </div>

      </div>

      <!-- Active community alerts -->
      <div v-if="alerts.length > 0" class="card rounded-2xl p-5">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-sm font-semibold text-primary-dark">Active Alerts</h2>
          <RouterLink to="/portal/alerts" class="text-xs text-primary font-medium hover:underline">
            See all
          </RouterLink>
        </div>
        <div class="space-y-2">
          <div
            v-for="alert in alertsWithConfig"
            :key="alert.id"
            :class="['flex items-start gap-3 p-3 rounded-xl border', alert.bgClass, alert.borderClass]"
          >
            <ExclamationTriangleIcon :class="['w-4 h-4 flex-shrink-0 mt-0.5', alert.iconClass]" />
            <div>
              <p :class="['text-sm font-medium', alert.titleClass]">{{ alert.title }}</p>
              <p :class="['text-xs mt-0.5', alert.subClass]">{{ alert.message }}</p>
            </div>
          </div>
        </div>
      </div>

    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import {
  ExclamationCircleIcon,
  CalendarDaysIcon,
  BellAlertIcon,
  TrashIcon,
  ChevronRightIcon,
  ExclamationTriangleIcon,
  ClipboardDocumentListIcon,
} from '@heroicons/vue/24/outline'
import { useAuthStore } from '@/stores/auth'
import api from '@/services/api'
import StatCard from '@/components/common/StatCard.vue'
import AppBadge from '@/components/common/AppBadge.vue'

const authStore  = useAuthStore()
const user       = computed(() => authStore.user)
const loading    = ref(true)
const stats      = ref({ total_incidents: 0, total_appointments: 0, total_waste: 0, unread_alerts: 0 })
const activity   = ref([])
const alerts     = ref([])

const timeOfDay = computed(() => {
  const h = new Date().getHours()
  if (h < 12) return 'morning'
  if (h < 18) return 'afternoon'
  return 'evening'
})

const statCards = computed(() => [
  { label: 'Incidents Filed',  value: stats.value.total_incidents,    icon: ExclamationCircleIcon, color: 'red'    },
  { label: 'Appointments',     value: stats.value.total_appointments,  icon: CalendarDaysIcon,      color: 'green'  },
  { label: 'Unread Alerts',    value: stats.value.unread_alerts,       icon: BellAlertIcon,         color: 'yellow' },
  { label: 'Waste Pickups',    value: stats.value.total_waste,         icon: TrashIcon,             color: 'blue'   },
])

const statusVariantMap = {
  pending:   { variant: 'warning', label: 'Pending'      },
  review:    { variant: 'info',    label: 'Under Review' },
  resolved:  { variant: 'success', label: 'Resolved'     },
  approved:  { variant: 'success', label: 'Approved'     },
  cancelled: { variant: 'danger',  label: 'Cancelled'    },
  logged:    { variant: 'default', label: 'Logged'       },
}

const iconMap = {
  incident:    { icon: ExclamationCircleIcon, iconBg: 'bg-red-50',   iconColor: 'text-red-500'   },
  appointment: { icon: CalendarDaysIcon,      iconBg: 'bg-green-50', iconColor: 'text-green-600' },
}

const alertConfig = {
  emergency:    { bgClass: 'bg-red-50',    borderClass: 'border-red-200',    iconClass: 'text-red-500',    titleClass: 'text-red-800',    subClass: 'text-red-600'    },
  announcement: { bgClass: 'bg-green-50',  borderClass: 'border-green-200',  iconClass: 'text-green-600',  titleClass: 'text-green-800',  subClass: 'text-green-600'  },
  maintenance:  { bgClass: 'bg-yellow-50', borderClass: 'border-yellow-200', iconClass: 'text-yellow-500', titleClass: 'text-yellow-800', subClass: 'text-yellow-600' },
}

const quickActions = [
  { label: 'File an Incident', to: '/portal/incidents/new',    icon: ExclamationCircleIcon },
  { label: 'Book Appointment', to: '/portal/appointments/new', icon: CalendarDaysIcon      },
  { label: 'View Alerts',      to: '/portal/alerts',           icon: BellAlertIcon         },
  { label: 'Waste Registry',   to: '/portal/waste',            icon: TrashIcon             },
]

const alertsWithConfig = computed(() =>
  alerts.value.map(a => ({ ...a, ...alertConfig[a.type] }))
)

const activityWithMeta = computed(() =>
  activity.value.map(a => ({
    ...a,
    ...(iconMap[a.type] ?? iconMap.incident),
    status: statusVariantMap[a.status] ?? { variant: 'default', label: a.status },
  }))
)

onMounted(async () => {
  try {
    const { data } = await api.get('/resident/dashboard')
    stats.value    = data.stats
    activity.value = data.recent_activity
    alerts.value   = data.active_alerts
  } finally {
    loading.value = false
  }
})
</script>