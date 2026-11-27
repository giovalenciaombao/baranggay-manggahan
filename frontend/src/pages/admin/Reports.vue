<template>
  <div class="p-4 sm:p-6 space-y-5">

    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-xl font-semibold text-primary-dark">Reports</h1>
        <p class="text-sm text-gray-400 mt-0.5">Generate and export barangay reports.</p>
      </div>
    </div>

    <!-- Report type selector -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
      <button
        v-for="type in reportTypes"
        :key="type.value"
        @click="switchReport(type.value)"
        :class="[
          'card rounded-2xl p-4 text-left transition-all border-2',
          activeReport === type.value
            ? 'border-primary bg-primary-light'
            : 'border-transparent hover:border-gray-200'
        ]"
      >
        <div :class="['w-9 h-9 rounded-xl flex items-center justify-center mb-2', type.iconBg]">
          <component :is="type.icon" :class="['w-4 h-4', type.iconColor]" />
        </div>
        <p class="text-sm font-semibold text-primary-dark">{{ type.label }}</p>
        <p class="text-xs text-gray-400 mt-0.5">{{ rows.length }} records</p>
      </button>
    </div>

    <!-- Filters row -->
    <div class="card rounded-2xl p-4 flex flex-wrap items-end gap-3">
      <div class="flex-1 min-w-[140px]">
        <label class="block text-xs text-gray-400 mb-1">Date From</label>
        <input
          v-model="filters.from"
          type="date"
          class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-primary"
        />
      </div>
      <div class="flex-1 min-w-[140px]">
        <label class="block text-xs text-gray-400 mb-1">Date To</label>
        <input
          v-model="filters.to"
          type="date"
          class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-primary"
        />
      </div>
      <div class="flex-1 min-w-[140px]">
        <label class="block text-xs text-gray-400 mb-1">Status</label>
        <select
          v-model="filters.status"
          class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-primary"
        >
          <option value="">All</option>
          <option v-for="s in currentStatuses" :key="s" :value="s">{{ s }}</option>
        </select>
      </div>
      <AppButton variant="outline" size="sm" @click="fetchReport">
        Apply
      </AppButton>
      <AppButton variant="primary" size="sm" @click="exportCSV">
        <ArrowDownTrayIcon class="w-4 h-4" />
        Export CSV
      </AppButton>
    </div>

    <!-- Summary cards -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
      <div v-for="stat in summaryStats" :key="stat.label" class="card rounded-2xl p-4">
        <p class="text-xs text-gray-400 mb-1">{{ stat.label }}</p>
        <p class="text-2xl font-semibold text-primary-dark">{{ stat.value }}</p>
      </div>
    </div>

    <!-- Loading state -->
    <div v-if="fetching" class="card rounded-2xl p-10 text-center text-gray-400 text-sm">
      Loading...
    </div>

    <!-- Table -->
    <div v-else class="card rounded-2xl overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="bg-primary-light border-b border-gray-100">
              <th
                v-for="col in currentColumns"
                :key="col.key"
                class="px-4 py-3 text-left text-xs font-semibold text-primary-dark"
              >
                {{ col.label }}
              </th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="filteredRows.length === 0">
              <td :colspan="currentColumns.length" class="px-4 py-10 text-center text-gray-400 text-sm">
                No records found.
              </td>
            </tr>
            <tr
              v-for="row in filteredRows"
              :key="row.id"
              class="border-b border-gray-50 hover:bg-gray-50 transition-colors"
            >
              <td
                v-for="col in currentColumns"
                :key="col.key"
                class="px-4 py-3 text-gray-700"
              >
                <AppBadge
                  v-if="col.key === 'status' || col.key === 'type'"
                  :variant="statusVariant(row[col.key])"
                >
                  {{ row[col.key] }}
                </AppBadge>
                <span v-else>{{ row[col.key] ?? '—' }}</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, reactive, onMounted } from 'vue'
import {
  ExclamationCircleIcon,
  CalendarDaysIcon,
  BellAlertIcon,
  ArrowDownTrayIcon,
} from '@heroicons/vue/24/outline'
import { useToast } from 'vue-toastification'
import api from '@/services/api'
import AppButton from '@/components/common/AppButton.vue'
import AppBadge from '@/components/common/AppBadge.vue'

const toast        = useToast()
const activeReport = ref('incidents')
const fetching     = ref(false)
const rows         = ref([])

const filters = reactive({ from: '', to: '', status: '' })

// ─── Report type definitions ───────────────────────────────────────────────
const reportTypes = [
  { value: 'incidents',    label: 'Incidents',    icon: ExclamationCircleIcon, iconBg: 'bg-red-50',    iconColor: 'text-red-400'    },
  { value: 'appointments', label: 'Appointments', icon: CalendarDaysIcon,      iconBg: 'bg-green-50',  iconColor: 'text-green-600'  },
  { value: 'alerts',       label: 'Alerts',       icon: BellAlertIcon,         iconBg: 'bg-yellow-50', iconColor: 'text-yellow-500' },
]

// ─── Column definitions per report ────────────────────────────────────────
const columnMap = {
  incidents:    [
    { key: 'id',       label: '#'         },
    { key: 'resident', label: 'Resident'  },
    { key: 'title',    label: 'Title'     },
    { key: 'category', label: 'Category'  },
    { key: 'location', label: 'Location'  },
    { key: 'date',     label: 'Date'      },
    { key: 'status',   label: 'Status'    },
  ],
  appointments: [
    { key: 'id',       label: '#'         },
    { key: 'resident', label: 'Resident'  },
    { key: 'purpose',  label: 'Purpose'   },
    { key: 'date',     label: 'Date'      },
    { key: 'time',     label: 'Time'      },
    { key: 'status',   label: 'Status'    },
  ],
  alerts: [
    { key: 'id',      label: '#'       },
    { key: 'type',    label: 'Type'    },
    { key: 'title',   label: 'Title'   },
    { key: 'message', label: 'Message' },
    { key: 'date',    label: 'Date'    },
  ],
}

const currentColumns = computed(() => columnMap[activeReport.value])

const currentStatuses = computed(() => {
  const statuses = [...new Set(rows.value.map(r => r.status).filter(Boolean))]
  return statuses
})

const filteredRows = computed(() => {
  if (!filters.status) return rows.value
  return rows.value.filter(r => r.status === filters.status)
})

const summaryStats = computed(() => {
  const total      = filteredRows.value.length
  const statuses   = [...new Set(filteredRows.value.map(r => r.status).filter(Boolean))]
  const mainStatus = statuses[0] ?? '—'
  const mainCount  = filteredRows.value.filter(r => r.status === mainStatus).length

  return [
    { label: 'Total Records', value: total     },
    { label: mainStatus,      value: mainCount },
    { label: 'This Month',    value: total     },
    { label: 'Exported',      value: 0         },
  ]
})

// ─── Fetch from API ────────────────────────────────────────────────────────
async function fetchReport() {
  fetching.value = true
  rows.value     = []
  try {
    const params = {}
    if (filters.from) params.from = filters.from
    if (filters.to)   params.to   = filters.to

    const { data } = await api.get(`/admin/reports/${activeReport.value}`, { params })
    rows.value = data.data
  } catch (err) {
    toast.error('Failed to load report.')
  } finally {
    fetching.value = false
  }
}

function switchReport(type) {
  activeReport.value = type
  filters.from       = ''
  filters.to         = ''
  filters.status     = ''
  fetchReport()
}

function statusVariant(status) {
  const map = {
    Pending:       'warning',
    'Under Review':'info',
    Resolved:      'success',
    Approved:      'success',
    Completed:     'default',
    Cancelled:     'danger',
    Scheduled:     'info',
    Collected:     'success',
    Missed:        'danger',
    Emergency:     'danger',
    Announcement:  'success',
    Maintenance:   'warning',
  }
  return map[status] ?? 'default'
}

function exportCSV() {
  const cols = currentColumns.value
  const data = filteredRows.value

  const header = cols.map(c => c.label).join(',')
  const body   = data.map(r =>
    cols.map(c => `"${(r[c.key] ?? '').toString().replace(/"/g, '""')}"`).join(',')
  ).join('\n')

  const csv  = `${header}\n${body}`
  const blob = new Blob([csv], { type: 'text/csv' })
  const url  = URL.createObjectURL(blob)
  const a    = document.createElement('a')
  a.href     = url
  a.download = `${activeReport.value}-report-${new Date().toISOString().split('T')[0]}.csv`
  a.click()
  URL.revokeObjectURL(url)
  toast.success('CSV exported!')
}

onMounted(() => fetchReport())
</script>