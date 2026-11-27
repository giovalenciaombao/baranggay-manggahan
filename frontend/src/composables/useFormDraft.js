import { reactive, watch } from 'vue'
import { useAuthStore } from '@/stores/auth'

export function useFormDraft(name, defaults) {
  const userId = useAuthStore().user?.id
  const key = userId ? `manggahan:draft:${userId}:${name}` : null
  const fields = Object.keys(defaults)
  const form = reactive({ ...defaults })

  if (key) {
    try {
      const saved = JSON.parse(sessionStorage.getItem(key))
      for (const field of fields) {
        if (typeof saved?.[field] === 'string') form[field] = saved[field]
      }
    } catch {
      // The form still works if browser storage is unavailable.
    }
  }

  function clearDraft() {
    if (!key) return
    try {
      sessionStorage.removeItem(key)
    } catch {
      // Browser storage may be disabled.
    }
  }

  watch(() => fields.map(field => form[field]), values => {
    if (!key) return
    if (values.every(value => value === '')) {
      clearDraft()
      return
    }
    try {
      const draft = Object.fromEntries(fields.map((field, index) => [field, values[index]]))
      sessionStorage.setItem(key, JSON.stringify(draft))
    } catch {
      // Browser storage may be disabled or full.
    }
  }, { flush: 'sync' })

  return { form, clearDraft }
}
