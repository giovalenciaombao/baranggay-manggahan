<template>
  <div>

    <!-- ── Hero ─────────────────────────────────────────────────────────────── -->
    <section class="bg-primary-dark px-4 sm:px-6 pt-16 pb-20 text-center">
      <div class="inline-flex items-center gap-1.5 bg-primary/30 text-green-200 text-xs font-medium px-3.5 py-1.5 rounded-full border border-primary/40 mb-6">
        <MapPinIcon class="w-3.5 h-3.5" />
        Barangay Manggahan, Quezon City
      </div>

      <h1 class="font-display text-3xl sm:text-4xl lg:text-5xl font-semibold text-primary-light leading-tight max-w-xl mx-auto mb-5">
        Your barangay services, online and at your fingertips
      </h1>

      <p class="text-primary-light/60 text-sm sm:text-base max-w-md mx-auto mb-8 leading-relaxed">
        File incident reports, book appointments, and stay updated with community alerts — all in one portal.
      </p>

      <div class="flex items-center justify-center gap-3 flex-wrap">
        <RouterLink to="/register" class="btn-primary gap-2">
          <UserPlusIcon class="w-4 h-4" />
          Register as resident
        </RouterLink>
        <a href="#services" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm font-medium border border-primary-light/25 text-primary-light hover:bg-white/10 transition-colors">
          Learn more
          <ChevronDownIcon class="w-4 h-4" />
        </a>
      </div>

      <!-- Stats -->
      <div class="flex items-center justify-center gap-8 sm:gap-14 mt-14 flex-wrap">
        <div v-for="(stat, i) in stats" :key="i" class="text-center">
          <p class="font-display text-2xl font-semibold text-primary-light">{{ stat.value }}</p>
          <p class="text-xs text-primary-light/40 mt-0.5">{{ stat.label }}</p>
        </div>
      </div>
    </section>

    <!-- ── Services ──────────────────────────────────────────────────────────── -->
    <section id="services" class="bg-primary-light/60 px-4 sm:px-6 py-16">
      <div class="max-w-4xl mx-auto">
        <p class="text-center text-xs font-medium text-primary uppercase tracking-widest mb-2">What you can do</p>
        <h2 class="font-display text-2xl font-semibold text-primary-dark text-center mb-10">Services available online</h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          <div
            v-for="service in services"
            :key="service.label"
            class="card hover:border-primary/30 transition-colors duration-200"
          >
            <div class="w-11 h-11 rounded-xl bg-green-50 flex items-center justify-center mb-4">
              <component :is="service.icon" class="w-5 h-5 text-primary" />
            </div>
            <p class="font-medium text-primary-dark text-sm mb-1">{{ service.label }}</p>
            <p class="text-xs text-gray-500 leading-relaxed">{{ service.desc }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- ── How it works ──────────────────────────────────────────────────────── -->
    <section class="bg-white px-4 sm:px-6 py-16">
      <div class="max-w-lg mx-auto">
        <p class="text-center text-xs font-medium text-primary uppercase tracking-widest mb-2">Getting started</p>
        <h2 class="font-display text-2xl font-semibold text-primary-dark text-center mb-10">How it works</h2>

        <div class="flex flex-col gap-6">
          <div v-for="step in steps" :key="step.num" class="flex items-start gap-4">
            <div class="w-9 h-9 rounded-full bg-primary flex items-center justify-center flex-shrink-0 mt-0.5">
              <span class="text-primary-light text-sm font-medium">{{ step.num }}</span>
            </div>
            <div>
              <p class="font-medium text-primary-dark text-sm mb-1">{{ step.title }}</p>
              <p class="text-xs text-gray-500 leading-relaxed">{{ step.desc }}</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- ── CTA ───────────────────────────────────────────────────────────────── -->
    <section class="bg-primary-dark px-4 sm:px-6 py-16 text-center">
      <h2 class="font-display text-2xl font-semibold text-primary-light mb-3">Ready to get started?</h2>
      <p class="text-primary-light/50 text-sm mb-8">Create your free resident account today.</p>
      <RouterLink to="/register" class="btn-primary gap-2">
        <UserPlusIcon class="w-4 h-4" />
        Create account
      </RouterLink>
    </section>

  </div>
</template>

<script setup>
import { RouterLink } from 'vue-router'
import {
  MapPinIcon,
  UserPlusIcon,
  ChevronDownIcon,
  ExclamationTriangleIcon,
  CalendarDaysIcon,
  BellIcon,
  ArrowPathIcon,
} from '@heroicons/vue/24/outline'

const stats = [
  { value: '5',         label: 'Online services' },
  { value: '24/7',      label: 'Portal access'   },
  { value: 'Real-time', label: 'Notifications'   },
]

const services = [
  {
    icon: ExclamationTriangleIcon,
    label: 'Incident reports',
    desc: 'File and track reports with photo and location details.',
  },
  {
    icon: CalendarDaysIcon,
    label: 'Appointments',
    desc: 'Book slots with barangay officials online.',
  },
  {
    icon: BellIcon,
    label: 'Community alerts',
    desc: 'Get notified on announcements instantly.',
  },
  {
    icon: ArrowPathIcon,
    label: 'Waste concerns',
    desc: 'Log sanitation concerns for admin action.',
  },
]

const steps = [
  {
    num: 1,
    title: 'Register your account',
    desc: 'Fill in your basic info. The barangay admin reviews and approves your account.',
  },
  {
    num: 2,
    title: 'Verify your email',
    desc: 'Check your inbox and click the verification link we send you.',
  },
  {
    num: 3,
    title: 'Access your portal',
    desc: 'Once approved, log in and start using all barangay services online.',
  },
]
</script>
