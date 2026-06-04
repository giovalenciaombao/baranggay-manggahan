<template>
    <div class="p-4 sm:p-6 max-w-xl mx-auto space-y-5">

      <!-- Header -->
      <div class="flex items-center gap-3">
        <button @click="router.back()" class="p-2 rounded-lg hover:bg-primary-light transition-colors">
          <ArrowLeftIcon class="w-4 h-4 text-primary-dark" />
        </button>
        <div>
          <h1 class="text-xl font-semibold text-primary-dark">File an Incident Report</h1>
          <p class="text-sm text-gray-400 mt-0.5">Describe the issue and we'll act on it.</p>
        </div>
      </div>

      <div class="card rounded-2xl p-5 space-y-4">

        <!-- API error -->
        <div v-if="apiError" class="flex items-center gap-2 bg-red-50 border border-red-200 rounded-xl px-4 py-3">
          <ExclamationCircleIcon class="w-4 h-4 text-red-500 flex-shrink-0" />
          <p class="text-sm text-red-700">{{ apiError }}</p>
        </div>

        <AppInput
          v-model="form.title"
          label="Incident title"
          placeholder="e.g. Noise complaint, illegal dumping..."
          :error="errors.title"
          required
        />

        <div>
          <label class="block text-sm font-medium text-gray-600 mb-1">
            Category <span class="text-red-400">*</span>
          </label>
          <select
            v-model="form.category"
            class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-primary"
          >
            <option value="" disabled>Select a category</option>
            <option v-for="c in categories" :key="c" :value="c">{{ c }}</option>
          </select>
          <p v-if="errors.category" class="text-xs text-red-500 mt-1">{{ errors.category }}</p>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-600 mb-1">
            Description <span class="text-red-400">*</span>
          </label>
          <textarea
            v-model="form.description"
            rows="4"
            placeholder="Describe what happened, when, and where..."
            class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-primary resize-none"
          />
          <p v-if="errors.description" class="text-xs text-red-500 mt-1">{{ errors.description }}</p>
        </div>

        <AppInput
          v-model="form.location"
          label="Exact location"
          placeholder="e.g. Purok 3, near the basketball court"
          :error="errors.location"
          required
        />

        <!-- Photo upload -->
        <div>
          <label class="block text-sm font-medium text-gray-600 mb-1">Photo (optional)</label>
          <div
            class="border-2 border-dashed border-gray-200 rounded-xl p-6 text-center hover:border-primary transition-colors cursor-pointer"
            @click="$refs.fileInput.click()"
          >
            <PhotoIcon class="w-8 h-8 text-gray-300 mx-auto mb-2" />
            <p class="text-sm text-gray-400">Click to upload a photo</p>
            <p class="text-xs text-gray-300 mt-1">JPG, PNG up to 5MB</p>
          </div>
          <input
            ref="fileInput"
            type="file"
            accept="image/*"
            class="hidden"
            @change="handlePhoto"
          />
          <div v-if="photoPreview" class="mt-3 relative">
            <img :src="photoPreview" class="w-full h-40 object-cover rounded-xl" />
            <button
              @click="photoPreview = null; form.photo = null"
              class="absolute top-2 right-2 bg-white rounded-full p-1 shadow"
            >
              <XMarkIcon class="w-4 h-4 text-gray-500" />
            </button>
          </div>
        </div>

        <AppButton
          type="button"
          variant="primary"
          class="w-full"
          :loading="loading"
          @click="handleSubmit"
        >
          <PaperAirplaneIcon class="w-4 h-4" />
          Submit Report
        </AppButton>

      </div>
    </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'vue-toastification'
import {
  ArrowLeftIcon,
  ExclamationCircleIcon,
  PhotoIcon,
  XMarkIcon,
  PaperAirplaneIcon,
} from '@heroicons/vue/24/outline'
import api from '@/services/api'
import AppInput from '@/components/common/AppInput.vue'
import AppButton from '@/components/common/AppButton.vue'

const router = useRouter()
const toast  = useToast()

const loading      = ref(false)
const apiError     = ref('')
const photoPreview = ref(null)

const categories = [
  'Noise Complaint',
  'Illegal Dumping',
  'Broken Infrastructure',
  'Public Disturbance',
  'Flooding / Drainage',
  'Stray Animals',
  'Others',
]

const form = reactive({
  title:       '',
  category:    '',
  description: '',
  location:    '',
  photo:       null,
})

const errors = reactive({
  title:       '',
  category:    '',
  description: '',
  location:    '',
})

function handlePhoto(e) {
  const file = e.target.files[0]
  if (!file) return
  form.photo = file
  photoPreview.value = URL.createObjectURL(file)
}

function validate() {
  errors.title       = !form.title       ? 'Title is required.'       : ''
  errors.category    = !form.category    ? 'Category is required.'    : ''
  errors.description = !form.description ? 'Description is required.' : ''
  errors.location    = !form.location    ? 'Location is required.'    : ''
  return Object.values(errors).every(e => !e)
}

async function handleSubmit() {
  if (!validate()) return
  loading.value  = true
  apiError.value = ''
  try {
    const formData = new FormData()
    formData.append('title',       form.title)
    formData.append('category',    form.category)
    formData.append('description', form.description)
    formData.append('location',    form.location)
    if (form.photo) {
      formData.append('photo', form.photo)
    }

    await api.post('/incidents', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })

    toast.success('Incident report submitted!')
    router.push('/portal/incidents')
  } catch (err) {
    apiError.value = err.response?.data?.message || 'Failed to submit. Please try again.'
  } finally {
    loading.value = false
  }
}
</script>