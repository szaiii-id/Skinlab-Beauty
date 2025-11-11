<script setup lang="ts">
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
// Ikon untuk Social Media
import { Facebook, Instagram, Youtube, Twitter } from 'lucide-vue-next';

const email = ref('');

const handleNewsletterSubmit = () => {
    // Nanti, kita akan kirim 'email.value' ke backend
    alert(`Thank you for subscribing with ${email.value}!`);
    email.value = '';
};
</script>

<template>
    <footer class="bg-white border-t border-gray-200">
        <div class="max-w-7xl mx-auto py-16 px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
                
                <div>
                    <h4 class="text-sm font-semibold text-gray-900 tracking-wider uppercase">Shop</h4>
                    <ul class="mt-4 space-y-3">
                        <li>
                            <Link href="/" class="text-base text-gray-500 hover:text-rose-600">
                                Catalog
                            </Link>
                        </li>
                        <li>
                            <Link href="#" class="text-base text-gray-500 hover:text-rose-600">
                                New Arrivals
                            </Link>
                        </li>
                        <li>
                            <Link href="#" class="text-base text-gray-500 hover:text-rose-600">
                                Best Sellers
                            </Link>
                        </li>
                        <li>
                            <Link href="#" class="text-base text-gray-500 hover:text-rose-600">
                                Brands
                            </Link>
                        </li>
                    </ul>
                </div>

                <div>
                    <h4 class="text-sm font-semibold text-gray-900 tracking-wider uppercase">Help</h4>
                    <ul class="mt-4 space-y-3">
                        <li>
                            <Link href="#" class="text-base text-gray-500 hover:text-rose-600">
                                FAQ
                            </Link>
                        </li>
                        <li>
                            <Link href="#" class="text-base text-gray-500 hover:text-rose-600">
                                Contact Us
                            </Link>
                        </li>
                        <li>
                            <Link href="#" class="text-base text-gray-500 hover:text-rose-600">
                                Shipping & Returns
                            </Link>
                        </li>
                        <li>
                            <Link href="#" class="text-base text-gray-500 hover:text-rose-600">
                                Track Order
                            </Link>
                        </li>
                    </ul>
                </div>

                <div>
                    <h4 class="text-sm font-semibold text-gray-900 tracking-wider uppercase">Company</h4>
                    <ul class="mt-4 space-y-3">
                        <li>
                            <Link href="/about" class="text-base text-gray-500 hover:text-rose-600">
                                About
                            </Link>
                        </li>
                        <li>
                            <Link href="#" class="text-base text-gray-500 hover:text-rose-600">
                                Privacy Policy
                            </Link>
                        </li>
                        <li>
                            <Link href="#" class="text-base text-gray-500 hover:text-rose-600">
                                Terms of Service
                            </Link>
                        </li>
                    </ul>
                </div>

                <div>
                    <h4 class="text-sm font-semibold text-gray-900 tracking-wider uppercase">Subscribe</h4>
                    <p class="mt-4 text-gray-500 text-sm">
                        Get the latest deals, beauty tips, and new product announcements.
                    </p>
                    <form @submit.prevent="handleNewsletterSubmit" class="mt-4 flex">
                        <input 
                            v-model="email"
                            type="email" 
                            placeholder="Your email"
                            required
                            class="w-full px-4 py-2 border border-gray-300 rounded-l-md focus:outline-none focus:ring-2 focus:ring-rose-400 text-sm"
                        />
                        <button 
                            type="submit"
                            class="px-4 py-2 bg-rose-600 text-white font-semibold rounded-r-md hover:bg-rose-700 transition-colors text-sm"
                        >
                            Go
                        </button>
                    </form>
                </div>

            </div>

            <div class="mt-12 border-t border-gray-200 pt-8 flex flex-col md:flex-row justify-between items-center">
                <p class="text-sm text-gray-500 md:order-1">
                    © 2025 SkinLab Beauty. All rights reserved.
                </p>
                <div class="flex space-x-6 md:order-2 mt-4 md:mt-0">
                    <Link href="#" class="text-gray-400 hover:text-rose-600">
                        <Instagram class="h-6 w-6" />
                    </Link>
                    <Link href="#" class="text-gray-400 hover:text-rose-600">
                        <Facebook class="h-6 w-6" />
                    </Link>
                    <Link href="#" class="text-gray-400 hover:text-rose-600">
                        <Twitter class="h-6 w-6" />
                    </Link>
                    <Link href="#" class="text-gray-400 hover:text-rose-600">
                        <Youtube class="h-6 w-6" />
                    </Link>
                </div>
            </div>
        </div>
    </footer>
</template>