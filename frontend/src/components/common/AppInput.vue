<template>
  <div class="form-group">
    <label v-if="label" :for="id" class="label">
      {{ label }}
      <span v-if="required" class="text-red-500 ml-0.5">*</span>
    </label>
    <div class="relative">
      <div v-if="$slots.prefix" class="absolute inset-y-0 left-3 flex items-center pointer-events-none text-gray-400">
        <slot name="prefix" />
      </div>
      <input
        :id="id"
        :type="type"
        :value="modelValue"
        :placeholder="placeholder"
        :disabled="disabled"
        :required="required"
        :class="[
          'input',
          $slots.prefix  ? 'pl-10' : '',
          $slots.suffix  ? 'pr-10' : '',
          error ? 'border-red-400 focus:ring-red-300 focus:border-red-400' : ''
        ]"
        @input="$emit('update:modelValue', $event.target.value)"
        @blur="$emit('blur', $event)"
        v-bind="$attrs"
      />
      <div v-if="$slots.suffix" class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-gray-400">
        <slot name="suffix" />
      </div>
    </div>
    <p v-if="error" class="mt-1 text-xs text-red-500">{{ error }}</p>
    <p v-else-if="hint" class="mt-1 text-xs text-gray-400">{{ hint }}</p>
  </div>
</template>

<script setup>
defineProps({
  id:          { type: String, default: () => `input-${Math.random().toString(36).slice(2,7)}` },
  modelValue:  { type: [String, Number], default: '' },
  label:       { type: String, default: '' },
  type:        { type: String, default: 'text' },
  placeholder: { type: String, default: '' },
  error:       { type: String, default: '' },
  hint:        { type: String, default: '' },
  disabled:    { type: Boolean, default: false },
  required:    { type: Boolean, default: false },
})

defineEmits(['update:modelValue', 'blur'])
defineOptions({ inheritAttrs: false })
</script>
