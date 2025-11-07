<script setup>
import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'

const router = useRouter()
const route = useRoute()

const username = ref('')
const password = ref('')
const error = ref('')
const loading = ref(false)

const apiBaseUrl = import.meta.env.VITE_API_BASE_URL?.replace(/\/$/, '') ?? '/api'
const ADMIN_TOKEN_KEY = 'vyomAdminCreds'

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

    if (typeof window !== 'undefined') {
      const token = btoa(`${username.value}:${password.value}`)
      localStorage.setItem(ADMIN_TOKEN_KEY, token)
    }

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
  background: rgba(10, 17, 34, 0.85);
  border-radius: 2rem;
  border: 1px solid rgba(120, 144, 255, 0.25);
  padding: clamp(1.8rem, 4vw, 2.6rem);
  color: #e2e8ff;
  box-shadow: 0 30px 60px rgba(5, 10, 26, 0.55);
  backdrop-filter: blur(12px);
  display: flex;
  flex-direction: column;
  gap: 1.8rem;
}

header .eyebrow {
  font-size: 0.8rem;
  text-transform: uppercase;
  letter-spacing: 0.16em;
  color: rgba(224, 229, 255, 0.6);
}

header h1 {
  margin: 0.35rem 0;
  font-size: 2.3rem;
  font-family: 'Playfair Display', serif;
}

.lead {
  color: rgba(224, 229, 255, 0.68);
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
  color: rgba(224, 229, 255, 0.75);
}

.field input {
  border-radius: 1rem;
  border: 1px solid rgba(120, 144, 255, 0.35);
  background: rgba(9, 14, 30, 0.85);
  color: #f3f6ff;
  padding: 0.85rem 1rem;
  font-size: 1rem;
  outline: none;
}

.field input:focus {
  border-color: rgba(255, 176, 91, 0.8);
  box-shadow: 0 0 0 4px rgba(255, 176, 91, 0.2);
}

.primary-btn {
  padding: 0.9rem 1.6rem;
  border-radius: 999px;
  background: linear-gradient(135deg, #4059d6, #6c81ff);
  color: #fff;
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
  box-shadow: 0 12px 24px rgba(67, 88, 182, 0.35);
}

.error {
  color: #ffb4b4;
  background: rgba(255, 124, 124, 0.18);
  border: 1px solid rgba(255, 124, 124, 0.35);
  padding: 0.75rem 1rem;
  border-radius: 0.9rem;
}

footer {
  color: rgba(224, 229, 255, 0.55);
}
</style>

