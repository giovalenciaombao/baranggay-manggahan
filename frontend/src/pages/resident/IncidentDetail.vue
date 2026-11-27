<template>
  <div class="mx-auto max-w-2xl space-y-4 p-4 sm:p-6">
    <RouterLink :to="{ name: 'resident-incidents' }" class="btn-ghost">
      <ArrowLeftIcon class="h-4 w-4" aria-hidden="true" />
      Back to incident reports
    </RouterLink>

    <p v-if="loading" class="card text-sm text-gray-600" role="status">Loading incident report…</p>
    <p v-else-if="error" class="card text-sm text-red-700" role="alert">{{ error }}</p>
    <article v-else-if="incident" class="card space-y-5">
      <div class="flex flex-wrap items-center justify-between gap-2">
        <h2 class="break-words text-xl font-semibold text-primary-dark">{{ incident.title }}</h2>
        <AppBadge :variant="status.variant">{{ status.label }}</AppBadge>
      </div>
      <dl class="grid gap-3 text-sm sm:grid-cols-2">
        <div>
          <dt class="font-semibold text-gray-700">Category</dt>
          <dd>{{ incident.category }}</dd>
        </div>
        <div>
          <dt class="font-semibold text-gray-700">Location</dt>
          <dd>{{ incident.location }}</dd>
        </div>
        <div>
          <dt class="font-semibold text-gray-700">Date filed</dt>
          <dd>{{ formatDate(incident.created_at) }}</dd>
        </div>
      </dl>
      <p class="whitespace-pre-wrap break-words text-sm leading-relaxed text-gray-700">{{ incident.description }}</p>
      <div v-if="incident.has_photo || incident.photo">
        <h3 class="mb-2 text-sm font-semibold text-gray-700">Incident photo</h3>
        <img v-if="photoUrl" :src="photoUrl" alt="Photo attached to your incident report" class="max-h-96 w-full rounded-xl object-contain" />
        <p v-else class="text-sm text-gray-600" role="status">{{ photoError || 'Loading photo…' }}</p>
      </div>
    </article>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { ArrowLeftIcon } from '@heroicons/vue/24/outline'
import AppBadge from '@/components/common/AppBadge.vue'
import api from '@/services/api'
import { formatDate } from '@/utils/formatters'

const route = useRoute()
const incident = ref(null)
const loading = ref(true)
const error = ref('')
const photoUrl = ref('')
const photoError = ref('')

const statuses = {
  pending: { label: 'Pending', variant: 'warning' },
  review: { label: 'Under Review', variant: 'info' },
  resolved: { label: 'Resolved', variant: 'success' },
  dismissed: { label: 'Dismissed', variant: 'default' },
}
const status = computed(() => statuses[incident.value?.status] ?? { label: 'Unknown', variant: 'default' })

watch(() => route.params.id, async (id, _, onCleanup) => {
  const controller = new AbortController()
  let objectUrl
  onCleanup(() => {
    controller.abort()
    if (objectUrl) URL.revokeObjectURL(objectUrl)
  })

  incident.value = null
  loading.value = true
  error.value = ''
  photoUrl.value = ''
  photoError.value = ''

  if (!/^\d+$/.test(id ?? '')) {
    error.value = 'This incident report could not be opened.'
    loading.value = false
    return
  }

  try {
    const { data } = await api.get('/incidents/' + id, { signal: controller.signal })
    if (controller.signal.aborted) return
    incident.value = data.incident
  } catch {
    if (!controller.signal.aborted) error.value = 'This incident report could not be opened.'
  } finally {
    if (!controller.signal.aborted) loading.value = false
  }

  if (controller.signal.aborted || (!incident.value?.has_photo && !incident.value?.photo)) return
  try {
    const { data } = await api.get('/incidents/' + id + '/photo', {
      responseType: 'blob',
      signal: controller.signal,
    })
    if (controller.signal.aborted) return
    objectUrl = URL.createObjectURL(data)
    photoUrl.value = objectUrl
  } catch {
    if (!controller.signal.aborted) photoError.value = 'The attached photo is unavailable.'
  }
}, { immediate: true })
</script>
