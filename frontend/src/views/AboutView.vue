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
  return output.filter(Boolean)
})

const leadParagraph = computed(() => paragraphs.value.at(0) ?? '')
const bodyParagraphs = computed(() => (paragraphs.value.length > 1 ? paragraphs.value.slice(1) : []))
const hasImage = computed(() => Boolean(about.value?.image_url))

onMounted(() => {
  fetchAbout()
})
</script>

<template>
  <section class="about-page">
    <div class="about-hero">
      <div class="hero-overlay">
        <div class="hero-text">
          <p class="eyebrow">Vyom Heritage Museum</p>
          <h1>{{ about?.title ?? 'About Our Museum' }}</h1>
          <p v-if="leadParagraph" class="lead">{{ leadParagraph }}</p>
        </div>
      </div>
    </div>

    <div v-if="loading" class="loading">Curating our story…</div>
    <div v-else-if="error" class="error-banner">{{ error }}</div>
    <div v-else-if="about" class="content-layout">
      <article class="story-card">
        <p v-for="(paragraph, index) in bodyParagraphs" :key="index">
          {{ paragraph }}
        </p>
        <p v-if="!bodyParagraphs.length" class="body-placeholder">
          Vyom Heritage is an evolving archive. More of our story will be added here soon.
        </p>
      </article>

      <aside v-if="hasImage" class="media-card">
        <img :src="about.image_url" :alt="about.title" />
        <span class="caption">Vyom Heritage archives</span>
      </aside>

      <aside v-else class="media-placeholder">
        Add an archival photograph to the museum record.
      </aside>
    </div>
  </section>
</template>

<style scoped>
.about-page {
  display: flex;
  flex-direction: column;
  gap: 4rem;
  color: var(--vh-text-primary);
  padding-inline: clamp(1.5rem, 6vw, 6rem);
  padding-block: clamp(2rem, 5vw, 5rem);
}

.about-hero {
  position: relative;
  padding: clamp(2.2rem, 5vw, 5.5rem);
  border-radius: 2.25rem;
  overflow: hidden;
  background:
    radial-gradient(circle at 10% 20%, rgba(120, 143, 213, 0.2), transparent 55%),
    radial-gradient(circle at 90% 25%, rgba(197, 167, 121, 0.16), transparent 45%),
    linear-gradient(135deg, rgba(8, 14, 28, 0.92), rgba(16, 28, 48, 0.9));
  box-shadow: var(--vh-shadow-strong);
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

.hero-overlay {
  position: relative;
  backdrop-filter: blur(18px);
  background: linear-gradient(135deg, rgba(6, 12, 26, 0.55), rgba(14, 26, 46, 0.6));
  border-radius: 1.75rem;
  padding: clamp(2.6rem, 5vw, 4.2rem);
  max-width: 940px;
  border: 1px solid var(--vh-border-strong);
  box-shadow: var(--vh-shadow-soft);
  margin: 0 auto;
}

.eyebrow {
  text-transform: uppercase;
  letter-spacing: 0.18em;
  font-size: 0.8rem;
  color: var(--vh-text-subtle);
  margin-bottom: 0.75rem;
}

.hero-text {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  text-align: center;
  align-items: center;
}

.hero-text h1 {
  font-family: 'Playfair Display', serif;
  font-size: clamp(2.4rem, 4.8vw, 3.6rem);
  margin: 0;
}

.lead {
  margin-top: 1rem;
  line-height: 1.8;
  color: var(--vh-text-secondary);
  font-size: 1.08rem;
}

.content-layout {
  display: grid;
  gap: 2.5rem;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  align-items: stretch;
  max-width: 1100px;
  margin: 0 auto;
}

.story-card {
  display: flex;
  flex-direction: column;
  gap: 1.4rem;
  font-size: 1.05rem;
  line-height: 1.8;
  color: var(--vh-text-secondary);
  padding: clamp(1.8rem, 4vw, 2.6rem);
  border-radius: 1.75rem;
  background: var(--vh-surface-100);
  border: 1px solid var(--vh-border-soft);
  box-shadow: var(--vh-shadow-soft);
}

.story-card p {
  margin: 0;
}

.body-placeholder {
  color: var(--vh-text-muted);
  font-style: italic;
}

.media-card {
  border-radius: 1.75rem;
  overflow: hidden;
  border: 1px solid var(--vh-border-strong);
  background: var(--vh-surface-100);
  box-shadow: var(--vh-shadow-soft);
  display: flex;
  flex-direction: column;
}

.media-card img {
  width: 100%;
  display: block;
  object-fit: cover;
}

.caption {
  padding: 0.85rem 1.1rem;
  color: var(--vh-text-muted);
  font-size: 0.85rem;
  text-transform: uppercase;
  letter-spacing: 0.12em;
}

.media-placeholder {
  border-radius: 1.75rem;
  border: 1px dashed var(--vh-border-soft);
  background: var(--vh-surface-200);
  color: var(--vh-text-muted);
  display: grid;
  place-items: center;
  padding: 2.2rem;
  min-height: 280px;
  font-size: 0.95rem;
  text-align: center;
}

.error-banner {
  padding: 1rem;
  border-radius: 1rem;
  background: var(--vh-alert-error-bg);
  border: 1px solid var(--vh-alert-error-border);
  color: var(--vh-text-primary);
}

.loading {
  font-weight: 600;
  color: var(--vh-text-muted);
}

@media (max-width: 720px) {
  .hero-text {
    text-align: center;
  }

  .content-layout {
    grid-template-columns: 1fr;
  }
}
</style>

