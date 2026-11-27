<template>
  <div class="min-h-screen bg-primary-50 flex">

    <!-- Sidebar (desktop) -->
    <aside
      class="fixed inset-y-0 left-0 z-40 w-64 barangay-sidebar flex flex-col
             transition-transform duration-300 ease-in-out
             -translate-x-full lg:translate-x-0"
      :class="{ 'translate-x-0': sidebarOpen }"
    >
      <!-- Logo -->
      <div class="flex items-center gap-3 px-5 py-5 border-b border-white/10">
        <img :src="logo" alt="" width="44" height="44" class="h-11 w-11 rounded-full bg-white" />
        <div>
          <p class="font-display font-semibold text-white text-sm leading-none">Barangay Manggahan</p>
          <p class="text-xs text-primary-light/50 mt-0.5 leading-none">Resident Portal</p>
        </div>
      </div>

      <!-- Nav items -->
      <nav class="flex-1 px-3 py-4 space-y-0.5 overflow-y-auto">
        <SidebarLink
          v-for="item in navItems"
          :key="item.name"
          :to="item.to"
          :icon="item.icon"
          :label="item.label"
          @click="sidebarOpen = false"
        />
      </nav>

      <!-- User info + logout -->
      <div class="border-t border-white/10 p-4">
        <div class="flex items-center gap-3 mb-3">
          <div class="w-9 h-9 rounded-full bg-primary flex items-center justify-center flex-shrink-0 overflow-hidden">
            <img v-if="authStore.profilePhotoUrl" :src="authStore.profilePhotoUrl" alt="" class="h-full w-full object-cover" />
            <span v-else class="text-white text-sm font-medium">{{ initials }}</span>
          </div>
          <div class="min-w-0">
            <p class="text-white text-sm font-medium truncate">{{ fullName }}</p>
            <p class="text-primary-light/50 text-xs truncate">{{ authStore.user?.email }}</p>
          </div>
        </div>
        <button @click="handleLogout" class="w-full btn-ghost text-primary-light/70 hover:text-white text-sm justify-start px-2">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
          </svg>
          Logout
        </button>
      </div>
    </aside>

    <!-- Sidebar backdrop (mobile) -->
    <div
      v-if="sidebarOpen"
      class="fixed inset-0 z-30 bg-black/50 lg:hidden"
      @click="sidebarOpen = false"
    />

    <!-- Main area -->
    <div class="flex-1 lg:ml-64 min-w-0 flex flex-col min-h-screen">

      <!-- Topbar -->
      <header class="sticky top-0 z-20 bg-white/95 border-b border-primary/10 backdrop-blur h-16 flex items-center px-4 sm:px-6 gap-3">
        <!-- Hamburger (mobile) -->
        <button
          @click="sidebarOpen = true"
          class="lg:hidden p-2 rounded-lg hover:bg-gray-100 text-gray-500"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
          </svg>
        </button>

        <!-- Page title -->
        <h1 class="font-display font-semibold text-primary-dark text-lg flex-1">
          {{ currentTitle }}
        </h1>

        <!-- Notification bell -->
        <div class="relative" ref="bellRef">
          <button
            @click="toggleNotifications"
            class="relative p-2 rounded-lg hover:bg-gray-100 text-gray-500"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
            </svg>
            <span
              v-if="unreadCount > 0"
              class="absolute -top-0.5 -right-0.5 w-4 h-4 rounded-full bg-red-500 text-white text-[10px] font-bold flex items-center justify-center"
            >
              {{ unreadCount > 9 ? '9+' : unreadCount }}
            </span>
          </button>

          <!-- Notification dropdown -->
          <div
            v-if="showNotifications"
            class="absolute right-0 top-12 w-80 bg-white rounded-2xl shadow-xl border border-gray-100 z-50 overflow-hidden"
          >
            <!-- Header -->
            <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
              <p class="text-sm font-semibold text-primary-dark">Notifications</p>
              <button
                v-if="unreadCount > 0"
                @click="markAllAsRead"
                class="text-xs text-primary hover:underline"
              >
                Mark all as read
              </button>
            </div>

            <!-- List -->
            <div class="max-h-80 overflow-y-auto divide-y divide-gray-50">
              <div
                v-if="notifications.length === 0"
                class="px-4 py-8 text-center text-gray-400 text-sm"
              >
                No notifications yet.
              </div>
              <div
                v-for="notif in notifications"
                :key="notif.id"
                @click="markAsRead(notif)"
                :class="[
                  'px-4 py-3 cursor-pointer hover:bg-gray-50 transition-colors',
                  !notif.read_at ? 'bg-primary-light/40' : ''
                ]"
              >
                <div class="flex items-start gap-3">
                  <!-- Icon -->
                  <div :class="['w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0', typeConfig[notif.type]?.bg ?? 'bg-gray-100']">
                    <component :is="typeConfig[notif.type]?.icon" :class="['w-4 h-4', typeConfig[notif.type]?.color ?? 'text-gray-500']" />
                  </div>
                  <div class="flex-1 min-w-0">
                    <p class="text-xs font-semibold text-gray-800">{{ notif.title }}</p>
                    <p class="text-xs text-gray-500 mt-0.5 leading-relaxed">{{ notif.message }}</p>
                    <p class="text-[10px] text-gray-400 mt-1">{{ formatDate(notif.created_at) }}</p>
                  </div>
                  <div v-if="!notif.read_at" class="w-2 h-2 rounded-full bg-primary flex-shrink-0 mt-1" />
                </div>
              </div>
            </div>

            <!-- Footer -->
            <div class="px-4 py-3 border-t border-gray-100 text-center">
              <button
                @click="router.push('/portal/alerts'); showNotifications = false"
                class="text-xs text-primary hover:underline"
              >
                View all alerts
              </button>
            </div>
          </div>
        </div>
      </header>

      <!-- Page content -->
      <main class="flex-1">
        <RouterView />
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { RouterView, useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useToast } from 'vue-toastification'
import {
  ExclamationCircleIcon,
  CalendarDaysIcon,
  TrashIcon,
  UserCircleIcon,
  BellAlertIcon,
} from '@heroicons/vue/24/outline'
import api from '@/services/api'
import SidebarLink from '@/components/layout/SidebarLink.vue'
import logo from '@/assets/barangay-manggahan-logo.png'

const authStore        = useAuthStore()
const route            = useRoute()
const router           = useRouter()
const toast            = useToast()
const sidebarOpen      = ref(false)
const showNotifications = ref(false)
const notifications    = ref([])
const unreadCount      = ref(0)
const bellRef          = ref(null)

const initials = computed(() => {
  const u = authStore.user
  if (!u) return '?'
  return `${u.first_name?.[0] ?? ''}${u.last_name?.[0] ?? ''}`.toUpperCase()
})

const fullName = computed(() => {
  const u = authStore.user
  if (!u) return ''
  return `${u.first_name ?? ''} ${u.last_name ?? ''}`.trim()
})

const currentTitle = computed(() => route.meta?.title || 'Portal')

const typeConfig = {
  incident:    { bg: 'bg-red-50',    color: 'text-red-500',    icon: ExclamationCircleIcon },
  appointment: { bg: 'bg-green-50',  color: 'text-green-600',  icon: CalendarDaysIcon      },
  waste:       { bg: 'bg-blue-50',   color: 'text-blue-500',   icon: TrashIcon             },
  user:        { bg: 'bg-yellow-50', color: 'text-yellow-500', icon: UserCircleIcon        },
  alert:       { bg: 'bg-red-50',    color: 'text-red-500',    icon: BellAlertIcon         },
}

const navItems = [
  { name: 'dashboard',    to: '/portal',              icon: 'home',     label: 'Dashboard'    },
  { name: 'incidents',    to: '/portal/incidents',    icon: 'alert',    label: 'Incidents'    },
  { name: 'appointments', to: '/portal/appointments', icon: 'calendar', label: 'Appointments' },
  { name: 'alerts',       to: '/portal/alerts',       icon: 'bell',     label: 'Alerts'       },
  { name: 'profile',      to: '/portal/profile',      icon: 'user',     label: 'My Profile'   },
]

function formatDate(date) {
  return new Date(date).toLocaleDateString('en-PH', {
    month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit'
  })
}

async function fetchNotifications() {
  try {
    const { data } = await api.get('/notifications')
    notifications.value = data.notifications
    unreadCount.value   = data.unread_count
  } catch (_) {}
}

function toggleNotifications() {
  showNotifications.value = !showNotifications.value
  if (showNotifications.value) fetchNotifications()
}

async function markAsRead(notif) {
  if (notif.read_at) return
  try {
    await api.put(`/notifications/${notif.id}/read`)
    notif.read_at   = new Date().toISOString()
    unreadCount.value = Math.max(0, unreadCount.value - 1)
  } catch (_) {}
}

async function markAllAsRead() {
  try {
    await api.put('/notifications/read-all')
    notifications.value.forEach(n => n.read_at = new Date().toISOString())
    unreadCount.value = 0
  } catch (_) {}
}

// Close dropdown when clicking outside
function handleClickOutside(e) {
  if (bellRef.value && !bellRef.value.contains(e.target)) {
    showNotifications.value = false
  }
}

async function handleLogout() {
  await authStore.logout()
  toast.success('Logged out successfully.')
  router.push({ name: 'login' })
}

onMounted(async () => {
  void authStore.loadProfilePhoto()
  await fetchNotifications()
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})
</script>