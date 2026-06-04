<template>
  <span :class="['badge', colorClass]">
    <span v-if="dot" class="w-1.5 h-1.5 rounded-full" :class="dotColor" />
    <slot />
  </span>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  color: { type: String, default: 'gray' },  // green | yellow | red | blue | gray
  dot:   { type: Boolean, default: false },
})

const colorClass = computed(() => ({
  green:  'badge-green',
  yellow: 'badge-yellow',
  red:    'badge-red',
  blue:   'badge-blue',
  gray:   'badge-gray',
}[props.color] || 'badge-gray'))

const dotColor = computed(() => ({
  green:  'bg-green-500',
  yellow: 'bg-yellow-500',
  red:    'bg-red-500',
  blue:   'bg-blue-500',
  gray:   'bg-gray-400',
}[props.color] || 'bg-gray-400'))
</script>
