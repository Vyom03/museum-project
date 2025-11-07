<script setup>
import { computed, onMounted } from 'vue'
import { useCartStore } from '@/stores/cart'
import { useRouter } from 'vue-router'

const cartStore = useCartStore()
const router = useRouter()

const hasItems = computed(() => cartStore.items.length > 0)

function updateQuantity(item, quantity) {
  const value = Number(quantity)
  if (Number.isNaN(value) || value < 1) return
  cartStore.updateItem(item.id, value)
}

function removeItem(item) {
  cartStore.removeItem(item.id)
}

const formattedSubtotal = computed(() =>
  `₹ ${Number(cartStore.subtotal || 0).toLocaleString('en-IN')}`
)

onMounted(() => {
  cartStore.ensureCart()
})
</script>

<template>
  <section class="cart-page">
    <header class="cart-header">
      <h1>Your Gallery Cart</h1>
      <p>
        Secure rare textiles and museum editions. Items remain reserved for 15 minutes after adding them to your cart.
      </p>
    </header>

    <div v-if="cartStore.error" class="error-banner">{{ cartStore.error }}</div>

    <div v-if="cartStore.loading" class="loading">Updating cart…</div>

    <div v-if="!hasItems && !cartStore.loading" class="empty-state">
      <p>Your cart is currently empty.</p>
      <button class="primary-btn" type="button" @click="router.push('/shop')">
        Continue Exploring the Collection
      </button>
    </div>

    <div v-else-if="hasItems" class="cart-content">
      <div class="line-items">
        <article v-for="item in cartStore.items" :key="item.id" class="cart-item">
          <div class="thumb">
            <img
              v-if="item.product?.images?.length"
              :src="item.product.images[0].file_url"
              :alt="item.product.images[0].alt_text ?? item.product_name"
            />
            <div v-else class="thumb-placeholder">Vyom</div>
          </div>

          <div class="details">
            <h3>{{ item.product_name ?? item.product?.name }}</h3>
            <p>SKU {{ item.sku ?? item.product?.sku }}</p>
            <div class="pricing">
              <span>₹ {{ Number(item.unit_price).toLocaleString('en-IN') }} each</span>
              <strong>₹ {{ Number(item.line_total).toLocaleString('en-IN') }}</strong>
            </div>

            <div class="item-controls">
              <label>
                Qty
                <input
                  :value="item.quantity"
                  type="number"
                  min="1"
                  @change="event => updateQuantity(item, event.target.value)"
                />
              </label>
              <button class="ghost-btn" type="button" @click="removeItem(item)">Remove</button>
              <button
                v-if="item.product?.slug"
                class="link-btn"
                type="button"
                @click="router.push({ name: 'product-detail', params: { slug: item.product.slug } })"
              >
                View details
              </button>
            </div>
          </div>
        </article>
      </div>

      <aside class="summary">
        <h2>Order Summary</h2>
        <div class="summary-row">
          <span>Subtotal</span>
          <span>{{ formattedSubtotal }}</span>
        </div>
        <div class="summary-row">
          <span>Shipping</span>
          <span>Calculated at confirmation</span>
        </div>
        <div class="summary-row">
          <span>Taxes</span>
          <span>Inclusive</span>
        </div>
        <hr />
        <div class="summary-row total">
          <span>Total (INR)</span>
          <strong>{{ formattedSubtotal }}</strong>
        </div>

        <button class="primary-btn" type="button" @click="router.push('/shop/checkout')">
          Proceed to Checkout
        </button>
        <button class="ghost-btn" type="button" @click="router.push('/shop')">
          Continue Shopping
        </button>
      </aside>
    </div>
  </section>
</template>

<style scoped>
.cart-page {
  display: flex;
  flex-direction: column;
  gap: 2.5rem;
  color: #e2e8ff;
}

.cart-header h1 {
  font-family: 'Playfair Display', serif;
  font-size: clamp(2rem, 3vw, 2.6rem);
}

.cart-header p {
  color: rgba(214, 220, 255, 0.7);
  max-width: 620px;
  line-height: 1.6;
}

.cart-content {
  display: grid;
  gap: 2rem;
  grid-template-columns: minmax(0, 1fr) minmax(260px, 320px);
}

.line-items {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.cart-item {
  display: grid;
  grid-template-columns: 140px 1fr;
  gap: 1.5rem;
  background: rgba(14, 22, 48, 0.92);
  border: 1px solid rgba(107, 132, 255, 0.25);
  border-radius: 1.5rem;
  padding: 1.2rem;
  box-shadow: 0 16px 32px rgba(5, 10, 26, 0.5);
}

.thumb {
  border-radius: 1.2rem;
  overflow: hidden;
  background: rgba(122, 145, 255, 0.12);
}

.thumb img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.thumb-placeholder {
  display: grid;
  place-items: center;
  height: 100%;
  font-weight: 600;
  letter-spacing: 0.08em;
  color: rgba(224, 229, 255, 0.6);
}

.details h3 {
  font-size: 1.3rem;
  margin-bottom: 0.25rem;
}

.details p {
  color: rgba(214, 220, 255, 0.55);
  margin-bottom: 0.75rem;
}

.pricing {
  display: flex;
  gap: 0.75rem;
  align-items: baseline;
  margin-bottom: 1rem;
}

.pricing strong {
  font-size: 1.05rem;
  color: #a8baff;
}

.item-controls {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
  align-items: center;
}

.item-controls input {
  width: 80px;
  padding: 0.6rem 0.75rem;
  border-radius: 0.75rem;
  border: 1px solid rgba(107, 132, 255, 0.35);
  background: rgba(9, 14, 30, 0.8);
  color: #f0f4ff;
}

.summary {
  background: rgba(15, 23, 50, 0.92);
  border-radius: 1.5rem;
  border: 1px solid rgba(107, 132, 255, 0.25);
  padding: 1.8rem;
  display: flex;
  flex-direction: column;
  gap: 1rem;
  box-shadow: 0 18px 32px rgba(5, 10, 26, 0.5);
}

.summary-row {
  display: flex;
  justify-content: space-between;
  color: rgba(214, 220, 255, 0.68);
}

.summary-row.total {
  font-size: 1.1rem;
  font-weight: 700;
  color: #a8baff;
}

.primary-btn,
.ghost-btn {
  border-radius: 999px;
  padding: 0.85rem 1.6rem;
  font-weight: 600;
  border: none;
  cursor: pointer;
  width: 100%;
}

.primary-btn {
  background: linear-gradient(135deg, #4059d6, #6c81ff);
  color: #fff;
}

.ghost-btn {
  background: rgba(64, 89, 214, 0.18);
  color: #a8baff;
}

.link-btn {
  background: none;
  border: none;
  color: #9eb4ff;
  font-weight: 600;
  cursor: pointer;
}

.link-btn:hover {
  text-decoration: underline;
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

.empty-state {
  background: rgba(14, 22, 48, 0.92);
  border-radius: 1.5rem;
  border: 1px solid rgba(107, 132, 255, 0.25);
  padding: 2.5rem;
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
  align-items: flex-start;
  box-shadow: 0 20px 36px rgba(5, 10, 26, 0.55);
}

@media (max-width: 900px) {
  .cart-content {
    grid-template-columns: 1fr;
  }

  .summary {
    position: sticky;
    bottom: 2rem;
  }
}

@media (max-width: 640px) {
  .cart-item {
    grid-template-columns: 1fr;
  }
}
</style>

