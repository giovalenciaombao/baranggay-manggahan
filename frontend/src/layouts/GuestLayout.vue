<template>
  <div class="min-h-screen bg-primary-50 flex flex-col">
    <!-- Navbar -->
    <nav class="bg-white/95 backdrop-blur border-b border-primary/10 sticky top-0 z-50">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between gap-2 h-[4.5rem]">
          <!-- Logo -->
          <RouterLink to="/" class="flex items-center gap-3" aria-label="Barangay Manggahan home">
            <img
              :src="logo"
              alt=""
              width="48"
              height="48"
              class="h-12 w-12 shrink-0 rounded-lg object-contain"
            />
            <div class="min-w-0">
              <p class="font-display font-semibold text-primary-dark text-xs leading-tight sm:text-base">Barangay Manggahan</p>
              <p class="hidden text-xs text-gray-600 sm:block">Resident Services Portal</p>
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
            <img :src="logo" alt="" width="32" height="32" class="h-8 w-8 rounded-full bg-white" />
            <span class="text-sm font-display">Brgy. Manggahan</span>
          </div>
          <p class="text-xs text-primary-light/75">
            © {{ year }} Barangay Manggahan. All rights reserved.
          </p>
        </div>
      </div>
    </footer>
  </div>
</template>

<script setup>
import { RouterLink, RouterView } from 'vue-router'

import logo from '../assets/barangay-manggahan-logo.png'

const year = new Date().getFullYear()
</script>
