<template>
  <div class="min-h-screen flex flex-col items-center justify-center px-4 text-center gap-4 bg-primary-light">
    <p class="font-display text-8xl font-semibold text-primary/20">404</p>
    <h2 class="font-display text-2xl font-semibold text-primary-dark -mt-4">Page not found</h2>
    <p class="text-sm text-gray-500">The page you're looking for doesn't exist or has been moved.</p>
    <RouterLink to="/" class="btn-primary">Go to Home</RouterLink>
  </div>
</template>
<script setup>
import { RouterLink } from 'vue-router'
</script>
