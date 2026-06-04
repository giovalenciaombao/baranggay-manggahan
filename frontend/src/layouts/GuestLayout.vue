<template>
  <div class="min-h-screen bg-primary-light flex flex-col">
    <!-- Navbar -->
    <nav class="bg-white border-b border-primary/10 sticky top-0 z-50">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
          <!-- Logo -->
          <RouterLink to="/" class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-primary flex items-center justify-center">
              <svg class="w-5 h-5 text-primary-light" fill="currentColor" viewBox="0 0 24 24">
                <path d="M3 9.5L12 3l9 6.5V20a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V9.5z"/>
              </svg>
            </div>
            <div class="hidden sm:block">
              <p class="font-display font-semibold text-primary-dark text-sm leading-none">Barangay Manggahan</p>
              <p class="text-xs text-gray-400 leading-none mt-0.5">Resident Services Portal</p>
            </div>
          </RouterLink>

          <!-- Nav links -->
          <div class="flex items-center gap-2">
            <RouterLink to="/login"
              class="btn-ghost text-sm px-3 py-2"
              active-class="text-primary font-medium">
              Login
            </RouterLink>
            <RouterLink to="/register" class="btn-primary text-sm">
              Register
            </RouterLink>
          </div>
        </div>
      </div>
    </nav>

    <!-- Page content -->
    <main class="flex-1">
      <RouterView />
    </main>

    <!-- Footer -->
    <footer class="bg-primary-dark text-primary-light/70 py-8">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col sm:flex-row items-center justify-between gap-3">
          <div class="flex items-center gap-2">
            <div class="w-6 h-6 rounded-lg bg-primary flex items-center justify-center">
              <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 24 24">
                <path d="M3 9.5L12 3l9 6.5V20a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V9.5z"/>
              </svg>
            </div>
            <span class="text-sm font-display">Brgy. Manggahan</span>
          </div>
          <p class="text-xs text-primary-light/40">
            © {{ year }} Barangay Manggahan. All rights reserved.
          </p>
        </div>
      </div>
    </footer>
  </div>
</template>

<script setup>
import { RouterLink, RouterView } from 'vue-router'
const year = new Date().getFullYear()
</script>
