<template>
  <div class="p-4 sm:p-6 space-y-5">

    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <p class="text-sm text-gray-400 mt-0.5">Post and manage barangay alerts.</p>
      </div>
      <AppButton variant="primary" size="sm" @click="openModal()">
        <PlusIcon class="w-4 h-4" />
        Post Alert
      </AppButton>
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
        :class="['card rounded-2xl p-4 space-y-3 border-l-4', item.borderColor]"
      >
        <div class="flex items-start gap-3">
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
            <p class="text-xs text-gray-300 mt-1.5">{{ item.date }}</p>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex gap-2 pt-1 border-t border-gray-100">
          <button
            @click="openModal(item)"
            class="flex-1 flex items-center justify-center gap-1.5 py-1.5 rounded-lg border border-gray-200 text-xs text-gray-600 hover:bg-gray-50 transition-colors"
          >
            <PencilIcon class="w-3.5 h-3.5" />
            Edit
          </button>
          <button
            @click="deleteAlert(item.id)"
            class="flex-1 flex items-center justify-center gap-1.5 py-1.5 rounded-lg bg-red-50 hover:bg-red-100 text-xs text-red-600 transition-colors"
          >
            <TrashIcon class="w-3.5 h-3.5" />
            Delete
          </button>
        </div>
      </div>
    </div>

    <!-- Create / Edit modal -->
    <Teleport to="body">
      <div
        v-if="showModal"
        class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 px-4"
        @click.self="closeModal"
      >
        <div class="bg-white rounded-2xl p-6 w-full max-w-md space-y-4 shadow-xl">
          <div class="flex items-center justify-between">
            <h3 class="text-base font-semibold text-primary-dark">
              {{ editingId ? 'Edit Alert' : 'Post New Alert' }}
            </h3>
            <button @click="closeModal" class="text-gray-400 hover:text-gray-600">
              <XMarkIcon class="w-5 h-5" />
            </button>
          </div>

          <!-- Type -->
          <div>
            <label class="block text-sm font-medium text-gray-600 mb-1">
              Type <span class="text-red-400">*</span>
            </label>
            <select
              v-model="form.type"
              class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-primary"
            >
              <option value="" disabled>Select type</option>
              <option value="emergency">Emergency</option>
              <option value="announcement">Announcement</option>
              <option value="maintenance">Maintenance</option>
            </select>
            <p v-if="errors.type" class="text-xs text-red-500 mt-1">{{ errors.type }}</p>
          </div>

          <!-- Title -->
          <AppInput
            v-model="form.title"
            label="Title"
            placeholder="e.g. Typhoon Advisory"
            :error="errors.title"
            required
          />

          <!-- Message -->
          <div>
            <label class="block text-sm font-medium text-gray-600 mb-1">
              Message <span class="text-red-400">*</span>
            </label>
            <textarea
              v-model="form.message"
              rows="3"
              placeholder="Alert details..."
              class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-primary resize-none"
            />
            <p v-if="errors.message" class="text-xs text-red-500 mt-1">{{ errors.message }}</p>
          </div>

          <div class="flex gap-2 pt-1">
            <AppButton variant="outline" class="flex-1" @click="closeModal">
              Cancel
            </AppButton>
            <AppButton variant="primary" class="flex-1" :loading="loading" @click="handleSubmit">
              {{ editingId ? 'Save Changes' : 'Post Alert' }}
            </AppButton>
          </div>
        </div>
      </div>
    </Teleport>

  </div>
</template>

<script setup>
import { ref, computed, reactive, onMounted } from 'vue'
import { useToast } from 'vue-toastification'
import {
  PlusIcon,
  PencilIcon,
  TrashIcon,
  XMarkIcon,
  ExclamationTriangleIcon,
  MegaphoneIcon,
  WrenchScrewdriverIcon,
} from '@heroicons/vue/24/outline'
import api from '@/services/api'
import { useFormDraft } from '@/composables/useFormDraft'
import AppButton from '@/components/common/AppButton.vue'
import AppBadge from '@/components/common/AppBadge.vue'
import AppInput from '@/components/common/AppInput.vue'

const toast        = useToast()
const activeFilter = ref('all')
const showModal    = ref(false)
const editingId    = ref(null)
const loading      = ref(false)
const fetching     = ref(true)
const alerts       = ref([])

const typeConfig = {
  emergency:    { icon: ExclamationTriangleIcon, iconBg: 'bg-red-50',    iconColor: 'text-red-500',    borderColor: 'border-red-400',    badge: { variant: 'danger',  label: 'Emergency'    } },
  announcement: { icon: MegaphoneIcon,           iconBg: 'bg-green-50',  iconColor: 'text-green-600',  borderColor: 'border-green-400',  badge: { variant: 'success', label: 'Announcement' } },
  maintenance:  { icon: WrenchScrewdriverIcon,   iconBg: 'bg-yellow-50', iconColor: 'text-yellow-500', borderColor: 'border-yellow-400', badge: { variant: 'warning', label: 'Maintenance'  } },
}

const filters = [
  { label: 'All',          value: 'all'          },
  { label: 'Emergency',    value: 'emergency'    },
  { label: 'Announcement', value: 'announcement' },
  { label: 'Maintenance',  value: 'maintenance'  },
]

const alertsWithConfig = computed(() =>
  alerts.value.map(a => ({
    ...a,
    ...(typeConfig[a.type] ?? typeConfig.announcement),
    date: new Date(a.created_at).toLocaleDateString('en-PH', {
      year: 'numeric', month: 'long', day: 'numeric'
    }),
  }))
)

const filtered = computed(() =>
  activeFilter.value === 'all'
    ? alertsWithConfig.value
    : alertsWithConfig.value.filter(a => a.type === activeFilter.value)
)

const { form, restoreDraft, clearDraft } = useFormDraft(null, { type: '', title: '', message: '' })
const errors = reactive({ type: '', title: '', message: '' })

function openModal(item = null) {
  editingId.value = item?.id ?? null
  restoreDraft('alert:' + (item?.id ?? 'new'), {
    type: item?.type ?? '',
    title: item?.title ?? '',
    message: item?.message ?? '',
  })
  errors.type    = ''
  errors.title   = ''
  errors.message = ''
  showModal.value = true
}

function closeModal() {
  showModal.value = false
}

function validate() {
  errors.type    = !form.type    ? 'Type is required.'    : ''
  errors.title   = !form.title   ? 'Title is required.'   : ''
  errors.message = !form.message ? 'Message is required.' : ''
  return Object.values(errors).every(e => !e)
}

async function handleSubmit() {
  if (!validate()) return
  loading.value = true
  try {
    if (editingId.value) {
      const { data } = await api.put(`/alerts/${editingId.value}`, form)
      const idx = alerts.value.findIndex(a => a.id === editingId.value)
      if (idx !== -1) alerts.value[idx] = data.alert
      toast.success('Alert updated!')
    } else {
      const { data } = await api.post('/alerts', form)
      alerts.value.unshift(data.alert)
      toast.success('Alert posted!')
    }
    clearDraft()
    closeModal()
  } catch (err) {
    toast.error(err.response?.data?.message || 'Failed to save alert.')
  } finally {
    loading.value = false
  }
}

async function deleteAlert(id) {
  try {
    await api.delete(`/alerts/${id}`)
    alerts.value = alerts.value.filter(a => a.id !== id)
    toast.success('Alert deleted.')
  } catch (err) {
    toast.error(err.response?.data?.message || 'Failed to delete alert.')
  }
}

onMounted(async () => {
  try {
    const { data } = await api.get('/alerts')
    alerts.value = data.alerts
  } finally {
    fetching.value = false
  }
})
</script>
