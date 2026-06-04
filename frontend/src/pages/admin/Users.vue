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
          <p class="text-xs text-gray-300 mt-0.5">{{ user.address }}</p>
        </div>

        <!-- Status badge -->
        <AppBadge :variant="user.status.variant" class="flex-shrink-0 hidden sm:inline-flex">
          {{ user.status.label }}
        </AppBadge>

        <!-- Actions -->
        <div class="flex gap-1.5 flex-shrink-0">
          <template v-if="user.statusKey === 'pending'">
            <button
              @click="updateStatus(user.id, 'active')"
              class="p-2 rounded-lg bg-green-50 hover:bg-green-100 transition-colors"
              title="Approve"
            >
              <CheckIcon class="w-4 h-4 text-green-600" />
            </button>
            <button
              @click="updateStatus(user.id, 'rejected')"
              class="p-2 rounded-lg bg-red-50 hover:bg-red-100 transition-colors"
              title="Reject"
            >
              <XMarkIcon class="w-4 h-4 text-red-500" />
            </button>
          </template>
          <template v-else>
            <button
              @click="updateStatus(user.id, user.statusKey === 'active' ? 'suspended' : 'active')"
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

  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useToast } from 'vue-toastification'
import {
  MagnifyingGlassIcon,
  CheckIcon,
  XMarkIcon,
  NoSymbolIcon,
} from '@heroicons/vue/24/outline'
import AppBadge from '@/components/common/AppBadge.vue'

const toast        = useToast()
const activeFilter = ref('all')
const search       = ref('')

const statusMap = {
  active:    { variant: 'success', label: 'Active'    },
  pending:   { variant: 'warning', label: 'Pending'   },
  rejected:  { variant: 'danger',  label: 'Rejected'  },
  suspended: { variant: 'default', label: 'Suspended' },
}

const users = ref([
  { id: 1, name: 'Juan dela Cruz',   email: 'juan@email.com',   address: 'Purok 1, Manggahan', statusKey: 'active'    },
  { id: 2, name: 'Maria Santos',     email: 'maria@email.com',  address: 'Purok 2, Manggahan', statusKey: 'pending'   },
  { id: 3, name: 'Pedro Reyes',      email: 'pedro@email.com',  address: 'Purok 3, Manggahan', statusKey: 'pending'   },
  { id: 4, name: 'Ana Villanueva',   email: 'ana@email.com',    address: 'Purok 4, Manggahan', statusKey: 'active'    },
  { id: 5, name: 'Jose Mercado',     email: 'jose@email.com',   address: 'Purok 5, Manggahan', statusKey: 'suspended' },
  { id: 6, name: 'Liza Chua',        email: 'liza@email.com',   address: 'Purok 1, Manggahan', statusKey: 'rejected'  },
])

const usersWithStatus = computed(() =>
  users.value.map(u => ({ ...u, status: statusMap[u.statusKey] }))
)

const filters = computed(() => [
  { label: 'All',       value: 'all',       count: users.value.length },
  { label: 'Pending',   value: 'pending',   count: users.value.filter(u => u.statusKey === 'pending').length   || null },
  { label: 'Active',    value: 'active',    count: null },
  { label: 'Suspended', value: 'suspended', count: null },
  { label: 'Rejected',  value: 'rejected',  count: null },
])

const filtered = computed(() => {
  let list = usersWithStatus.value
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

function initials(name) {
  return name.split(' ').map(n => n[0]).join('').slice(0, 2).toUpperCase()
}

function updateStatus(id, newStatus) {
  const user = users.value.find(u => u.id === id)
  if (!user) return
  user.statusKey = newStatus
  const messages = {
    active:    'User approved!',
    rejected:  'User rejected.',
    suspended: 'User suspended.',
  }
  toast.success(messages[newStatus] ?? 'Status updated.')
}
</script>