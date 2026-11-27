import { ref } from 'vue'
import api from '@/services/api'

/**
 * Composable para sa API calls with loading + error state
 * Usage:
 *   const { data, loading, error, execute } = useApi()
 *   await execute(() => api.get('/incidents'))
 */
export function useApi() {
  const data    = ref(null)
  const loading = ref(false)
  const error   = ref(null)

  async function execute(fn) {
    loading.value = true
    error.value   = null
    try {
      const response = await fn()
      data.value = response.data
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || err.message || 'Something went wrong.'
      throw err
    } finally {
      loading.value = false
    }
  }

  return { data, loading, error, execute }
}

// ─── Service helpers ────────────────────────────────────────────────────────
export const incidentService = {
  getAll:   (params) => api.get('/incidents', { params }),
  getOne:   (id)     => api.get(`/incidents/${id}`),
  create:   (data)   => api.post('/incidents', data, { headers: { 'Content-Type': 'multipart/form-data' } }),
  update:   (id, d)  => api.put(`/incidents/${id}`, d),
  remove:   (id)     => api.delete(`/incidents/${id}`),
}

export const appointmentService = {
  getAll:   (params) => api.get('/appointments', { params }),
  create:   (data)   => api.post('/appointments', data),
  update:   (id, d)  => api.put(`/appointments/${id}`, d),
  remove:   (id)     => api.delete(`/appointments/${id}`),
}

export const alertService = {
  getAll:   (params) => api.get('/alerts', { params }),
  create:   (data)   => api.post('/alerts', data),
  update:   (id, d)  => api.put(`/alerts/${id}`, d),
  remove:   (id)     => api.delete(`/alerts/${id}`),
}

export const userService = {
  getAll:   (params)  => api.get('/admin/users', { params }),
  approve:  (id)      => api.post(`/admin/users/${id}/approve`),
  reject:   (id)      => api.post(`/admin/users/${id}/reject`),
}

export const reportService = {
  generate: (params)  => api.get('/admin/reports', { params }),
  export:   (params)  => api.get('/admin/reports/export', { params, responseType: 'blob' }),
}

export default api
