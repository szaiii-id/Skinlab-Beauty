<script setup lang="ts">
import { computed } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import AppLogo from './AppLogo.vue'; 
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

// Komponen Dropdown (untuk category dan brand saja)
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

// Ambil props global
const page = usePage();
const categories = computed(() => page.props.categories || []);
const brands = computed(() => page.props.brands || []);
const cartCount = computed(() => page.props.cartCount || 0);
const user = computed(() => page.props.auth.user);

// Fungsi navigasi dropdown (tetap ada)
const navigateToCategory = (event: Event) => {
    const target = event.target as HTMLSelectElement;
    const slug = target.value;
    if (slug) router.get(`/categories/${slug}`);
};
const navigateToBrand = (event: Event) => {
    const target = event.target as HTMLSelectElement;
    const slug = target.value;
    if (slug) router.get(`/brands/${slug}`);
};

// ==========================================================
// HELPER UNTUK KONDISI AKTIF (COMPUTED PROPERTIES)
// ==========================================================

// Home aktif hanya jika URL adalah root (tepat '/')
const isHomeActive = computed(() => page.url === '/');

// Catalog aktif jika URL adalah /catalog ATAU diawali dengan /products (untuk halaman show)
const isCatalogActive = computed(() => {
    return page.url === '/catalog' || page.url.startsWith('/products');
});

// About aktif hanya jika URL adalah /about
const isAboutActive = computed(() => page.url === '/about');

// Dropdown aktif jika URL diawali dengan path-nya
const isCategoryDropdownActive = computed(() => page.url.startsWith('/categories'));
const isBrandDropdownActive = computed(() => page.url.startsWith('/brands'));

// ==========================================================
// LOGIC UNTUK ICON AKUN
// ==========================================================

// Tentukan href berdasarkan status login
const accountHref = computed(() => {
    return user.value ? '/dashboard' : '/login';
});

// Tentukan title berdasarkan status login
const accountTitle = computed(() => {
    return user.value ? 'My Dashboard' : 'Login / Register';
});

</script>

<template>
    <header class="bg-white border-b border-gray-200 shadow-sm sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                
                <div class="flex items-center">
                    <div class="flex-shrink-0 mr-6">
                        <Link href="/">
                            <AppLogo />
                        </Link>
                    </div>
                    
                    <nav class="hidden sm:flex sm:space-x-4 items-center">
                        
                        <Link 
                            href="/" 
                            :class="{ 
                                'border-rose-500 text-gray-900 border-b-2': isHomeActive, 
                                'border-transparent text-gray-500 hover:text-gray-900 border-b-2': !isHomeActive
                            }"
                            class="inline-flex items-center px-1 pt-1 text-sm font-medium"
                        >
                            <Home class="mr-2 h-5 w-5" />
                            <span>Home</span>
                        </Link>
                        
                        <Link 
                            href="/catalog" 
                            :class="{ 
                                'border-rose-500 text-gray-900 border-b-2': isCatalogActive,
                                'border-transparent text-gray-500 hover:text-gray-900 border-b-2': !isCatalogActive
                            }"
                            class="inline-flex items-center px-1 pt-1 text-sm font-medium"
                        >
                            <ShoppingBag class="mr-2 h-5 w-5" />
                            <span>Catalog</span>
                        </Link>

                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <button 
                                    :class="{ 
                                        'border-rose-500 text-gray-900 border-b-2': isCategoryDropdownActive, 
                                        'border-transparent text-gray-500 hover:text-gray-900 border-b-2': !isCategoryDropdownActive
                                    }"
                                    class="inline-flex items-center px-1 pt-1 text-sm font-medium"
                                >
                                    <span>Category</span>
                                    <ChevronDown class="ml-1 h-4 w-4" />
                                </button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent class="w-56 bg-white shadow-lg ring-1 ring-black ring-opacity-5 rounded-md" align="start">
                                <DropdownMenuGroup>
                                    <DropdownMenuItem v-for="category in categories" :key="category.id" as-child>
                                        <Link :href="`/categories/${category.slug}`"
                                            class="block w-full px-4 py-2 text-sm text-gray-700 
                                                   hover:bg-rose-50 hover:text-rose-600 focus:outline-none focus:bg-rose-50 focus:text-rose-600"
                                        >
                                            {{ category.name }}
                                        </Link>
                                    </DropdownMenuItem>
                                </DropdownMenuGroup>
                            </DropdownMenuContent>
                        </DropdownMenu>

                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <button 
                                    :class="{ 
                                        'border-rose-500 text-gray-900 border-b-2': isBrandDropdownActive, 
                                        'border-transparent text-gray-500 hover:text-gray-900 border-b-2': !isBrandDropdownActive
                                    }"
                                    class="inline-flex items-center px-1 pt-1 text-sm font-medium"
                                >
                                    <span>Brand</span>
                                    <ChevronDown class="ml-1 h-4 w-4" />
                                </button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent class="w-56 bg-white shadow-lg ring-1 ring-black ring-opacity-5 rounded-md" align="start">
                                <DropdownMenuGroup>
                                    <DropdownMenuItem v-for="brand in brands" :key="brand.id" as-child>
                                        <Link :href="`/brands/${brand.slug}`"
                                            class="block w-full px-4 py-2 text-sm text-gray-700 
                                                   hover:bg-rose-50 hover:text-rose-600 focus:outline-none focus:bg-rose-50 focus:text-rose-600"
                                        >
                                            {{ brand.name }}
                                        </Link>
                                    </DropdownMenuItem>
                                </DropdownMenuGroup>
                            </DropdownMenuContent>
                        </DropdownMenu>
                        
                        <Link 
                            href="/about" 
                            :class="{ 
                                'border-rose-500 text-gray-900 border-b-2': isAboutActive, 
                                'border-transparent text-gray-500 hover:text-gray-900 border-b-2': !isAboutActive
                            }"
                            class="inline-flex items-center px-1 pt-1 text-sm font-medium"
                        >
                            <Info class="mr-2 h-5 w-5" />
                            <span>About</span>
                        </Link>

                    </nav>
                </div>

                <div class="hidden sm:flex items-center justify-center flex-1 px-8">
                    <Searchbar />
                </div>

                <div class="flex items-center space-x-3">
                    <Link 
                        href="#" 
                        class="p-2 rounded-full text-gray-500 hover:text-rose-600 hover:bg-rose-50"
                        title="My Wishlist"
                    >
                        <Heart class="h-6 w-6" />
                    </Link>

                    <Link 
                        href="/cart" 
                        class="relative p-2 rounded-full text-gray-500 hover:text-rose-600 hover:bg-rose-50"
                        title="View Cart"
                    >
                        <ShoppingCart class="h-6 w-6" />
                        <span v-if="cartCount > 0" class="absolute -top-1 -right-1 bg-rose-600 text-white text-xs font-bold rounded-full h-5 w-5 flex items-center justify-center">
                            {{ cartCount }}
                        </span>
                    </Link>

                    <!-- ICON AKUN - TANPA DROPDOWN -->
                    <Link 
                        :href="accountHref"
                        class="p-2 rounded-full text-gray-500 hover:text-rose-600 hover:bg-rose-50 transition-colors"
                        :title="accountTitle"
                    >
                        <span v-if="user" class="flex h-8 w-8 items-center justify-center rounded-full bg-rose-100 text-rose-600 font-semibold text-xs">
                            {{ user.name.charAt(0) }}
                        </span>
                        <User v-else class="h-6 w-6 text-gray-500" />
                    </Link>

                </div>
            </div>
        </div>
    </header>
</template>