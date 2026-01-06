<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import Searchbar from '@/components/Searchbar.vue';
import NotificationDropdown from '@/components/NotificationDropdown.vue'; 
import { requestPermission } from '@/firebase';

import { 
    ShoppingBag, 
    ShoppingCart, 
    Heart,
    ChevronDown,
    User,
    Info,
    Home,
    Menu,
    X,
    ChevronRight,
    Search,
    LayoutGrid // Icon untuk Catalog Mobile
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
const pendingOrdersCount = computed(() => page.props.pendingOrdersCount || 0);

const wishlistCount = ref(page.props.wishlistCount || 0);
watch(() => page.props.wishlistCount, (newCount) => { wishlistCount.value = newCount || 0; });

onMounted(() => {
    requestPermission();
});

// Helper Active State
const isActive = (url) => page.url === url || page.url.startsWith(url);

const accountHref = computed(() => user.value ? '/dashboard' : '/login');

// State Mobile Menu (Drawer Kiri)
const isMobileMenuOpen = ref(false);
const isMobileCategoryOpen = ref(false);
const isMobileBrandOpen = ref(false);

// --- [LOGIC AUTO-CLOSE] ---
// Memantau perubahan URL. Jika user pindah halaman (klik link apapun), menu akan tertutup.
watch(() => page.url, () => {
    isMobileMenuOpen.value = false;
    // Reset accordion juga agar rapi saat dibuka kembali
    isMobileCategoryOpen.value = false;
    isMobileBrandOpen.value = false;
});
</script>

<template>
    <header class="bg-white border-b border-gray-100 shadow-sm sticky top-0 z-40 font-sans">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                
                <div class="flex items-center gap-3">
                    
                    <button 
                        @click="isMobileMenuOpen = true"
                        class="md:hidden p-2 -ml-2 text-gray-600 hover:text-rose-600 rounded-full active:bg-rose-50"
                    >
                        <Menu class="w-6 h-6" />
                    </button>

                    <Link href="/" class="flex flex-col items-start justify-center group">
                        <div class="text-xl md:text-2xl font-bold leading-none tracking-tight text-transparent bg-clip-text bg-gradient-to-r from-rose-600 to-pink-600 group-hover:scale-105 transition-transform">
                            SkinLab
                        </div>
                        <div class="text-[8px] md:text-[9px] text-rose-500 font-bold tracking-[0.3em] uppercase mt-0.5 ml-0.5">
                            BEAUTY
                        </div>
                    </Link>

                    <nav class="hidden md:flex space-x-1 items-center ml-6">
                        <Link href="/" class="px-3 py-2 rounded-lg text-sm font-medium transition-all flex items-center gap-2" :class="isActive('/') ? 'text-gray-900 bg-rose-50' : 'text-gray-700 hover:bg-gray-50'">
                            <Home class="w-4 h-4 text-rose-500" /> Home
                        </Link>
                        <Link href="/catalog" class="px-3 py-2 rounded-lg text-sm font-medium transition-all flex items-center gap-2" :class="isActive('/catalog') ? 'text-gray-900 bg-rose-50' : 'text-gray-700 hover:bg-gray-50'">
                            <ShoppingBag class="w-4 h-4 text-rose-500" /> Catalog
                        </Link>
                        
                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <button class="px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition-all flex items-center gap-1 outline-none group data-[state=open]:bg-rose-50">
                                    Category <ChevronDown class="w-3 h-3 text-rose-400 group-data-[state=open]:rotate-180" />
                                </button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent class="w-56 !bg-white shadow-xl border border-gray-100 rounded-xl p-1 z-[60]" align="start">
                                <DropdownMenuGroup>
                                    <DropdownMenuItem v-for="category in categories" :key="category.id" as-child>
                                        <Link :href="`/categories/${category.slug}`" class="block px-3 py-2.5 rounded-lg text-sm font-medium text-gray-700 hover:bg-rose-50 hover:text-rose-600 transition-colors w-full">
                                            {{ category.name }}
                                        </Link>
                                    </DropdownMenuItem>
                                </DropdownMenuGroup>
                            </DropdownMenuContent>
                        </DropdownMenu>

                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <button class="px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition-all flex items-center gap-1 outline-none group data-[state=open]:bg-rose-50">
                                    Brand <ChevronDown class="w-3 h-3 text-rose-400 group-data-[state=open]:rotate-180" />
                                </button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent class="w-56 !bg-white shadow-xl border border-gray-100 rounded-xl p-1 z-[60]" align="start">
                                <DropdownMenuGroup>
                                    <DropdownMenuItem v-for="brand in brands" :key="brand.id" as-child>
                                        <Link :href="`/brands/${brand.slug}`" class="block px-3 py-2.5 rounded-lg text-sm font-medium text-gray-700 hover:bg-rose-50 hover:text-rose-600 transition-colors w-full">
                                            {{ brand.name }}
                                        </Link>
                                    </DropdownMenuItem>
                                </DropdownMenuGroup>
                            </DropdownMenuContent>
                        </DropdownMenu>

                        <Link href="/about" class="px-3 py-2 rounded-lg text-sm font-medium transition-all flex items-center gap-2" :class="isActive('/about') ? 'text-gray-900 bg-rose-50' : 'text-gray-700 hover:bg-gray-50'">
                            <Info class="w-4 h-4 text-rose-500" /> About
                        </Link>
                    </nav>
                </div>

                <div class="flex-1 px-4 max-w-md mx-auto">
                    <Searchbar />
                </div>

                <div class="flex items-center space-x-2">
                    
                    <NotificationDropdown />

                    <div class="hidden md:flex items-center space-x-2">
                        <Link href="/wishlist" class="relative p-2.5 rounded-full transition-all group text-gray-500 hover:bg-rose-50 hover:text-rose-600" :class="isActive('/wishlist') ? 'bg-rose-50 text-rose-600' : ''">
                            <Heart class="h-5 w-5 group-hover:scale-110 transition-transform" />
                            <span v-if="wishlistCount > 0" class="absolute top-0.5 right-0.5 bg-rose-600 text-white text-[9px] font-bold rounded-full h-4 w-4 flex items-center justify-center ring-2 ring-white">{{ wishlistCount }}</span>
                        </Link>

                        <Link href="/cart" class="relative p-2.5 rounded-full transition-all group text-gray-500 hover:bg-rose-50 hover:text-rose-600" :class="isActive('/cart') ? 'bg-rose-50 text-rose-600' : ''">
                            <ShoppingCart class="h-5 w-5 group-hover:scale-110 transition-transform" />
                            <span v-if="cartCount > 0" class="absolute top-0.5 right-0.5 bg-rose-600 text-white text-[9px] font-bold rounded-full h-4 w-4 flex items-center justify-center ring-2 ring-white">{{ cartCount }}</span>
                        </Link>

                        <Link :href="accountHref" class="relative ml-2 pl-2 border-l border-gray-200">
                            <div v-if="user" class="flex items-center gap-2 hover:opacity-80 transition-opacity">
                                <span class="flex h-9 w-9 items-center justify-center rounded-full bg-gradient-to-br from-rose-500 to-pink-600 text-white font-bold text-sm shadow-md ring-2 ring-white">{{ user.name.charAt(0).toUpperCase() }}</span>
                            </div>
                            <div v-else class="p-2 rounded-full text-gray-500 hover:bg-gray-100 hover:text-rose-600 transition-colors">
                                <User class="h-5 w-5" />
                            </div>
                            <span v-if="user && pendingOrdersCount > 0" class="absolute -top-1 right-0 bg-orange-500 text-white text-[9px] font-bold rounded-full h-4 w-4 flex items-center justify-center ring-2 ring-white animate-pulse">{{ pendingOrdersCount }}</span>
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <nav class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)] z-50 md:hidden pb-safe">
        <div class="flex justify-around items-center h-16">
            
            <Link href="/" class="flex flex-col items-center justify-center w-full h-full space-y-1" :class="isActive('/') ? 'text-rose-600' : 'text-gray-400 hover:text-gray-600'">
                <Home class="w-6 h-6" :class="isActive('/') ? 'fill-rose-100' : ''" />
                <span class="text-[10px] font-medium">Home</span>
            </Link>

            <Link href="/catalog" class="flex flex-col items-center justify-center w-full h-full space-y-1" :class="isActive('/catalog') ? 'text-rose-600' : 'text-gray-400 hover:text-gray-600'">
                <LayoutGrid class="w-6 h-6" :class="isActive('/catalog') ? 'fill-rose-100' : ''" />
                <span class="text-[10px] font-medium">Shop</span>
            </Link>

            <Link href="/cart" class="relative flex flex-col items-center justify-center w-full h-full space-y-1 group" :class="isActive('/cart') ? 'text-rose-600' : 'text-gray-400 hover:text-gray-600'">
                <div class="relative">
                    <ShoppingCart class="w-6 h-6 transition-transform group-active:scale-90" :class="isActive('/cart') ? 'fill-rose-100' : ''" />
                    <span v-if="cartCount > 0" class="absolute -top-2 -right-2 bg-rose-600 text-white text-[9px] font-bold rounded-full h-4 w-4 flex items-center justify-center ring-2 ring-white animate-in zoom-in">
                        {{ cartCount }}
                    </span>
                </div>
                <span class="text-[10px] font-medium">Cart</span>
            </Link>

            <Link href="/wishlist" class="relative flex flex-col items-center justify-center w-full h-full space-y-1 group" :class="isActive('/wishlist') ? 'text-rose-600' : 'text-gray-400 hover:text-gray-600'">
                <div class="relative">
                    <Heart class="w-6 h-6 transition-transform group-active:scale-90" :class="isActive('/wishlist') ? 'fill-rose-600 text-rose-600' : ''" />
                    <span v-if="wishlistCount > 0" class="absolute -top-2 -right-2 bg-rose-600 text-white text-[9px] font-bold rounded-full h-4 w-4 flex items-center justify-center ring-2 ring-white">
                        {{ wishlistCount }}
                    </span>
                </div>
                <span class="text-[10px] font-medium">Saved</span>
            </Link>

            <Link :href="accountHref" class="flex flex-col items-center justify-center w-full h-full space-y-1" :class="isActive('/dashboard') || isActive('/login') ? 'text-rose-600' : 'text-gray-400 hover:text-gray-600'">
                <div v-if="user" class="w-6 h-6 rounded-full bg-gradient-to-br from-rose-500 to-pink-600 text-white flex items-center justify-center text-xs font-bold ring-2 ring-transparent" :class="isActive('/dashboard') ? 'ring-rose-200' : ''">
                    {{ user.name.charAt(0).toUpperCase() }}
                </div>
                <User v-else class="w-6 h-6" />
                <span class="text-[10px] font-medium">{{ user ? 'Account' : 'Login' }}</span>
            </Link>

        </div>
    </nav>

    <div v-if="isMobileMenuOpen" class="fixed inset-0 z-[60] md:hidden font-sans">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm transition-opacity" @click="isMobileMenuOpen = false"></div>
        <div class="absolute inset-y-0 left-0 w-3/4 max-w-xs bg-white shadow-2xl flex flex-col transform transition-transform animate-in slide-in-from-left duration-300">
            
            <div class="flex items-center justify-between p-5 border-b border-gray-100 bg-gradient-to-r from-rose-50 to-white">
                <span class="text-lg font-bold text-gray-800 tracking-tight">Categories & Menu</span>
                <button @click="isMobileMenuOpen = false" class="p-1 text-gray-400 hover:text-red-500 transition-colors">
                    <X class="w-6 h-6" />
                </button>
            </div>

            <div class="flex-1 overflow-y-auto p-4 space-y-4">
                
                <div>
                    <button @click="isMobileCategoryOpen = !isMobileCategoryOpen" 
                        class="w-full flex items-center justify-between px-3 py-2 text-sm font-bold text-gray-800 rounded-lg hover:bg-gray-50">
                        <span>Shop by Category</span>
                        <ChevronDown class="w-4 h-4 text-gray-400 transition-transform duration-300" :class="{ 'rotate-180': isMobileCategoryOpen }" />
                    </button>
                    <div v-show="isMobileCategoryOpen" class="mt-2 pl-4 space-y-2 border-l-2 border-rose-100 ml-3">
                        <Link v-for="cat in categories" :key="cat.id" :href="`/categories/${cat.slug}`"
                            class="block text-sm text-gray-600 hover:text-rose-600 py-1">
                            {{ cat.name }}
                        </Link>
                    </div>
                </div>

                <div>
                    <button @click="isMobileBrandOpen = !isMobileBrandOpen" 
                        class="w-full flex items-center justify-between px-3 py-2 text-sm font-bold text-gray-800 rounded-lg hover:bg-gray-50">
                        <span>Shop by Brand</span>
                        <ChevronDown class="w-4 h-4 text-gray-400 transition-transform duration-300" :class="{ 'rotate-180': isMobileBrandOpen }" />
                    </button>
                    <div v-show="isMobileBrandOpen" class="mt-2 pl-4 space-y-2 border-l-2 border-rose-100 ml-3">
                        <Link v-for="brand in brands" :key="brand.id" :href="`/brands/${brand.slug}`"
                            class="block text-sm text-gray-600 hover:text-rose-600 py-1">
                            {{ brand.name }}
                        </Link>
                    </div>
                </div>

                <div class="border-t border-gray-100 pt-4">
                    <Link href="/about" class="flex items-center gap-3 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 rounded-lg">
                        <Info class="w-5 h-5 text-gray-400" /> About SkinLab
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.pb-safe {
    padding-bottom: env(safe-area-inset-bottom);
}
</style>