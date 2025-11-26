<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import Searchbar from '@/components/Searchbar.vue';
import { 
    ShoppingBag, 
    ShoppingCart, 
    Heart,
    ChevronDown,
    User,
    Info,
    Home
} from 'lucide-vue-next';

import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

const page = usePage();
const categories = computed(() => page.props.categories || []);
const brands = computed(() => page.props.brands || []);
const cartCount = computed(() => page.props.cartCount || 0);
const user = computed(() => page.props.auth.user);

// Ambil jumlah pending order dari props (backend)
const pendingOrdersCount = computed(() => page.props.pendingOrdersCount || 0);

const wishlistCount = ref(page.props.wishlistCount || 0);

watch(() => page.props.wishlistCount, (newCount) => {
    wishlistCount.value = newCount || 0;
});

const handleWishlistUpdate = (event) => {
    wishlistCount.value = event.detail.count;
};

onMounted(() => {
    window.addEventListener('wishlist-updated', handleWishlistUpdate);
});

onUnmounted(() => {
    window.removeEventListener('wishlist-updated', handleWishlistUpdate);
});

// Helper Kondisi Aktif
const isHomeActive = computed(() => page.url === '/');
const isCatalogActive = computed(() => page.url === '/catalog' || page.url.startsWith('/products'));
const isAboutActive = computed(() => page.url === '/about');
const isWishlistActive = computed(() => page.url === '/wishlist');
const isCartActive = computed(() => page.url === '/cart');
const isCategoryDropdownActive = computed(() => page.url.startsWith('/categories'));
const isBrandDropdownActive = computed(() => page.url.startsWith('/brands'));

const accountHref = computed(() => user.value ? '/dashboard' : '/login');
const accountTitle = computed(() => user.value ? 'My Dashboard' : 'Login / Register');
</script>

<template>
    <header class="bg-white border-b border-gray-200 shadow-sm sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                
                <div class="flex items-center">
                    <div class="flex-shrink-0 mr-6 cursor-pointer">
                        <Link href="/" class="flex flex-col items-start justify-center">
                            <div class="text-xl font-light leading-none text-rose-800 bg-gradient-to-r from-rose-600 to-pink-600 bg-clip-text text-transparent">
                                SkinLab
                            </div>
                            <div class="text-[8px] text-rose-500 font-medium tracking-[0.25em] uppercase mt-0.5">
                                BEAUTY
                            </div>
                        </Link>
                    </div>
                    
                    <nav class="hidden sm:flex sm:space-x-6 items-center">
                        <Link href="/" :class="isHomeActive ? 'text-rose-600 border-b-2 border-rose-600' : 'text-gray-500 hover:text-gray-900'" class="px-1 py-5 text-sm font-medium transition-colors flex items-center gap-2">
                            <Home class="w-4 h-4" /> Home
                        </Link>
                        
                        <Link href="/catalog" :class="isCatalogActive ? 'text-rose-600 border-b-2 border-rose-600' : 'text-gray-500 hover:text-gray-900'" class="px-1 py-5 text-sm font-medium transition-colors flex items-center gap-2">
                            <ShoppingBag class="w-4 h-4" /> Catalog
                        </Link>

                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <button :class="isCategoryDropdownActive ? 'text-rose-600 border-b-2 border-rose-600' : 'text-gray-500 hover:text-gray-900'" class="px-1 py-5 text-sm font-medium transition-colors flex items-center gap-1 outline-none">
                                    Category <ChevronDown class="w-3 h-3" />
                                </button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent class="w-56 bg-white shadow-lg ring-1 ring-black ring-opacity-5 rounded-md" align="start">
                                <DropdownMenuGroup>
                                    <DropdownMenuItem v-for="category in categories" :key="category.id" as-child>
                                        <Link :href="`/categories/${category.slug}`" class="block w-full px-4 py-2 text-sm text-gray-700 hover:bg-rose-50 hover:text-rose-600">
                                            {{ category.name }}
                                        </Link>
                                    </DropdownMenuItem>
                                </DropdownMenuGroup>
                            </DropdownMenuContent>
                        </DropdownMenu>

                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <button :class="isBrandDropdownActive ? 'text-rose-600 border-b-2 border-rose-600' : 'text-gray-500 hover:text-gray-900'" class="px-1 py-5 text-sm font-medium transition-colors flex items-center gap-1 outline-none">
                                    Brand <ChevronDown class="w-3 h-3" />
                                </button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent class="w-56 bg-white shadow-lg ring-1 ring-black ring-opacity-5 rounded-md" align="start">
                                <DropdownMenuGroup>
                                    <DropdownMenuItem v-for="brand in brands" :key="brand.id" as-child>
                                        <Link :href="`/brands/${brand.slug}`" class="block w-full px-4 py-2 text-sm text-gray-700 hover:bg-rose-50 hover:text-rose-600">
                                            {{ brand.name }}
                                        </Link>
                                    </DropdownMenuItem>
                                </DropdownMenuGroup>
                            </DropdownMenuContent>
                        </DropdownMenu>
                        
                        <Link href="/about" :class="isAboutActive ? 'text-rose-600 border-b-2 border-rose-600' : 'text-gray-500 hover:text-gray-900'" class="px-1 py-5 text-sm font-medium transition-colors flex items-center gap-2">
                            <Info class="w-4 h-4" /> About
                        </Link>
                    </nav>
                </div>

                <div class="hidden sm:flex items-center justify-center flex-1 px-8">
                    <Searchbar />
                </div>

                <div class="flex items-center space-x-2">
                    <Link href="/wishlist" :class="isWishlistActive ? 'text-rose-600 bg-rose-50' : 'text-gray-500 hover:text-rose-600 hover:bg-rose-50'" class="relative p-2.5 rounded-full transition-colors">
                        <Heart class="h-5 w-5" />
                        <span v-if="wishlistCount > 0" class="absolute top-0 right-0 bg-rose-600 text-white text-[10px] font-bold rounded-full h-4 w-4 flex items-center justify-center ring-2 ring-white">
                            {{ wishlistCount }}
                        </span>
                    </Link>

                    <Link href="/cart" :class="isCartActive ? 'text-rose-600 bg-rose-50' : 'text-gray-500 hover:text-rose-600 hover:bg-rose-50'" class="relative p-2.5 rounded-full transition-colors">
                        <ShoppingCart class="h-5 w-5" />
                        <span v-if="cartCount > 0" class="absolute top-0 right-0 bg-rose-600 text-white text-[10px] font-bold rounded-full h-4 w-4 flex items-center justify-center ring-2 ring-white">
                            {{ cartCount }}
                        </span>
                    </Link>

                    <Link 
                        :href="accountHref"
                        class="relative p-1.5 rounded-full text-gray-500 hover:text-rose-600 hover:bg-rose-50 transition-colors ml-1"
                        :title="accountTitle"
                    >
                        <span v-if="user" class="flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-br from-rose-500 to-pink-500 text-white font-medium text-sm shadow-sm">
                            {{ user.name.charAt(0).toUpperCase() }}
                        </span>
                        
                        <div v-else class="p-1">
                            <User class="h-6 w-6" />
                        </div>

                        <span 
                            v-if="user && pendingOrdersCount > 0"
                            class="absolute -top-1 -right-1 bg-red-500 text-white text-[10px] font-bold rounded-full h-5 w-5 flex items-center justify-center ring-2 ring-white animate-pulse"
                            title="Pesanan belum selesai"
                        >
                            {{ pendingOrdersCount }}
                        </span>
                    </Link>

                </div>
            </div>
        </div>
    </header>
</template>