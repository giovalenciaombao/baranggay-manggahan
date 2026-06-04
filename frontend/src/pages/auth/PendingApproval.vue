<template>
  <div class="min-h-[60vh] flex flex-col items-center justify-center px-4 text-center gap-4">
    <div class="w-16 h-16 rounded-2xl bg-yellow-50 flex items-center justify-center">
      <svg class="w-8 h-8 text-yellow-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
    </div>
    <h2 class="font-display text-xl font-semibold text-primary-dark">Account Pending Approval</h2>
    <p class="text-sm text-gray-500 max-w-sm">
      Your account is currently under review by the barangay admin. You'll receive an email once your account is approved.
    </p>
    <button @click="authStore.logout(); $router.push('/login')" class="btn-secondary text-sm">
      Back to Login
    </button>
  </div>
</template>
<script setup>
import { useAuthStore } from '@/stores/auth'
const authStore = useAuthStore()
</script>
