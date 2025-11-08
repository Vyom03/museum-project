<script setup>
import { onMounted, reactive, ref } from 'vue'
import axios from 'axios'
import { useCartStore } from '@/stores/cart'
import { useRouter } from 'vue-router'

const cartStore = useCartStore()
const router = useRouter()

const apiBaseUrl = import.meta.env.VITE_API_BASE_URL?.replace(/\/$/, '') ?? '/api'

const checkoutForm = reactive({
  customer_name: '',
  email: '',
  phone: '',
  country_code: '+91',
  address_line1: '',
  address_line2: '',
  city: '',
  state: '',
  postal_code: ''
})

const submitting = ref(false)
const error = ref('')
const successOrder = ref(null)

async function submitOrder() {
  if (!cartStore.token || cartStore.items.length === 0) {
    error.value = 'Your cart is empty.'
    return
  }

  submitting.value = true
  error.value = ''

  try {
    const { data } = await axios.post(`${apiBaseUrl}/shop/checkout`, {
      cart_token: cartStore.token,
      ...checkoutForm,
      notes: {
        preferred_contact: checkoutForm.email,
        phone: checkoutForm.phone
      }
    })

    successOrder.value = data.data
    await cartStore.ensureCart()
  } catch (err) {
    error.value = err.response?.data?.message ?? 'Unable to complete the checkout right now.'
  } finally {
    submitting.value = false
  }
}

function goToShop() {
  router.push('/shop')
}

onMounted(() => {
  cartStore.ensureCart()
})
</script>

<template>
  <section class="checkout-page">
    <header class="checkout-header">
      <h1>Checkout</h1>
      <p>Share your shipping details and our concierge will confirm availability and payment within one business day.</p>
    </header>

    <div v-if="successOrder" class="success-card">
      <h2>Thank you for your request!</h2>
      <p>
        Order <strong>{{ successOrder.order_number }}</strong> has been recorded. Our team will connect with you at
        <strong>{{ successOrder.email }}</strong> to finalise payment and delivery timelines.
      </p>
      <button class="primary-btn" type="button" @click="goToShop">Return to Collection</button>
    </div>

    <div v-else class="checkout-layout">
      <div class="form-panel">
        <form class="checkout-form" @submit.prevent="submitOrder">
          <div class="form-row">
            <label>
              Full Name
              <input v-model="checkoutForm.customer_name" required type="text" placeholder="Your name" />
            </label>
            <label>
              Email
              <input v-model="checkoutForm.email" required type="email" placeholder="you@example.com" />
            </label>
          </div>

          <div class="form-row">
            <label>
              Country Code
              <input v-model="checkoutForm.country_code" type="text" placeholder="+91" />
            </label>
            <label>
              Phone
              <input v-model="checkoutForm.phone" type="tel" placeholder="Contact number" />
            </label>
          </div>

          <label>
            Address Line 1
            <input v-model="checkoutForm.address_line1" required type="text" placeholder="Building and street" />
          </label>
          <label>
            Address Line 2 (optional)
            <input v-model="checkoutForm.address_line2" type="text" placeholder="Apartment, suite, etc." />
          </label>

          <div class="form-row">
            <label>
              City
              <input v-model="checkoutForm.city" required type="text" />
            </label>
            <label>
              State / Region
              <input v-model="checkoutForm.state" type="text" />
            </label>
            <label>
              Postal Code
              <input v-model="checkoutForm.postal_code" required type="text" />
            </label>
          </div>

          <div v-if="error" class="error-banner">{{ error }}</div>

          <button class="primary-btn" type="submit" :disabled="submitting">
            <span v-if="submitting">Processing…</span>
            <span v-else>Submit Reservation</span>
          </button>
        </form>
      </div>

      <aside class="order-preview">
        <h2>Order Preview</h2>
        <div v-if="cartStore.items.length === 0" class="empty-preview">
          Your cart is empty. Add items from the collection before checking out.
        </div>
        <ul v-else class="preview-items">
          <li v-for="item in cartStore.items" :key="item.id">
            <div>
              <strong>{{ item.product_name ?? item.product?.name }}</strong>
              <span>× {{ item.quantity }}</span>
            </div>
            <div>₹ {{ Number(item.line_total).toLocaleString('en-IN') }}</div>
          </li>
        </ul>

        <div class="preview-total">
          <span>Estimated total</span>
          <strong>₹ {{ Number(cartStore.subtotal || 0).toLocaleString('en-IN') }}</strong>
        </div>

        <p class="note">
          A concierge will confirm curated packaging, shipping charges, and payment options (UPI, bank transfer, or
          card) before dispatch.
        </p>
      </aside>
    </div>
  </section>
</template>

<style scoped>
.checkout-page {
  display: flex;
  flex-direction: column;
  gap: 2.5rem;
  color: var(--vh-text-primary);
}

.checkout-header h1 {
  font-family: 'Playfair Display', serif;
  font-size: clamp(2rem, 3vw, 2.6rem);
}

.checkout-header p {
  color: var(--vh-text-secondary);
  max-width: 620px;
}

.checkout-layout {
  display: grid;
  gap: 2rem;
  grid-template-columns: minmax(0, 1fr) minmax(280px, 340px);
}

.form-panel {
  background: var(--vh-surface-100);
  border-radius: 1.75rem;
  border: 1px solid var(--vh-border-strong);
  padding: clamp(1.6rem, 3vw, 2.5rem);
  box-shadow: var(--vh-shadow-soft);
}

.checkout-form {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.form-row {
  display: grid;
  gap: 1rem;
  grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
}

label {
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
  color: var(--vh-text-secondary);
  font-weight: 600;
  font-size: 0.9rem;
}

input {
  padding: 0.8rem 1rem;
  border-radius: 0.85rem;
  border: 1px solid var(--vh-border-soft);
  background: var(--vh-surface-200);
  font-weight: 500;
  color: var(--vh-text-primary);
}

input:focus {
  outline: none;
  border-color: var(--vh-accent-warm);
  box-shadow: var(--vh-focus-ring);
}

.primary-btn {
  border-radius: 999px;
  padding: 0.9rem 1.6rem;
  font-weight: 600;
  border: none;
  cursor: pointer;
  background: var(--vh-button-primary);
  color: var(--vh-text-primary);
}

.primary-btn:hover {
  box-shadow: var(--vh-button-primary-hover-shadow);
  transform: translateY(-1px);
}

.primary-btn:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.error-banner {
  padding: 1rem;
  border-radius: 1rem;
  background: var(--vh-alert-error-bg);
  border: 1px solid var(--vh-alert-error-border);
  color: var(--vh-text-primary);
}

.order-preview {
  background: var(--vh-surface-100);
  border-radius: 1.75rem;
  border: 1px solid var(--vh-border-strong);
  padding: 1.8rem;
  box-shadow: var(--vh-shadow-soft);
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.preview-items {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 0.9rem;
}

.preview-items li {
  display: flex;
  justify-content: space-between;
  color: var(--vh-text-muted);
}

.preview-total {
  display: flex;
  justify-content: space-between;
  align-items: baseline;
  font-weight: 700;
  color: var(--vh-accent-primary-strong);
}

.note {
  color: var(--vh-text-muted);
  font-size: 0.9rem;
  line-height: 1.6;
}

.empty-preview {
  color: var(--vh-text-muted);
}

.success-card {
  background: rgba(20, 35, 32, 0.88);
  border-radius: 1.5rem;
  border: 1px solid rgba(120, 175, 150, 0.35);
  padding: clamp(1.8rem, 3vw, 2.6rem);
  box-shadow: 0 20px 40px rgba(12, 32, 28, 0.45);
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.success-card h2 {
  font-family: 'Playfair Display', serif;
  font-size: clamp(1.8rem, 3vw, 2.4rem);
  color: #b5e7d0;
}

.success-card p {
  color: rgba(181, 236, 210, 0.78);
  line-height: 1.6;
}

@media (max-width: 860px) {
  .checkout-layout {
    grid-template-columns: 1fr;
  }
}
</style>

