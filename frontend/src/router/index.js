import { createRouter, createWebHistory } from 'vue-router'

const routes = [
  { path: '/', name: 'home', component: () => import('../views/Home.vue') },
  { path: '/products', name: 'products', component: () => import('../views/Products.vue') },
  { path: '/products/:id(\\d+)', name: 'product', component: () => import('../views/ProductDetail.vue') },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior() { return { top: 0 } },
})

export default router
