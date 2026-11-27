import { reactive, watch } from 'vue'
import { useAuthStore } from '@/stores/auth'

export function useFormDraft(name, defaults, { guest = false } = {}) {
  const authStore = guest ? null : useAuthStore()
  const fields = Object.keys(defaults)
  const form = reactive({ ...defaults })
  let key
  let baseline = defaults
  let restoring = false

  function restoreDraft(draftName, initialValues = defaults) {
    const owner = guest ? 'guest' : authStore.user?.id
    key = owner && draftName ? 'manggahan:draft:' + owner + ':' + draftName : null
    baseline = { ...initialValues }
    let saved
    if (key) {
      try {
        saved = JSON.parse(sessionStorage.getItem(key))
      } catch {
        // The form still works if browser storage is unavailable.
      }
    }

    restoring = true
    for (const field of fields) {
      form[field] = typeof saved?.[field] === 'string' ? saved[field] : baseline[field]
    }
    restoring = false
  }

  function clearDraft() {
    if (!key) return
    try {
      sessionStorage.removeItem(key)
    } catch {
      // Browser storage may be disabled.
    }
  }

  restoreDraft(name)
  watch(() => fields.map(field => form[field]), () => {
    if (!key || restoring) return

    const changes = fields.filter(field => form[field] !== baseline[field])
    if (!changes.length) {
      clearDraft()
      return
    }
    try {
      const draft = Object.fromEntries(changes.map(field => [field, form[field]]))
      sessionStorage.setItem(key, JSON.stringify(draft))
    } catch {
      // Browser storage may be disabled or full.
    }
  }, { flush: 'sync' })

  return { form, clearDraft, restoreDraft }
}
