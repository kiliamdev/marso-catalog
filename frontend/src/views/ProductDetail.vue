<script setup>
import { onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { api } from '../lib/api'

const route = useRoute()
const product = ref(null)
const loading = ref(true)

onMounted(async () => {
  loading.value = true
  try {
    const { data } = await api.get(`/products/${route.params.id}`)
    product.value = data
  } finally {
    loading.value = false
  }
})

function formatPrice(cents) {
  return new Intl.NumberFormat('hu-HU', { style: 'currency', currency: 'HUF', maximumFractionDigits: 0 }).format(cents / 100)
}
</script>

<template>
  <div v-if="loading" class="p-6 bg-white rounded shadow animate-pulse h-64"></div>
  <div v-else-if="product" class="grid md:grid-cols-2 gap-6">
    <div class="rounded bg-white p-4 shadow">
      <div class="aspect-square bg-gray-100 rounded flex items-center justify-center overflow-hidden">
        <img v-if="product.imageUrl" :src="product.imageUrl" :alt="product.name" class="object-contain w-full h-full" />
        <span v-else class="text-gray-400 text-sm">Nincs kép</span>
      </div>
    </div>
    <div class="space-y-3">
      <h1 class="text-2xl font-semibold">{{ product.name }}</h1>
      <div class="text-gray-600">{{ product.description }}</div>
      <div class="text-2xl font-bold">{{ formatPrice(product.priceCents) }}</div>
      <RouterLink to="/cart" class="inline-block mt-2 px-4 py-2 rounded bg-black text-white">Kosárba</RouterLink>
    </div>
  </div>
</template>
