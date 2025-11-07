import { defineStore } from 'pinia'
import axios from 'axios'

const apiBaseUrl = import.meta.env.VITE_API_BASE_URL?.replace(/\/$/, '') ?? '/api'

const CART_TOKEN_KEY = 'vyom_cart_token'

export const useCartStore = defineStore('cart', {
  state: () => ({
    token: localStorage.getItem(CART_TOKEN_KEY),
    currency: 'INR',
    items: [],
    subtotal: 0,
    loading: false,
    error: null
  }),
  getters: {
    itemCount: state => state.items.length,
    totalQuantity: state => state.items.reduce((acc, item) => acc + item.quantity, 0)
  },
  actions: {
    async ensureCart() {
      if (this.loading) return

      this.loading = true
      this.error = null

      try {
        const payload = {}
        if (this.token) {
          payload.cart_token = this.token
        }

        const { data } = await axios.post(`${apiBaseUrl}/shop/cart`, payload)
        this.applyCartResponse(data)
      } catch (error) {
        this.error = this.extractError(error)
      } finally {
        this.loading = false
      }
    },

    async addItem(productId, quantity = 1) {
      await this.ensureCart()
      this.loading = true
      this.error = null

      try {
        const { data } = await axios.post(`${apiBaseUrl}/shop/cart/items`, {
          cart_token: this.token,
          product_id: productId,
          quantity
        })
        this.applyCartResponse(data)
      } catch (error) {
        this.error = this.extractError(error)
        throw error
      } finally {
        this.loading = false
      }
    },

    async updateItem(itemId, quantity) {
      if (!this.token) return
      this.loading = true
      this.error = null

      try {
        const { data } = await axios.patch(`${apiBaseUrl}/shop/cart/items/${itemId}`, {
          cart_token: this.token,
          quantity
        })
        this.applyCartResponse(data)
      } catch (error) {
        this.error = this.extractError(error)
        throw error
      } finally {
        this.loading = false
      }
    },

    async removeItem(itemId) {
      if (!this.token) return
      this.loading = true
      this.error = null

      try {
        const { data } = await axios.delete(`${apiBaseUrl}/shop/cart/items/${itemId}`, {
          data: { cart_token: this.token }
        })
        this.applyCartResponse(data)
      } catch (error) {
        this.error = this.extractError(error)
        throw error
      } finally {
        this.loading = false
      }
    },

    clearCartState() {
      this.items = []
      this.subtotal = 0
      this.currency = 'INR'
    },

    applyCartResponse(payload) {
      const cart = payload.data ?? payload
      if (!cart) return

      this.token = cart.token
      localStorage.setItem(CART_TOKEN_KEY, this.token)

      this.currency = cart.currency
      this.items = cart.items || []
      this.subtotal = cart.subtotal || 0
      this.error = null
    },

    extractError(error) {
      if (error.response?.data?.message) {
        return error.response.data.message
      }
      return 'Something went wrong. Please try again.'
    }
  }
})

