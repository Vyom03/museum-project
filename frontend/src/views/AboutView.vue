<script setup>
import { computed, onMounted, ref } from 'vue'
import axios from 'axios'

const apiBaseUrl = import.meta.env.VITE_API_BASE_URL?.replace(/\/$/, '') ?? '/api'

const loading = ref(false)
const error = ref('')
const about = ref(null)

async function fetchAbout() {
  loading.value = true
  error.value = ''

  try {
    const { data } = await axios.get(`${apiBaseUrl}/about`)
    about.value = data.data
  } catch (err) {
    error.value = err.response?.data?.message ?? 'Unable to load museum story right now.'
  } finally {
    loading.value = false
  }
}

const paragraphs = computed(() => {
  if (!about.value) return []
  const output = [about.value.paragraph_one, about.value.paragraph_two]
  if (about.value.paragraph_three) {
    output.push(about.value.paragraph_three)
  }
  return output
})

onMounted(() => {
  fetchAbout()
})
</script>

<template>
  <section class="about-page">
    <div class="about-hero">
      <div class="glass-card">
        <p class="eyebrow">Vyom Heritage Museum</p>
        <h1>{{ about?.title ?? 'About Our Museum' }}</h1>
        <p v-if="paragraphs[0]" class="lead">{{ paragraphs[0] }}</p>
      </div>
    </div>

    <div v-if="loading" class="loading">Curating our story…</div>
    <div v-else-if="error" class="error-banner">{{ error }}</div>
    <div v-else-if="about" class="content">
      <article class="narrative">
        <p v-for="(paragraph, index) in paragraphs.slice(1)" :key="index">
          {{ paragraph }}
        </p>
      </article>

      <figure v-if="about.image_url" class="image-frame">
        <img :src="about.image_url" :alt="about.title" />
        <figcaption>Vyom Heritage archives</figcaption>
      </figure>
      <div v-else class="image-placeholder">Add an image URL to the Vyom archives.</div>
    </div>
  </section>
</template>

<style scoped>
.about-page {
  display: flex;
  flex-direction: column;
  gap: 3rem;
  color: #e2e8ff;
}

.about-hero {
  position: relative;
  padding: clamp(2rem, 4vw, 4rem);
  border-radius: 2.25rem;
  overflow: hidden;
  background:
    radial-gradient(circle at 10% 20%, rgba(120, 144, 255, 0.28), transparent 55%),
    radial-gradient(circle at 90% 25%, rgba(255, 176, 91, 0.18), transparent 45%),
    linear-gradient(135deg, rgba(6, 12, 26, 0.95), rgba(14, 28, 54, 0.92));
  box-shadow: 0 30px 70px rgba(4, 10, 24, 0.6);
}

.about-hero::after {
  content: '';
  position: absolute;
  inset: 0;
  background: url('https://images.unsplash.com/photo-1495435229349-e86db7bfa013?ixlib=rb-4.0.3&auto=format&fit=crop&w=1600&q=80')
    center/cover;
  opacity: 0.35;
  mix-blend-mode: screen;
  pointer-events: none;
}

.glass-card {
  position: relative;
  backdrop-filter: blur(22px);
  background: rgba(6, 12, 26, 0.55);
  border: 1px solid rgba(120, 144, 255, 0.25);
  border-radius: 1.75rem;
  padding: clamp(2rem, 4vw, 3.2rem);
  max-width: 760px;
  box-shadow: 0 25px 60px rgba(6, 12, 26, 0.55);
}

.eyebrow {
  text-transform: uppercase;
  letter-spacing: 0.18em;
  font-size: 0.8rem;
  color: rgba(224, 229, 255, 0.55);
  margin-bottom: 0.75rem;
}

.glass-card h1 {
  font-family: 'Playfair Display', serif;
  font-size: clamp(2.2rem, 4.5vw, 3.4rem);
  margin: 0;
}

.lead {
  margin-top: 1rem;
  line-height: 1.8;
  color: rgba(224, 229, 255, 0.75);
  font-size: 1.05rem;
}

.content {
  display: grid;
  gap: 2rem;
  grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
  align-items: start;
}

.narrative {
  display: flex;
  flex-direction: column;
  gap: 1.2rem;
  font-size: 1.05rem;
  line-height: 1.75;
  color: rgba(224, 229, 255, 0.78);
}

.image-frame {
  border-radius: 1.5rem;
  overflow: hidden;
  border: 1px solid rgba(107, 132, 255, 0.28);
  background: rgba(10, 17, 34, 0.8);
  box-shadow: 0 18px 38px rgba(8, 12, 30, 0.55);
}

.image-frame img {
  width: 100%;
  display: block;
  object-fit: cover;
}

.image-frame figcaption {
  padding: 0.75rem 1rem;
  color: rgba(224, 229, 255, 0.6);
  font-size: 0.85rem;
  text-transform: uppercase;
  letter-spacing: 0.12em;
}

.image-placeholder {
  border-radius: 1.5rem;
  border: 1px dashed rgba(107, 132, 255, 0.35);
  background: rgba(10, 17, 34, 0.6);
  color: rgba(224, 229, 255, 0.55);
  display: grid;
  place-items: center;
  padding: 2rem;
  min-height: 280px;
  font-size: 0.95rem;
}

.error-banner {
  padding: 1rem;
  border-radius: 1rem;
  background: rgba(255, 124, 124, 0.15);
  border: 1px solid rgba(255, 124, 124, 0.3);
  color: #ffd8d8;
}

.loading {
  font-weight: 600;
  color: rgba(224, 229, 255, 0.6);
}

@media (max-width: 720px) {
  .hero {
    text-align: center;
  }
}
</style>

