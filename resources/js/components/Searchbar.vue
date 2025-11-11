<script setup lang="ts">
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { Search } from 'lucide-vue-next';

const query = ref('');

// Fungsi ini akan mencoba memanggil backend
// (Backend-nya bisa kita buat nanti)
const submitSearch = () => {
    if (query.value.trim()) {
        router.get('/search', { q: query.value }, {
            preserveState: true // Agar tidak reload penuh
        });
        // Nanti kita akan buat Rute GET /search di routes/web.php
    }
};
</script>

<template>
    <form @submit.prevent="submitSearch" class="relative w-full max-w-md">
        <input
            type="text"
            v-model="query"
            placeholder="Search products..."
            class="w-full pl-10 pr-4 py-2 rounded-full border border-gray-300 bg-gray-100 focus:bg-white focus:outline-none focus:ring-2 focus:ring-rose-400"
        />
        <span class="absolute left-3 top-1/2 -translate-y-1/2">
            <Search class="h-5 w-5 text-gray-400" />
        </span>
    </form>
</template>