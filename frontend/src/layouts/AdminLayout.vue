<template>
  <div class="min-h-screen bg-primary-50 flex">

    <!-- Sidebar -->
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
        </div>
      </div>

      <!-- Nav -->
      <nav class="flex-1 px-3 py-4 space-y-0.5 overflow-y-auto">
        <p class="px-3 text-xs font-medium text-primary-light/30 uppercase tracking-wider mb-2">Overview</p>
        <SidebarLink to="/admin"            icon="dashboard"  label="Dashboard"      @click="sidebarOpen = false" />
        <SidebarLink to="/admin/users"      icon="users"      label="User Management"@click="sidebarOpen = false" />
        <SidebarLink
          v-if="authStore.user?.is_super_admin"
          to="/admin/staff"
          icon="users"
          label="Staff Accounts"
          @click="sidebarOpen = false"
        />
        <SidebarLink
          v-if="authStore.user?.is_super_admin"
          to="/admin/assist-logs"
          icon="file-text"
          label="Assist Logs"
          @click="sidebarOpen = false"
        />

        <p class="px-3 text-xs font-medium text-primary-light/30 uppercase tracking-wider mt-4 mb-2">Modules</p>
        <SidebarLink to="/admin/incidents"    icon="alert"    label="Incidents"       @click="sidebarOpen = false" />
        <SidebarLink to="/admin/appointments" icon="calendar" label="Appointments"    @click="sidebarOpen = false" />
        <SidebarLink to="/admin/alerts"       icon="bell"     label="Alerts"          @click="sidebarOpen = false" />

        <p class="px-3 text-xs font-medium text-primary-light/30 uppercase tracking-wider mt-4 mb-2">Data</p>
        <SidebarLink to="/admin/reports"    icon="file-text"  label="Reports & CSV"  @click="sidebarOpen = false" />
      </nav>

      <!-- Admin profile -->
      <div class="border-t border-white/10 p-4">
        <div class="flex items-center gap-3 mb-3">
          <div class="w-9 h-9 rounded-full bg-amber-500 flex items-center justify-center flex-shrink-0">
            <span class="text-white text-sm font-medium">{{ initials }}</span>
          </div>
          <div class="min-w-0">
            <p class="text-white text-sm font-medium truncate">{{ fullName }}</p>
            <span class="text-xs bg-amber-500/20 text-amber-300 px-2 py-0.5 rounded-full">
              {{ authStore.user?.is_super_admin ? 'Super Admin' : 'Admin' }}
            </span>
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

    <!-- Backdrop -->
    <div
      v-if="sidebarOpen"
      class="fixed inset-0 z-30 bg-black/50 lg:hidden"
      @click="sidebarOpen = false"
    />

    <!-- Main -->
    <div class="flex-1 lg:ml-64 min-w-0 flex flex-col min-h-screen">
      <!-- Topbar -->
      <header class="sticky top-0 z-20 bg-white/95 border-b border-primary/10 backdrop-blur h-16 flex items-center px-4 sm:px-6 gap-3">
        <button
          @click="sidebarOpen = true"
          class="lg:hidden p-2 rounded-lg hover:bg-gray-100 text-gray-500"
          aria-label="Open menu"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
          </svg>
        </button>
        <h1 class="font-display font-semibold text-primary-dark text-lg flex-1">
          {{ currentTitle }}
        </h1>
        <span class="badge-yellow text-xs hidden sm:flex">Admin Panel</span>
      </header>

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
import logo from '@/assets/barangay-manggahan-logo.png'

const authStore = useAuthStore()
const route     = useRoute()
const router    = useRouter()
const toast     = useToast()

const sidebarOpen = ref(false)
const currentTitle = computed(() => route.meta?.title || 'Admin')

const fullName = computed(() => {
  const u = authStore.user
  if (!u) return ''
  return `${u.first_name ?? ''} ${u.last_name ?? ''}`.trim()
})

const initials = computed(() => {
  const u = authStore.user
  if (!u) return '?'
  return `${u.first_name?.[0] ?? ''}${u.last_name?.[0] ?? ''}`.toUpperCase()
})

async function handleLogout() {
  await authStore.logout()
  toast.success('Logged out.')
  router.push({ name: 'login' })
}
</script>