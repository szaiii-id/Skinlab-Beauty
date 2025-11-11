<script setup>
import { Head, Link } from '@inertiajs/vue3';
// Gunakan layout navbar Anda (pastikan path-nya benar)
import AppNavbarLayout from '@/layouts/AppNavbarLayout.vue'; 

defineOptions({
    layout: AppNavbarLayout
});
</script>

<template>
    <Head title="My Account" />

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <h1 class="text-3xl font-light text-gray-900 mb-8">My Account</h1>

            <div class="flex flex-col md:flex-row gap-8">
                
                <nav class="md:w-1/4">
                    <ul class="space-y-2">
                        <li>
                            <Link 
                                href="/my-account"
                                class="block w-full px-4 py-3 text-sm font-medium rounded-lg"
                                :class="{
                                    'bg-rose-100 text-rose-700': $page.url === '/my-account',
                                    'text-gray-600 hover:bg-gray-100': $page.url !== '/my-account' 
                                }"
                            >
                                My Profile
                            </Link>
                        </li>
                        <li>
                            <Link 
                                href="#" class="block w-full px-4 py-3 text-sm font-medium rounded-lg text-gray-600 hover:bg-gray-100"
                            >
                                Order History
                            </Link>
                        </li>
                        <li>
                            <Link 
                                href="#" class="block w-full px-4 py-3 text-sm font-medium rounded-lg text-gray-600 hover:bg-gray-100"
                            >
                                My Addresses
                            </Link>
                        </li>
                        <li>
                            <Link 
                                href="#" class="block w-full px-4 py-3 text-sm font-medium rounded-lg text-gray-600 hover:bg-gray-100"
                            >
                                Security & Password
                            </Link>
                        </li>
                    </ul>
                </nav>

                <div class="md:w-3/4">
                    <div class="bg-white rounded-lg shadow-xl overflow-hidden p-8">
                        <h2 class="text-2xl font-semibold text-gray-900 mb-6">
                            Profile Settings
                        </h2>
                        
                        <form @submit.prevent>
                            <div class="space-y-6">
                                <div>
                                    <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
                                    <input 
                                        type="text" 
                                        id="name"
                                        class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-rose-500 focus:border-rose-500"
                                        placeholder="Your Name"
                                    >
                                </div>
                                <div>
                                    <label for="email" class="block text-sm font-medium text-gray-700">Email Address</label>
                                    <input 
                                        type="email" 
                                        id="email"
                                        class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-rose-500 focus:border-rose-500"
                                        placeholder="your@email.com"
                                    >
                                </div>
                                
                                <div class="border-t pt-6">
                                    <button 
                                        type="submit"
                                        class="px-6 py-2 bg-rose-600 text-white font-semibold rounded-md hover:bg-rose-700"
                                    >
                                        Save Changes
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>
</template>