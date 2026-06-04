<template>
  <div class="min-h-screen bg-gray-50 flex">

    <!-- ── Sidebar (desktop) ──────────────────────────────────────────────── -->
    <aside
      class="fixed inset-y-0 left-0 z-40 w-64 bg-primary-dark flex flex-col
             transition-transform duration-300 ease-in-out
             -translate-x-full lg:translate-x-0"
      :class="{ 'translate-x-0': sidebarOpen }"
    >
      <!-- Logo -->
      <div class="flex items-center gap-3 px-5 py-5 border-b border-white/10">
        <div class="w-9 h-9 rounded-xl bg-primary flex items-center justify-center flex-shrink-0">
          <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
            <path d="M3 9.5L12 3l9 6.5V20a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V9.5z"/>
          </svg>
        </div>
        <div>
          <p class="font-display font-semibold text-white text-sm leading-none">Brgy. Manggahan</p>
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
          <div class="w-9 h-9 rounded-full bg-primary flex items-center justify-center flex-shrink-0">
            <span class="text-white text-sm font-medium">{{ initials }}</span>
          </div>
          <div class="min-w-0">
            <p class="text-white text-sm font-medium truncate">{{ authStore.user?.name }}</p>
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

    <!-- ── Main area ──────────────────────────────────────────────────────── -->
    <div class="flex-1 lg:ml-64 flex flex-col min-h-screen">

      <!-- Topbar -->
      <header class="sticky top-0 z-20 bg-white border-b border-gray-100 h-16 flex items-center px-4 sm:px-6 gap-3">
        <!-- Hamburger (mobile) -->
        <button
          @click="sidebarOpen = true"
          class="lg:hidden p-2 rounded-lg hover:bg-gray-100 text-gray-500"
          aria-label="Open menu"
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
        <button class="relative p-2 rounded-lg hover:bg-gray-100 text-gray-500">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
          </svg>
          <span class="absolute top-1.5 right-1.5 w-2 h-2 rounded-full bg-red-500" />
        </button>
      </header>

      <!-- Page content -->
      <main class="flex-1">
        <RouterView />
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { RouterView, useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useToast } from 'vue-toastification'
import SidebarLink from '@/components/layout/SidebarLink.vue'

const authStore = useAuthStore()
const route     = useRoute()
const router    = useRouter()
const toast     = useToast()

const sidebarOpen = ref(false)

const initials = computed(() => {
  const name = authStore.user?.name || ''
  return name.split(' ').map(n => n[0]).slice(0, 2).join('').toUpperCase()
})

const currentTitle = computed(() => route.meta?.title || 'Portal')

const navItems = [
  { name: 'dashboard',    to: '/portal',             icon: 'home',     label: 'Dashboard'    },
  { name: 'incidents',    to: '/portal/incidents',   icon: 'alert',    label: 'Incidents'    },
  { name: 'appointments', to: '/portal/appointments',icon: 'calendar', label: 'Appointments' },
  { name: 'alerts',       to: '/portal/alerts',      icon: 'bell',     label: 'Alerts'       },
  { name: 'waste',        to: '/portal/waste',       icon: 'recycle',  label: 'Waste'        },
  { name: 'profile',      to: '/portal/profile',     icon: 'user',     label: 'My Profile'   },
]

async function handleLogout() {
  await authStore.logout()
  toast.success('Logged out successfully.')
  router.push({ name: 'login' })
}
</script>
