<template>
  <RouterLink
    :to="to"
    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-colors duration-150
          text-primary-light/60 hover:text-white hover:bg-white/10"
    active-class=""
    exact-active-class="!text-white !bg-white/15"
    v-bind="$attrs"
  >
    <!-- Icon -->
    <component :is="iconComponent" class="w-5 h-5 flex-shrink-0" />
    {{ label }}
  </RouterLink>
</template>

<script setup>
import { computed } from 'vue'
import { RouterLink } from 'vue-router'

// Heroicons (outline)
import {
  HomeIcon,
  ExclamationTriangleIcon,
  CalendarDaysIcon,
  BellIcon,
  ArrowPathIcon,
  UserIcon,
  UsersIcon,
  DocumentTextIcon,
  Squares2X2Icon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
  to:    { type: String, required: true },
  icon:  { type: String, required: true },
  label: { type: String, required: true },
})

const iconMap = {
  home:       HomeIcon,
  alert:      ExclamationTriangleIcon,
  calendar:   CalendarDaysIcon,
  bell:       BellIcon,
  recycle:    ArrowPathIcon,
  user:       UserIcon,
  users:      UsersIcon,
  'file-text':DocumentTextIcon,
  dashboard:  Squares2X2Icon,
}

const iconComponent = computed(() => iconMap[props.icon] || HomeIcon)
</script>
