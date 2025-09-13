<script setup>
</script>

<template>
  <div class="min-h-screen bg-gray-50 text-gray-900">
    <header class="bg-white border-b sticky top-0 z-10">
      <nav class="container mx-auto px-4 py-3 flex items-center gap-6">
        <RouterLink to="/" class="font-semibold">Főoldal</RouterLink>
        <RouterLink to="/products" class="text-sm hover:underline">Termékek</RouterLink>
      </nav>
    </header>

    <main class="container mx-auto px-4 py-6">
      <RouterView />
    </main>

    <footer class="border-t py-6 text-center text-sm text-gray-500">
      © {{ new Date().getFullYear() }} Marso Katalógus | Gyarmati Bence
    </footer>
  </div>
</template>
