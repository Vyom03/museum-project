<script setup>
import { onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'
import { useCartStore } from '@/stores/cart'

const route = useRoute()
const router = useRouter()
const cartStore = useCartStore()

const apiBaseUrl = import.meta.env.VITE_API_BASE_URL?.replace(/\/$/, '') ?? '/api'

const product = ref(null)
const loading = ref(false)
const error = ref('')
const quantity = ref(1)
const activeImageIndex = ref(0)

async function loadProduct(slug) {
  loading.value = true
  error.value = ''

  try {
    const { data } = await axios.get(`${apiBaseUrl}/shop/products/${slug}`)
    product.value = data.data
    activeImageIndex.value = 0
  } catch (err) {
    error.value = err.response?.data?.message ?? 'Unable to load this object right now.'
  } finally {
    loading.value = false
  }
}

async function addToCart() {
  if (!product.value) return
  try {
    await cartStore.addItem(product.value.id, quantity.value)
  } catch (err) {
    // cart store already captures error message
  }
}

function selectImage(index) {
  activeImageIndex.value = index
}

watch(
  () => route.params.slug,
  slug => {
    if (slug) {
      loadProduct(slug)
    }
  },
  { immediate: true }
)

onMounted(() => {
  cartStore.ensureCart()
})
</script>

<template>
  <div class="product-page">
    <button class="back-link" type="button" @click="router.push('/shop')">← Back to Collection</button>

    <div v-if="loading" class="loading">Curating details…</div>
    <div v-else-if="error" class="error-banner">{{ error }}</div>

    <div v-else-if="product" class="product-layout">
      <div class="gallery">
        <div class="main-image">
          <img
            v-if="product.images?.length"
            :src="product.images[activeImageIndex]?.file_url"
            :alt="product.images[activeImageIndex]?.alt_text ?? product.name"
          />
          <div v-else class="image-placeholder">Vyom Heritage Collection</div>
        </div>
        <div v-if="(product.images?.length ?? 0) > 1" class="thumb-strip">
          <button
            v-for="(image, index) in product.images"
            :key="image.id"
            type="button"
            :class="['thumb', { active: index === activeImageIndex }]"
            @click="selectImage(index)"
          >
            <img :src="image.file_url" :alt="image.alt_text ?? product.name" />
          </button>
        </div>
      </div>

      <div class="product-meta">
        <h1>{{ product.name }}</h1>
        <p class="sku">Catalogue #{{ product.sku }}</p>

        <div class="price-block">
          <span class="current">₹ {{ Number(product.price).toLocaleString('en-IN') }}</span>
          <span v-if="product.compare_at_price" class="compare">
            ₹ {{ Number(product.compare_at_price).toLocaleString('en-IN') }}
          </span>
        </div>

        <p class="summary">{{ product.summary }}</p>

        <div class="rich-text" v-html="product.description"></div>

        <div class="inventory" :class="{ low: product.inventory_count <= 5 }">
          {{ product.inventory_count }} piece(s) remaining in the studio
        </div>

        <div class="actions">
          <label class="qty-field">
            <span>Quantity</span>
            <input v-model.number="quantity" type="number" min="1" :max="product.inventory_count" />
          </label>

          <button class="primary-btn" type="button" @click="addToCart">Add to Cart</button>
          <button class="ghost-btn" type="button" @click="router.push('/shop/cart')">View Cart</button>
        </div>

        <div class="info-panels">
          <div class="panel">
            <h3>Authentication</h3>
            <p>Each textile ships with a signed provenance card and conservation notes from our archives.</p>
          </div>
          <div class="panel">
            <h3>Shipping</h3>
            <p>Worldwide shipping within 5–7 business days. Complimentary delivery across India.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.product-page {
  display: flex;
  flex-direction: column;
  gap: 2rem;
  color: #dee4ff;
}

.back-link {
  align-self: flex-start;
  background: none;
  border: none;
  color: #9eb4ff;
  font-weight: 600;
  cursor: pointer;
}

.back-link:hover {
  text-decoration: underline;
}

.product-layout {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
  gap: 3rem;
}

.gallery {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.main-image {
  border-radius: 2rem;
  overflow: hidden;
  background: rgba(14, 22, 48, 0.92);
  border: 1px solid rgba(107, 132, 255, 0.25);
  box-shadow: 0 22px 44px rgba(5, 10, 26, 0.6);
}

.main-image img {
  width: 100%;
  height: 100%;
  min-height: 420px;
  display: block;
  object-fit: cover;
}

.image-placeholder {
  height: 100%;
  min-height: 420px;
  display: grid;
  place-items: center;
  background: linear-gradient(135deg, rgba(122, 145, 255, 0.24), rgba(255, 176, 91, 0.24));
  font-weight: 600;
  letter-spacing: 0.1em;
  color: rgba(224, 229, 255, 0.7);
}

.thumb-strip {
  display: flex;
  gap: 0.75rem;
  flex-wrap: wrap;
}

.thumb {
  width: 72px;
  height: 72px;
  border-radius: 1rem;
  overflow: hidden;
  border: 2px solid transparent;
  padding: 0;
  cursor: pointer;
  background: rgba(9, 14, 30, 0.6);
}

.thumb.active {
  border-color: rgba(122, 145, 255, 0.8);
}

.thumb img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.product-meta {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
  background: rgba(15, 23, 48, 0.92);
  border-radius: 2rem;
  border: 1px solid rgba(107, 132, 255, 0.25);
  padding: clamp(1.6rem, 3vw, 2.5rem);
  box-shadow: 0 20px 40px rgba(5, 10, 26, 0.55);
}

.product-meta h1 {
  font-family: 'Playfair Display', serif;
  font-size: clamp(2rem, 3.2vw, 2.8rem);
  color: #f4f6ff;
}

.sku {
  color: rgba(214, 220, 255, 0.5);
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.price-block {
  display: flex;
  gap: 0.75rem;
  align-items: baseline;
}

.price-block .current {
  font-size: 1.6rem;
  font-weight: 700;
  color: #a8b5ff;
}

.price-block .compare {
  color: rgba(214, 220, 255, 0.4);
  text-decoration: line-through;
}

.summary {
  color: rgba(214, 220, 255, 0.7);
  font-size: 1.05rem;
}

.rich-text {
  color: rgba(214, 220, 255, 0.75);
  line-height: 1.7;
}

.rich-text :deep(h3) {
  font-family: 'Playfair Display', serif;
  margin-top: 1.5rem;
}

.inventory {
  font-weight: 600;
  color: #6ed6a0;
}

.inventory.low {
  color: #ff9f8f;
}

.actions {
  display: flex;
  flex-wrap: wrap;
  gap: 1rem;
  align-items: center;
}

.qty-field {
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
  color: rgba(13, 23, 51, 0.7);
}

.qty-field input {
  width: 90px;
  padding: 0.7rem 0.9rem;
  border-radius: 0.75rem;
  border: 1px solid rgba(107, 132, 255, 0.35);
  text-align: center;
  background: rgba(9, 14, 30, 0.8);
  color: #f0f4ff;
}

.primary-btn,
.ghost-btn {
  border-radius: 999px;
  padding: 0.85rem 1.6rem;
  font-weight: 600;
  border: none;
  cursor: pointer;
}

.primary-btn {
  background: linear-gradient(135deg, #4059d6, #6c81ff);
  color: #fff;
}

.ghost-btn {
  background: rgba(64, 89, 214, 0.17);
  color: #a8baff;
}

.info-panels {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 1.25rem;
}

.panel {
  background: rgba(13, 21, 46, 0.85);
  border-radius: 1rem;
  padding: 1rem 1.25rem;
  border: 1px solid rgba(107, 132, 255, 0.22);
  color: rgba(214, 220, 255, 0.7);
}

.error-banner {
  padding: 1rem;
  border-radius: 1rem;
  background: rgba(255, 124, 124, 0.15);
  border: 1px solid rgba(255, 124, 124, 0.3);
  color: #861d1d;
}

.loading {
  font-weight: 600;
  color: rgba(214, 220, 255, 0.6);
}

@media (max-width: 768px) {
  .product-layout {
    gap: 2rem;
  }
}
</style>

