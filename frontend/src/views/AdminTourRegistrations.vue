<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import { buildAdminAuthHeader, clearAdminToken, getAdminToken } from '@/utils/adminAuth'

const router = useRouter()

const apiBaseUrl = import.meta.env.VITE_API_BASE_URL?.replace(/\/$/, '') ?? '/api'

const loading = ref(false)
const error = ref('')
const registrations = ref([])
const meta = ref(null)

const selectedDate = ref('')
const searchTerm = ref('')

function redirectToLogin() {
  router.replace({
    name: 'admin-login',
    query: { redirect: '/admin/tour-registrations' }
  })
}

function ensureToken() {
  const token = getAdminToken()

  if (!token) {
    redirectToLogin()
    return null
  }

  return token
}

function handleUnauthorized() {
  clearAdminToken()
  redirectToLogin()
}

const availableDates = computed(() => meta.value?.available_dates ?? [])

const filteredGroups = computed(() => {
  const term = searchTerm.value.trim().toLowerCase()
  const onlyDate = selectedDate.value

  return registrations.value
    .filter(group => !onlyDate || group.date === onlyDate)
    .map(group => {
      const slots = group.slots
        .map(slot => {
          const filteredRegistrations = slot.registrations.filter(registration => {
            if (!term) return true

            const haystack = [
              registration.contact_name,
              registration.email,
              registration.organisation,
              registration.phone,
              registration.country_code
            ]
              .filter(Boolean)
              .join(' ')
              .toLowerCase()

            return haystack.includes(term)
          })

          const totalVisitors = filteredRegistrations.reduce(
            (sum, registration) => sum + (registration.visitors_count ?? 0),
            0
          )

          return {
            ...slot,
            registrations: filteredRegistrations,
            filteredVisitors: totalVisitors,
            filteredCount: filteredRegistrations.length
          }
        })
        .filter(slot => (term ? slot.filteredCount > 0 : true))

      const totalVisitors = slots.reduce((sum, slot) => sum + slot.filteredVisitors, 0)
      const totalRegistrations = slots.reduce((sum, slot) => sum + slot.filteredCount, 0)

      return {
        ...group,
        slots,
        filteredVisitors: totalVisitors,
        filteredRegistrations: totalRegistrations
      }
    })
    .filter(group => {
      if (term) {
        return group.filteredRegistrations > 0
      }

      return true
    })
})

const totalFilteredVisitors = computed(() =>
  filteredGroups.value.reduce((sum, group) => sum + group.filteredVisitors, 0)
)

const totalFilteredRegistrations = computed(() =>
  filteredGroups.value.reduce((sum, group) => sum + group.filteredRegistrations, 0)
)

async function fetchRegistrations() {
  const token = ensureToken()

  if (!token) {
    registrations.value = []
    return
  }

  loading.value = true
  error.value = ''

  try {
    const params = {}

    if (selectedDate.value) {
      params.date = selectedDate.value
    }

    const { data } = await axios.get(`${apiBaseUrl}/admin/tour-registrations`, {
      params,
      headers: buildAdminAuthHeader()
    })

    registrations.value = data.data
    meta.value = data.meta
  } catch (err) {
    if (err.response?.status === 401) {
      handleUnauthorized()
      return
    }

    error.value = err.response?.data?.message ?? 'Unable to load registrations right now.'
  } finally {
    loading.value = false
  }
}

function logout() {
  clearAdminToken()
  registrations.value = []
  router.replace({ name: 'admin-login' })
}

watch(meta, () => {
  if (!selectedDate.value) return

  const exists = (meta.value?.available_dates ?? []).some(date => date.value === selectedDate.value)
  if (!exists) {
    selectedDate.value = ''
  }
})

onMounted(() => {
  fetchRegistrations()
})

function slotStatusColour(slot) {
  if (slot.capacity === null || slot.capacity === 0) {
    return 'status-open'
  }

  const fillRatio = slot.booked_visitors / slot.capacity

  if (fillRatio >= 1) return 'status-full'
  if (fillRatio >= 0.75) return 'status-high'
  return 'status-open'
}
</script>

<template>
  <section class="admin-page">
    <header class="page-header">
      <div>
        <p class="eyebrow">Vyom Heritage – Admin</p>
        <h1>Tour Registrations</h1>
        <p class="lead">Review visitor bookings by date and monitor slot capacity.</p>
      </div>

      <div class="actions">
        <button class="ghost-btn" type="button" @click="fetchRegistrations" :disabled="loading">
          {{ loading ? 'Refreshing…' : 'Refresh data' }}
        </button>
        <button class="ghost-btn" type="button" @click="logout">
          Sign out
        </button>
      </div>
    </header>

    <nav class="admin-nav">
      <RouterLink class="admin-nav-link" :to="{ name: 'admin-dashboard' }">Analytics</RouterLink>
      <RouterLink class="admin-nav-link" :to="{ name: 'admin-tour-registrations' }">Tour Registrations</RouterLink>
    </nav>

    <section class="filters">
      <label class="filter-field">
        <span>Date</span>
        <select v-model="selectedDate" @change="fetchRegistrations">
          <option value="">All upcoming dates</option>
          <option v-for="date in availableDates" :key="date.value" :value="date.value">
            {{ date.label }}
          </option>
        </select>
      </label>
      <label class="filter-field grow">
        <span>Search</span>
        <input
          v-model.trim="searchTerm"
          type="search"
          placeholder="Search by guest name, email, organisation, or phone"
        />
      </label>
    </section>

    <section v-if="meta" class="summary">
      <article class="summary-card">
        <span class="label">Days with bookings</span>
        <strong class="value">{{ meta.total_days }}</strong>
      </article>
      <article class="summary-card">
        <span class="label">Registrations</span>
        <strong class="value">{{ meta.total_registrations }}</strong>
        <span class="sub">{{ totalFilteredRegistrations }} showing</span>
      </article>
      <article class="summary-card">
        <span class="label">Visitors</span>
        <strong class="value">{{ meta.total_visitors }}</strong>
        <span class="sub">{{ totalFilteredVisitors }} showing</span>
      </article>
    </section>

    <div v-if="error" class="error-banner">{{ error }}</div>
    <div v-else-if="loading && !registrations.length" class="loading">Loading registrations…</div>

    <div v-else-if="filteredGroups.length" class="date-groups">
      <section v-for="group in filteredGroups" :key="group.date" class="date-group">
        <header class="group-header">
          <div>
            <h2>{{ group.readable_date }}</h2>
            <p class="group-stats">
              {{ group.filteredRegistrations }} registrations · {{ group.filteredVisitors }} visitors
            </p>
          </div>
        </header>

        <div class="slot-grid">
          <article v-for="slot in group.slots" :key="slot.slot" class="slot-card">
            <header class="slot-header">
              <div>
                <h3>{{ slot.label }}</h3>
                <p>
                  <template v-if="slot.capacity !== null">
                    {{ slot.booked_visitors }} / {{ slot.capacity }} visitors booked
                  </template>
                  <template v-else>
                    {{ slot.booked_visitors }} visitors booked
                  </template>
                </p>
              </div>
              <span class="slot-status" :class="slotStatusColour(slot)">
                <template v-if="slot.capacity === null">
                  Open
                </template>
                <template v-else-if="slot.remaining_capacity > 0">
                  {{ slot.remaining_capacity }} slots left
                </template>
                <template v-else>Full</template>
              </span>
            </header>

            <ul class="registration-list">
              <li v-for="registration in slot.registrations" :key="registration.id" class="registration-item">
                <div class="registration-main">
                  <strong>{{ registration.contact_name }}</strong>
                  <span class="meta">{{ registration.visitors_count }} visitors</span>
                </div>
                <div class="registration-meta">
                  <p v-if="registration.organisation" class="organisation">{{ registration.organisation }}</p>
                  <p class="contact">
                    <span>{{ registration.email }}</span>
                    <span v-if="registration.phone"> · {{ registration.country_code ? '+' + registration.country_code + ' ' : '' }}{{ registration.phone }}</span>
                  </p>
                  <p class="submitted">Submitted {{ registration.submitted_at }}</p>
                  <p v-if="registration.needs_guided_tour" class="badge guided">Guided tour requested</p>
                  <p v-if="registration.notes" class="notes">Notes: {{ registration.notes }}</p>
                </div>
              </li>
            </ul>

            <p v-if="!slot.registrations.length" class="empty-slot">No registrations match this filter.</p>
          </article>
        </div>
      </section>
    </div>

    <p v-else class="empty-state">No registrations match the current filters.</p>
  </section>
</template>

<style scoped>
.admin-page {
  display: flex;
  flex-direction: column;
  gap: 2.2rem;
  color: var(--vh-text-primary);
}

.admin-page,
.admin-page * {
  font-family:
    'Open Sans',
    'Kanit',
    'Segoe UI',
    system-ui,
    -apple-system,
    BlinkMacSystemFont,
    Roboto,
    sans-serif;
}

.admin-page h1,
.admin-page h2,
.admin-page h3 {
  font-family: 'Playfair Display', serif;
}

.page-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  background: var(--vh-surface-100);
  border-radius: 1.75rem;
  border: 1px solid var(--vh-border-strong);
  padding: clamp(1.6rem, 4vw, 2.4rem);
  box-shadow: var(--vh-shadow-strong);
}

.lead {
  margin-top: 0.6rem;
  color: var(--vh-text-secondary);
}

.actions {
  display: flex;
  gap: 0.8rem;
}

.ghost-btn {
  background: var(--vh-button-ghost-bg);
  color: var(--vh-text-secondary);
  border: 1px solid var(--vh-border-soft);
  border-radius: 999px;
  padding: 0.8rem 1.6rem;
  font-weight: 600;
  transition: background 0.2s ease, transform 0.2s ease;
}

.ghost-btn:hover {
  background: var(--vh-button-ghost-hover-bg);
  transform: translateY(-1px);
}

.admin-nav {
  display: inline-flex;
  gap: 0.75rem;
  padding: 0.4rem;
  border-radius: 999px;
  background: var(--vh-surface-muted);
  border: 1px solid var(--vh-border-soft);
  width: fit-content;
}

.admin-nav-link {
  padding: 0.55rem 1.4rem;
  border-radius: 999px;
  text-decoration: none;
  color: var(--vh-text-muted);
  font-weight: 600;
  transition: background 0.2s ease, color 0.2s ease;
}

.admin-nav-link.router-link-active {
  background: var(--vh-button-primary);
  color: var(--vh-text-primary);
}

.filters {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
  background: var(--vh-surface-100);
  border-radius: 1.75rem;
  border: 1px solid var(--vh-border-soft);
  padding: 1.3rem 1.6rem;
}

.filter-field {
  display: flex;
  flex-direction: column;
  gap: 0.45rem;
  min-width: 220px;
}

.filter-field span {
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.16em;
  color: var(--vh-text-subtle);
}

.filter-field select,
.filter-field input {
  border-radius: 0.9rem;
  border: 1px solid var(--vh-border-soft);
  background: var(--vh-surface-200);
  color: var(--vh-text-primary);
  padding: 0.85rem 1rem;
  font-size: 0.95rem;
  outline: none;
}

.filter-field input::placeholder {
  color: var(--vh-text-subtle);
}

.filter-field input:focus,
.filter-field select:focus {
  border-color: var(--vh-accent-warm);
  box-shadow: var(--vh-focus-ring);
}

.filter-field.grow {
  flex: 1;
}

.summary {
  display: grid;
  gap: 1.2rem;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
}

.summary-card {
  background: var(--vh-surface-200);
  border-radius: 1.4rem;
  border: 1px solid var(--vh-border-soft);
  padding: 1.2rem;
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
  box-shadow: var(--vh-shadow-soft);
}

.summary-card .label {
  text-transform: uppercase;
  font-size: 0.7rem;
  letter-spacing: 0.16em;
  color: var(--vh-text-subtle);
}

.summary-card .value {
  font-size: 1.5rem;
  color: var(--vh-text-primary);
}

.summary-card .sub {
  font-size: 0.8rem;
  color: var(--vh-text-muted);
}

.error-banner {
  padding: 1rem;
  border-radius: 1rem;
  background: var(--vh-alert-error-bg);
  border: 1px solid var(--vh-alert-error-border);
  color: var(--vh-text-primary);
}

.loading {
  font-weight: 600;
  color: var(--vh-text-muted);
}

.date-groups {
  display: flex;
  flex-direction: column;
  gap: 1.8rem;
}

.date-group {
  background: var(--vh-surface-100);
  border-radius: 1.8rem;
  border: 1px solid var(--vh-border-soft);
  padding: clamp(1.4rem, 3vw, 2rem);
  box-shadow: var(--vh-shadow-soft);
}

.group-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 1.4rem;
}

.group-header h2 {
  margin: 0;
  font-family: 'Playfair Display', serif;
  font-size: clamp(1.6rem, 3vw, 2.2rem);
}

.group-stats {
  margin: 0.35rem 0 0;
  color: var(--vh-text-muted);
}

.slot-grid {
  display: grid;
  gap: 1.4rem;
  grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
}

.slot-card {
  background: var(--vh-surface-200);
  border-radius: 1.4rem;
  border: 1px solid var(--vh-border-soft);
  padding: 1.2rem;
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.slot-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 1rem;
}

.slot-header h3 {
  margin: 0;
  font-size: 1.1rem;
}

.slot-header p {
  margin: 0.35rem 0 0;
  color: var(--vh-text-muted);
}

.slot-status {
  padding: 0.4rem 0.9rem;
  border-radius: 999px;
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.12em;
  font-weight: 600;
}

.slot-status.status-open {
  background: rgba(112, 176, 148, 0.2);
  color: #8ed1b5;
}

.slot-status.status-high {
  background: rgba(197, 167, 121, 0.2);
  color: var(--vh-accent-warm);
}

.slot-status.status-full {
  background: rgba(205, 92, 92, 0.2);
  color: #f2b5b5;
}

.registration-list {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.registration-item {
  background: var(--vh-surface-300);
  border-radius: 1rem;
  border: 1px solid var(--vh-border-soft);
  padding: 0.9rem 1rem;
  display: flex;
  flex-direction: column;
  gap: 0.45rem;
}

.registration-main {
  display: flex;
  justify-content: space-between;
  gap: 1rem;
  align-items: center;
}

.registration-main strong {
  font-size: 1.05rem;
}

.registration-main .meta {
  font-size: 0.85rem;
  color: var(--vh-text-muted);
}

.registration-meta {
  display: flex;
  flex-direction: column;
  gap: 0.3rem;
  font-size: 0.9rem;
  color: var(--vh-text-muted);
}

.registration-meta .contact span {
  color: var(--vh-text-muted);
}

.registration-meta .organisation {
  font-weight: 600;
  color: var(--vh-text-secondary);
}

.registration-meta .submitted {
  font-size: 0.8rem;
  color: var(--vh-text-subtle);
}

.badge.guided {
  display: inline-block;
  margin-top: 0.4rem;
  background: var(--vh-accent-primary-soft);
  color: var(--vh-text-secondary);
  padding: 0.3rem 0.75rem;
  border-radius: 999px;
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.12em;
  font-weight: 600;
}

.notes {
  font-style: italic;
  color: var(--vh-text-secondary);
}

.empty-slot {
  margin: 0;
  font-size: 0.85rem;
  color: var(--vh-text-muted);
  text-align: center;
  padding: 0.5rem 0;
}

.empty-state {
  text-align: center;
  padding: 2rem;
  border-radius: 1.6rem;
  background: var(--vh-surface-100);
  border: 1px solid var(--vh-border-soft);
  color: var(--vh-text-muted);
}

@media (max-width: 640px) {
  .page-header {
    flex-direction: column;
    align-items: flex-start;
  }

  .actions {
    width: 100%;
    justify-content: flex-start;
    flex-wrap: wrap;
  }

  .registration-main {
    flex-direction: column;
    align-items: flex-start;
  }
}
</style>

