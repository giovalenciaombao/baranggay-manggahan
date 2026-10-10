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
              <AppointmentStatusBadge :status="item.status" class="flex-shrink-0" />
            </div>
            <p class="text-xs text-gray-400 mt-0.5">{{ item.resident }}</p>

            <!-- Current schedule -->
            <p class="text-xs text-gray-500 mt-0.5 flex items-center gap-1">
              <ClockIcon class="w-3.5 h-3.5" />
              {{ formatDate(item.date) }} · {{ item.time }}
            </p>

            <!-- Original schedule (kung na-resched) -->
            <p
              v-if="item.original_date"
              class="text-xs text-gray-400 mt-0.5 line-through"
            >
              Requested: {{ formatDate(item.original_date) }} · {{ item.original_time }}
            </p>
          </div>
        </div>

        <!-- Resident notes -->
        <p v-if="item.notes" class="text-xs text-gray-400 pl-[52px] italic">
          "{{ item.notes }}"
        </p>

        <!-- Admin note -->
        <p
          v-if="item.admin_note"
          class="text-xs text-blue-700 bg-blue-50 rounded-lg px-3 py-2"
        >
          <span class="font-medium">Admin note:</span> {{ item.admin_note }}
        </p>

        <!-- Actions -->
        <div class="flex flex-wrap items-center gap-2 pt-1 border-t border-gray-100">
          <template v-if="canModify(item)">
            <button
              v-if="item.status === 'pending'"
              @click="updateStatus(item, 'approved')"
              class="flex-1 min-w-[90px] flex items-center justify-center gap-1.5 py-1.5 rounded-lg bg-green-50 hover:bg-green-100 text-xs font-medium text-green-700 transition-colors"
            >
              <CheckIcon class="w-3.5 h-3.5" />
              Approve
            </button>

            <button
              v-if="item.status === 'approved' || item.status === 'rescheduled'"
              @click="updateStatus(item, 'completed')"
              class="flex-1 min-w-[90px] flex items-center justify-center gap-1.5 py-1.5 rounded-lg bg-gray-100 hover:bg-gray-200 text-xs font-medium text-gray-700 transition-colors"
            >
              <CheckIcon class="w-3.5 h-3.5" />
              Complete
            </button>

            <button
              @click="openModal(item, 'rescheduled')"
              class="flex-1 min-w-[90px] flex items-center justify-center gap-1.5 py-1.5 rounded-lg bg-blue-50 hover:bg-blue-100 text-xs font-medium text-blue-700 transition-colors"
            >
              <ArrowPathIcon class="w-3.5 h-3.5" />
              Reschedule
            </button>

            <button
              @click="openModal(item, 'cancelled')"
              class="flex-1 min-w-[90px] flex items-center justify-center gap-1.5 py-1.5 rounded-lg bg-red-50 hover:bg-red-100 text-xs font-medium text-red-600 transition-colors"
            >
              <XMarkIcon class="w-3.5 h-3.5" />
              Cancel
            </button>
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

    <!-- Reschedule / Cancel modal -->
    <AppointmentActionModal
      :open="actionModal.open"
      :appointment="actionModal.appointment"
      :action="actionModal.action"
      @close="actionModal.open = false"
      @updated="onUpdated"
    />

    <!-- Detail modal -->
    <Teleport to="body">
      <div
        v-if="showModal && selectedItem"
        class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 px-4"
        @click.self="showModal = false"
      >
        <div class="bg-white rounded-2xl p-6 w-full max-w-md space-y-4 shadow-xl max-h-[90vh] overflow-y-auto">
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
              <p class="text-xs text-gray-400 mb-0.5">
                {{ selectedItem.original_date ? 'Current Schedule' : 'Date & Time' }}
              </p>
              <p class="text-gray-700">
                {{ formatDate(selectedItem.date) }} · {{ selectedItem.time }}
              </p>
            </div>
            <div v-if="selectedItem.original_date">
              <p class="text-xs text-gray-400 mb-0.5">Originally Requested</p>
              <p class="text-gray-500 line-through">
                {{ formatDate(selectedItem.original_date) }} · {{ selectedItem.original_time }}
              </p>
            </div>
            <div v-if="selectedItem.notes">
              <p class="text-xs text-gray-400 mb-0.5">Resident Notes</p>
              <p class="text-gray-700 italic">"{{ selectedItem.notes }}"</p>
            </div>
            <div v-if="selectedItem.admin_note">
              <p class="text-xs text-gray-400 mb-0.5">Admin Note</p>
              <p class="text-gray-700">{{ selectedItem.admin_note }}</p>
            </div>
            <div class="flex items-center gap-2">
              <p class="text-xs text-gray-400">Status</p>
              <AppointmentStatusBadge :status="selectedItem.status" />
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
import { ref, reactive, computed, onMounted } from 'vue'
import { useToast } from 'vue-toastification'
import {
  MagnifyingGlassIcon,
  CalendarDaysIcon,
  ClockIcon,
  CheckIcon,
  XMarkIcon,
  ArrowPathIcon,
} from '@heroicons/vue/24/outline'
import api from '@/services/api'
import AppointmentStatusBadge from '@/components/appointments/AppointmentStatusBadge.vue'
import AppointmentActionModal from '@/components/appointments/AppointmentActionModal.vue'

const toast        = useToast()
const activeFilter = ref('all')
const search       = ref('')
const loading      = ref(true)
const showModal    = ref(false)
const selectedItem = ref(null)
const appointments = ref([])

const actionModal = reactive({ open: false, appointment: null, action: 'rescheduled' })

const filters = [
  { label: 'All',         value: 'all'         },
  { label: 'Pending',     value: 'pending'     },
  { label: 'Approved',    value: 'approved'    },
  { label: 'Rescheduled', value: 'rescheduled' },
  { label: 'Completed',   value: 'completed'   },
  { label: 'Cancelled',   value: 'cancelled'   },
]

const appointmentsWithMeta = computed(() =>
  appointments.value.map(a => ({
    ...a,
    resident: a.user ? `${a.user.first_name} ${a.user.last_name}` : '—',
  }))
)

const filtered = computed(() => {
  let list = appointmentsWithMeta.value
  if (activeFilter.value !== 'all') {
    list = list.filter(a => a.status === activeFilter.value)
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

function canModify(item) {
  return !['cancelled', 'completed'].includes(item.status)
}

function formatDate(d) {
  if (!d) return ''
  return new Date(String(d).slice(0, 10) + 'T00:00:00').toLocaleDateString('en-PH', {
    month: 'short', day: 'numeric', year: 'numeric',
  })
}

function openModal(item, action) {
  actionModal.appointment = item
  actionModal.action      = action
  actionModal.open        = true
}

function onUpdated(updated) {
  const original = appointments.value.find(a => a.id === updated.id)
  if (original) Object.assign(original, updated)

  if (selectedItem.value?.id === updated.id) {
    Object.assign(selectedItem.value, updated)
  }
}

async function updateStatus(item, newStatus) {
  try {
    const { data } = await api.put(`/appointments/${item.id}`, { status: newStatus })
    onUpdated(data.appointment)
    toast.success('Appointment status updated!')
  } catch (err) {
    toast.error(err.response?.data?.message || 'Failed to update status.')
  }
}

onMounted(async () => {
  try {
    const { data } = await api.get('/admin/appointments')
    appointments.value = data.appointments
  } catch (err) {
    toast.error(err.response?.data?.message || 'Failed to load appointments.')
  } finally {
    loading.value = false
  }
})
</script>