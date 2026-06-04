<template>
  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex items-start gap-4">
    <!-- Icon -->
    <div :class="['w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0', iconBg]">
      <component :is="iconComponent" :class="['w-6 h-6', iconColor]" />
    </div>

    <!-- Content -->
    <div class="min-w-0">
      <p class="text-sm text-gray-500">{{ label }}</p>
      <p class="text-2xl font-display font-semibold text-primary-dark mt-0.5">{{ value }}</p>
      <p v-if="sub" class="text-xs text-gray-400 mt-0.5">{{ sub }}</p>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import {
  ExclamationTriangleIcon,
  CalendarDaysIcon,
  BellIcon,
  ArrowPathIcon,
  UsersIcon,
  DocumentTextIcon,
  HomeIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
  label:  { type: String, required: true },
  value:  { type: [String, Number], required: true },
  sub:    { type: String, default: '' },
  icon:   { type: String, default: 'home' },
  color:  { type: String, default: 'green' },  // green | yellow | red | blue | gray
})

const iconMap = {
  alert:     ExclamationTriangleIcon,
  calendar:  CalendarDaysIcon,
  bell:      BellIcon,
  recycle:   ArrowPathIcon,
  users:     UsersIcon,
  document:  DocumentTextIcon,
  home:      HomeIcon,
}

const iconComponent = computed(() => iconMap[props.icon] || HomeIcon)

const iconBg = computed(() => ({
  green:  'bg-green-50',
  yellow: 'bg-yellow-50',
  red:    'bg-red-50',
  blue:   'bg-blue-50',
  gray:   'bg-gray-50',
  olive:  'bg-primary-50',
}[props.color] || 'bg-primary-50'))

const iconColor = computed(() => ({
  green:  'text-green-600',
  yellow: 'text-yellow-600',
  red:    'text-red-600',
  blue:   'text-blue-600',
  gray:   'text-gray-500',
  olive:  'text-primary',
}[props.color] || 'text-primary'))
</script>
