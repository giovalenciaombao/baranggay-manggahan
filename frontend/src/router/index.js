import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const routes = [
  // --- Public / Guest ---
  {
    path: '/',
    component: () => import('@/layouts/GuestLayout.vue'),
    children: [
      {
        path: '',
        name: 'home',
        component: () => import('@/pages/public/Home.vue'),
        meta: { title: 'Barangay Manggahan' }
      },
      {
        path: 'login',
        name: 'login',
        component: () => import('@/pages/auth/Login.vue'),
        meta: { title: 'Login', guestOnly: true }
      },
      {
        path: 'register',
        name: 'register',
        component: () => import('@/pages/auth/Register.vue'),
        meta: { title: 'Register', guestOnly: true }
      },
      {
        path: 'verify-email',
        name: 'verify-email',
        component: () => import('@/pages/auth/VerifyEmail.vue'),
        meta: { title: 'Verify Email' }
      },
      {
        path: 'pending-approval',
        name: 'pending-approval',
        component: () => import('@/pages/auth/PendingApproval.vue'),
        meta: { title: 'Pending Approval' }
      },
      {
        path: '/auth/callback',
        name: 'google-callback',
        component: () => import('@/pages/auth/GoogleCallback.vue'),
      },
    ]
  },

  // --- Resident Portal ---
  {
    path: '/portal',
    component: () => import('@/layouts/ResidentLayout.vue'),
    meta: { requiresAuth: true, role: 'resident' },
    children: [
      {
        path: '',
        name: 'resident-dashboard',
        component: () => import('@/pages/resident/Dashboard.vue'),
        meta: { title: 'My Dashboard' }
      },
      {
        path: 'incidents',
        name: 'resident-incidents',
        component: () => import('@/pages/resident/Incidents.vue'),
        meta: { title: 'Incident Reports' }
      },
      {
        path: 'incidents/new',
        name: 'resident-incidents-new',
        component: () => import('@/pages/resident/IncidentForm.vue'),
        meta: { title: 'File a Report' }
      },
      {
        path: 'appointments',
        name: 'resident-appointments',
        component: () => import('@/pages/resident/Appointments.vue'),
        meta: { title: 'Appointments' }
      },
      {
        path: 'appointments/new',
        name: 'resident-appointments-new',
        component: () => import('@/pages/resident/AppointmentForm.vue'),
        meta: { title: 'Book Appointment' }
      },
      {
        path: 'alerts',
        name: 'resident-alerts',
        component: () => import('@/pages/resident/Alerts.vue'),
        meta: { title: 'Alerts & Announcements' }
      },
      {
        path: 'waste',
        name: 'resident-waste',
        component: () => import('@/pages/resident/Waste.vue'),
        meta: { title: 'Waste Management' }
      },
      {
        path: 'profile',
        name: 'resident-profile',
        component: () => import('@/pages/resident/Profile.vue'),
        meta: { title: 'My Profile' }
      }
    ]
  },

  // --- Admin Dashboard ---
  {
    path: '/admin',
    component: () => import('@/layouts/AdminLayout.vue'),
    meta: { requiresAuth: true, role: 'admin' },
    children: [
      {
        path: '',
        name: 'admin-dashboard',
        component: () => import('@/pages/admin/Dashboard.vue'),
        meta: { title: 'Admin Dashboard' }
      },
      {
        path: 'users',
        name: 'admin-users',
        component: () => import('@/pages/admin/Users.vue'),
        meta: { title: 'User Management' }
      },
      {
        path: 'staff',
        name: 'admin-staff',
        component: () => import('@/pages/admin/StaffAccounts.vue'),
        meta: { title: 'Staff Accounts', superAdmin: true }
      },
      {
        path: 'incidents',
        name: 'admin-incidents',
        component: () => import('@/pages/admin/Incidents.vue'),
        meta: { title: 'Incident Reports' }
      },
      {
        path: 'appointments',
        name: 'admin-appointments',
        component: () => import('@/pages/admin/Appointments.vue'),
        meta: { title: 'Appointments' }
      },
      {
        path: 'alerts',
        name: 'admin-alerts',
        component: () => import('@/pages/admin/Alerts.vue'),
        meta: { title: 'Alerts & Announcements' }
      },
      {
        path: 'waste',
        name: 'admin-waste',
        component: () => import('@/pages/admin/Waste.vue'),
        meta: { title: 'Waste Management' }
      },
      {
        path: 'waste-schedules',
        name: 'admin-waste-schedules',
        component: () => import('@/pages/admin/WasteSchedules.vue'),
        meta: { title: 'Garbage Truck Schedule' }
      },
      {
        path: 'reports',
        name: 'admin-reports',
        component: () => import('@/pages/admin/Reports.vue'),
        meta: { title: 'Reports' }
      }
    ]
  },

  // --- Catch-all 404 ---
  {
    path: '/:pathMatch(.*)*',
    name: 'not-found',
    component: () => import('@/pages/NotFound.vue'),
    meta: { title: '404 Not Found' }
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior(to, from, savedPosition) {
    if (savedPosition) return savedPosition
    return { top: 0, behavior: 'smooth' }
  }
})

// --- Navigation Guards ---
router.beforeEach(async (to, from, next) => {
  // Update page title
  document.title = to.meta.title
    ? `${to.meta.title} | Brgy. Manggahan`
    : 'Barangay Manggahan'

  const authStore = useAuthStore()

  // Restore auth from localStorage on first load
  if (!authStore.checked) {
    await authStore.checkAuth()
  }

  const isAuthenticated = authStore.isAuthenticated
  const userRole = authStore.user?.role

  // Guest-only routes (login, register) - redirect if already logged in
  if (to.meta.guestOnly && isAuthenticated) {
    return next(userRole === 'admin' ? '/admin' : '/portal')
  }

  // Protected routes
  if (to.meta.requiresAuth) {
    if (!isAuthenticated) {
      return next({ name: 'login', query: { redirect: to.fullPath } })
    }

    // Pending or unverified
    if (authStore.user?.status === 'pending') {
      return next({ name: 'pending-approval' })
    }

    // Role mismatch
    if (to.meta.role && to.meta.role !== userRole) {
      return next(userRole === 'admin' ? '/admin' : '/portal')
    }

    if (to.meta.superAdmin && !authStore.user?.is_super_admin) {
      return next('/admin')
    }
  }

  next()
})

export default router