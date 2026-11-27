<template>
  <div>
    <section class="barangay-banner text-white">
      <div class="mx-auto grid max-w-7xl items-center gap-10 px-5 py-14 sm:px-8 sm:py-20 md:grid-cols-[1.35fr_1fr] lg:px-12">
        <div>
          <p class="mb-5 flex items-center gap-2 text-sm font-semibold text-amber-200">
            <MapPinIcon class="h-5 w-5" aria-hidden="true" />
            Montalban, Rizal
          </p>
          <h1 class="max-w-xl text-5xl font-semibold leading-none tracking-tight sm:text-6xl lg:text-7xl">
            Barangay<br /> Manggahan
          </h1>
          <p class="mt-6 max-w-md text-base leading-relaxed text-white/[.85] sm:text-lg">
            Book a barangay appointment, report a concern, or check community announcements.
          </p>
          <div class="mt-8 flex flex-wrap gap-3">
            <RouterLink to="/register" class="btn bg-amber-200 text-primary-dark hover:bg-amber-100 focus:ring-amber-200">
              Create resident account
              <ArrowRightIcon class="h-4 w-4" aria-hidden="true" />
            </RouterLink>
            <a href="#services" class="btn border border-white/40 text-white hover:bg-white/10 focus:ring-white">
              Explore services
            </a>
          </div>
        </div>
        <div class="relative hidden aspect-square w-full max-w-xs place-self-center rounded-full border border-white/25 p-5 md:block lg:max-w-sm">
          <div class="grid h-full place-items-center rounded-full border border-amber-200/50 bg-white/10 p-5">
            <img :src="logo" alt="Barangay Manggahan seal" width="960" height="960"
              class="w-full rounded-full bg-white" />
          </div>
        </div>
      </div>
    </section>

    <section id="services" class="mx-auto max-w-7xl scroll-mt-24 px-5 py-12 sm:px-8 sm:py-16 lg:px-12">
      <h2 class="text-3xl font-semibold tracking-tight text-primary-dark sm:text-4xl">Resident services</h2>
      <div class="mt-6 grid gap-4 md:grid-cols-3">
        <article v-for="service in services" :key="service.title" class="card flex flex-col p-6">
          <div class="mb-5 grid h-11 w-11 place-items-center rounded-xl" :class="service.iconStyle">
            <component :is="service.icon" class="h-5 w-5" aria-hidden="true" />
          </div>
          <h3 class="text-xl font-semibold text-primary-dark">{{ service.title }}</h3>
          <p class="mt-2 flex-1 text-sm leading-relaxed text-gray-600">{{ service.description }}</p>
          <RouterLink :to="service.route" class="mt-5 inline-flex w-fit items-center gap-2 rounded-sm text-sm font-semibold text-primary-700 hover:underline focus-visible:ring-2 focus-visible:ring-primary">
            {{ service.action }}
            <ArrowRightIcon class="h-4 w-4" aria-hidden="true" />
          </RouterLink>
        </article>
      </div>
    </section>

    <section class="mx-auto grid max-w-7xl gap-5 px-5 pb-14 sm:px-8 lg:grid-cols-2 lg:px-12">
      <div class="rounded-2xl border border-amber-200 bg-amber-50 p-6 sm:p-8">
        <h2 class="text-2xl font-semibold text-primary-dark">Before you register</h2>
        <p class="mt-3 text-sm leading-relaxed text-gray-700">
          Have a photo of your barangay ID or a copy of a billing statement ready. Barangay staff review your proof of residency before activating your account.
        </p>
        <RouterLink to="/register" class="btn-primary mt-5">Register</RouterLink>
      </div>
      <div class="rounded-2xl border border-primary/15 bg-white p-6 sm:p-8">
        <h2 class="text-2xl font-semibold text-primary-dark">Barangay updates</h2>
        <p class="mt-3 text-sm leading-relaxed text-gray-700">
          Follow Barangay Manggahan for community events, advisories, and public announcements.
        </p>
        <a href="https://www.facebook.com/BarangayManggahanOfficial/" class="btn-secondary mt-5">
          Visit the Facebook page
          <ArrowTopRightOnSquareIcon class="h-4 w-4" aria-hidden="true" />
        </a>
      </div>
    </section>
  </div>
</template>

<script setup>
import { RouterLink } from 'vue-router'
import {
  ArrowRightIcon,
  ArrowTopRightOnSquareIcon,
  BellIcon,
  CalendarDaysIcon,
  ExclamationTriangleIcon,
  MapPinIcon,
} from '@heroicons/vue/24/outline'
import logo from '@/assets/barangay-manggahan-logo.png'
const services = [
  {
    title: 'Book an appointment',
    description: 'Submit your request and receive the next available date and time.',
    icon: CalendarDaysIcon,
    iconStyle: 'bg-green-100 text-green-800',
    route: { name: 'resident-appointments-new' },
    action: 'Book an appointment',
  },
  {
    title: 'Report an incident',
    description: 'Send barangay staff a report with the location, details, and an optional photo.',
    icon: ExclamationTriangleIcon,
    iconStyle: 'bg-amber-100 text-amber-800',
    route: { name: 'resident-incidents-new' },
    action: 'File a report',
  },
  {
    title: 'Read community alerts',
    description: 'Check barangay advisories and announcements in your resident portal.',
    icon: BellIcon,
    iconStyle: 'bg-blue-100 text-blue-800',
    route: { name: 'resident-alerts' },
    action: 'Open alerts',
  },
]
</script>
