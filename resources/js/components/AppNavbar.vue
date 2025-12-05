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
const pendingOrdersCount = computed(() => page.props.pendingOrdersCount || 0);

const wishlistCount = ref(page.props.wishlistCount || 0);
watch(() => page.props.wishlistCount, (newCount) => { wishlistCount.value = newCount || 0; });

onMounted(() => {
    requestPermission();
});

// Helper Active State
const isActive = (url) => page.url === url || page.url.startsWith(url);

const accountHref = computed(() => user.value ? '/dashboard' : '/login');
const accountTitle = computed(() => user.value ? 'My Dashboard' : 'Login / Register');
</script>

<template>
    <header class="bg-white border-b border-gray-100 shadow-sm sticky top-0 z-50 font-sans">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                
                <div class="flex items-center">
                    <div class="flex-shrink-0 mr-8 cursor-pointer group">
                        <Link href="/" class="flex flex-col items-start justify-center">
                            <div class="text-2xl font-bold leading-none tracking-tight text-transparent bg-clip-text bg-gradient-to-r from-rose-600 to-pink-600 group-hover:scale-105 transition-transform">
                                SkinLab
                            </div>
                            <div class="text-[9px] text-rose-500 font-bold tracking-[0.3em] uppercase mt-0.5 ml-0.5 group-hover:text-rose-600 transition-colors">
                                BEAUTY
                            </div>
                        </Link>
                    </div>
                    
                    <nav class="hidden md:flex space-x-1 items-center">
                        
                        <Link href="/" 
                            class="px-3 py-2 rounded-lg text-sm font-medium transition-all flex items-center gap-2"
                            :class="isActive('/') ? 'text-gray-900 bg-rose-50' : 'text-gray-700 hover:bg-gray-50'"
                        >
                            <Home class="w-4 h-4 text-rose-500" /> 
                            Home
                        </Link>
                        
                        <Link href="/catalog" 
                            class="px-3 py-2 rounded-lg text-sm font-medium transition-all flex items-center gap-2"
                            :class="isActive('/catalog') ? 'text-gray-900 bg-rose-50' : 'text-gray-700 hover:bg-gray-50'"
                        >
                            <ShoppingBag class="w-4 h-4 text-rose-500" />
                            Catalog
                        </Link>

                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <button class="px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition-all flex items-center gap-1 outline-none group data-[state=open]:bg-rose-50 data-[state=open]:text-rose-600">
                                    Category <ChevronDown class="w-3 h-3 text-rose-400 group-hover:text-rose-600 transition-transform group-data-[state=open]:rotate-180" />
                                </button>
                            </DropdownMenuTrigger>
                            
                            <DropdownMenuContent class="w-56 !bg-white shadow-xl border border-gray-100 rounded-xl p-1 z-[60]" align="start">
                                <DropdownMenuGroup>
                                    <DropdownMenuItem v-for="category in categories" :key="category.id" as-child>
                                        <Link :href="`/categories/${category.slug}`" class="block px-3 py-2.5 rounded-lg text-sm font-medium text-gray-700 hover:bg-rose-50 hover:text-rose-600 transition-colors cursor-pointer w-full">
                                            {{ category.name }}
                                        </Link>
                                    </DropdownMenuItem>
                                </DropdownMenuGroup>
                            </DropdownMenuContent>
                        </DropdownMenu>

                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <button class="px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition-all flex items-center gap-1 outline-none group data-[state=open]:bg-rose-50 data-[state=open]:text-rose-600">
                                    Brand <ChevronDown class="w-3 h-3 text-rose-400 group-hover:text-rose-600 transition-transform group-data-[state=open]:rotate-180" />
                                </button>
                            </DropdownMenuTrigger>
                            
                            <DropdownMenuContent class="w-56 !bg-white shadow-xl border border-gray-100 rounded-xl p-1 z-[60]" align="start">
                                <DropdownMenuGroup>
                                    <DropdownMenuItem v-for="brand in brands" :key="brand.id" as-child>
                                        <Link :href="`/brands/${brand.slug}`" class="block px-3 py-2.5 rounded-lg text-sm font-medium text-gray-700 hover:bg-rose-50 hover:text-rose-600 transition-colors cursor-pointer w-full">
                                            {{ brand.name }}
                                        </Link>
                                    </DropdownMenuItem>
                                </DropdownMenuGroup>
                            </DropdownMenuContent>
                        </DropdownMenu>
                        
                        <Link href="/about" 
                            class="px-3 py-2 rounded-lg text-sm font-medium transition-all flex items-center gap-2"
                            :class="isActive('/about') ? 'text-gray-900 bg-rose-50' : 'text-gray-700 hover:bg-gray-50'"
                        >
                            <Info class="w-4 h-4 text-rose-500" />
                            About
                        </Link>
                    </nav>
                </div>

                <div class="hidden lg:flex items-center justify-center flex-1 px-8 max-w-md">
                    <Searchbar />
                </div>

                <div class="flex items-center space-x-2">
                    
                    <div class="relative flex items-center justify-center">
                        <NotificationDropdown />
                    </div>

                    <Link href="/wishlist" 
                          class="relative p-2.5 rounded-full transition-all group text-gray-500 hover:bg-rose-50 hover:text-rose-600"
                          :class="isActive('/wishlist') ? 'bg-rose-50 text-rose-600' : ''">
                        <Heart class="h-5 w-5 group-hover:scale-110 transition-transform" />
                        <span v-if="wishlistCount > 0" class="absolute top-0.5 right-0.5 bg-rose-600 text-white text-[9px] font-bold rounded-full h-4 w-4 flex items-center justify-center ring-2 ring-white">
                            {{ wishlistCount }}
                        </span>
                    </Link>

                    <Link href="/cart" 
                          class="relative p-2.5 rounded-full transition-all group text-gray-500 hover:bg-rose-50 hover:text-rose-600"
                          :class="isActive('/cart') ? 'bg-rose-50 text-rose-600' : ''">
                        <ShoppingCart class="h-5 w-5 group-hover:scale-110 transition-transform" />
                        <span v-if="cartCount > 0" class="absolute top-0.5 right-0.5 bg-rose-600 text-white text-[9px] font-bold rounded-full h-4 w-4 flex items-center justify-center ring-2 ring-white">
                            {{ cartCount }}
                        </span>
                    </Link>

                    <Link 
                        :href="accountHref"
                        class="relative ml-2 pl-2 border-l border-gray-200"
                        :title="accountTitle"
                    >
                        <div v-if="user" class="flex items-center gap-2 hover:opacity-80 transition-opacity">
                            <span class="flex h-9 w-9 items-center justify-center rounded-full bg-gradient-to-br from-rose-500 to-pink-600 text-white font-bold text-sm shadow-md ring-2 ring-white">
                                {{ user.name.charAt(0).toUpperCase() }}
                            </span>
                        </div>
                        
                        <div v-else class="p-2 rounded-full text-gray-500 hover:bg-gray-100 hover:text-rose-600 transition-colors">
                            <User class="h-5 w-5" />
                        </div>

                        <span 
                            v-if="user && pendingOrdersCount > 0"
                            class="absolute -top-1 right-0 bg-orange-500 text-white text-[9px] font-bold rounded-full h-4 w-4 flex items-center justify-center ring-2 ring-white animate-pulse"
                            title="Pending Orders"
                        >
                            {{ pendingOrdersCount }}
                        </span>
                    </Link>

                </div>
            </div>
        </div>
    </header>
</template>