<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <aside class="w-64 bg-white border-r border-gray-200 flex flex-col">
        
        <div class="h-16 flex items-center justify-center border-b border-gray-200">
            <h1 class="text-xl font-semibold text-rose-600">SkinLab</h1>
        </div>
        
        <nav class="flex flex-col space-y-2 p-4 mt-4">
            
            <Link 
                href="/"
                :class="{ 'bg-rose-50 text-rose-600': $page.url === '/' }"
                class="flex items-center space-x-3 px-3 py-2 rounded-md text-sm font-medium text-gray-700 hover:bg-rose-50 hover:text-rose-600"
            >
                <span>Catalog</span>
            </Link>
            
            <Link 
                href="/cart" 
                :class="{ 'bg-rose-50 text-rose-600': $page.url.startsWith('/cart') }"
                class="flex items-center space-x-3 px-3 py-2 rounded-md text-sm font-medium text-gray-700 hover:bg-rose-50 hover:text-rose-600"
            >
                <span>Cart</span>
            </Link>
            
            <Link 
                href="/dashboard"
                :class="{ 'bg-rose-50 text-rose-600': $page.url.startsWith('/dashboard') }"
                class="flex items-center space-x-3 px-3 py-2 rounded-md text-sm font-medium text-gray-700 hover:bg-rose-50 hover:text-rose-600"
            >
                <span>Dashboard</span>
            </Link>

        </nav>
    </aside>
</template>