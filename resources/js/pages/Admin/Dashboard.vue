<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <AdminLayout title="Overview">
        
        <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
            <h1 class="text-2xl font-bold text-gray-800 mb-4">Dashboard Area</h1>
            <p class="text-gray-600">
                Ini adalah konten utama yang akan tampil di tengah.
                Sidebar dan Header sekarang sudah terpisah di file komponen masing-masing.
            </p>
        </div>

    </AdminLayout>
</template>