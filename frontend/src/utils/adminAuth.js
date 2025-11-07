export const ADMIN_TOKEN_KEY = 'vyomAdminCreds'

export function getAdminToken() {
  if (typeof window === 'undefined') return null
  return localStorage.getItem(ADMIN_TOKEN_KEY)
}

export function storeAdminToken(token) {
  if (typeof window === 'undefined') return
  localStorage.setItem(ADMIN_TOKEN_KEY, token)
}

export function clearAdminToken() {
  if (typeof window === 'undefined') return
  localStorage.removeItem(ADMIN_TOKEN_KEY)
}

export function hasAdminToken() {
  return Boolean(getAdminToken())
}

export function buildAdminAuthHeader() {
  const token = getAdminToken()
  return token ? { Authorization: `Basic ${token}` } : {}
}

