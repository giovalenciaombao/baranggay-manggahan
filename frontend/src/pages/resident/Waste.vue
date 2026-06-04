<template>
  <div class="p-4 sm:p-6 space-y-5">

    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <p class="text-sm text-gray-400 mt-0.5">Track your waste pickup schedule.</p>
      </div>
      <AppButton variant="primary" size="sm" @click="showModal = true">
        <PlusIcon class="w-4 h-4" />
        Register
      </AppButton>
    </div>

    <!-- Schedule cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
      <div
        v-for="sched in schedule"
        :key="sched.day"
        :class="['card rounded-2xl p-4 flex items-center gap-3 border-l-4', sched.borderColor]"
      >
        <div :class="['w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0', sched.iconBg]">
          <TrashIcon :class="['w-5 h-5', sched.iconColor]" />
        </div>
        <div>
          <p class="text-sm font-semibold text-gray-800">{{ sched.type }}</p>
          <p class="text-xs text-gray-400">Every {{ sched.day }}</p>
        </div>
      </div>
    </div>

    <!-- Collection history -->
    <div class="card rounded-2xl p-5">
      <h2 class="text-sm font-semibold text-primary-dark mb-4">Collection History</h2>

      <div v-if="history.length === 0" class="text-center py-8 text-gray-400 text-sm">
        No records yet.
      </div>

      <div v-else class="space-y-3">
        <div
          v-for="item in history"
          :key="item.id"
          class="flex items-center gap-3"
        >
          <div :class="['w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0', item.iconBg]">
            <TrashIcon :class="['w-4 h-4', item.iconColor]" />
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-sm text-gray-700 font-medium">{{ item.type }}</p>
            <p class="text-xs text-gray-400">{{ item.date }}</p>
          </div>
          <AppBadge :variant="item.status.variant">
            {{ item.status.label }}
          </AppBadge>
        </div>
      </div>
    </div>

    <!-- Register modal -->
    <Teleport to="body">
      <div
        v-if="showModal"
        class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 px-4"
        @click.self="showModal = false"
      >
        <div class="bg-white rounded-2xl p-6 w-full max-w-sm space-y-4 shadow-xl">
          <div class="flex items-center justify-between">
            <h3 class="text-base font-semibold text-primary-dark">Register Waste Pickup</h3>
            <button @click="showModal = false" class="text-gray-400 hover:text-gray-600">
              <XMarkIcon class="w-5 h-5" />
            </button>
          </div>

          <!-- Waste type -->
          <div>
            <label class="block text-sm font-medium text-gray-600 mb-1">
              Waste type <span class="text-red-400">*</span>
            </label>
            <select
              v-model="form.type"
              class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-primary"
            >
              <option value="" disabled>Select type</option>
              <option v-for="t in wasteTypes" :key="t" :value="t">{{ t }}</option>
            </select>
            <p v-if="errors.type" class="text-xs text-red-500 mt-1">{{ errors.type }}</p>
          </div>

          <!-- Pickup date -->
          <AppInput
            v-model="form.date"
            label="Preferred pickup date"
            type="date"
            :min="today"
            :error="errors.date"
            required
          />

          <!-- Notes -->
          <div>
            <label class="block text-sm font-medium text-gray-600 mb-1">Notes (optional)</label>
            <textarea
              v-model="form.notes"
              rows="2"
              placeholder="e.g. Large appliance disposal..."
              class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-primary resize-none"
            />
          </div>

          <div class="flex gap-2 pt-1">
            <AppButton variant="outline" class="flex-1" @click="showModal = false">
              Cancel
            </AppButton>
            <AppButton variant="primary" class="flex-1" :loading="loading" @click="handleSubmit">
              Submit
            </AppButton>
          </div>
        </div>
      </div>
    </Teleport>

  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useToast } from 'vue-toastification'
import { PlusIcon, TrashIcon, XMarkIcon } from '@heroicons/vue/24/outline'
import AppButton from '@/components/common/AppButton.vue'
import AppBadge from '@/components/common/AppBadge.vue'
import AppInput from '@/components/common/AppInput.vue'

const toast     = useToast()
const showModal = ref(false)
const loading   = ref(false)
const today     = new Date().toISOString().split('T')[0]

const schedule = [
  { type: 'Biodegradable',    day: 'Monday & Thursday',  iconBg: 'bg-green-50',  iconColor: 'text-green-600', borderColor: 'border-green-400' },
  { type: 'Non-Biodegradable', day: 'Tuesday & Friday',  iconBg: 'bg-blue-50',   iconColor: 'text-blue-500',  borderColor: 'border-blue-400'  },
  { type: 'Residual',         day: 'Wednesday & Saturday', iconBg: 'bg-gray-50', iconColor: 'text-gray-500',  borderColor: 'border-gray-400'  },
]

const history = ref([
  {
    id: 1,
    type: 'Biodegradable',
    date: 'June 2, 2026',
    iconBg: 'bg-green-50',
    iconColor: 'text-green-600',
    status: { variant: 'success', label: 'Collected' },
  },
  {
    id: 2,
    type: 'Non-Biodegradable',
    date: 'May 30, 2026',
    iconBg: 'bg-blue-50',
    iconColor: 'text-blue-500',
    status: { variant: 'success', label: 'Collected' },
  },
  {
    id: 3,
    type: 'Residual',
    date: 'May 28, 2026',
    iconBg: 'bg-gray-50',
    iconColor: 'text-gray-500',
    status: { variant: 'warning', label: 'Pending' },
  },
])

const wasteTypes = ['Biodegradable', 'Non-Biodegradable', 'Residual', 'Hazardous', 'Bulk / Large Items']

const form = reactive({ type: '', date: '', notes: '' })
const errors = reactive({ type: '', date: '' })

function validate() {
  errors.type = !form.type ? 'Waste type is required.' : ''
  errors.date = !form.date ? 'Date is required.'       : ''
  return Object.values(errors).every(e => !e)
}

async function handleSubmit() {
  if (!validate()) return
  loading.value = true
  try {
    await new Promise(r => setTimeout(r, 800))
    toast.success('Pickup request submitted!')
    history.value.unshift({
      id: Date.now(),
      type: form.type,
      date: new Date(form.date).toLocaleDateString('en-PH', { year: 'numeric', month: 'long', day: 'numeric' }),
      iconBg: 'bg-green-50',
      iconColor: 'text-green-600',
      status: { variant: 'warning', label: 'Pending' },
    })
    form.type  = ''
    form.date  = ''
    form.notes = ''
    showModal.value = false
  } finally {
    loading.value = false
  }
}
</script>