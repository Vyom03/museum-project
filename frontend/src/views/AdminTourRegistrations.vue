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

const availableDates = computed(() =>
  registrations.value.map(group => ({
    value: group.date,
    label: group.readable_date
  }))
)

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

watch(registrations, () => {
  if (!selectedDate.value) return

  const exists = registrations.value.some(group => group.date === selectedDate.value)
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
  color: #e2e8ff;
}

.page-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  background: rgba(10, 17, 34, 0.78);
  border-radius: 1.75rem;
  border: 1px solid rgba(120, 144, 255, 0.22);
  padding: clamp(1.6rem, 4vw, 2.4rem);
  box-shadow: 0 24px 48px rgba(5, 10, 26, 0.5);
}

.lead {
  margin-top: 0.6rem;
  color: rgba(224, 229, 255, 0.7);
}

.actions {
  display: flex;
  gap: 0.8rem;
}

.ghost-btn {
  background: rgba(43, 63, 158, 0.12);
  color: #a8baff;
  border: 1px solid rgba(120, 144, 255, 0.3);
  border-radius: 999px;
  padding: 0.8rem 1.6rem;
  font-weight: 600;
  transition: background 0.2s ease, transform 0.2s ease;
}

.ghost-btn:hover {
  background: rgba(43, 63, 158, 0.2);
  transform: translateY(-1px);
}

.admin-nav {
  display: inline-flex;
  gap: 0.75rem;
  padding: 0.4rem;
  border-radius: 999px;
  background: rgba(10, 17, 34, 0.6);
  border: 1px solid rgba(120, 144, 255, 0.22);
  width: fit-content;
}

.admin-nav-link {
  padding: 0.55rem 1.4rem;
  border-radius: 999px;
  text-decoration: none;
  color: rgba(224, 229, 255, 0.75);
  font-weight: 600;
  transition: background 0.2s ease, color 0.2s ease;
}

.admin-nav-link.router-link-active {
  background: linear-gradient(135deg, #4059d6, #6c81ff);
  color: #fff;
}

.filters {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
  background: rgba(10, 17, 34, 0.72);
  border-radius: 1.75rem;
  border: 1px solid rgba(120, 144, 255, 0.2);
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
  color: rgba(224, 229, 255, 0.6);
}

.filter-field select,
.filter-field input {
  border-radius: 0.9rem;
  border: 1px solid rgba(120, 144, 255, 0.35);
  background: rgba(9, 14, 30, 0.82);
  color: #f3f6ff;
  padding: 0.85rem 1rem;
  font-size: 0.95rem;
  outline: none;
}

.filter-field input::placeholder {
  color: rgba(224, 229, 255, 0.45);
}

.filter-field input:focus,
.filter-field select:focus {
  border-color: rgba(255, 176, 91, 0.8);
  box-shadow: 0 0 0 4px rgba(255, 176, 91, 0.18);
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
  background: linear-gradient(135deg, rgba(12, 22, 46, 0.78), rgba(8, 16, 34, 0.7));
  border-radius: 1.4rem;
  border: 1px solid rgba(120, 144, 255, 0.18);
  padding: 1.2rem;
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
  box-shadow: 0 18px 38px rgba(4, 10, 24, 0.45);
}

.summary-card .label {
  text-transform: uppercase;
  font-size: 0.7rem;
  letter-spacing: 0.16em;
  color: rgba(224, 229, 255, 0.55);
}

.summary-card .value {
  font-size: 1.5rem;
}

.summary-card .sub {
  font-size: 0.8rem;
  color: rgba(224, 229, 255, 0.6);
}

.error-banner {
  padding: 1rem;
  border-radius: 1rem;
  background: rgba(255, 124, 124, 0.15);
  border: 1px solid rgba(255, 124, 124, 0.3);
  color: #ffd8d8;
}

.loading {
  font-weight: 600;
  color: rgba(224, 229, 255, 0.6);
}

.date-groups {
  display: flex;
  flex-direction: column;
  gap: 1.8rem;
}

.date-group {
  background: rgba(10, 17, 34, 0.74);
  border-radius: 1.8rem;
  border: 1px solid rgba(120, 144, 255, 0.2);
  padding: clamp(1.4rem, 3vw, 2rem);
  box-shadow: 0 20px 44px rgba(4, 10, 24, 0.42);
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
  color: rgba(224, 229, 255, 0.65);
}

.slot-grid {
  display: grid;
  gap: 1.4rem;
  grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
}

.slot-card {
  background: rgba(12, 20, 44, 0.82);
  border-radius: 1.4rem;
  border: 1px solid rgba(120, 144, 255, 0.25);
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
  color: rgba(224, 229, 255, 0.6);
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
  background: rgba(78, 201, 164, 0.18);
  color: #9ef0d0;
}

.slot-status.status-high {
  background: rgba(255, 193, 111, 0.22);
  color: #ffddaa;
}

.slot-status.status-full {
  background: rgba(255, 122, 122, 0.22);
  color: #ffc2c2;
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
  background: rgba(10, 17, 34, 0.7);
  border-radius: 1rem;
  border: 1px solid rgba(120, 144, 255, 0.18);
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
  color: rgba(224, 229, 255, 0.7);
}

.registration-meta {
  display: flex;
  flex-direction: column;
  gap: 0.3rem;
  font-size: 0.9rem;
  color: rgba(224, 229, 255, 0.7);
}

.registration-meta .contact span {
  color: rgba(224, 229, 255, 0.65);
}

.registration-meta .organisation {
  font-weight: 600;
}

.registration-meta .submitted {
  font-size: 0.8rem;
  color: rgba(224, 229, 255, 0.5);
}

.badge.guided {
  display: inline-block;
  margin-top: 0.4rem;
  background: rgba(120, 144, 255, 0.22);
  color: #dbe2ff;
  padding: 0.3rem 0.75rem;
  border-radius: 999px;
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.12em;
  font-weight: 600;
}

.notes {
  font-style: italic;
  color: rgba(224, 229, 255, 0.7);
}

.empty-slot {
  margin: 0;
  font-size: 0.85rem;
  color: rgba(224, 229, 255, 0.55);
  text-align: center;
  padding: 0.5rem 0;
}

.empty-state {
  text-align: center;
  padding: 2rem;
  border-radius: 1.6rem;
  background: rgba(12, 20, 44, 0.8);
  border: 1px solid rgba(120, 144, 255, 0.2);
  color: rgba(224, 229, 255, 0.65);
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

