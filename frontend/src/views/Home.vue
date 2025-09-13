<script setup>
import { onMounted, ref } from 'vue'
import { api } from '../lib/api'

const loading = ref(true)
const products = ref([])
const error = ref(null)

async function load() {
  try {
    loading.value = true
    const { data } = await api.get('/products/random', { params: { count: 8 } })
    const list = data.member || data['hydra:member'] || []
    products.value = list
  } catch (e) {
    error.value = e?.message || 'Ismeretlen hiba'
  } finally {
    loading.value = false
  }
}

onMounted(load)

function formatPrice(cents) {
  return new Intl.NumberFormat('hu-HU', { style: 'currency', currency: 'HUF', maximumFractionDigits: 0 }).format(cents / 100)
}
</script>

<template>
  <section class="space-y-6">
    <h1 class="text-2xl font-semibold">Kiemelt / véletlen termékek</h1>

    <div v-if="error" class="p-4 bg-red-50 text-red-700 rounded">{{ error }}</div>

    <div v-if="loading" class="grid grid-cols-2 md:grid-cols-4 gap-4">
      <div v-for="n in 8" :key="n" class="rounded-xl bg-white p-4 shadow-sm animate-pulse space-y-3">
        <div class="h-32 bg-gray-200 rounded"></div>
        <div class="h-4 bg-gray-200 rounded w-3/4"></div>
        <div class="h-4 bg-gray-200 rounded w-1/2"></div>
      </div>
    </div>

    <div v-else class="grid grid-cols-2 md:grid-cols-4 gap-4">
      <article v-for="p in products" :key="p.id" class="rounded-xl bg-white p-4 shadow-sm hover:shadow-md transition">
        <div class="aspect-square bg-gray-100 rounded flex items-center justify-center overflow-hidden">
          <img v-if="p.imageUrl" :src="p.imageUrl" :alt="p.name" class="object-contain w-full h-full" />
          <span v-else class="text-gray-400 text-sm">Nincs kép</span>
        </div>
        <RouterLink :to="`/products/${p.id}`" class="mt-3 block font-medium hover:underline line-clamp-2">{{ p.name }}</RouterLink>
        <div class="mt-1 text-sm text-gray-500 line-clamp-2">{{ p.description }}</div>
        <div class="mt-2 font-semibold">{{ formatPrice(p.priceCents) }}</div>
      </article>
    </div>
  </section>
</template>
