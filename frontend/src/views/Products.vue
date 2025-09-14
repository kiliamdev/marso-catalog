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
const showAll = ref(route.query.showAll === '1')

// SZŰRŐK
// season: '', 'nyari', 'teli', 'allseason' (HU), a backend felé EN kulcsszót küldünk
const season = ref(route.query.season || '')
// diameter integer (pl. 13 → "R13" a névben)
const diameter = ref(route.query.diameter ? Number(route.query.diameter) : null)

const loading = ref(false)
const items = ref([])
const total = ref(0)
const view = ref({ next: null, prev: null })
const hasSearched = ref(!!q.value || showAll.value || !!season.value || !!diameter.value)

// Hydra view parser
function parseHydraView(data) {
  const v = data['hydra:view'] || data.view || {}
  return {
    next: v['hydra:next'] || v.next || null,
    prev: v['hydra:previous'] || v.previous || null,
  }
}

// HU → EN szezon kulcsszó a névhez (a dataset tényleg "winter/summer/crossclimate/all season" mintákat tartalmaz)
function seasonKeywordHUtoEN(value) {
  switch (value) {
    case 'teli':       return 'winter'         // pl. "WINTER", "Alpin", "Snow" — a "winter" biztos kulcs
    case 'nyari':      return 'summer'         // nyárihoz "summer"
    case 'allseason':  return 'crossclimate'   // sok all-season Michelin név "CrossClimate"; alternatíva: "all season"
    default:           return ''
  }
}

// Átmérő → "R13" token
function diameterToken(value) {
  if (!value) return ''
  const n = Number(value)
  if (!Number.isInteger(n) || n < 10 || n > 30) return ''
  return `R${n}`
}

async function load() {
  loading.value = true
  try {
    const params = { page: page.value }

    // Ha nincs semmilyen szűrő és nem kérte az összeset, üres állapot
    const anyFilter = !!q.value || !!season.value || !!diameter.value || !!order.value
    if (!showAll.value && !anyFilter) {
      items.value = []
      total.value = 0
      view.value = { next: null, prev: null }
      return
    }

    // name/description partial keresés összerakása
    // FONTOS: az ApiPlatform SearchFilter (partial) egy LIKE, ezért egyetlen kulcsszó a legstabilabb.
    // - szabad szöveg (q) → name
    // - szezon → EN kulcsszó a name-hez
    // - átmérő → "R13" a name-hez
    // Ha több van, egyben fűzzük — a legtöbb névben a szavak sorrendje rugalmas, de ha túl szigorú, hagyd meg csak a legfontosabbat.
    const nameParts = []

    if (q.value) nameParts.push(q.value.trim())

    const seasonKW = seasonKeywordHUtoEN(season.value)
    if (seasonKW) nameParts.push(seasonKW)

    const diaKW = diameterToken(diameter.value)
    if (diaKW) nameParts.push(diaKW)

    const nameQuery = nameParts.join(' ').trim()
    if (nameQuery) params.name = nameQuery

    // ha csak description-ben szeretnél keresni is:
    // if (q.value) params.description = q.value

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
      season: season.value || undefined,
      diameter: diameter.value || undefined,
    },
  })
}

function showAllNow() {
  showAll.value = true
  hasSearched.value = true
  router.replace({
    name: 'products',
    query: {
      showAll: '1',
      page: 1,
      season: season.value || undefined,
      diameter: diameter.value || undefined,
    },
  })
}

function go(delta) {
  router.push({
    name: 'products',
    query: { ...route.query, page: Math.max(1, page.value + delta) },
  })
}

// Query változásra újratöltés
watch(() => route.query, () => {
  q.value = route.query.q || ''
  order.value = route.query.order || ''
  dir.value = route.query.dir || 'asc'
  page.value = Number(route.query.page || 1)
  showAll.value = route.query.showAll === '1'
  season.value = route.query.season || ''
  diameter.value = route.query.diameter ? Number(route.query.diameter) : null

  if (q.value || showAll.value || season.value || diameter.value) load()
})
</script>

<template>
  <section class="space-y-6">
    <h1 class="text-2xl font-semibold">Összes termék</h1>

    <!-- Kereső + szűrők -->
    <div class="flex flex-col gap-3">
      <div class="flex flex-col md:flex-row gap-3 md:items-center">
        <input
          v-model="q"
          @keyup.enter="onSearch"
          type="search"
          placeholder="Keresés név / leírás…"
          class="border rounded px-3 py-2 w-full md:w-80 bg-white text-gray-800 placeholder:text-gray-400"
        />

        <!-- Évszak (HU) → EN kulcsszó a névben -->
        <select v-model="season" class="border rounded px-2 py-2 w-full md:w-48 bg-white text-gray-800">
          <option value="">Évszak (mind)</option>
          <option value="nyari">Nyári</option>
          <option value="teli">Téli</option>
          <option value="allseason">4 évszakos</option>
        </select>

        <!-- Átmérő (R13 → 13") -->
        <select
          v-model.number="diameter"
          class="border rounded px-2 py-2 w-full md:w-40 bg-white text-gray-800"
        >
          <option :value="null">Átmérő (mind)</option>
          <option v-for="d in Array.from({ length: 11 }, (_, i) => i + 12)" :key="d" :value="d">
              {{ d }}"
            </option>
        </select>


        <div class="flex gap-2 items-center">
          <select v-model="order" class="border rounded px-2 py-2 bg-white text-gray-800">
            <option value="">Rendezés nélkül</option>
            <option value="priceCents">Ár</option>
            <option value="name">Név</option>
            <option value="createdAt">Dátum</option>
          </select>
          <select v-model="dir" class="border rounded px-2 py-2 bg-white text-gray-800" :disabled="!order">
            <option value="asc">Növekvő</option>
            <option value="desc">Csökkenő</option>
          </select>

          <!-- Gombok: explicit világos stílus -->
          <button
            @click="onSearch"
            class="inline-flex items-center justify-center px-4 py-2 rounded-lg border border-gray-300 bg-white text-gray-800 hover:bg-gray-100 active:bg-gray-200 transition"
          >
            Keresés
          </button>
          <button
            v-if="!hasSearched"
            @click="showAllNow"
            class="inline-flex items-center justify-center px-4 py-2 rounded-lg border border-gray-300 bg-white text-gray-800 hover:bg-gray-100 active:bg-gray-200 transition"
          >
            Összes megjelenítése
          </button>
        </div>

        <div class="md:ml-auto text-sm text-gray-500" v-if="hasSearched">
          Oldalanként: 20 • Összesen: {{ total }}
        </div>
      </div>

      <p class="text-xs text-gray-500">
        Tipp: az átmérő szűrő a terméknévben szereplő <code>R13</code>, <code>R14</code>… mintákra keres.
        Az évszak szűrés a magyar választást angol kulcsszóra fordítja (pl. <em>Nyári → summer</em>,
        <em>Téli → winter</em>, <em>4 évszakos → crossclimate</em>).
      </p>
    </div>

    <!-- Üres állapot keresés előtt -->
    <div v-if="!hasSearched" class="rounded-xl bg-white p-6 border text-gray-600">
      Írj be egy kulcsszót a kereséshez, válassz szűrőket, vagy kattints az <strong>Összes megjelenítése</strong> gombra.
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
        <RouterLink :to="`/products/${p.id}`" class="mt-3 block font-medium hover:underline line-clamp-2">
          {{ p.name }}
        </RouterLink>
        <div class="mt-2 font-semibold">
          {{
            new Intl.NumberFormat('hu-HU', {
              style: 'currency',
              currency: 'HUF',
              maximumFractionDigits: 0
            }).format(p.priceCents / 100)
          }}
        </div>
      </article>
    </div>

    <!-- Lapozó (világos, jól látható) -->
    <div class="mt-4 flex items-center gap-2" v-if="hasSearched && items.length">
      <button
        class="inline-flex items-center justify-center px-3 py-2 rounded-lg border border-gray-300 bg-white text-gray-800 hover:bg-gray-100 active:bg-gray-200 disabled:bg-gray-200 disabled:text-gray-400 disabled:cursor-not-allowed transition"
        :disabled="page<=1"
        @click="go(-1)"
      >
        Előző
      </button>

      <span class="text-sm text-gray-700">Oldal: {{ page }}</span>

      <button
        class="inline-flex items-center justify-center px-3 py-2 rounded-lg border border-gray-300 bg-white text-gray-800 hover:bg-gray-100 active:bg-gray-200 disabled:bg-gray-200 disabled:text-gray-400 disabled:cursor-not-allowed transition"
        :disabled="!view.next"
        @click="go(1)"
      >
        Következő
      </button>
    </div>
  </section>
</template>
