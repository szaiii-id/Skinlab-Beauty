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
    <footer class="bg-white border-t border-gray-200 font-sans">
        <div class="max-w-7xl mx-auto pt-12 px-4 sm:px-6 lg:px-8 pb-24 md:pb-16">
            
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8 mb-12">
                
                <div>
                    <h4 class="text-sm font-bold text-gray-900 tracking-widest uppercase mb-4">Shop</h4>
                    <ul class="space-y-3">
                        <li><Link href="/catalog" class="text-sm text-gray-500 hover:text-rose-600 transition-colors">Catalog</Link></li>
                        <li><Link href="/catalog" class="text-sm text-gray-500 hover:text-rose-600 transition-colors">All Products</Link></li>
                        <li><Link href="/search?q=new" class="text-sm text-gray-500 hover:text-rose-600 transition-colors">New Arrivals</Link></li>
                        <li><Link href="/search?q=best" class="text-sm text-gray-500 hover:text-rose-600 transition-colors">Best Sellers</Link></li>
                    </ul>
                </div>

                <div>
                    <h4 class="text-sm font-bold text-gray-900 tracking-widest uppercase mb-4">Help</h4>
                    <ul class="space-y-3">
                        <li><Link href="/faq" class="text-sm text-gray-500 hover:text-rose-600 transition-colors">FAQ</Link></li>
                        <li><Link href="/contact" class="text-sm text-gray-500 hover:text-rose-600 transition-colors">Contact Us</Link></li>
                        <li><Link href="/orders" class="text-sm text-gray-500 hover:text-rose-600 transition-colors">Shipping & Returns</Link></li>
                        <li><Link href="/orders" class="text-sm text-gray-500 hover:text-rose-600 transition-colors">Track Order</Link></li>
                    </ul>
                </div>

                <div>
                    <h4 class="text-sm font-bold text-gray-900 tracking-widest uppercase mb-4">Company</h4>
                    <ul class="space-y-3">
                        <li><Link href="/about" class="text-sm text-gray-500 hover:text-rose-600 transition-colors">About</Link></li>
                        <li><Link href="/privacy-policy" class="text-sm text-gray-500 hover:text-rose-600 transition-colors">Privacy Policy</Link></li>
                        <li><Link href="/terms-of-service" class="text-sm text-gray-500 hover:text-rose-600 transition-colors">Terms of Service</Link></li>
                        <li><Link href="/careers" class="text-sm text-gray-500 hover:text-rose-600 transition-colors">Careers</Link></li>
                    </ul>
                </div>

                <div class="col-span-2 md:col-span-1 mt-4 md:mt-0">
                    <h4 class="text-sm font-bold text-gray-900 tracking-widest uppercase mb-4">Subscribe</h4>
                    <p class="text-gray-500 text-sm mb-4 leading-relaxed">
                        Get the latest deals, beauty tips, and new product announcements.
                    </p>
                    <form @submit.prevent="handleNewsletterSubmit" class="flex shadow-sm rounded-md">
                        <input 
                            v-model="email"
                            type="email" 
                            placeholder="Your email"
                            required
                            class="w-full px-4 py-2 border border-gray-300 rounded-l-lg focus:outline-none focus:ring-2 focus:ring-rose-400 focus:border-transparent text-sm"
                        />
                        <button 
                            type="submit"
                            class="px-4 py-2 bg-rose-600 text-white font-semibold rounded-r-lg hover:bg-rose-700 transition-colors text-sm whitespace-nowrap"
                        >
                            Join
                        </button>
                    </form>
                </div>

            </div>

            <div class="pt-8 border-t border-gray-200 flex flex-col md:flex-row justify-between items-center gap-4">
                
                <p class="text-xs text-gray-400 text-center md:text-left md:order-1">
                    © 2025 SkinLab Beauty. All rights reserved.
                </p>
                
                <div class="flex space-x-6 md:order-2">
                    <a href="https://instagram.com/skinlabbeauty" target="_blank" class="text-gray-400 hover:text-rose-600 hover:scale-110 transition-all p-1">
                        <span class="sr-only">Instagram</span>
                        <Instagram class="h-5 w-5" />
                    </a>
                    <a href="https://facebook.com/skinlabbeauty" target="_blank" class="text-gray-400 hover:text-rose-600 hover:scale-110 transition-all p-1">
                        <span class="sr-only">Facebook</span>
                        <Facebook class="h-5 w-5" />
                    </a>
                    <a href="https://twitter.com/skinlabbeauty" target="_blank" class="text-gray-400 hover:text-rose-600 hover:scale-110 transition-all p-1">
                        <span class="sr-only">Twitter</span>
                        <Twitter class="h-5 w-5" />
                    </a>
                    <a href="https://youtube.com/skinlabbeauty" target="_blank" class="text-gray-400 hover:text-rose-600 hover:scale-110 transition-all p-1">
                        <span class="sr-only">YouTube</span>
                        <Youtube class="h-5 w-5" />
                    </a>
                </div>
            </div>
        </div>
    </footer>
</template>