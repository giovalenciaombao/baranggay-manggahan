<template>
  <div class="p-4 sm:p-6 space-y-5">

    <!-- Header -->
    <div>
      <p class="text-sm text-gray-400 mt-0.5">Review and manage resident appointments.</p>
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
        placeholder="Search by resident or purpose..."
        class="w-full pl-9 pr-4 py-2.5 border border-gray-200 rounded-xl text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-primary"
      />
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
        class="card rounded-2xl p-4 space-y-3"
      >
        <!-- Top row -->
        <div class="flex items-start gap-3">
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
            <p class="text-xs text-gray-400 mt-0.5">{{ item.resident }}</p>
            <p class="text-xs text-gray-500 mt-0.5 flex items-center gap-1">
              <ClockIcon class="w-3.5 h-3.5" />
              {{ item.date }} · {{ item.time }}
            </p>
          </div>
        </div>

        <!-- Notes -->
        <p v-if="item.notes" class="text-xs text-gray-400 pl-13 italic">
          "{{ item.notes }}"
        </p>

        <!-- Actions -->
        <div class="flex items-center gap-2 pt-1 border-t border-gray-100">
          <template v-if="item.statusKey === 'pending'">
            <button
              @click="updateStatus(item, 'approved')"
              class="flex-1 flex items-center justify-center gap-1.5 py-1.5 rounded-lg bg-green-50 hover:bg-green-100 text-xs font-medium text-green-700 transition-colors"
            >
              <CheckIcon class="w-3.5 h-3.5" />
              Approve
            </button>
            <button
              @click="updateStatus(item, 'cancelled')"
              class="flex-1 flex items-center justify-center gap-1.5 py-1.5 rounded-lg bg-red-50 hover:bg-red-100 text-xs font-medium text-red-600 transition-colors"
            >
              <XMarkIcon class="w-3.5 h-3.5" />
              Cancel
            </button>
          </template>
          <template v-else>
            <select
              v-model="item.statusKey"
              @change="updateStatus(item, item.statusKey)"
              class="flex-1 border border-gray-200 rounded-lg px-3 py-1.5 text-xs text-gray-700 focus:outline-none focus:ring-2 focus:ring-primary"
            >
              <option value="approved">Approved</option>
              <option value="pending">Pending</option>
              <option value="cancelled">Cancelled</option>
              <option value="completed">Completed</option>
            </select>
          </template>
          <button
            @click="selectedItem = item; showModal = true"
            class="px-3 py-1.5 rounded-lg border border-gray-200 text-xs text-gray-600 hover:bg-gray-50 transition-colors"
          >
            View
          </button>
        </div>
      </div>
    </div>

    <!-- Detail modal -->
    <Teleport to="body">
      <div
        v-if="showModal && selectedItem"
        class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 px-4"
        @click.self="showModal = false"
      >
        <div class="bg-white rounded-2xl p-6 w-full max-w-md space-y-4 shadow-xl">
          <div class="flex items-center justify-between">
            <h3 class="text-base font-semibold text-primary-dark">Appointment Details</h3>
            <button @click="showModal = false" class="text-gray-400 hover:text-gray-600">
              <XMarkIcon class="w-5 h-5" />
            </button>
          </div>

          <div class="space-y-3 text-sm">
            <div>
              <p class="text-xs text-gray-400 mb-0.5">Resident</p>
              <p class="font-medium text-gray-800">{{ selectedItem.resident }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-400 mb-0.5">Purpose</p>
              <p class="text-gray-700">{{ selectedItem.purpose }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-400 mb-0.5">Date & Time</p>
              <p class="text-gray-700">{{ selectedItem.date }} · {{ selectedItem.time }}</p>
            </div>
            <div v-if="selectedItem.notes">
              <p class="text-xs text-gray-400 mb-0.5">Notes</p>
              <p class="text-gray-700 italic">"{{ selectedItem.notes }}"</p>
            </div>
            <div class="flex items-center gap-2">
              <p class="text-xs text-gray-400">Status</p>
              <AppBadge :variant="selectedItem.status.variant">
                {{ selectedItem.status.label }}
              </AppBadge>
            </div>
          </div>

          <button
            @click="showModal = false"
            class="w-full py-2 rounded-xl border border-gray-200 text-sm text-gray-600 hover:bg-gray-50 transition-colors"
          >
            Close
          </button>
        </div>
      </div>
    </Teleport>

  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useToast } from 'vue-toastification'
import {
  MagnifyingGlassIcon,
  CalendarDaysIcon,
  ClockIcon,
  CheckIcon,
  XMarkIcon,
} from '@heroicons/vue/24/outline'
import AppBadge from '@/components/common/AppBadge.vue'

const toast        = useToast()
const activeFilter = ref('all')
const search       = ref('')
const showModal    = ref(false)
const selectedItem = ref(null)

const statusMap = {
  pending:   { variant: 'warning', label: 'Pending'   },
  approved:  { variant: 'success', label: 'Approved'  },
  cancelled: { variant: 'danger',  label: 'Cancelled' },
  completed: { variant: 'default', label: 'Completed' },
}

const appointments = ref([
  {
    id: 1,
    resident: 'Juan dela Cruz',
    purpose: 'Barangay ID Renewal',
    date: 'June 10, 2026',
    time: '9:00 AM',
    notes: 'Please bring 1 valid ID.',
    statusKey: 'pending',
  },
  {
    id: 2,
    resident: 'Maria Santos',
    purpose: 'Certificate of Residency',
    date: 'June 15, 2026',
    time: '2:00 PM',
    notes: 'For employment purposes.',
    statusKey: 'approved',
  },
  {
    id: 3,
    resident: 'Pedro Reyes',
    purpose: 'Barangay Clearance',
    date: 'June 12, 2026',
    time: '10:00 AM',
    notes: '',
    statusKey: 'completed',
  },
  {
    id: 4,
    resident: 'Ana Villanueva',
    purpose: 'Certificate of Indigency',
    date: 'June 8, 2026',
    time: '1:00 PM',
    notes: 'For scholarship application.',
    statusKey: 'cancelled',
  },
])

const appointmentsWithStatus = computed(() =>
  appointments.value.map(a => ({ ...a, status: statusMap[a.statusKey] }))
)

const filters = [
  { label: 'All',       value: 'all'       },
  { label: 'Pending',   value: 'pending'   },
  { label: 'Approved',  value: 'approved'  },
  { label: 'Completed', value: 'completed' },
  { label: 'Cancelled', value: 'cancelled' },
]

const filtered = computed(() => {
  let list = appointmentsWithStatus.value
  if (activeFilter.value !== 'all') {
    list = list.filter(a => a.statusKey === activeFilter.value)
  }
  if (search.value.trim()) {
    const q = search.value.toLowerCase()
    list = list.filter(a =>
      a.resident.toLowerCase().includes(q) ||
      a.purpose.toLowerCase().includes(q)
    )
  }
  return list
})

function updateStatus(item, newStatus) {
  item.statusKey = newStatus
  item.status    = statusMap[newStatus]
  toast.success('Appointment status updated!')
}
</script>