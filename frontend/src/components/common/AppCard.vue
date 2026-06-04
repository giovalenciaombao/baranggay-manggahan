<template>
  <div :class="['bg-white border border-gray-100 shadow-sm', radiusClass, paddingClass]">
    <div v-if="title || $slots.header" class="flex items-center justify-between mb-4">
      <slot name="header">
        <div>
          <h3 class="font-display font-semibold text-primary-dark text-base">{{ title }}</h3>
          <p v-if="subtitle" class="text-xs text-gray-400 mt-0.5">{{ subtitle }}</p>
        </div>
      </slot>
      <slot name="actions" />
    </div>
    <slot />
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  title:    { type: String, default: '' },
  subtitle: { type: String, default: '' },
  size:     { type: String, default: 'md' },  // sm | md | lg
  rounded:  { type: String, default: 'xl' },  // lg | xl | 2xl
})

const radiusClass = computed(() => ({
  lg:  'rounded-lg',
  xl:  'rounded-xl',
  '2xl': 'rounded-2xl',
}[props.rounded] || 'rounded-xl'))

const paddingClass = computed(() => ({
  sm: 'p-4',
  md: 'p-5',
  lg: 'p-6',
}[props.size] || 'p-5'))
</script>
