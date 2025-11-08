<script setup>
import { computed, onMounted } from 'vue'
import { RouterLink, RouterView, useRoute } from 'vue-router'
import { useCartStore } from '@/stores/cart'

const route = useRoute()
const cartStore = useCartStore()

const navigation = [
  { name: 'Shop', path: '/shop' },
  { name: 'About', path: '/about' },
  { name: 'Book a Tour', path: '/tours' }
]

const currentYear = new Date().getFullYear()
const cartCount = computed(() => cartStore.totalQuantity)

onMounted(() => {
  cartStore.ensureCart()
})
</script>

<template>
  <div class="app-shell">
    <header class="site-header">
      <div class="site-brand">
        <RouterLink class="brand-link" to="/shop">
          Vyom Heritage Studio
        </RouterLink>
        <span class="brand-tagline">Museum Collection Studio</span>
      </div>

      <nav class="site-nav">
        <RouterLink
          v-for="item in navigation"
          :key="item.path"
          :to="item.path"
          class="nav-link"
          :class="{ 'is-active': route.path.startsWith(item.path) }"
        >
          {{ item.name }}
        </RouterLink>
      </nav>

      <div class="site-actions">
        <RouterLink to="/shop/cart" class="cart-button" aria-label="Open cart">
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <path
              d="M7.5 6h14l-1.6 8.04a2 2 0 0 1-1.96 1.61H10.1a2 2 0 0 1-1.97-1.67L6.5 3.5H3"
              fill="none"
              stroke="#f7f9ff"
              stroke-width="1.7"
              stroke-linecap="round"
              stroke-linejoin="round"
            />
            <circle cx="11" cy="20" r="1.6" fill="#f7f9ff" />
            <circle cx="18" cy="20" r="1.6" fill="#f7f9ff" />
          </svg>
          <span v-if="cartCount > 0" class="cart-count">{{ cartCount }}</span>
        </RouterLink>
      </div>
    </header>

    <main class="site-main">
      <RouterView />
    </main>

    <footer class="site-footer">
      <div>© {{ currentYear }} Vyom Heritage Foundation</div>
      <div class="footer-links">
        <a href="mailto:concierge@vyomheritage.com">concierge@vyomheritage.com</a>
        <span>•</span>
        <a href="tel:+910000000000">+91 000 000 0000</a>
      </div>
    </footer>
  </div>
</template>

<style scoped>
.app-shell {
  min-height: 100vh;
  background: var(--vh-background-gradient);
  display: flex;
  flex-direction: column;
  color: var(--vh-text-primary);
}

.site-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem clamp(1.5rem, 5vw, 4rem);
  gap: 1.5rem;
  position: sticky;
  top: 0;
  z-index: 5;
  background: var(--vh-surface-overlay);
  backdrop-filter: blur(16px);
  border-bottom: 1px solid var(--vh-border-strong);
}

.site-brand {
  display: flex;
  flex-direction: column;
  gap: 0.3rem;
}

.brand-link {
  text-decoration: none;
  font-size: clamp(1.4rem, 2.6vw, 2rem);
  font-family: 'Playfair Display', serif;
  font-weight: 600;
  color: var(--vh-text-primary);
}

.brand-link:hover {
  color: var(--vh-accent-primary-strong);
}

.brand-tagline {
  font-size: 0.85rem;
  color: var(--vh-text-muted);
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.site-nav {
  display: flex;
  gap: 1.5rem;
  flex-wrap: wrap;
}

.nav-link {
  text-decoration: none;
  font-weight: 600;
  color: var(--vh-text-muted);
  position: relative;
  padding-bottom: 0.25rem;
  transition: color 0.2s ease;
}

.nav-link::after {
  content: '';
  position: absolute;
  left: 0;
  right: 0;
  bottom: 0;
  height: 2px;
  background: linear-gradient(90deg, var(--vh-accent-primary), rgba(197, 167, 121, 0.75));
  border-radius: 999px;
  transform: scaleX(0);
  transform-origin: left;
  transition: transform 0.2s ease;
}

.nav-link:hover,
.nav-link.is-active {
  color: var(--vh-accent-primary-strong);
}

.nav-link.is-active::after,
.nav-link:hover::after {
  transform: scaleX(1);
}

.site-actions {
  display: flex;
  align-items: center;
}

.cart-button {
  position: relative;
  width: 46px;
  height: 46px;
  border-radius: 50%;
  background: var(--vh-surface-muted);
  border: 1px solid var(--vh-border-strong);
  display: grid;
  place-items: center;
  color: var(--vh-text-primary);
  text-decoration: none;
  transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
}

.cart-button:hover {
  transform: translateY(-2px);
  background: var(--vh-accent-primary-soft);
  box-shadow: var(--vh-button-primary-shadow);
  color: var(--vh-text-primary);
}

.cart-button svg {
  width: 24px;
  height: 24px;
}

.cart-count {
  position: absolute;
  top: -6px;
  right: -6px;
  background: var(--vh-badge-surface);
  color: var(--vh-badge-text);
  padding: 0.2rem 0.45rem;
  border-radius: 999px;
  font-size: 0.7rem;
  font-weight: 700;
  box-shadow: 0 6px 12px rgba(0, 0, 0, 0.35);
}

.site-main {
  flex: 1;
  padding: clamp(1.5rem, 5vw, 4rem);
  position: relative;
  z-index: 1;
}

.site-footer {
  padding: 1.5rem clamp(1.5rem, 5vw, 4rem);
  background: var(--vh-surface-overlay);
  border-top: 1px solid var(--vh-border-soft);
  color: var(--vh-text-muted);
  display: flex;
  justify-content: space-between;
  gap: 1rem;
  flex-wrap: wrap;
}

.site-footer a {
  color: inherit;
  text-decoration: none;
}

.site-footer a:hover {
  color: var(--vh-accent-primary);
}

.footer-links {
  display: flex;
  gap: 0.75rem;
  align-items: center;
}

@media (max-width: 720px) {
  .site-header {
    flex-direction: column;
    align-items: flex-start;
  }

  .site-nav {
    width: 100%;
    justify-content: flex-start;
  }

  .site-actions {
    width: 100%;
    justify-content: flex-start;
  }
}
</style>
