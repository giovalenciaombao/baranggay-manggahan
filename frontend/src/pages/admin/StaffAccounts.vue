<template>
  <div class="p-4 sm:p-6 space-y-5">

    <!-- Header -->
    <div class="flex items-center justify-between gap-3">
      <p class="text-sm text-gray-400">
        Create and manage barangay staff accounts. Only the super admin can see this page.
      </p>
      <AppButton variant="primary" size="sm" @click="openCreate">
        <PlusIcon class="w-4 h-4" />
        Add Staff
      </AppButton>
    </div>

    <!-- Loading / empty -->
    <div v-if="fetching" class="card rounded-2xl p-10 text-center text-gray-400 text-sm">
      Loading staff...
    </div>
    <div v-else-if="staffList.length === 0" class="card rounded-2xl p-10 text-center text-gray-400 text-sm">
      No staff accounts yet.
    </div>

    <!-- List -->
    <div v-else class="space-y-3">
      <div
        v-for="s in staffList"
        :key="s.id"
        class="card rounded-2xl p-4 flex items-center gap-4"
      >
        <div class="w-10 h-10 rounded-full bg-primary-light flex items-center justify-center flex-shrink-0">
          <span class="text-sm font-semibold text-primary-dark">{{ s.initials }}</span>
        </div>

        <div class="flex-1 min-w-0">
          <div class="flex items-center gap-2">
            <p class="text-sm font-semibold text-gray-800 truncate">{{ s.name }}</p>
            <span
              v-if="s.is_super_admin"
              class="text-[10px] font-medium bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full flex-shrink-0"
            >Super Admin</span>
            <span
              v-else-if="s.id === me?.id"
              class="text-[10px] font-medium bg-gray-100 text-gray-500 px-2 py-0.5 rounded-full flex-shrink-0"
            >You</span>
          </div>
          <p class="text-xs text-gray-400 truncate">{{ s.email }}</p>
          <p class="text-xs text-gray-300 mt-0.5">{{ s.contact_number || '—' }}</p>
        </div>

        <AppBadge :variant="s.badge.variant" class="flex-shrink-0 hidden sm:inline-flex">
          {{ s.badge.label }}
        </AppBadge>

        <div v-if="s.manageable" class="flex gap-1.5 flex-shrink-0">
          <button
            @click="openReset(s)"
            class="p-2 rounded-lg bg-gray-50 hover:bg-gray-100 transition-colors"
            title="Reset password"
          >
            <KeyIcon class="w-4 h-4 text-gray-500" />
          </button>
          <button
            @click="toggleStatus(s)"
            :class="[
              'p-2 rounded-lg transition-colors',
              s.status === 'active' ? 'bg-yellow-50 hover:bg-yellow-100' : 'bg-green-50 hover:bg-green-100'
            ]"
            :title="s.status === 'active' ? 'Deactivate' : 'Reactivate'"
          >
            <NoSymbolIcon v-if="s.status === 'active'" class="w-4 h-4 text-yellow-500" />
            <CheckIcon v-else class="w-4 h-4 text-green-600" />
          </button>
        </div>
      </div>
    </div>

    <!-- Create modal -->
    <Teleport to="body">
      <div
        v-if="showCreate"
        class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 px-4"
        @click.self="showCreate = false"
      >
        <div class="bg-white rounded-2xl p-6 w-full max-w-md space-y-4 shadow-xl max-h-[92vh] overflow-y-auto">
          <div class="flex items-center justify-between">
            <h3 class="text-base font-semibold text-primary-dark">Add Staff Account</h3>
            <button @click="showCreate = false" class="text-gray-400 hover:text-gray-600">
              <XMarkIcon class="w-5 h-5" />
            </button>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <AppInput v-model="form.first_name" label="First name" placeholder="Juan" :error="errors.first_name" required />
            <AppInput v-model="form.last_name" label="Last name" placeholder="Cruz" :error="errors.last_name" required />
          </div>

          <AppInput v-model="form.email" label="Email address" type="email" placeholder="staff@example.com" :error="errors.email" required />
          <AppInput v-model="form.contact_number" label="Contact number" type="tel" placeholder="09xx-xxx-xxxx" :error="errors.contact_number" required />

          <div>
            <AppInput
              v-model="form.password"
              label="Temporary password"
              :type="showPw ? 'text' : 'password'"
              hint="At least 8 characters. Tell the staff to keep it private."
              :error="errors.password"
              required
            >
              <template #suffix>
                <button type="button" @click="showPw = !showPw" class="text-gray-400 hover:text-gray-600">
                  <EyeSlashIcon v-if="showPw" class="w-4 h-4" />
                  <EyeIcon v-else class="w-4 h-4" />
                </button>
              </template>
            </AppInput>
            <button
              type="button"
              @click="form.password = generatePassword(); showPw = true"
              class="mt-1 inline-flex items-center gap-1 text-xs text-primary font-medium hover:underline"
            >
              <ArrowPathIcon class="w-3.5 h-3.5" />
              Generate password
            </button>
          </div>

          <div class="flex gap-2 pt-1">
            <AppButton variant="outline" class="flex-1" @click="showCreate = false">Cancel</AppButton>
            <AppButton variant="primary" class="flex-1" :loading="saving" @click="submitCreate">Create account</AppButton>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Reset password modal -->
    <Teleport to="body">
      <div
        v-if="resetTarget"
        class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 px-4"
        @click.self="resetTarget = null"
      >
        <div class="bg-white rounded-2xl p-6 w-full max-w-sm space-y-4 shadow-xl">
          <div class="flex items-center justify-between">
            <div>
              <h3 class="text-base font-semibold text-primary-dark">Reset password</h3>
              <p class="text-xs text-gray-400">{{ resetTarget.name }}</p>
            </div>
            <button @click="resetTarget = null" class="text-gray-400 hover:text-gray-600">
              <XMarkIcon class="w-5 h-5" />
            </button>
          </div>

          <div>
            <AppInput
              v-model="resetPassword"
              label="New password"
              :type="showResetPw ? 'text' : 'password'"
              hint="The staff will be signed out and must use this password."
              :error="resetError"
              required
            >
              <template #suffix>
                <button type="button" @click="showResetPw = !showResetPw" class="text-gray-400 hover:text-gray-600">
                  <EyeSlashIcon v-if="showResetPw" class="w-4 h-4" />
                  <EyeIcon v-else class="w-4 h-4" />
                </button>
              </template>
            </AppInput>
            <button
              type="button"
              @click="resetPassword = generatePassword(); showResetPw = true"
              class="mt-1 inline-flex items-center gap-1 text-xs text-primary font-medium hover:underline"
            >
              <ArrowPathIcon class="w-3.5 h-3.5" />
              Generate password
            </button>
          </div>

          <div class="flex gap-2 pt-1">
            <AppButton variant="outline" class="flex-1" @click="resetTarget = null">Cancel</AppButton>
            <AppButton variant="primary" class="flex-1" :loading="saving" @click="submitReset">Reset password</AppButton>
          </div>
        </div>
      </div>
    </Teleport>

  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useToast } from 'vue-toastification'
import {
  PlusIcon, XMarkIcon, KeyIcon, NoSymbolIcon, CheckIcon,
  EyeIcon, EyeSlashIcon, ArrowPathIcon,
} from '@heroicons/vue/24/outline'
import api from '@/services/api'
import { useFormDraft } from '@/composables/useFormDraft'
import { useAuthStore } from '@/stores/auth'
import AppButton from '@/components/common/AppButton.vue'
import AppBadge from '@/components/common/AppBadge.vue'
import AppInput from '@/components/common/AppInput.vue'

const toast     = useToast()
const authStore = useAuthStore()
const me        = computed(() => authStore.user)

const fetching = ref(true)
const saving   = ref(false)
const staff    = ref([])

const showCreate = ref(false)
const showPw     = ref(false)

const resetTarget = ref(null)
const resetPassword = ref('')
const resetError  = ref('')
const showResetPw = ref(false)

const { form, restoreDraft, clearDraft } = useFormDraft('staff-new', {
  first_name: '', last_name: '', email: '', contact_number: '',
})
form.password = ''
const errors = reactive({
  first_name: '', last_name: '', email: '', contact_number: '', password: '',
})

const badgeMap = {
  active:    { variant: 'success', label: 'Active'      },
  suspended: { variant: 'default', label: 'Deactivated' },
}

const staffList = computed(() =>
  staff.value.map(s => ({
    ...s,
    name:       `${s.first_name} ${s.last_name}`.trim(),
    initials:   `${s.first_name?.[0] ?? ''}${s.last_name?.[0] ?? ''}`.toUpperCase(),
    badge:      badgeMap[s.status] ?? { variant: 'default', label: s.status },
    manageable: !s.is_super_admin && s.id !== me.value?.id,
  }))
)

function generatePassword(len = 10) {
  const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789'
  const arr = new Uint32Array(len)
  crypto.getRandomValues(arr)
  return Array.from(arr, n => chars[n % chars.length]).join('')
}

function openCreate() {
  restoreDraft('staff-new')
  form.password = ''
  Object.keys(errors).forEach(k => (errors[k] = ''))
  showPw.value = false
  showCreate.value = true
}

function validateCreate() {
  errors.first_name     = !form.first_name     ? 'First name is required.'     : ''
  errors.last_name      = !form.last_name      ? 'Last name is required.'      : ''
  errors.email          = !form.email          ? 'Email is required.'          : ''
  errors.contact_number = !form.contact_number ? 'Contact number is required.' : ''
  errors.password       = form.password.length < 8 ? 'At least 8 characters.'  : ''
  return Object.values(errors).every(e => !e)
}

async function submitCreate() {
  if (!validateCreate()) return
  saving.value = true
  try {
    const { data } = await api.post('/admin/staff', { ...form })
    clearDraft()
    form.password = ''
    staff.value.push(data.staff)
    toast.success('Staff account created.')
    showCreate.value = false
  } catch (err) {
    const serverErrors = err.response?.data?.errors
    if (serverErrors) {
      Object.keys(serverErrors).forEach(k => { if (k in errors) errors[k] = serverErrors[k][0] })
    }
    toast.error(err.response?.data?.message || 'Failed to create staff account.')
  } finally {
    saving.value = false
  }
}

async function toggleStatus(s) {
  const next = s.status === 'active' ? 'suspended' : 'active'
  const msg  = next === 'suspended'
    ? `Deactivate ${s.name}? They will be signed out immediately.`
    : `Reactivate ${s.name}?`
  if (!window.confirm(msg)) return

  try {
    const { data } = await api.put(`/admin/staff/${s.id}/status`, { status: next })
    const idx = staff.value.findIndex(x => x.id === s.id)
    if (idx !== -1) staff.value[idx] = data.staff
    toast.success(data.message)
  } catch (err) {
    toast.error(err.response?.data?.message || 'Failed to update staff.')
  }
}

function openReset(s) {
  resetTarget.value   = s
  resetPassword.value = ''
  resetError.value    = ''
  showResetPw.value   = false
}

async function submitReset() {
  if (resetPassword.value.length < 8) {
    resetError.value = 'At least 8 characters.'
    return
  }
  resetError.value = ''
  saving.value = true
  try {
    const { data } = await api.put(`/admin/staff/${resetTarget.value.id}/password`, {
      password: resetPassword.value,
    })
    toast.success(data.message)
    resetTarget.value = null
  } catch (err) {
    resetError.value = err.response?.data?.errors?.password?.[0]
      || err.response?.data?.message
      || 'Failed to reset password.'
  } finally {
    saving.value = false
  }
}

onMounted(async () => {
  try {
    const { data } = await api.get('/admin/staff')
    staff.value = data.staff
  } catch (_) {
    toast.error('Failed to load staff accounts.')
  } finally {
    fetching.value = false
  }
})
</script>