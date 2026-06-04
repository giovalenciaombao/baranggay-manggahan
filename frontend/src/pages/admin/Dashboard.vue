<template>
  <div class="p-4 sm:p-6 space-y-6">

    <!-- Header -->
    <div>
      <p class="text-sm text-gray-400 mt-0.5">Overview of Barangay Manggahan.</p>
    </div>

    <!-- Stat cards -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
      <StatCard
        v-for="stat in stats"
        :key="stat.label"
        :label="stat.label"
        :value="stat.value"
        :icon="stat.icon"
        :color="stat.color"
      />
    </div>

    <!-- Charts row -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

      <!-- Incidents per month -->
      <div class="card rounded-2xl p-5">
        <h2 class="text-sm font-semibold text-primary-dark mb-4">Incidents per Month</h2>
        <canvas ref="incidentChart" height="200" />
      </div>

      <!-- Appointments status -->
      <div class="card rounded-2xl p-5">
        <h2 class="text-sm font-semibold text-primary-dark mb-4">Appointments by Status</h2>
        <canvas ref="appointmentChart" height="200" />
      </div>

    </div>

    <!-- Pending approvals + Recent incidents -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

      <!-- Pending user approvals -->
      <div class="card rounded-2xl p-5">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-sm font-semibold text-primary-dark">Pending Approvals</h2>
          <RouterLink to="/admin/users" class="text-xs text-primary font-medium hover:underline">
            View all
          </RouterLink>
        </div>

        <div v-if="pendingUsers.length === 0" class="text-center py-6 text-gray-400 text-sm">
          No pending approvals.
        </div>

        <div v-else class="space-y-3">
          <div
            v-for="user in pendingUsers"
            :key="user.id"
            class="flex items-center gap-3"
          >
            <div class="w-8 h-8 rounded-full bg-primary-light flex items-center justify-center flex-shrink-0">
              <span class="text-xs font-semibold text-primary-dark">{{ initials(user.name) }}</span>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-gray-800 truncate">{{ user.name }}</p>
              <p class="text-xs text-gray-400">{{ user.email }}</p>
            </div>
            <div class="flex gap-1.5">
              <button
                @click="approveUser(user.id)"
                class="p-1.5 rounded-lg bg-green-50 hover:bg-green-100 transition-colors"
              >
                <CheckIcon class="w-3.5 h-3.5 text-green-600" />
              </button>
              <button
                @click="rejectUser(user.id)"
                class="p-1.5 rounded-lg bg-red-50 hover:bg-red-100 transition-colors"
              >
                <XMarkIcon class="w-3.5 h-3.5 text-red-500" />
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Recent incidents -->
      <div class="card rounded-2xl p-5">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-sm font-semibold text-primary-dark">Recent Incidents</h2>
          <RouterLink to="/admin/incidents" class="text-xs text-primary font-medium hover:underline">
            View all
          </RouterLink>
        </div>

        <div class="space-y-3">
          <div
            v-for="item in recentIncidents"
            :key="item.id"
            class="flex items-start gap-3"
          >
            <div class="w-8 h-8 rounded-lg bg-red-50 flex items-center justify-center flex-shrink-0">
              <ExclamationCircleIcon class="w-4 h-4 text-red-400" />
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-gray-800 truncate">{{ item.title }}</p>
              <p class="text-xs text-gray-400">{{ item.resident }} · {{ item.date }}</p>
            </div>
            <AppBadge :variant="item.status.variant" class="flex-shrink-0">
              {{ item.status.label }}
            </AppBadge>
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
  UsersIcon,
  ExclamationCircleIcon,
  CalendarDaysIcon,
  BellAlertIcon,
  CheckIcon,
  XMarkIcon,
} from '@heroicons/vue/24/outline'
import { useToast } from 'vue-toastification'
import api from '@/services/api'
import StatCard from '@/components/common/StatCard.vue'
import AppBadge from '@/components/common/AppBadge.vue'

const toast = useToast()

const incidentChart    = ref(null)
const appointmentChart = ref(null)
const loading          = ref(true)

const stats            = ref({})
const pendingUsers     = ref([])
const recentIncidents  = ref([])

const statusMap = {
  pending:   { variant: 'warning', label: 'Pending'      },
  review:    { variant: 'info',    label: 'Under Review' },
  resolved:  { variant: 'success', label: 'Resolved'     },
  dismissed: { variant: 'default', label: 'Dismissed'    },
}

const statCards = computed(() => [
  { label: 'Total Residents', value: stats.value.total_residents   ?? 0, icon: UsersIcon,             color: 'blue'   },
  { label: 'Incidents',       value: stats.value.total_incidents   ?? 0, icon: ExclamationCircleIcon, color: 'red'    },
  { label: 'Appointments',    value: stats.value.total_appointments ?? 0, icon: CalendarDaysIcon,      color: 'green'  },
  { label: 'Active Alerts',   value: stats.value.active_alerts     ?? 0, icon: BellAlertIcon,         color: 'yellow' },
])

async function approveUser(id) {
  await api.put(`/admin/users/${id}/status`, { status: 'active' })
  pendingUsers.value = pendingUsers.value.filter(u => u.id !== id)
  toast.success('User approved!')
}

async function rejectUser(id) {
  await api.put(`/admin/users/${id}/status`, { status: 'rejected' })
  pendingUsers.value = pendingUsers.value.filter(u => u.id !== id)
  toast.error('User rejected.')
}

function initials(user) {
  return `${user.first_name?.[0] ?? ''}${user.last_name?.[0] ?? ''}`.toUpperCase()
}

onMounted(async () => {
  try {
    const { data } = await api.get('/admin/dashboard')

    stats.value           = data.stats
    pendingUsers.value    = data.pending_users
    recentIncidents.value = data.recent_incidents.map(i => ({
      ...i,
      resident: i.user ? `${i.user.first_name} ${i.user.last_name}` : '—',
      date:     new Date(i.created_at).toLocaleDateString('en-PH', { month: 'short', day: 'numeric' }),
      status:   statusMap[i.status] ?? { variant: 'default', label: i.status },
    }))

    // Charts
    const Chart = (await import('chart.js/auto')).default

    // Incidents per month
    const monthLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
    const monthData   = Array(12).fill(0)
    data.incidents_per_month.forEach(i => { monthData[i.month - 1] = i.count })

    new Chart(incidentChart.value, {
      type: 'bar',
      data: {
        labels: monthLabels,
        datasets: [{
          label: 'Incidents',
          data: monthData,
          backgroundColor: '#5C6B2E',
          borderRadius: 6,
        }],
      },
      options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
          y: { beginAtZero: true, ticks: { stepSize: 1 } },
          x: { grid: { display: false } },
        },
      },
    })

    // Appointments by status
    const apptData   = data.appointments_by_status
    const apptLabels = apptData.map(a => a.status.charAt(0).toUpperCase() + a.status.slice(1))
    const apptCounts = apptData.map(a => a.count)

    new Chart(appointmentChart.value, {
      type: 'doughnut',
      data: {
        labels: apptLabels,
        datasets: [{
          data: apptCounts,
          backgroundColor: ['#5C6B2E', '#F5A623', '#E74C3C', '#95A5A6'],
          borderWidth: 0,
        }],
      },
      options: {
        responsive: true,
        plugins: {
          legend: {
            position: 'bottom',
            labels: { boxWidth: 12, padding: 16, font: { size: 12 } },
          },
        },
      },
    })

  } finally {
    loading.value = false
  }
})
</script>