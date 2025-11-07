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
  color: #e2e8ff;
}

.shop-hero {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 2.5rem;
  padding: clamp(1.5rem, 3vw, 2.5rem);
  background: radial-gradient(circle at top left, rgba(98, 124, 255, 0.3), transparent 55%),
    linear-gradient(135deg, rgba(12, 22, 46, 0.95), rgba(20, 32, 62, 0.95));
  border: 1px solid rgba(107, 132, 255, 0.25);
  border-radius: 2rem;
  box-shadow: 0 24px 48px rgba(15, 22, 48, 0.5);
}

.hero-copy {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
  color: #f0f4ff;
}

.hero-copy h1 {
  font-family: 'Playfair Display', serif;
  font-size: clamp(2rem, 4vw, 3.2rem);
  line-height: 1.2;
  color: #f6f8ff;
}

.hero-copy p {
  line-height: 1.7;
  font-size: 1rem;
  color: rgba(224, 229, 255, 0.75);
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
  border: 1px solid rgba(107, 132, 255, 0.45);
  background: rgba(10, 17, 34, 0.85);
  color: #f0f4ff;
  font-size: 0.95rem;
  outline: none;
}

.search-input:focus {
  border-color: rgba(255, 176, 91, 0.85);
  box-shadow: 0 0 0 4px rgba(255, 176, 91, 0.2);
}

.hero-highlight {
  background: rgba(9, 14, 30, 0.75);
  border-radius: 1.75rem;
  border: 1px solid rgba(107, 132, 255, 0.25);
  box-shadow: 0 24px 48px rgba(8, 12, 30, 0.65);
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
  background: rgba(12, 20, 42, 0.6);
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
  color: #e8ecff;
}

.highlight-meta h2 {
  font-size: clamp(1.6rem, 3vw, 2.2rem);
  font-family: 'Playfair Display', serif;
  cursor: pointer;
  margin: 0;
}

.highlight-meta h2:hover {
  color: #a8baff;
}

.highlight-meta p {
  color: rgba(224, 229, 255, 0.72);
  line-height: 1.6;
}

.highlight-price {
  font-size: 1.1rem;
  font-weight: 600;
  color: #a8baff;
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
  color: rgba(224, 229, 255, 0.68);
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
  background: rgba(15, 22, 48, 0.92);
  border-radius: 1.5rem;
  border: 1px solid rgba(99, 122, 214, 0.32);
  padding: 1.4rem;
  box-shadow: 0 18px 35px rgba(5, 10, 26, 0.55);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.product-card:hover {
  transform: translateY(-6px);
  box-shadow: 0 22px 46px rgba(5, 10, 26, 0.65);
}

.product-image {
  position: relative;
  border-radius: 1.2rem;
  overflow: hidden;
  cursor: pointer;
  background: rgba(8, 12, 28, 0.6);
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
  background: linear-gradient(135deg, rgba(122, 145, 255, 0.25), rgba(255, 176, 91, 0.25));
  color: rgba(224, 229, 255, 0.65);
  font-weight: 600;
  letter-spacing: 0.1em;
}

.badge {
  position: absolute;
  top: 14px;
  left: 14px;
  background: rgba(255, 176, 91, 0.95);
  color: #0f1a33;
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
  color: #f3f6ff;
}

.product-body h3:hover {
  color: #9ab0ff;
}

.product-summary {
  color: rgba(214, 220, 255, 0.65);
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
  color: #a8baff;
}

.product-price .compare {
  color: rgba(214, 220, 255, 0.4);
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
  background: linear-gradient(135deg, #4059d6, #6c81ff);
  color: #fff;
}

.primary-btn:hover {
  transform: translateY(-1px);
  box-shadow: 0 12px 24px rgba(67, 88, 182, 0.35);
}

.ghost-btn {
  background: rgba(64, 89, 214, 0.15);
  color: #a8baff;
}

.ghost-btn:hover {
  background: rgba(64, 89, 214, 0.25);
}

.secondary-btn {
  background: rgba(64, 89, 214, 0.1);
  border: 1px solid rgba(64, 89, 214, 0.3);
  color: #a8baff;
}

.secondary-btn:hover {
  border-color: rgba(64, 89, 214, 0.5);
}

.error-banner {
  padding: 1rem 1.25rem;
  border-radius: 1rem;
  background: rgba(255, 124, 124, 0.15);
  color: #861d1d;
  border: 1px solid rgba(255, 124, 124, 0.3);
}

.loading {
  font-weight: 600;
  color: rgba(214, 220, 255, 0.6);
}

.empty-state {
  padding: 2rem;
  border-radius: 1.5rem;
  background: rgba(16, 27, 58, 0.9);
  border: 1px solid rgba(107, 132, 255, 0.25);
  text-align: center;
  color: rgba(224, 229, 255, 0.72);
}
</style>

