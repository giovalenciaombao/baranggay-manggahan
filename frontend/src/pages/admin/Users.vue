<template>
  <div class="p-4 sm:p-6 space-y-5">

    <!-- Header -->
    <div>
      <p class="text-sm text-gray-400 mt-0.5">Manage resident accounts and approvals.</p>
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
        <span
          v-if="f.count"
          :class="[
            'ml-1.5 px-1.5 py-0.5 rounded-full text-xs',
            activeFilter === f.value ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-500'
          ]"
        >{{ f.count }}</span>
      </button>
    </div>

    <!-- Search -->
    <div class="relative">
      <MagnifyingGlassIcon class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" />
      <input
        v-model="search"
        type="text"
        placeholder="Search by name or email..."
        class="w-full pl-9 pr-4 py-2.5 border border-gray-200 rounded-xl text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-primary"
      />
    </div>

    <!-- Empty -->
    <div v-if="filtered.length === 0" class="card rounded-2xl p-10 text-center text-gray-400 text-sm">
      No users found.
    </div>

    <!-- List -->
    <div v-else class="space-y-3">
      <div
        v-for="user in filtered"
        :key="user.id"
        class="card rounded-2xl p-4 flex items-center gap-4"
      >
        <!-- Avatar -->
        <div class="w-10 h-10 rounded-full bg-primary-light flex items-center justify-center flex-shrink-0">
          <span class="text-sm font-semibold text-primary-dark">{{ initials(user.name) }}</span>
        </div>

        <!-- Info -->
        <div class="flex-1 min-w-0">
          <p class="text-sm font-semibold text-gray-800 truncate">{{ user.name }}</p>
          <p class="text-xs text-gray-400 truncate">{{ user.email }}</p>
          <p class="text-xs text-gray-300 mt-0.5 truncate">{{ user.address }}</p>
          <p
            class="text-xs mt-0.5"
            :class="user.has_proof ? 'text-primary' : 'text-red-400'"
          >
            {{ user.has_proof ? proofLabel(user.proof_type) : 'No proof of residency' }}
          </p>
        </div>

        <!-- Status badge -->
        <AppBadge :variant="user.status.variant" class="flex-shrink-0 hidden sm:inline-flex">
          {{ user.status.label }}
        </AppBadge>

        <!-- Actions -->
        <div class="flex gap-1.5 flex-shrink-0">
          <!-- Pending: must open the review (para makita muna ang proof) -->
          <template v-if="user.statusKey === 'pending'">
            <button
              @click="openReview(user)"
              class="px-3 py-2 rounded-lg bg-primary text-white text-xs font-medium hover:opacity-90 transition-opacity"
            >
              Review
            </button>
          </template>

          <!-- Others: view proof + suspend / reactivate -->
          <template v-else>
            <button
              v-if="user.has_proof"
              @click="openReview(user)"
              class="p-2 rounded-lg bg-gray-50 hover:bg-gray-100 transition-colors"
              title="View proof"
            >
              <EyeIcon class="w-4 h-4 text-gray-500" />
            </button>
            <button
              @click="quickStatus(user.id, user.statusKey === 'active' ? 'suspended' : 'active')"
              :class="[
                'p-2 rounded-lg transition-colors',
                user.statusKey === 'active'
                  ? 'bg-yellow-50 hover:bg-yellow-100'
                  : 'bg-green-50 hover:bg-green-100'
              ]"
              :title="user.statusKey === 'active' ? 'Suspend' : 'Reactivate'"
            >
              <NoSymbolIcon v-if="user.statusKey === 'active'" class="w-4 h-4 text-yellow-500" />
              <CheckIcon v-else class="w-4 h-4 text-green-600" />
            </button>
          </template>
        </div>
      </div>
    </div>

    <!-- Review modal -->
    <div
      v-if="selectedUser"
      class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/50 sm:p-4"
      @click.self="closeReview"
    >
      <div class="bg-white w-full sm:max-w-lg rounded-t-2xl sm:rounded-2xl max-h-[92vh] overflow-y-auto">

        <!-- Modal header -->
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 sticky top-0 bg-white">
          <div>
            <h3 class="text-base font-semibold text-gray-800">
              {{ isPending ? 'Review registration' : 'Resident details' }}
            </h3>
            <p class="text-xs text-gray-400">{{ selectedUser.name }}</p>
          </div>
          <button @click="closeReview" class="p-2 rounded-lg hover:bg-gray-100">
            <XMarkIcon class="w-5 h-5 text-gray-500" />
          </button>
        </div>

        <div class="p-5 space-y-5">

          <!-- Details -->
          <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-3 text-sm">
            <div>
              <dt class="text-xs text-gray-400">Email</dt>
              <dd class="text-gray-700 break-all">{{ selectedUser.email }}</dd>
            </div>
            <div>
              <dt class="text-xs text-gray-400">Contact number</dt>
              <dd class="text-gray-700">{{ selectedUser.contact_number || '—' }}</dd>
            </div>
            <div class="sm:col-span-2">
              <dt class="text-xs text-gray-400">Home address</dt>
              <dd class="text-gray-700">{{ selectedUser.address || '—' }}</dd>
            </div>
            <div>
              <dt class="text-xs text-gray-400">Document type</dt>
              <dd class="text-gray-700">{{ proofLabel(selectedUser.proof_type) }}</dd>
            </div>
            <div>
              <dt class="text-xs text-gray-400">Submitted</dt>
              <dd class="text-gray-700">{{ formatDate(selectedUser.proof_submitted_at) }}</dd>
            </div>
          </dl>

          <!-- Proof -->
          <div>
            <p class="text-xs text-gray-400 mb-2">Proof of residency</p>

            <div v-if="!selectedUser.has_proof" class="bg-red-50 border border-red-200 rounded-xl px-4 py-3 text-sm text-red-700">
              This resident has not submitted proof of residency yet.
            </div>

            <div v-else-if="proofLoading" class="border border-gray-200 rounded-xl py-10 text-center text-sm text-gray-400">
              Loading proof...
            </div>

            <div v-else-if="proofError" class="bg-red-50 border border-red-200 rounded-xl px-4 py-3 text-sm text-red-700">
              {{ proofError }}
            </div>

            <div v-else-if="proofUrl" class="space-y-2">
              <img
                v-if="proofMime.startsWith('image/')"
                :src="proofUrl"
                alt="Proof of residency"
                class="w-full max-h-96 object-contain rounded-xl border border-gray-200 bg-gray-50"
              />
              <iframe
                v-else
                :src="proofUrl"
                class="w-full h-96 rounded-xl border border-gray-200"
              ></iframe>
              <a
                :href="proofUrl"
                target="_blank"
                rel="noopener"
                class="inline-block text-xs text-primary font-medium hover:underline"
              >
                Open in new tab
              </a>
            </div>
          </div>

          <!-- Previous rejection reason -->
          <div
            v-if="selectedUser.statusKey === 'rejected' && selectedUser.rejection_reason"
            class="bg-red-50 border border-red-200 rounded-xl px-4 py-3"
          >
            <p class="text-xs text-red-500 mb-0.5">Rejection reason</p>
            <p class="text-sm text-red-700">{{ selectedUser.rejection_reason }}</p>
          </div>

          <!-- Pending actions -->
          <div v-if="isPending" class="space-y-3">
            <div v-if="rejecting" class="space-y-2">
              <label class="block text-sm font-medium text-gray-700">Reason for rejection</label>
              <textarea
                v-model="rejectReason"
                rows="3"
                placeholder="e.g. The uploaded ID is blurred / address is outside Barangay Manggahan"
                class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-primary"
              ></textarea>
              <p v-if="rejectError" class="text-xs text-red-500">{{ rejectError }}</p>
              <div class="flex gap-2">
                <button
                  @click="rejecting = false; rejectError = ''"
                  :disabled="saving"
                  class="flex-1 px-4 py-2.5 rounded-xl border border-gray-200 text-sm font-medium text-gray-600 hover:bg-gray-50 disabled:opacity-50"
                >
                  Back
                </button>
                <button
                  @click="confirmReject"
                  :disabled="saving"
                  class="flex-1 px-4 py-2.5 rounded-xl bg-red-500 text-white text-sm font-medium hover:bg-red-600 disabled:opacity-50"
                >
                  {{ saving ? 'Saving...' : 'Confirm reject' }}
                </button>
              </div>
            </div>

            <div v-else class="flex gap-2">
              <button
                @click="rejecting = true"
                :disabled="saving"
                class="flex-1 px-4 py-2.5 rounded-xl border border-red-200 bg-red-50 text-red-600 text-sm font-medium hover:bg-red-100 disabled:opacity-50"
              >
                Reject
              </button>
              <button
                @click="approve"
                :disabled="saving || !selectedUser.has_proof"
                class="flex-1 px-4 py-2.5 rounded-xl bg-primary text-white text-sm font-medium hover:opacity-90 disabled:opacity-50"
              >
                {{ saving ? 'Saving...' : 'Approve' }}
              </button>
            </div>
          </div>

        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { useToast } from 'vue-toastification'
import {
  MagnifyingGlassIcon,
  CheckIcon,
  XMarkIcon,
  NoSymbolIcon,
  EyeIcon,
} from '@heroicons/vue/24/outline'
import api from '@/services/api'
import AppBadge from '@/components/common/AppBadge.vue'

const toast        = useToast()
const activeFilter = ref('all')
const search       = ref('')
const loading      = ref(true)
const users        = ref([])

// Review modal state
const selectedId   = ref(null)
const proofUrl     = ref('')
const proofMime    = ref('')
const proofLoading = ref(false)
const proofError   = ref('')
const rejecting    = ref(false)
const rejectReason = ref('')
const rejectError  = ref('')
const saving       = ref(false)

const statusMap = {
  active:    { variant: 'success', label: 'Active'    },
  pending:   { variant: 'warning', label: 'Pending'   },
  rejected:  { variant: 'danger',  label: 'Rejected'  },
  suspended: { variant: 'default', label: 'Suspended' },
}

const proofLabels = {
  barangay_id:       'Barangay ID',
  billing_statement: 'Billing statement',
}

const filters = computed(() => [
  { label: 'All',       value: 'all',       count: users.value.length },
  { label: 'Pending',   value: 'pending',   count: users.value.filter(u => u.status === 'pending').length || null },
  { label: 'Active',    value: 'active',    count: null },
  { label: 'Suspended', value: 'suspended', count: null },
  { label: 'Rejected',  value: 'rejected',  count: null },
])

const usersWithMeta = computed(() =>
  users.value.map(u => ({
    ...u,
    name:      `${u.first_name} ${u.last_name}`,
    statusKey: u.status,
    status:    statusMap[u.status] ?? { variant: 'default', label: u.status },
  }))
)

const filtered = computed(() => {
  let list = usersWithMeta.value
  if (activeFilter.value !== 'all') {
    list = list.filter(u => u.statusKey === activeFilter.value)
  }
  if (search.value.trim()) {
    const q = search.value.toLowerCase()
    list = list.filter(u =>
      u.name.toLowerCase().includes(q) ||
      u.email.toLowerCase().includes(q)
    )
  }
  return list
})

const selectedUser = computed(() =>
  selectedId.value ? usersWithMeta.value.find(u => u.id === selectedId.value) ?? null : null
)

const isPending = computed(() => selectedUser.value?.statusKey === 'pending')

function initials(name) {
  return name.split(' ').map(n => n[0]).join('').slice(0, 2).toUpperCase()
}

function proofLabel(type) {
  return proofLabels[type] ?? '—'
}

function formatDate(value) {
  if (!value) return '—'
  return new Date(value).toLocaleString('en-PH', {
    year: 'numeric', month: 'short', day: 'numeric',
    hour: 'numeric', minute: '2-digit',
  })
}

// ─── Proof viewer ──────────────────────────────────────────
function revokeProof() {
  if (proofUrl.value) URL.revokeObjectURL(proofUrl.value)
  proofUrl.value  = ''
  proofMime.value = ''
}

async function loadProof(userId) {
  proofLoading.value = true
  proofError.value   = ''
  revokeProof()
  try {
    // Proof is on a private disk, so we fetch it with the admin token as a blob
    const res = await api.get(`/admin/users/${userId}/proof`, { responseType: 'blob' })
    if (selectedId.value !== userId) return // modal was closed/changed meanwhile
    proofMime.value = res.data.type || ''
    proofUrl.value  = URL.createObjectURL(res.data)
  } catch (_) {
    if (selectedId.value === userId) proofError.value = 'Could not load the proof file.'
  } finally {
    if (selectedId.value === userId) proofLoading.value = false
  }
}

function openReview(user) {
  selectedId.value   = user.id
  rejecting.value    = false
  rejectReason.value = ''
  rejectError.value  = ''
  proofError.value   = ''
  revokeProof()
  if (user.has_proof) loadProof(user.id)
}

function closeReview() {
  selectedId.value = null
  rejecting.value  = false
  revokeProof()
}

// ─── Status updates ────────────────────────────────────────
async function submitStatus(id, status, reason = null) {
  saving.value = true
  try {
    const payload = { status }
    if (reason) payload.rejection_reason = reason

    const { data } = await api.put(`/admin/users/${id}/status`, payload)

    const user = users.value.find(u => u.id === id)
    if (user) Object.assign(user, data.user)

    const messages = {
      active:    'User approved!',
      rejected:  'User rejected.',
      suspended: 'User suspended.',
    }
    toast.success(messages[status] ?? 'Status updated.')
    return true
  } catch (err) {
    toast.error(err.response?.data?.message || 'Failed to update status.')
    return false
  } finally {
    saving.value = false
  }
}

async function approve() {
  const ok = await submitStatus(selectedId.value, 'active')
  if (ok) closeReview()
}

async function confirmReject() {
  if (!rejectReason.value.trim()) {
    rejectError.value = 'Please give a reason for the rejection.'
    return
  }
  rejectError.value = ''
  const ok = await submitStatus(selectedId.value, 'rejected', rejectReason.value.trim())
  if (ok) closeReview()
}

// Suspend / reactivate (non-pending users)
async function quickStatus(id, newStatus) {
  await submitStatus(id, newStatus)
}

onMounted(async () => {
  try {
    const { data } = await api.get('/admin/users')
    users.value = data.users
  } finally {
    loading.value = false
  }
})

onBeforeUnmount(revokeProof)
</script>