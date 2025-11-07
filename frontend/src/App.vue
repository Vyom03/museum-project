<script setup>
import { computed, onMounted } from 'vue'
import { RouterLink, RouterView, useRoute } from 'vue-router'
import { useCartStore } from '@/stores/cart'

const route = useRoute()
const cartStore = useCartStore()

const navigation = [
  { name: 'Shop', path: '/shop' },
  { name: 'About', path: '/about' },
  { name: 'Book a Tour', path: '/tours' },
  { name: 'Admin', path: '/admin/dashboard' }
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
  background: linear-gradient(180deg, #050a18 0%, #0a1530 45%, #101d3e 100%);
  display: flex;
  flex-direction: column;
  color: #e0e6ff;
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
  background: rgba(7, 12, 28, 0.9);
  backdrop-filter: blur(16px);
  border-bottom: 1px solid rgba(92, 112, 181, 0.35);
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
  color: #f0f3ff;
}

.brand-link:hover {
  color: #8aa1ff;
}

.brand-tagline {
  font-size: 0.85rem;
  color: rgba(214, 220, 255, 0.6);
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
  color: rgba(225, 229, 255, 0.65);
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
  background: linear-gradient(90deg, #7c8dff, #ffaf7b);
  border-radius: 999px;
  transform: scaleX(0);
  transform-origin: left;
  transition: transform 0.2s ease;
}

.nav-link:hover,
.nav-link.is-active {
  color: #8aa1ff;
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
  background: rgba(37, 52, 102, 0.7);
  border: 1px solid rgba(122, 145, 255, 0.3);
  display: grid;
  place-items: center;
  color: #dfe4ff;
  text-decoration: none;
  transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
}

.cart-button:hover {
  transform: translateY(-2px);
  background: rgba(84, 107, 201, 0.7);
  box-shadow: 0 12px 24px rgba(84, 107, 201, 0.35);
  color: #f0f3ff;
}

.cart-button svg {
  width: 24px;
  height: 24px;
}

.cart-count {
  position: absolute;
  top: -6px;
  right: -6px;
  background: linear-gradient(135deg, #ff8a6c, #ffbd78);
  color: #0d142b;
  padding: 0.2rem 0.45rem;
  border-radius: 999px;
  font-size: 0.7rem;
  font-weight: 700;
  box-shadow: 0 6px 12px rgba(255, 138, 108, 0.4);
}

.site-main {
  flex: 1;
  padding: clamp(1.5rem, 5vw, 4rem);
  position: relative;
  z-index: 1;
}

.site-footer {
  padding: 1.5rem clamp(1.5rem, 5vw, 4rem);
  background: rgba(7, 12, 28, 0.9);
  border-top: 1px solid rgba(92, 112, 181, 0.3);
  color: rgba(214, 220, 255, 0.65);
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
  color: #2c3e87;
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
