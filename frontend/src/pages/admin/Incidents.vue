<template>
  <div class="p-4 sm:p-6 space-y-5">

    <!-- Header -->
    <div>
      <p class="text-sm text-gray-400 mt-0.5">Review and manage all filed incidents.</p>
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
        placeholder="Search by title or resident..."
        class="w-full pl-9 pr-4 py-2.5 border border-gray-200 rounded-xl text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-primary"
      />
    </div>

    <!-- Empty -->
    <div v-if="filtered.length === 0" class="card rounded-2xl p-10 text-center text-gray-400 text-sm">
      No incidents found.
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
          <div class="w-10 h-10 rounded-xl bg-red-50 flex items-center justify-center flex-shrink-0">
            <ExclamationCircleIcon class="w-5 h-5 text-red-400" />
          </div>
          <div class="flex-1 min-w-0">
            <div class="flex items-center justify-between gap-2">
              <p class="text-sm font-semibold text-gray-800 truncate">{{ item.title }}</p>
              <AppBadge :variant="item.status.variant" class="flex-shrink-0">
                {{ item.status.label }}
              </AppBadge>
            </div>
            <p class="text-xs text-gray-400 mt-0.5">{{ item.resident }} · {{ item.date }}</p>
            <p class="text-xs text-gray-500 mt-1 line-clamp-2">{{ item.description }}</p>
          </div>
        </div>

        <!-- Meta row -->
        <div class="flex items-center gap-4 text-xs text-gray-400 pl-13">
          <span class="flex items-center gap-1">
            <TagIcon class="w-3.5 h-3.5" />
            {{ item.category }}
          </span>
          <span class="flex items-center gap-1">
            <MapPinIcon class="w-3.5 h-3.5" />
            {{ item.location }}
          </span>
        </div>

        <!-- Actions -->
        <div class="flex items-center gap-2 pt-1 border-t border-gray-100">
          <select
            v-model="item.statusKey"
            @change="updateStatus(item)"
            class="flex-1 border border-gray-200 rounded-lg px-3 py-1.5 text-xs text-gray-700 focus:outline-none focus:ring-2 focus:ring-primary"
          >
            <option value="pending">Pending</option>
            <option value="review">Under Review</option>
            <option value="resolved">Resolved</option>
            <option value="dismissed">Dismissed</option>
          </select>
          <button
            @click="selectedItem = item; showModal = true"
            class="px-3 py-1.5 rounded-lg border border-gray-200 text-xs text-gray-600 hover:bg-gray-50 transition-colors"
          >
            View Details
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
            <h3 class="text-base font-semibold text-primary-dark">Incident Details</h3>
            <button @click="showModal = false" class="text-gray-400 hover:text-gray-600">
              <XMarkIcon class="w-5 h-5" />
            </button>
          </div>

          <div class="space-y-3 text-sm">
            <div>
              <p class="text-xs text-gray-400 mb-0.5">Title</p>
              <p class="font-medium text-gray-800">{{ selectedItem.title }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-400 mb-0.5">Filed by</p>
              <p class="text-gray-700">{{ selectedItem.resident }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-400 mb-0.5">Category</p>
              <p class="text-gray-700">{{ selectedItem.category }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-400 mb-0.5">Location</p>
              <p class="text-gray-700">{{ selectedItem.location }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-400 mb-0.5">Description</p>
              <p class="text-gray-700 leading-relaxed">{{ selectedItem.description }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-400 mb-0.5">Date filed</p>
              <p class="text-gray-700">{{ selectedItem.date }}</p>
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
  ExclamationCircleIcon,
  TagIcon,
  MapPinIcon,
  XMarkIcon,
} from '@heroicons/vue/24/outline'
import AppBadge from '@/components/common/AppBadge.vue'

const toast        = useToast()
const activeFilter = ref('all')
const search       = ref('')
const showModal    = ref(false)
const selectedItem = ref(null)

const statusMap = {
  pending:   { variant: 'warning', label: 'Pending'      },
  review:    { variant: 'info',    label: 'Under Review' },
  resolved:  { variant: 'success', label: 'Resolved'     },
  dismissed: { variant: 'default', label: 'Dismissed'    },
}

const incidents = ref([
  {
    id: 1,
    title: 'Noise complaint — Purok 3',
    resident: 'Juan dela Cruz',
    category: 'Noise Complaint',
    location: 'Purok 3, near basketball court',
    description: 'Loud music every night past 10PM near the basketball court.',
    date: 'June 2, 2026',
    statusKey: 'pending',
  },
  {
    id: 2,
    title: 'Illegal dumping near creek',
    resident: 'Maria Santos',
    category: 'Illegal Dumping',
    location: 'Purok 5, near creek',
    description: 'Someone has been dumping garbage near the creek at Purok 5.',
    date: 'May 28, 2026',
    statusKey: 'review',
  },
  {
    id: 3,
    title: 'Broken streetlight',
    resident: 'Pedro Reyes',
    category: 'Broken Infrastructure',
    location: 'Corner Rizal St.',
    description: 'Streetlight at corner of Rizal St. has been out for 2 weeks.',
    date: 'May 20, 2026',
    statusKey: 'resolved',
  },
])

const incidentsWithStatus = computed(() =>
  incidents.value.map(i => ({ ...i, status: statusMap[i.statusKey] }))
)

const filters = [
  { label: 'All',          value: 'all'       },
  { label: 'Pending',      value: 'pending'   },
  { label: 'Under Review', value: 'review'    },
  { label: 'Resolved',     value: 'resolved'  },
  { label: 'Dismissed',    value: 'dismissed' },
]

const filtered = computed(() => {
  let list = incidentsWithStatus.value
  if (activeFilter.value !== 'all') {
    list = list.filter(i => i.statusKey === activeFilter.value)
  }
  if (search.value.trim()) {
    const q = search.value.toLowerCase()
    list = list.filter(i =>
      i.title.toLowerCase().includes(q) ||
      i.resident.toLowerCase().includes(q)
    )
  }
  return list
})

function updateStatus(item) {
  item.status = statusMap[item.statusKey]
  toast.success('Status updated!')
}
</script>