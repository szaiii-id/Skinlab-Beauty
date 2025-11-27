<script setup lang="ts">
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import { Facebook, Instagram, Youtube, Twitter } from 'lucide-vue-next';

const email = ref('');

const handleNewsletterSubmit = () => {
    alert(`Thank you for subscribing with ${email.value}!`);
    email.value = '';
};
</script>

<template>
    <footer class="bg-white border-t border-gray-200">
        <div class="max-w-7xl mx-auto py-16 px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
                
                <!-- Shop Section -->
                <div>
                    <h4 class="text-sm font-semibold text-gray-900 tracking-wider uppercase">Shop</h4>
                    <ul class="mt-4 space-y-3">
                        <li><Link href="/catalog" class="text-base text-gray-500 hover:text-rose-600">Catalog</Link></li>
                        <li><Link href="/catalog" class="text-base text-gray-500 hover:text-rose-600">All Products</Link></li>
                        <li><Link href="/search?q=new" class="text-base text-gray-500 hover:text-rose-600">New Arrivals</Link></li>
                        <li><Link href="/search?q=best" class="text-base text-gray-500 hover:text-rose-600">Best Sellers</Link></li>
                    </ul>
                </div>

                <!-- Help Section -->
                <div>
                    <h4 class="text-sm font-semibold text-gray-900 tracking-wider uppercase">Help</h4>
                    <ul class="mt-4 space-y-3">
                        <li><Link href="/faq" class="text-base text-gray-500 hover:text-rose-600">FAQ</Link></li>
                        <li><Link href="/contact" class="text-base text-gray-500 hover:text-rose-600">Contact Us</Link></li>
                        <li><Link href="/orders" class="text-base text-gray-500 hover:text-rose-600">Shipping & Returns</Link></li>
                        <li><Link href="/orders" class="text-base text-gray-500 hover:text-rose-600">Track Order</Link></li>
                    </ul>
                </div>

                <!-- Company Section -->
                <div>
                    <h4 class="text-sm font-semibold text-gray-900 tracking-wider uppercase">Company</h4>
                    <ul class="mt-4 space-y-3">
                        <li><Link href="/about" class="text-base text-gray-500 hover:text-rose-600">About</Link></li>
                        <li><Link href="/privacy-policy" class="text-base text-gray-500 hover:text-rose-600">Privacy Policy</Link></li>
                        <li><Link href="/terms-of-service" class="text-base text-gray-500 hover:text-rose-600">Terms of Service</Link></li>
                        <li><Link href="/careers" class="text-base text-gray-500 hover:text-rose-600">Careers</Link></li>
                    </ul>
                </div>

                <!-- Newsletter Section -->
                <div>
                    <h4 class="text-sm font-semibold text-gray-900 tracking-wider uppercase">Subscribe</h4>
                    <p class="mt-4 text-gray-500 text-sm">Get the latest deals, beauty tips, and new product announcements.</p>
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
                <p class="text-sm text-gray-500 md:order-1">© 2025 SkinLab Beauty. All rights reserved.</p>
                <div class="flex space-x-6 md:order-2 mt-4 md:mt-0">
                    <a href="https://instagram.com/skinlabbeauty" target="_blank" class="text-gray-400 hover:text-rose-600 transition-colors"><Instagram class="h-6 w-6" /></a>
                    <a href="https://facebook.com/skinlabbeauty" target="_blank" class="text-gray-400 hover:text-rose-600 transition-colors"><Facebook class="h-6 w-6" /></a>
                    <a href="https://twitter.com/skinlabbeauty" target="_blank" class="text-gray-400 hover:text-rose-600 transition-colors"><Twitter class="h-6 w-6" /></a>
                    <a href="https://youtube.com/skinlabbeauty" target="_blank" class="text-gray-400 hover:text-rose-600 transition-colors"><Youtube class="h-6 w-6" /></a>
                </div>
            </div>
        </div>
    </footer>
</template>