<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useCartStore } from '@/stores/cart'
import axios from 'axios'
import { useRouter } from 'vue-router'

const router = useRouter()
const cartStore = useCartStore()

const apiBaseUrl = import.meta.env.VITE_API_BASE_URL?.replace(/\/$/, '') ?? '/api'

const loading = ref(false)
const error = ref('')
const products = ref([])
const searchTerm = ref('')
const highlightIndex = ref(0)

let highlightTimer = null

const highlightedProducts = computed(() => {
  if (!products.value.length) return []
  const featured = products.value.filter(product => product.is_featured)
  return featured.length ? featured : products.value.slice(0, 6)
})

const currentHighlight = computed(() => {
  const list = highlightedProducts.value
  if (!list.length) return null
  return list[highlightIndex.value % list.length]
})

const filteredProducts = computed(() => {
  if (!searchTerm.value) {
    return products.value
  }

  const term = searchTerm.value.toLowerCase()
  return products.value.filter(product =>
    product.name.toLowerCase().includes(term) ||
    (product.summary ?? '').toLowerCase().includes(term) ||
    (product.sku ?? '').toLowerCase().includes(term)
  )
})

async function loadProducts() {
  loading.value = true
  error.value = ''

  try {
    const { data } = await axios.get(`${apiBaseUrl}/shop/products`, {
      params: { per_page: 24 }
    })

    products.value = data.data
  } catch (err) {
    error.value = err.response?.data?.message ?? 'Unable to load the collection right now.'
  } finally {
    loading.value = false
  }
}

function startHighlightRotation() {
  clearHighlightRotation()

  if (highlightedProducts.value.length <= 1) return

  highlightTimer = setInterval(() => {
    highlightIndex.value = (highlightIndex.value + 1) % highlightedProducts.value.length
  }, 5500)
}

function clearHighlightRotation() {
  if (highlightTimer) {
    clearInterval(highlightTimer)
    highlightTimer = null
  }
}

async function addToCart(productId) {
  try {
    await cartStore.addItem(productId, 1)
  } catch (err) {
    // error state already captured in store
  }
}

function goToProduct(product) {
  router.push({ name: 'product-detail', params: { slug: product.slug } })
}

function openHighlight(product) {
  if (!product) return
  goToProduct(product)
}

onMounted(() => {
  loadProducts()
  cartStore.ensureCart()
  startHighlightRotation()
})

watch(highlightedProducts, () => {
  highlightIndex.value = 0
  startHighlightRotation()
})

onBeforeUnmount(() => {
  clearHighlightRotation()
})
</script>

<template>
  <div class="shop-page">
    <section class="shop-hero">
      <div class="hero-copy">
        <h1>Vyom Heritage Museum Store</h1>
        <p>
          Acquire limited-edition textiles, archival prints, and objects curated from the Vyom Heritage collection.
          Each piece is authenticated by our conservation team and ships with a provenance card.
        </p>
        <div class="hero-actions">
          <input v-model="searchTerm" class="search-input" type="search" placeholder="Search the collection" />
          <button class="secondary-btn" type="button" @click="router.push('/tours')">
            Book a Gallery Tour
          </button>
        </div>
      </div>
      <transition name="highlight" mode="out-in">
        <div class="hero-highlight" v-if="currentHighlight" :key="currentHighlight.id">
          <div class="highlight-image" @click="openHighlight(currentHighlight)">
            <img
              v-if="currentHighlight.images?.length"
              :src="currentHighlight.images[0].file_url"
              :alt="currentHighlight.images[0].alt_text ?? currentHighlight.name"
            />
            <div v-else class="image-placeholder">Vyom Collection</div>
            <span v-if="currentHighlight.is_featured" class="badge">Curator's Pick</span>
          </div>
          <div class="highlight-meta">
            <h2 @click="openHighlight(currentHighlight)">{{ currentHighlight.name }}</h2>
            <p>{{ currentHighlight.summary }}</p>
            <div class="highlight-price">
              <span>₹ {{ Number(currentHighlight.price).toLocaleString('en-IN') }}</span>
            </div>
            <div class="highlight-actions">
              <button class="primary-btn" type="button" @click="openHighlight(currentHighlight)">
                View Piece
              </button>
              <button class="ghost-btn" type="button" @click="addToCart(currentHighlight.id)">
                Add to Cart
              </button>
            </div>
          </div>
        </div>
      </transition>
    </section>

    <section class="collection-section">
      <header class="section-header">
        <h2>Featured Objects</h2>
        <p>Handpicked from our textile archives and seasonal collaborations.</p>
      </header>

      <div v-if="error" class="error-banner">{{ error }}</div>

      <div v-if="loading" class="loading">Loading the studio collection…</div>

      <div v-else class="product-grid">
        <article v-for="product in filteredProducts" :key="product.id" class="product-card">
          <div class="product-image" @click="goToProduct(product)">
            <img
              v-if="product.images?.length"
              :src="product.images[0].file_url"
              :alt="product.images[0].alt_text ?? product.name"
            />
            <div v-else class="image-placeholder">Vyom Collection</div>
            <span v-if="product.is_featured" class="badge">Curator's Pick</span>
          </div>

          <div class="product-body">
            <h3 @click="goToProduct(product)">{{ product.name }}</h3>
            <p class="product-summary">{{ product.summary ?? 'Discover more in the details.' }}</p>
            <div class="product-price">
              <span class="current">₹ {{ Number(product.price).toLocaleString('en-IN') }}</span>
              <span v-if="product.compare_at_price" class="compare">
                ₹ {{ Number(product.compare_at_price).toLocaleString('en-IN') }}
              </span>
            </div>
          </div>

          <div class="product-actions">
            <button class="primary-btn" type="button" @click="goToProduct(product)">
              View Details
            </button>
            <button class="ghost-btn" type="button" @click="addToCart(product.id)">
              Add to Cart
            </button>
          </div>
        </article>
      </div>

      <div v-if="!loading && filteredProducts.length === 0" class="empty-state">
        Nothing matched “{{ searchTerm }}”. Try a different phrase or explore upcoming drops.
      </div>
    </section>
  </div>
</template>

<style scoped>
.shop-page {
  display: flex;
  flex-direction: column;
  gap: 4rem;
  color: var(--vh-text-primary);
}

.shop-hero {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 2.5rem;
  padding: clamp(1.5rem, 3vw, 2.5rem);
  background: linear-gradient(135deg, rgba(16, 25, 42, 0.95), rgba(20, 30, 52, 0.95));
  border: 1px solid var(--vh-border-strong);
  border-radius: 2rem;
  box-shadow: var(--vh-shadow-strong);
}

.hero-copy {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
  color: var(--vh-text-primary);
}

.hero-copy h1 {
  font-family: 'Playfair Display', serif;
  font-size: clamp(2rem, 4vw, 3.2rem);
  line-height: 1.2;
  color: var(--vh-text-primary);
}

.hero-copy p {
  line-height: 1.7;
  font-size: 1rem;
  color: var(--vh-text-secondary);
}

.hero-actions {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
}

.search-input {
  flex: 1;
  min-width: 220px;
  padding: 0.9rem 1.2rem;
  border-radius: 999px;
  border: 1px solid var(--vh-border-soft);
  background: var(--vh-surface-200);
  color: var(--vh-text-primary);
  font-size: 0.95rem;
  outline: none;
}

.search-input:focus {
  border-color: var(--vh-accent-warm);
  box-shadow: var(--vh-focus-ring);
}

.hero-highlight {
  background: var(--vh-surface-100);
  border-radius: 1.75rem;
  border: 1px solid var(--vh-border-strong);
  box-shadow: var(--vh-shadow-soft);
  padding: clamp(1.6rem, 3vw, 2.4rem);
  display: grid;
  grid-template-columns: minmax(0, 220px) minmax(0, 1fr);
  gap: 1.5rem;
  align-items: center;
}

.highlight-image {
  position: relative;
  border-radius: 1.5rem;
  overflow: hidden;
  cursor: pointer;
  min-height: 220px;
  background: rgba(18, 28, 52, 0.6);
}

.highlight-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}
.highlight-enter-active,
.highlight-leave-active {
  position: relative;
  transition:
    opacity 0.55s ease,
    transform 0.55s cubic-bezier(0.22, 1, 0.36, 1),
    filter 0.55s ease;
}

.highlight-enter-from {
  opacity: 0;
  transform: translateY(10px) scale(0.98);
  filter: blur(8px);
}

.highlight-leave-to {
  opacity: 0;
  transform: translateY(-10px) scale(0.98);
  filter: blur(6px);
}

.highlight-meta {
  display: flex;
  flex-direction: column;
  gap: 0.9rem;
  color: var(--vh-text-primary);
}

.highlight-meta h2 {
  font-size: clamp(1.6rem, 3vw, 2.2rem);
  font-family: 'Playfair Display', serif;
  cursor: pointer;
  margin: 0;
}

.highlight-meta h2:hover {
  color: var(--vh-accent-primary-strong);
}

.highlight-meta p {
  color: var(--vh-text-secondary);
  line-height: 1.6;
}

.highlight-price {
  font-size: 1.1rem;
  font-weight: 600;
  color: var(--vh-accent-primary-strong);
}

.highlight-actions {
  display: flex;
  gap: 0.75rem;
  flex-wrap: wrap;
}

.collection-section {
  display: flex;
  flex-direction: column;
  gap: 2rem;
}

.section-header h2 {
  font-family: 'Playfair Display', serif;
  font-size: clamp(1.8rem, 3vw, 2.6rem);
  margin-bottom: 0.5rem;
}

.section-header p {
  color: var(--vh-text-muted);
  max-width: 560px;
}

.product-grid {
  display: grid;
  gap: 2rem;
  grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
}

.product-card {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
  background: var(--vh-surface-200);
  border-radius: 1.5rem;
  border: 1px solid var(--vh-border-soft);
  padding: 1.4rem;
  box-shadow: var(--vh-shadow-soft);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.product-card:hover {
  transform: translateY(-6px);
  box-shadow: var(--vh-shadow-strong);
}

.product-image {
  position: relative;
  border-radius: 1.2rem;
  overflow: hidden;
  cursor: pointer;
  background: rgba(14, 20, 36, 0.6);
}

.product-image img {
  width: 100%;
  height: 100%;
  min-height: 220px;
  object-fit: cover;
  display: block;
}

.image-placeholder {
  display: grid;
  place-items: center;
  height: 100%;
  min-height: 220px;
  background: linear-gradient(135deg, rgba(120, 143, 213, 0.2), rgba(197, 167, 121, 0.2));
  color: var(--vh-text-muted);
  font-weight: 600;
  letter-spacing: 0.1em;
}

.badge {
  position: absolute;
  top: 14px;
  left: 14px;
  background: var(--vh-badge-surface);
  color: var(--vh-badge-text);
  font-size: 0.75rem;
  font-weight: 600;
  padding: 0.35rem 0.65rem;
  border-radius: 999px;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.product-body h3 {
  font-size: 1.2rem;
  font-weight: 600;
  margin-bottom: 0.35rem;
  cursor: pointer;
  color: var(--vh-text-primary);
}

.product-body h3:hover {
  color: var(--vh-accent-primary-strong);
}

.product-summary {
  color: var(--vh-text-muted);
  font-size: 0.95rem;
  line-height: 1.5;
}

.product-price {
  display: flex;
  gap: 0.6rem;
  align-items: baseline;
}

.product-price .current {
  font-size: 1.1rem;
  font-weight: 700;
  color: var(--vh-accent-primary-strong);
}

.product-price .compare {
  color: var(--vh-text-subtle);
  text-decoration: line-through;
}

.product-actions {
  display: flex;
  gap: 0.75rem;
  flex-wrap: wrap;
}

.primary-btn,
.ghost-btn,
.secondary-btn {
  border-radius: 999px;
  padding: 0.8rem 1.4rem;
  font-weight: 600;
  font-size: 0.95rem;
  cursor: pointer;
  border: none;
  transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
}

.primary-btn {
  background: var(--vh-button-primary);
  color: var(--vh-text-primary);
}

.primary-btn:hover {
  transform: translateY(-1px);
  box-shadow: var(--vh-button-primary-hover-shadow);
}

.ghost-btn {
  background: var(--vh-button-ghost-bg);
  color: var(--vh-text-secondary);
}

.ghost-btn:hover {
  background: var(--vh-button-ghost-hover-bg);
}

.secondary-btn {
  background: transparent;
  border: 1px solid var(--vh-button-outline-border);
  color: var(--vh-text-secondary);
}

.secondary-btn:hover {
  border-color: var(--vh-accent-primary);
}

.error-banner {
  padding: 1rem 1.25rem;
  border-radius: 1rem;
  background: var(--vh-alert-error-bg);
  color: var(--vh-text-primary);
  border: 1px solid var(--vh-alert-error-border);
}

.loading {
  font-weight: 600;
  color: var(--vh-text-muted);
}

.empty-state {
  padding: 2rem;
  border-radius: 1.5rem;
  background: var(--vh-surface-100);
  border: 1px solid var(--vh-border-soft);
  text-align: center;
  color: var(--vh-text-muted);
}
</style>

