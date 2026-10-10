<template>
  <span :class="['inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium', style.class]">
    {{ style.label }}
  </span>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  status: { type: String, required: true },
})

const map = {
  pending:     { label: 'Pending',     class: 'bg-yellow-50 text-yellow-700' },
  approved:    { label: 'Approved',    class: 'bg-green-50 text-green-700' },
  rescheduled: { label: 'Rescheduled', class: 'bg-blue-50 text-blue-700' },
  cancelled:   { label: 'Cancelled',   class: 'bg-red-50 text-red-700' },
  completed:   { label: 'Completed',   class: 'bg-gray-100 text-gray-600' },
}

const style = computed(() => map[props.status] ?? { label: props.status, class: 'bg-gray-100 text-gray-600' })
</script>