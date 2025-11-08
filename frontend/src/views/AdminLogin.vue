<script setup>
import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'
import { storeAdminToken } from '@/utils/adminAuth'

const router = useRouter()
const route = useRoute()

const username = ref('')
const password = ref('')
const error = ref('')
const loading = ref(false)

const apiBaseUrl = import.meta.env.VITE_API_BASE_URL?.replace(/\/$/, '') ?? '/api'

async function handleLogin() {
  error.value = ''

  if (!username.value || !password.value) {
    error.value = 'Please enter both fields.'
    return
  }

  loading.value = true

  try {
    await axios.get(`${apiBaseUrl}/admin/analytics`, {
      auth: {
        username: username.value,
        password: password.value
      }
    })

    storeAdminToken(btoa(`${username.value}:${password.value}`))

    const redirectPath = route.query.redirect ?? '/admin/dashboard'
    router.replace(redirectPath)
  } catch (err) {
    if (err.response?.status === 401) {
      error.value = 'Invalid admin credentials.'
    } else {
      error.value = err.response?.data?.message ?? 'Unable to verify credentials right now.'
    }
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <section class="login-wrapper">
    <div class="login-card">
      <header>
        <p class="eyebrow">Vyom Heritage Museum</p>
        <h1>Admin Console</h1>
        <p class="lead">Secure access for museum staff only.</p>
      </header>

      <form class="form" @submit.prevent="handleLogin">
        <label class="field">
          <span>Username</span>
          <input v-model.trim="username" type="text" autocomplete="username" placeholder="admin" />
        </label>

        <label class="field">
          <span>Password</span>
          <input v-model.trim="password" type="password" autocomplete="current-password" placeholder="••••••" />
        </label>

        <p v-if="error" class="error">{{ error }}</p>

        <button class="primary-btn" type="submit" :disabled="loading">
          {{ loading ? 'Signing in…' : 'Sign in' }}
        </button>
      </form>

      <footer>
        <small>For assistance contact the Vyom digital team.</small>
      </footer>
    </div>
  </section>
</template>

<style scoped>
.login-wrapper {
  min-height: calc(100vh - 140px);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: clamp(2rem, 4vw, 3rem);
}

.login-card {
  width: min(420px, 100%);
  background: var(--vh-surface-100);
  border-radius: 2rem;
  border: 1px solid var(--vh-border-strong);
  padding: clamp(1.8rem, 4vw, 2.6rem);
  color: var(--vh-text-primary);
  box-shadow: var(--vh-shadow-strong);
  backdrop-filter: blur(12px);
  display: flex;
  flex-direction: column;
  gap: 1.8rem;
}

header .eyebrow {
  font-size: 0.8rem;
  text-transform: uppercase;
  letter-spacing: 0.16em;
  color: var(--vh-text-subtle);
}

header h1 {
  margin: 0.35rem 0;
  font-size: 2.3rem;
  font-family: 'Playfair Display', serif;
}

.lead {
  color: var(--vh-text-muted);
  margin: 0;
}

.form {
  display: flex;
  flex-direction: column;
  gap: 1.2rem;
}

.field {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.field span {
  font-size: 0.85rem;
  text-transform: uppercase;
  letter-spacing: 0.12em;
  color: var(--vh-text-muted);
}

.field input {
  border-radius: 1rem;
  border: 1px solid var(--vh-border-soft);
  background: var(--vh-surface-200);
  color: var(--vh-text-primary);
  padding: 0.85rem 1rem;
  font-size: 1rem;
  outline: none;
}

.field input:focus {
  border-color: var(--vh-accent-warm);
  box-shadow: var(--vh-focus-ring);
}

.primary-btn {
  padding: 0.9rem 1.6rem;
  border-radius: 999px;
  background: var(--vh-button-primary);
  color: var(--vh-text-primary);
  border: none;
  font-weight: 600;
  cursor: pointer;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.primary-btn:disabled {
  opacity: 0.7;
  cursor: wait;
}

.primary-btn:not(:disabled):hover {
  transform: translateY(-1px);
  box-shadow: var(--vh-button-primary-hover-shadow);
}

.error {
  color: #f3c5c5;
  background: var(--vh-alert-error-bg);
  border: 1px solid var(--vh-alert-error-border);
  padding: 0.75rem 1rem;
  border-radius: 0.9rem;
}

footer {
  color: var(--vh-text-muted);
}
</style>

