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

    <!-- Loading -->
    <div v-if="loading" class="card rounded-2xl p-10 text-center text-gray-400 text-sm">
      Loading appointments...
    </div>

    <!-- Empty -->
    <div v-else-if="filtered.length === 0" class="card rounded-2xl p-10 text-center text-gray-400 text-sm">
      No appointments found.
    </div>

    <!-- List -->
    <div v-else class="space-y-3">
      <div
        v-for="item in filtered"
        :key="item.id"
        :class="[
          'card rounded-2xl p-4 space-y-3',
          item.status === 'rescheduled' ? 'ring-1 ring-blue-200' : ''
        ]"
      >
        <div class="flex items-start gap-4">
          <div class="w-10 h-10 rounded-xl bg-green-50 flex items-center justify-center flex-shrink-0">
            <CalendarDaysIcon class="w-5 h-5 text-green-600" />
          </div>

          <div class="flex-1 min-w-0">
            <div class="flex items-center justify-between gap-2">
              <p class="text-sm font-semibold text-gray-800 truncate">{{ item.purpose }}</p>
              <AppointmentStatusBadge :status="item.status" class="flex-shrink-0" />
            </div>

            <!-- Current schedule -->
            <p class="text-xs text-gray-600 mt-0.5 flex items-center gap-1">
              <ClockIcon class="w-3.5 h-3.5" />
              {{ formatDate(item.date) }} · {{ item.time }}
            </p>

            <!-- Original schedule (kung na-resched) -->
            <p v-if="item.original_date" class="text-xs text-gray-400 mt-0.5 line-through">
              You requested: {{ formatDate(item.original_date) }} · {{ item.original_time }}
            </p>

            <!-- Resident notes -->
            <p v-if="item.notes" class="text-xs text-gray-400 mt-1 italic">
              "{{ item.notes }}"
            </p>
          </div>
        </div>

        <!-- Admin note / reason -->
        <div
          v-if="item.admin_note"
          :class="[
            'rounded-lg px-3 py-2 text-xs',
            item.status === 'cancelled'
              ? 'bg-red-50 text-red-700'
              : 'bg-blue-50 text-blue-700'
          ]"
        >
          <p class="font-medium mb-0.5">
            {{ item.status === 'rescheduled'
              ? 'Your appointment was rescheduled'
              : 'Note from Barangay staff' }}
          </p>
          <p>{{ item.admin_note }}</p>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import { useToast } from 'vue-toastification'
import { PlusIcon, CalendarDaysIcon, ClockIcon } from '@heroicons/vue/24/outline'
import api from '@/services/api'
import AppButton from '@/components/common/AppButton.vue'
import AppointmentStatusBadge from '@/components/appointments/AppointmentStatusBadge.vue'

const toast        = useToast()
const activeFilter = ref('all')
const loading      = ref(true)
const appointments = ref([])

const filters = [
  { label: 'All',         value: 'all'         },
  { label: 'Pending',     value: 'pending'     },
  { label: 'Approved',    value: 'approved'    },
  { label: 'Rescheduled', value: 'rescheduled' },
  { label: 'Completed',   value: 'completed'   },
  { label: 'Cancelled',   value: 'cancelled'   },
]

const filtered = computed(() =>
  activeFilter.value === 'all'
    ? appointments.value
    : appointments.value.filter(a => a.status === activeFilter.value)
)

function formatDate(d) {
  if (!d) return ''
  return new Date(String(d).slice(0, 10) + 'T00:00:00').toLocaleDateString('en-PH', {
    month: 'short', day: 'numeric', year: 'numeric',
  })
}

onMounted(async () => {
  try {
    const { data } = await api.get('/appointments')
    appointments.value = data.appointments
  } catch (err) {
    toast.error(err.response?.data?.message || 'Failed to load appointments.')
  } finally {
    loading.value = false
  }
})
</script>