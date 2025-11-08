<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import { buildAdminAuthHeader, clearAdminToken, getAdminToken } from '@/utils/adminAuth'

const router = useRouter()

const apiBaseUrl = import.meta.env.VITE_API_BASE_URL?.replace(/\/$/, '') ?? '/api'

const loading = ref(false)
const error = ref('')
const analytics = ref(null)

function redirectToLogin() {
  router.replace({
    name: 'admin-login',
    query: { redirect: '/admin/dashboard' }
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

async function fetchAnalytics() {
  const token = ensureToken()

  if (!token) {
    analytics.value = null
    return
  }

  loading.value = true
  error.value = ''

  try {
    const { data } = await axios.get(`${apiBaseUrl}/admin/analytics`, {
      headers: buildAdminAuthHeader()
    })
    analytics.value = data
  } catch (err) {
    if (err.response?.status === 401) {
      handleUnauthorized()
      return
    }
    error.value = err.response?.data?.message ?? 'Unable to load analytics right now.'
  } finally {
    loading.value = false
  }
}

function logout() {
  clearAdminToken()
  analytics.value = null
  router.replace({ name: 'admin-login' })
}

const highlightCards = computed(() => {
  if (!analytics.value) return []

  const cards = analytics.value.cards

  return [
    {
      label: 'Total revenue',
      value: `₹ ${Number(cards.total_revenue || 0).toLocaleString('en-IN')}`,
    },
    {
      label: 'Orders',
      value: cards.total_orders,
      sublabel: `${cards.pending_orders} pending`
    },
    {
      label: 'Unique patrons',
      value: cards.unique_customers,
    },
    {
      label: 'Active carts',
      value: cards.active_carts,
    },
    {
      label: 'Tour registrations',
      value: cards.total_tour_registrations,
      sublabel: `${cards.upcoming_tours} upcoming`
    }
  ]
})

onMounted(() => {
  fetchAnalytics()
})
</script>

<template>
  <section class="dashboard">
    <header class="page-header">
      <div>
        <p class="eyebrow">Vyom Heritage – Admin</p>
        <h1>Analytics Dashboard</h1>
      </div>

      <div class="actions">
        <button class="ghost-btn" type="button" @click="fetchAnalytics" :disabled="loading">
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

    <div v-if="error" class="error-banner">{{ error }}</div>

    <div v-else-if="loading && !analytics" class="loading">Aggregating insights…</div>

    <div v-else-if="analytics" class="dashboard-grid">
      <section class="metrics">
        <article v-for="card in highlightCards" :key="card.label" class="metric-card">
          <span class="metric-label">{{ card.label }}</span>
          <strong class="metric-value">{{ card.value }}</strong>
          <span v-if="card.sublabel" class="metric-sub">{{ card.sublabel }}</span>
        </article>
      </section>

      <section class="panel">
        <header>
          <h2>Top products</h2>
        </header>
        <table v-if="analytics.top_products.length" class="data-table">
          <thead>
            <tr>
              <th>Product</th>
              <th>Units sold</th>
              <th>Sales</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="product in analytics.top_products" :key="product.product_id ?? product.product_name">
              <td>{{ product.product_name }}</td>
              <td>{{ product.total_quantity }}</td>
              <td>₹ {{ Number(product.total_sales).toLocaleString('en-IN') }}</td>
            </tr>
          </tbody>
        </table>
        <p v-else class="empty-state">Start selling items to populate this list.</p>
      </section>

      <section class="panel">
        <header>
          <h2>Recent orders</h2>
        </header>
        <ul v-if="analytics.recent_orders.length" class="activity-list">
          <li v-for="order in analytics.recent_orders" :key="order.order_number" class="activity-item">
            <div>
              <strong>{{ order.order_number }}</strong>
              <p>{{ order.customer_name }} · {{ order.email }}</p>
            </div>
            <div class="activity-meta">
              <span class="status" :data-status="order.status">{{ order.status }}</span>
              <span>₹ {{ Number(order.grand_total).toLocaleString('en-IN') }}</span>
              <time>{{ new Date(order.created_at).toLocaleDateString('en-IN', { day: '2-digit', month: 'short' }) }}</time>
            </div>
          </li>
        </ul>
        <p v-else class="empty-state">Orders will appear here as guests check out.</p>
      </section>
    </div>
  </section>
</template>

<style scoped>
.dashboard {
  display: flex;
  flex-direction: column;
  gap: 2.5rem;
  color: var(--vh-text-primary);
}

.dashboard,
.dashboard * {
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

.dashboard h1,
.dashboard h2,
.dashboard h3 {
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
  padding: clamp(1.5rem, 4vw, 2.4rem);
  box-shadow: var(--vh-shadow-strong);
}

.eyebrow {
  text-transform: uppercase;
  letter-spacing: 0.18em;
  color: var(--vh-text-muted);
  font-size: 0.8rem;
}

.page-header h1 {
  margin: 0.4rem 0 0;
  font-family: 'Playfair Display', serif;
  font-size: clamp(2rem, 4vw, 2.8rem);
}

.actions {
  display: flex;
  gap: 0.75rem;
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

.dashboard-grid {
  display: grid;
  gap: 2rem;
}

.metrics {
  display: grid;
  gap: 1.5rem;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
}

.metric-card {
  background: var(--vh-surface-200);
  border-radius: 1.5rem;
  padding: 1.4rem;
  border: 1px solid var(--vh-border-soft);
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
  box-shadow: var(--vh-shadow-soft);
}

.metric-label {
  text-transform: uppercase;
  letter-spacing: 0.18em;
  font-size: 0.7rem;
  color: var(--vh-text-subtle);
}

.metric-value {
  font-size: 1.45rem;
  font-weight: 700;
  color: var(--vh-text-primary);
}

.metric-sub {
  font-size: 0.85rem;
  color: var(--vh-text-muted);
}

.panel {
  background: var(--vh-surface-100);
  border-radius: 1.75rem;
  border: 1px solid var(--vh-border-soft);
  padding: clamp(1.5rem, 4vw, 2.2rem);
  box-shadow: var(--vh-shadow-soft);
}

.panel header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1.2rem;
}

.panel h2 {
  font-size: 1.3rem;
  margin: 0;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
}

.data-table th,
.data-table td {
  padding: 0.75rem 0.5rem;
  border-bottom: 1px solid var(--vh-border-soft);
  text-align: left;
}

.data-table th {
  text-transform: uppercase;
  font-size: 0.75rem;
  letter-spacing: 0.12em;
  color: var(--vh-text-subtle);
}

.activity-list {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 1.1rem;
}

.activity-item {
  display: flex;
  justify-content: space-between;
  gap: 1.5rem;
  border-bottom: 1px solid var(--vh-border-soft);
  padding-bottom: 0.75rem;
}

.activity-item strong {
  font-size: 1rem;
}

.activity-item p {
  margin: 0.2rem 0 0;
  color: var(--vh-text-muted);
  font-size: 0.85rem;
}

.activity-meta {
  display: flex;
  align-items: center;
  gap: 1rem;
  font-size: 0.85rem;
  color: var(--vh-text-muted);
}

.status[data-status='pending'] {
  color: var(--vh-accent-warm);
}

.status[data-status='completed'],
.status[data-status='fulfilled'] {
  color: #86d5b8;
}

.empty-state {
  color: var(--vh-text-muted);
  font-size: 0.9rem;
  padding: 0.75rem 0;
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

@media (max-width: 720px) {
  .page-header {
    flex-direction: column;
    align-items: flex-start;
  }

  .activity-item {
    flex-direction: column;
    align-items: flex-start;
  }

  .activity-meta {
    flex-wrap: wrap;
  }
}
</style>

