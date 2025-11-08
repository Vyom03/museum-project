.field--country select {
  width: min(200px, 100%);
}
<script setup>
import { computed, nextTick, reactive, ref, watch } from 'vue'
import axios from 'axios'

const apiBaseUrl = import.meta.env.VITE_API_BASE_URL?.replace(/\/$/, '') ?? '/api'

const defaultForm = () => ({
  contact_name: '',
  email: '',
  phone: '',
  country_code: '91',
  organisation: '',
  group_type: 'Individual',
  preferred_date: '',
  preferred_slot: '',
  adults_count: 0,
  needs_guided_tour: false,
  notes: '',
})

const countryOptions = [
  { label: 'India (+91)', value: '91' },
  { label: 'United States (+1)', value: '1' },
  { label: 'United Kingdom (+44)', value: '44' },
  { label: 'United Arab Emirates (+971)', value: '971' },
  { label: 'Singapore (+65)', value: '65' },
  { label: 'Australia (+61)', value: '61' },
  { label: 'Canada (+1)', value: '1' },
  { label: 'France (+33)', value: '33' },
  { label: 'Germany (+49)', value: '49' },
  { label: 'Japan (+81)', value: '81' },
]

const form = reactive(defaultForm())
const errors = ref({})
const submitting = ref(false)
const successMessage = ref('')
const successBanner = ref(null)

const availability = reactive({
  status: 'idle',
  capacity: null,
  booked: null,
  remaining: null,
})
const availabilityError = ref('')

const capacitySlots = new Set([
  'Morning (10:30 AM - 12:00 PM)',
  'Afternoon (02:30 PM - 04:00 PM)',
])

let availabilityRequestId = 0

const formatDate = isoDate => {
  if (!isoDate) return ''

  const date = new Date(isoDate)
  if (Number.isNaN(date.getTime())) {
    return isoDate
  }

  return new Intl.DateTimeFormat('en-IN', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  }).format(date)
}

const groupOptions = [
  'Individual',
  'Family',
  'School / College',
  'Corporate',
  'Tour Group',
  'Other',
]

const timeOptions = [
  'Morning (10:30 AM - 12:00 PM)',
  'Afternoon (02:30 PM - 04:00 PM)',
  'Custom Slot',
]

const totalPeople = computed(() => Number(form.adults_count) || 0)

const requiresCapacityCheck = computed(() => capacitySlots.has(form.preferred_slot))

const isSlotFullyBooked = computed(() => requiresCapacityCheck.value && availability.remaining === 0)

const availabilityMessage = computed(() => {
  if (!requiresCapacityCheck.value || availability.remaining === null) {
    return ''
  }

  if (availability.remaining === 0) {
    return 'This slot is fully booked for the selected date.'
  }

  if (totalPeople.value < 1) {
    return `${availability.remaining} of ${availability.capacity} spots available. Enter your group size to secure the slot.`
  }

  return `${availability.remaining} spots remain for this slot on the selected date.`
})

const requestedExceedsCapacity = computed(() => {
  return (
    requiresCapacityCheck.value &&
    availability.remaining !== null &&
    availability.remaining >= 0 &&
    totalPeople.value > availability.remaining
  )
})

const availabilityBadge = computed(() => {
  if (!requiresCapacityCheck.value || availability.remaining === null || availability.capacity === null) {
    return ''
  }

  return `${availability.remaining} of ${availability.capacity} spots available`
})

const submitDisabled = computed(() => {
  if (submitting.value) {
    return true
  }

  if (!form.contact_name || !form.email || !form.preferred_date || !form.preferred_slot) {
    return true
  }

  if (totalPeople.value < 1) {
    return true
  }

  if (isSlotFullyBooked.value) {
    return true
  }

  if (requiresCapacityCheck.value && availability.remaining !== null && totalPeople.value > availability.remaining) {
    return true
  }

  return false
})

const resetAvailability = () => {
  availabilityRequestId += 1
  availability.status = 'idle'
  availability.capacity = null
  availability.booked = null
  availability.remaining = null
  availabilityError.value = ''
}

const resetForm = () => {
  Object.assign(form, defaultForm())
  resetAvailability()
}

const checkAvailability = async () => {
  if (!requiresCapacityCheck.value) {
    resetAvailability()
    return
  }

  if (!form.preferred_date || !form.preferred_slot) {
    resetAvailability()
    return
  }

  availability.status = 'loading'
  availabilityError.value = ''

  const requestId = ++availabilityRequestId

  try {
    const { data } = await axios.get(`${apiBaseUrl}/tour-registrations/availability`, {
      params: {
        preferred_date: form.preferred_date,
        preferred_slot: form.preferred_slot,
      },
    })

    if (requestId !== availabilityRequestId) {
      return
    }

    availability.status = 'ready'
    availability.capacity = data.capacity
    availability.booked = data.booked
    availability.remaining = data.remaining
  } catch (error) {
    if (requestId !== availabilityRequestId) {
      return
    }

    availability.status = 'error'
    if (error.name !== 'CanceledError') {
      availabilityError.value =
        'We could not verify the availability for this slot. Please try again or continue and we will confirm manually.'
    }
  }
}

watch([() => form.preferred_slot, () => form.preferred_date, totalPeople], () => {
  checkAvailability()
})

watch(successMessage, async value => {
  if (value) {
    await nextTick()
    successBanner.value?.scrollIntoView({ behavior: 'smooth', block: 'center' })
  }
})

const handleSubmit = async () => {
  errors.value = {}
  successMessage.value = ''

  try {
    submitting.value = true
    const payload = {
      ...form,
      group_type: form.group_type,
      adults_count: Number(form.adults_count) || 0,
    }

    const { data } = await axios.post(`${apiBaseUrl}/tour-registrations`, payload, {
      headers: { 'Content-Type': 'application/json' },
    })

    const record = data?.data ?? payload
    const submittedName = form.contact_name?.split(' ')[0] || 'visitor'
    const summary = {
      name: submittedName,
      email: form.email,
      slot: record.preferred_slot,
      date: record.preferred_date,
    }

    resetForm()

    const friendlyDate = formatDate(summary.date)
    successMessage.value = `Thank you, ${summary.name}! We received your request for ${friendlyDate} (${summary.slot}). We will reach out at ${summary.email} with confirmation details.`
  } catch (error) {
    if (error.response?.status === 422) {
      errors.value = error.response.data.errors ?? {}
      checkAvailability()
    } else {
      errors.value = {
        general: [
          'We could not submit your request at the moment. Please try again or contact the museum team.',
        ],
      }
    }
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <div class="page">
    <section class="form-section" id="booking">
      <div class="card">
        <div class="card__header">
          <h2 class="card__title">Vyom Heritage Museum Visit Request</h2>
          <p class="card__subtitle">
            Share your preferred date, slot, and party details below. We craft intimate tours to preserve the collections
            and respond within one business day.
          </p>
        </div>
        <div class="card__divider"></div>

        <form class="form" @submit.prevent="handleSubmit">
          <div v-if="errors.general" class="alert alert--error">
            <p v-for="err in errors.general" :key="err">{{ err }}</p>
          </div>

          <div class="grid grid--two">
            <label class="field">
              <span>Contact Name *</span>
              <input v-model="form.contact_name" type="text" placeholder="Full name" required />
              <small v-if="errors.contact_name" class="field__error">{{ errors.contact_name[0] }}</small>
            </label>

            <label class="field">
              <span>Email *</span>
              <input v-model="form.email" type="email" placeholder="name@example.com" required />
              <small v-if="errors.email" class="field__error">{{ errors.email[0] }}</small>
            </label>
          </div>

          <div class="grid grid--two">
            <label class="field field--country">
              <span>Country</span>
              <select v-model="form.country_code">
                <option v-for="option in countryOptions" :key="option.value" :value="option.value">
                  {{ option.label }}
                </option>
              </select>
              <small v-if="errors.country_code" class="field__error">{{ errors.country_code[0] }}</small>
            </label>

            <label class="field">
              <span>Mobile Number</span>
              <input v-model="form.phone" type="tel" placeholder="Your contact number" />
              <small v-if="errors.phone" class="field__error">{{ errors.phone[0] }}</small>
            </label>
          </div>

          <div class="grid grid--two">
            <label class="field">
              <span>Organisation / Institution</span>
              <input v-model="form.organisation" type="text" placeholder="Optional" />
              <small v-if="errors.organisation" class="field__error">{{ errors.organisation[0] }}</small>
            </label>

            <label class="field">
              <span>Visitor Type *</span>
              <select v-model="form.group_type" required>
                <option v-for="option in groupOptions" :key="option" :value="option">{{ option }}</option>
              </select>
              <small v-if="errors.group_type" class="field__error">{{ errors.group_type[0] }}</small>
            </label>
          </div>

          <div class="grid grid--two">
            <label class="field">
              <span>Preferred Date *</span>
              <input v-model="form.preferred_date" type="date" required />
              <small v-if="errors.preferred_date" class="field__error">{{ errors.preferred_date[0] }}</small>
            </label>

            <label class="field">
              <span class="field__label-with-badge">
                <span>Preferred Slot *</span>
                <span v-if="availabilityBadge" class="availability__badge">{{ availabilityBadge }}</span>
              </span>
              <select v-model="form.preferred_slot" required>
                <option value="" disabled>Select a time slot</option>
                <option v-for="option in timeOptions" :key="option" :value="option">{{ option }}</option>
              </select>
              <small v-if="errors.preferred_slot" class="field__error">{{ errors.preferred_slot[0] }}</small>
            </label>
          </div>

          <div v-if="requiresCapacityCheck" class="availability">
            <p v-if="availability.status === 'loading'" class="availability__status">
              Checking slot availability...
            </p>
            <p
              v-else-if="availabilityMessage"
              :class="[
                'availability__status',
                {
                  'availability__status--warning':
                    availability.remaining !== null && availability.remaining <= 5 && availability.remaining > 0,
                  'availability__status--error': isSlotFullyBooked,
                },
              ]"
            >
              {{ availabilityMessage }}
            </p>
            <p v-else-if="availabilityError" class="availability__status availability__status--error">
              {{ availabilityError }}
            </p>
            <p v-else-if="requiresCapacityCheck && totalPeople < 1" class="availability__status">
              Enter the number of visitors so we can confirm availability for this slot.
            </p>
          </div>

          <div class="grid grid--two">
            <label class="field">
              <span>Number of Visitors *</span>
              <input v-model.number="form.adults_count" type="number" min="0" />
              <small class="field__helper">Include yourself and every guest in your party.</small>
              <small v-if="errors.adults_count" class="field__error">{{ errors.adults_count[0] }}</small>
            </label>

            <div class="field field--placeholder"></div>
          </div>

          <p class="field__helper field__helper--muted">
            We verify capacities once we know your group size. Add the number of visitors after selecting your preferred
            date and slot.
          </p>

          <p
            v-if="requestedExceedsCapacity"
            class="availability__status availability__status--error availability__status--inline"
          >
            Only {{ availability.remaining }} spots remain for the selected slot. Please adjust your attendee count or
            choose another date.
          </p>

          <label class="field">
            <span>Additional Notes</span>
            <textarea
              v-model="form.notes"
              rows="4"
              placeholder="Share expectations, accessibility needs, or language preferences"
            ></textarea>
            <small v-if="errors.notes" class="field__error">{{ errors.notes[0] }}</small>
          </label>

          <label class="checkbox">
            <input v-model="form.needs_guided_tour" type="checkbox" />
            <span>Pair me with a senior curator for a bespoke walkthrough</span>
          </label>

          <p class="disclaimer">
            Our reservations operate on limited daily slots to safeguard the collections. We will respond with a
            confirmation or alternative within one business day.
          </p>

          <div v-if="successMessage" ref="successBanner" class="alert alert--success alert--inline" role="status">
            {{ successMessage }}
          </div>

          <button class="submit" type="submit" :disabled="submitDisabled">
            <span v-if="submitting" class="spinner" aria-hidden="true"></span>
            <span>{{ submitting ? 'Submitting...' : 'Submit Reservation Request' }}</span>
          </button>
        </form>
      </div>
    </section>
  </div>
</template>

<style scoped>


.page {
  min-height: 100vh;
  color: var(--vh-text-primary);
  font-family: 'Open Sans', 'Segoe UI', sans-serif;
  display: flex;
  justify-content: center;
  background: var(--vh-background-gradient);
  position: relative;
}

.page::before {
  content: '';
  position: fixed;
  inset: 0;
  background: radial-gradient(circle at 18% 20%, rgba(120, 143, 213, 0.18), transparent 55%),
    radial-gradient(circle at 80% 25%, rgba(120, 143, 213, 0.14), transparent 45%),
    radial-gradient(circle at 55% 78%, rgba(197, 167, 121, 0.14), transparent 42%);
  pointer-events: none;
  z-index: 0;
}

.page::after {
  content: '';
  position: fixed;
  inset: 0;
  background: url('https://www.transparenttextures.com/patterns/asfalt-dark.png');
  opacity: 0.08;
  pointer-events: none;
  z-index: 0;
}

.form-section {
  width: min(980px, 100%);
  padding: clamp(2rem, 5vw, 5rem);
  position: relative;
  z-index: 1;
}

.card {
  background: var(--vh-surface-100);
  width: min(960px, 100%);
  padding: clamp(2.5rem, 4vw, 3rem);
  border-radius: 1.75rem;
  box-shadow: var(--vh-shadow-strong);
  border: 1px solid var(--vh-border-strong);
  backdrop-filter: blur(14px);
  color: var(--vh-text-primary);
}

.card__header {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.card__title {
  font-family: 'Playfair Display', 'Kanit', serif;
  font-size: 2.15rem;
  margin: 0;
  color: var(--vh-text-primary);
}

.card__subtitle {
  font-size: 1rem;
  color: var(--vh-text-secondary);
  line-height: 1.7;
  max-width: 36rem;
}



.card__divider {
  border-bottom: 1px solid var(--vh-border-soft);
  margin: 1.75rem 0 2rem;
}

.form {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.grid {
  display: grid;
  gap: 1.5rem;
}

.grid--two {
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
}

.field {
  display: flex;
  flex-direction: column;
  gap: 0.45rem;
}

.field--country select {
  max-width: 200px;
}

.field--placeholder {
  visibility: hidden;
}

.field span {
  font-weight: 600;
  color: var(--vh-text-secondary);
}

.field__label-with-badge {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  flex-wrap: wrap;
}

.field input,
.field select,
.field textarea {
  border: 1px solid var(--vh-border-soft);
  border-radius: 0.75rem;
  padding: 0.85rem 1rem;
  font-size: 0.95rem;
  transition: border 0.2s ease, box-shadow 0.2s ease;
  outline: none;
  background: var(--vh-surface-200);
  color: var(--vh-text-primary);
}

.field input:focus,
.field select:focus,
.field textarea:focus {
  border-color: var(--vh-accent-warm);
  box-shadow: var(--vh-focus-ring);
}


.field__error {
  color: #f2b5b5;
  font-size: 0.85rem;
}

.field__helper {
  font-size: 0.85rem;
  color: var(--vh-text-muted);
}

.field__helper--muted {
  color: var(--vh-text-subtle);
  margin-top: -0.5rem;
}

.field input:disabled,
.field select:disabled,
.field textarea:disabled {
  background-color: rgba(22, 30, 56, 0.55);
  cursor: not-allowed;
  color: rgba(174, 184, 220, 0.65);
}

.availability {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.availability__status {
  font-size: 0.9rem;
  padding: 0.85rem 1rem;
  border-radius: 0.75rem;
  border: 1px solid var(--vh-border-soft);
  background: var(--vh-surface-200);
  color: var(--vh-text-secondary);
}

.availability__status--warning {
  border-color: rgba(197, 167, 121, 0.45);
  background: rgba(197, 167, 121, 0.22);
  color: var(--vh-accent-warm);
}

.availability__status--error {
  border-color: rgba(205, 92, 92, 0.45);
  background: rgba(205, 92, 92, 0.22);
  color: #f3c5c5;
}

.availability__status--inline {
  margin-top: -0.5rem;
}

.availability__badge {
  font-size: 0.8rem;
  font-weight: 600;
  padding: 0.2rem 0.75rem;
  border-radius: 999px;
  background: var(--vh-accent-primary-soft);
  color: var(--vh-text-secondary);
  border: 1px solid var(--vh-border-soft);
  white-space: nowrap;
}

.alert--inline {
  border-radius: 1rem;
  box-shadow: var(--vh-shadow-soft);
  border: 1px solid var(--vh-border-strong);
  background: var(--vh-surface-100);
  color: var(--vh-text-primary);
}

.checkbox {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  font-size: 0.95rem;
  color: var(--vh-text-secondary);
}

.checkbox input {
  width: 1.1rem;
  height: 1.1rem;
}

.disclaimer {
  font-size: 0.92rem;
  color: var(--vh-text-secondary);
  background: var(--vh-surface-300);
  padding: 1.15rem 1.35rem;
  border-radius: 0.9rem;
  border: 1px solid var(--vh-border-soft);
}

.submit {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
  border: none;
  border-radius: 999px;
  padding: 0.9rem 2.5rem;
  font-size: 1rem;
  font-weight: 600;
  background: var(--vh-button-primary);
  color: var(--vh-text-primary);
  cursor: pointer;
  transition: transform 0.2s ease, box-shadow 0.2s ease, opacity 0.2s ease;
}

.submit:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.submit:not(:disabled):hover {
  transform: translateY(-1px);
  box-shadow: var(--vh-button-primary-hover-shadow);
}

:deep(input[type='date']),
:deep(input[type='datetime-local']),
:deep(input[type='month']),
:deep(input[type='time']) {
  color-scheme: dark;
}

:deep(input[type='date']::-webkit-calendar-picker-indicator),
:deep(input[type='datetime-local']::-webkit-calendar-picker-indicator),
:deep(input[type='month']::-webkit-calendar-picker-indicator),
:deep(input[type='time']::-webkit-calendar-picker-indicator) {
  opacity: 1;
  cursor: pointer;
  transition: opacity 0.2s ease, transform 0.2s ease;
  background-color: transparent;
  background-position: center;
  background-repeat: no-repeat;
  background-size: 16px 16px;
  filter: none;
  -webkit-filter: none;
  mask: none;
  -webkit-mask: none;
  border-radius: 4px;
  padding: 2px;
  background-image: url("data:image/svg+xml,%3Csvg width='20' height='20' viewBox='0 0 24 24' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Crect x='3.5' y='5.5' width='17' height='15' rx='2.5' stroke='%23E6EBFF' stroke-width='1.5'/%3E%3Cpath d='M8 3.5V7' stroke='%2392A9F0' stroke-width='1.5' stroke-linecap='round'/%3E%3Cpath d='M16 3.5V7' stroke='%2392A9F0' stroke-width='1.5' stroke-linecap='round'/%3E%3Cpath d='M3.5 10H20.5' stroke='%2392A9F0' stroke-width='1.5' stroke-linecap='round'/%3E%3Cpath d='M8.25 13.25H10.75V15.75H8.25V13.25Z' fill='%2392A9F0'/%3E%3C/svg%3E");
}

:deep(input[type='date']::-webkit-calendar-picker-indicator:hover),
:deep(input[type='datetime-local']::-webkit-calendar-picker-indicator:hover),
:deep(input[type='month']::-webkit-calendar-picker-indicator:hover),
:deep(input[type='time']::-webkit-calendar-picker-indicator:hover) {
  opacity: 1;
  transform: scale(1.05);
}

.spinner {
  width: 1rem;
  height: 1rem;
  border-radius: 50%;
  border: 2px solid rgba(255, 255, 255, 0.45);
  border-top-color: #ffffff;
  animation: spin 0.8s linear infinite;
}

.alert {
  padding: 1rem 1.25rem;
  border-radius: 0.75rem;
  font-size: 0.95rem;
}

.alert--success {
  background: rgba(90, 150, 120, 0.22);
  color: #b7e5cf;
  border: 1px solid rgba(90, 150, 120, 0.35);
}

.alert--error {
  background: rgba(205, 92, 92, 0.16);
  color: #f3c5c5;
  border: 1px solid rgba(205, 92, 92, 0.3);
}

@keyframes spin {
  0% {
    transform: rotate(0deg);
  }
  100% {
    transform: rotate(360deg);
  }
}

@media (max-width: 640px) {
  .card {
    padding: 1.75rem;
  }

  .hero {
    padding-top: 3rem;
    padding-bottom: 1.5rem;
  }
}
</style>

