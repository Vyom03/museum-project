import { createRouter, createWebHistory } from 'vue-router'

import ShopHome from '@/views/ShopHome.vue'
import ProductDetail from '@/views/ProductDetail.vue'
import CartView from '@/views/CartView.vue'
import CheckoutView from '@/views/CheckoutView.vue'
import TourRegistrationForm from '@/components/TourRegistrationForm.vue'

const routes = [
  {
    path: '/',
    redirect: '/shop'
  },
  {
    path: '/shop',
    name: 'shop-home',
    component: ShopHome
  },
  {
    path: '/shop/product/:slug',
    name: 'product-detail',
    component: ProductDetail,
    props: true
  },
  {
    path: '/shop/cart',
    name: 'cart',
    component: CartView
  },
  {
    path: '/shop/checkout',
    name: 'checkout',
    component: CheckoutView
  },
  {
    path: '/tours',
    name: 'tour-registration',
    component: TourRegistrationForm
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior() {
    return { top: 0 }
  }
})

export default router

