import { createRouter, createWebHistory } from 'vue-router'

import ShopHome from '@/views/ShopHome.vue'
import ProductDetail from '@/views/ProductDetail.vue'
import CartView from '@/views/CartView.vue'
import CheckoutView from '@/views/CheckoutView.vue'
import AboutView from '@/views/AboutView.vue'
import AdminDashboard from '@/views/AdminDashboard.vue'
import AdminLogin from '@/views/AdminLogin.vue'
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
    path: '/about',
    name: 'about',
    component: AboutView
  },
  {
    path: '/admin/dashboard',
    name: 'admin-dashboard',
    component: AdminDashboard,
    meta: { requiresAdmin: true }
  },
  {
    path: '/admin/login',
    name: 'admin-login',
    component: AdminLogin,
    meta: { isAdminLogin: true }
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

const ADMIN_TOKEN_KEY = 'vyomAdminCreds'

function hasAdminCredentials() {
  if (typeof window === 'undefined') return false
  return Boolean(localStorage.getItem(ADMIN_TOKEN_KEY))
}

router.beforeEach((to, from, next) => {
  if (to.meta.requiresAdmin && !hasAdminCredentials()) {
    return next({ name: 'admin-login', query: { redirect: to.fullPath } })
  }

  if (to.meta.isAdminLogin && hasAdminCredentials()) {
    return next({ name: 'admin-dashboard' })
  }

  return next()
})

export default router

