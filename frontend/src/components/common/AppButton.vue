<template>
  <button
    :type="type"
    :disabled="disabled || loading"
    :class="[variantClass, sizeClass, 'btn']"
    v-bind="$attrs"
  >
    <svg v-if="loading" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
    </svg>
    <slot v-else-if="$slots.icon" name="icon" />
    <slot />
  </button>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  variant:  { type: String, default: 'primary' },  // primary | secondary | ghost | danger
  size:     { type: String, default: 'md' },        // sm | md | lg
  type:     { type: String, default: 'button' },
  loading:  { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
})

const variantClass = computed(() => ({
  primary:   'btn-primary',
  secondary: 'btn-secondary',
  ghost:     'btn-ghost',
  danger:    'btn-danger',
}[props.variant] || 'btn-primary'))

const sizeClass = computed(() => ({
  sm: 'text-xs px-3 py-1.5',
  md: 'text-sm px-4 py-2.5',
  lg: 'text-base px-5 py-3',
}[props.size]))
</script>
