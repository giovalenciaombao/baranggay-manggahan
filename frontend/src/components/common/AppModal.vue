<template>
  <Teleport to="body">
    <Transition name="modal">
      <div
        v-if="modelValue"
        class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4"
        @mousedown.self="closeOnBackdrop && $emit('update:modelValue', false)"
      >
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" />

        <!-- Panel -->
        <div
          class="relative bg-white w-full sm:rounded-2xl shadow-2xl overflow-hidden
                 sm:max-w-lg rounded-t-2xl"
          :class="sizeClass"
        >
          <!-- Header -->
          <div v-if="title || $slots.header" class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
            <slot name="header">
              <h3 class="font-display font-semibold text-primary-dark text-lg">{{ title }}</h3>
            </slot>
            <button
              v-if="showClose"
              @click="$emit('update:modelValue', false)"
              class="p-1.5 rounded-lg hover:bg-gray-100 text-gray-400 hover:text-gray-600"
              aria-label="Close"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
          </div>

          <!-- Body -->
          <div class="px-5 py-5 overflow-y-auto max-h-[70vh] sm:max-h-[60vh]">
            <slot />
          </div>

          <!-- Footer -->
          <div v-if="$slots.footer" class="px-5 py-4 border-t border-gray-100 flex items-center justify-end gap-3">
            <slot name="footer" />
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  modelValue:       { type: Boolean, default: false },
  title:            { type: String,  default: '' },
  size:             { type: String,  default: 'md' },   // sm | md | lg
  showClose:        { type: Boolean, default: true },
  closeOnBackdrop:  { type: Boolean, default: true },
})

defineEmits(['update:modelValue'])

const sizeClass = computed(() => ({
  sm: 'sm:max-w-sm',
  md: 'sm:max-w-lg',
  lg: 'sm:max-w-2xl',
}[props.size]))
</script>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: opacity 0.2s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
.modal-enter-active > div:last-child { transition: transform 0.25s ease; }
.modal-enter-from > div:last-child { transform: translateY(20px) scale(0.98); }
</style>
