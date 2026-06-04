<template>
  <div class="min-h-[60vh] flex flex-col items-center justify-center px-4 text-center gap-4">
    <div class="w-16 h-16 rounded-2xl bg-primary-50 flex items-center justify-center">
      <svg class="w-8 h-8 text-primary" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 9v.906a2.25 2.25 0 01-1.183 1.981l-6.478 3.488M2.25 9v.906a2.25 2.25 0 001.183 1.981l6.478 3.488m8.839 2.51l-4.66-2.51m0 0l-1.023-.55a2.25 2.25 0 00-2.134 0l-1.022.55m0 0l-4.661 2.51m16.5 1.615a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V8.844a2.25 2.25 0 011.183-1.98l7.5-4.04a2.25 2.25 0 012.134 0l7.5 4.04a2.25 2.25 0 011.183 1.98V19.5z" />
      </svg>
    </div>
    <h2 class="font-display text-xl font-semibold text-primary-dark">Verify Your Email</h2>
    <p class="text-sm text-gray-500 max-w-sm">
      We've sent a verification link to your email address. Please check your inbox and click the link to continue.
    </p>
    <RouterLink to="/login" class="btn-secondary text-sm">Back to Login</RouterLink>
  </div>
</template>
<script setup>
import { RouterLink } from 'vue-router'
</script>
