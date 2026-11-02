<template>
  <div class="mb-4">
    <label class="flex items-start gap-2.5 cursor-pointer">
      <input
        type="checkbox"
        :checked="modelValue"
        @change="$emit('update:modelValue', $event.target.checked)"
        class="mt-0.5 w-4 h-4 rounded border-gray-300 text-primary focus:ring-primary flex-shrink-0"
      />
      <span class="text-sm text-gray-600 leading-snug">
        Pumapayag ako na gamitin ang dokumentong ina-upload ko para i-verify ang residency ko sa Barangay Manggahan.
      </span>
    </label>

    <button
      type="button"
      @click="open = !open"
      class="mt-1.5 ml-6 text-xs text-primary font-medium hover:underline"
    >
      {{ open ? 'Itago ang detalye' : 'Basahin ang detalye' }}
    </button>

    <div
      v-if="open"
      class="mt-2 ml-6 rounded-xl bg-gray-50 border border-gray-200 px-3.5 py-3 text-xs text-gray-600 space-y-1.5 leading-relaxed"
    >
      <p class="font-semibold text-gray-700">Data Privacy Notice (Data Privacy Act of 2012, RA 10173)</p>
      <p><span class="font-medium">Anong data:</span> ang Barangay ID o billing statement na ia-upload mo, kasama ang pangalan, address, at contact details mo.</p>
      <p><span class="font-medium">Bakit:</span> para patunayan lang na taga-Barangay Manggahan ka at maprotektahan ang system laban sa maling gumagamit.</p>
      <p><span class="font-medium">Sino ang makakakita:</span> ang mga awtorisadong admin at staff ng barangay lang. Hindi ito makikita ng ibang residente at hindi ibabahagi sa iba.</p>
      <p><span class="font-medium">Gaano katagal:</span> itatago ito habang aktibo ang account mo. Maaari mong hilingin sa barangay office na burahin ito.</p>
      <p><span class="font-medium">Karapatan mo:</span> may karapatan kang malaman, i-update, o ipabura ang personal na data mo ayon sa batas.</p>
    </div>

    <p v-if="error" class="text-xs text-red-500 mt-1 ml-6">{{ error }}</p>
  </div>
</template>

<script setup>
import { ref } from 'vue'

defineProps({
  modelValue: { type: Boolean, default: false },
  error:      { type: String,  default: '' },
})
defineEmits(['update:modelValue'])

const open = ref(false)
</script>