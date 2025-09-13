<script setup>
import { ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { api } from '../lib/api'

const route = useRoute()
const router = useRouter()

// kereső és állapot
const q = ref(route.query.q || '')
const order = ref(route.query.order || '')
const dir = ref(route.query.dir || 'asc')
const page = ref(Number(route.query.page || 1))
const showAll = ref(route.query.showAll === '1') // ha valaki mégis az összeset akarja

const loading = ref(false)
const items = ref([])
const total = ref(0)
const view = ref({ next: null, prev: null })
const hasSearched = ref(!!q.value || showAll.value) // első render: csak akkor “keresett”, ha q volt vagy showAll=1

function parseHydraView(data) {
  const v = data['hydra:view'] || data.view || {}
  return {
    next: v['hydra:next'] || v.next || null,
    prev: v['hydra:previous'] || v.previous || null,
  }
}

async function load() {
  loading.value = true
  try {
    const params = { page: page.value }
    if (!showAll.value) {
      // csak névre keresünk (ha szeretnéd, átválthatjuk description-re, de OR-olni csak külön backenddel szép)
      if (!q.value) {
        items.value = []
        total.value = 0
        view.value = { next: null, prev: null }
        return
      }
      params.name = q.value
    }
    if (order.value) {
      params[`order[${order.value}]`] = dir.value
    }
    const { data } = await api.get('/products', { params })
    items.value = data['hydra:member'] || data.member || []
    total.value = data['hydra:totalItems'] ?? data.totalItems ?? items.value.length
    view.value = parseHydraView(data)
  } finally {
    loading.value = false
  }
}

function onSearch() {
  hasSearched.value = true
  router.replace({
    name: 'products',
    query: {
      q: q.value || undefined,
      order: order.value || undefined,
      dir: dir.value || undefined,
      page: 1,
      showAll: showAll.value ? '1' : undefined,
    },
  })
}

function showAllNow() {
  showAll.value = true
  hasSearched.value = true
  router.replace({ name: 'products', query: { showAll: '1', page: 1 } })
}

function go(delta) {
  router.push({ name: 'products', query: { ...route.query, page: Math.max(1, page.value + delta) } })
}

// ha a query változik, újratöltünk (de csak ha van q vagy showAll)
watch(() => route.query, () => {
  q.value = route.query.q || ''
  order.value = route.query.order || ''
  dir.value = route.query.dir || 'asc'
  page.value = Number(route.query.page || 1)
  showAll.value = route.query.showAll === '1'
  if (q.value || showAll.value) load()
})
</script>

<template>
  <section class="space-y-6">
    <h1 class="text-2xl font-semibold">Összes termék</h1>

    <!-- Keresősor -->
    <div class="flex flex-col md:flex-row gap-3 md:items-center">
      <input
        v-model="q"
        @keyup.enter="onSearch"
        type="search"
        placeholder="Keresés név / leírás…"
        class="border rounded px-3 py-2 w-full md:w-80"
      />

      <div class="flex gap-2 items-center">
        <select v-model="order" class="border rounded px-2 py-2">
          <option value="">Rendezés nélkül</option>
          <option value="priceCents">Ár</option>
          <option value="name">Név</option>
          <option value="createdAt">Dátum</option>
        </select>
        <select v-model="dir" class="border rounded px-2 py-2" :disabled="!order">
          <option value="asc">Növekvő</option>
          <option value="desc">Csökkenő</option>
        </select>

        <button @click="onSearch" class="px-4 py-2 rounded bg-black text-white">Keresés</button>
        <button v-if="!hasSearched" @click="showAllNow" class="px-4 py-2 rounded border bg-white hover:bg-gray-50">
          Összes megjelenítése
        </button>
      </div>

      <div class="ml-auto text-sm text-gray-500" v-if="hasSearched">
        Oldalanként: 24 • Összesen: {{ total }}
      </div>
    </div>

    <!-- Üres állapot keresés előtt -->
    <div v-if="!hasSearched" class="rounded-xl bg-white p-6 border text-gray-600">
      Írj be egy kulcsszót a kereséshez, vagy kattints az <strong>Összes megjelenítése</strong> gombra.
    </div>

    <!-- Skeleton -->
    <div v-else-if="loading" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
      <div v-for="n in 8" :key="n" class="rounded-xl bg-white p-4 shadow-sm animate-pulse space-y-3">
        <div class="h-32 bg-gray-200 rounded"></div>
        <div class="h-4 bg-gray-200 rounded w-3/4"></div>
        <div class="h-4 bg-gray-200 rounded w-1/2"></div>
      </div>
    </div>

    <!-- Lista -->
    <div v-else class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
      <article v-for="p in items" :key="p.id" class="rounded-xl bg-white p-4 shadow-sm hover:shadow-md transition">
        <div class="aspect-square bg-gray-100 rounded flex items-center justify-center overflow-hidden">
          <img v-if="p.imageUrl" :src="p.imageUrl" :alt="p.name" class="object-contain w-full h-full" />
          <span v-else class="text-gray-400 text-sm">Nincs kép</span>
        </div>
        <RouterLink :to="`/products/${p.id}`" class="mt-3 block font-medium hover:underline line-clamp-2">{{ p.name }}</RouterLink>
        <div class="mt-2 font-semibold">
          {{ new Intl.NumberFormat('hu-HU', { style: 'currency', currency: 'HUF', maximumFractionDigits: 0 }).format(p.priceCents / 100) }}
        </div>
      </article>
    </div>

    <!-- Lapozó -->
    <div class="mt-4 flex items-center gap-2" v-if="hasSearched && items.length">
      <button class="px-3 py-2 border rounded" :disabled="page<=1" @click="go(-1)">Előző</button>
      <span class="text-sm">Oldal: {{ page }}</span>
      <button class="px-3 py-2 border rounded" :disabled="!view.next" @click="go(1)">Következő</button>
    </div>
  </section>
</template>
