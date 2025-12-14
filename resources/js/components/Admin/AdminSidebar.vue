<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { ref, onMounted, computed } from 'vue'; // Tambah computed

const page = usePage();
const user = page.props.auth.user;

// --- ROLE PERMISSION HELPER (BARU) ---
// Fungsi ini mengecek apakah user yang login punya hak akses
const userRole = computed(() => page.props.auth.user.role);

const can = (roles: string[]) => {
    return roles.includes(userRole.value);
};
// -------------------------------------

// --- SCROLL PERSISTENCE ---
const sidebarNav = ref<HTMLElement | null>(null);

const handleScroll = () => {
    if (sidebarNav.value) {
        localStorage.setItem('sidebarScrollPos', sidebarNav.value.scrollTop.toString());
    }
};

onMounted(() => {
    const savedPos = localStorage.getItem('sidebarScrollPos');
    if (sidebarNav.value && savedPos) {
        sidebarNav.value.scrollTop = parseInt(savedPos);
    }
});
// --------------------------

// --- FUNGSI IS ACTIVE YANG REAKTIF ---
const isActive = (routeName: string) => {
    const currentUrl = usePage().url; 
    try {
        return route().current(routeName) || route().current(routeName + '*');
    } catch (e) {
        return false;
    }
};
</script>

<template>
    <aside class="w-72 bg-white border-r border-gray-100 hidden md:flex flex-col h-screen sticky top-0 shadow-[4px_0_24px_rgba(0,0,0,0.02)] z-30 font-sans">
        
        <div class="h-24 flex items-center justify-center border-b border-gray-50 px-6 shrink-0">
            <div class="text-center group cursor-default">
                <h1 class="text-xl font-extrabold tracking-tighter uppercase bg-clip-text text-transparent bg-gradient-to-r from-pink-600 to-rose-700 group-hover:scale-105 transition-transform duration-300 leading-tight">
                    SKIN LAB BEAUTY
                </h1>
                <div class="flex items-center justify-center gap-2 mt-1.5">
                    <span class="h-px w-3 bg-gray-200"></span>
                    <span class="text-[10px] font-bold tracking-[0.3em] text-gray-400 uppercase">
                        Control Center
                    </span>
                    <span class="h-px w-3 bg-gray-200"></span>
                </div>
            </div>
        </div>
        
        <nav 
            ref="sidebarNav"
            @scroll="handleScroll"
            class="flex-1 overflow-y-auto py-6 px-4 space-y-8 scrollbar-thin scrollbar-thumb-pink-100 scrollbar-track-transparent hover:scrollbar-thumb-pink-200"
        >
            
            <div>
                <p class="px-4 text-[10px] font-extrabold text-gray-400 uppercase tracking-widest mb-3">Overview</p>
                
                <Link 
                    :href="route('admin.dashboard')" 
                    class="flex items-center px-4 py-3 text-sm font-medium rounded-xl transition-all duration-200 group relative overflow-hidden"
                    :class="isActive('admin.dashboard') 
                        ? 'bg-gradient-to-r from-pink-500 to-rose-600 text-white shadow-lg shadow-pink-200' 
                        : 'text-gray-600 hover:bg-pink-50 hover:text-pink-700'"
                >
                    <svg class="w-5 h-5 mr-3 transition-colors" :class="isActive('admin.dashboard') ? 'text-white' : 'text-gray-400 group-hover:text-pink-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                    Dashboard
                </Link>
            </div>

            <div v-if="can(['super_admin', 'warehouse'])">
                <p class="px-4 text-[10px] font-extrabold text-gray-400 uppercase tracking-widest mb-3">Catalog & Stock</p>
                <div class="space-y-1">
                    
                    <Link 
                        :href="route('admin.categories.index')" 
                        class="flex items-center px-4 py-2.5 text-sm font-medium rounded-xl transition-all duration-200 group relative overflow-hidden"
                        :class="isActive('admin.categories') 
                            ? 'bg-gradient-to-r from-pink-500 to-rose-600 text-white shadow-lg shadow-pink-200' 
                            : 'text-gray-600 hover:bg-pink-50 hover:text-pink-700'"
                    >
                        <svg class="w-5 h-5 mr-3 transition-colors" :class="isActive('admin.categories') ? 'text-white' : 'text-gray-400 group-hover:text-pink-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path></svg>
                        Categories
                    </Link>

                    <Link 
                        :href="route('admin.brands.index')" 
                        class="flex items-center px-4 py-2.5 text-sm font-medium rounded-xl transition-all duration-200 group relative overflow-hidden"
                        :class="isActive('admin.brands') 
                            ? 'bg-gradient-to-r from-pink-500 to-rose-600 text-white shadow-lg shadow-pink-200' 
                            : 'text-gray-600 hover:bg-pink-50 hover:text-pink-700'"
                    >
                        <svg class="w-5 h-5 mr-3 transition-colors" :class="isActive('admin.brands') ? 'text-white' : 'text-gray-400 group-hover:text-pink-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.384-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path></svg>
                        Brands
                    </Link>

                    <Link 
                        :href="route('admin.products.index')" 
                        class="flex items-center px-4 py-2.5 text-sm font-medium rounded-xl transition-all duration-200 group relative overflow-hidden"
                        :class="isActive('admin.products') 
                            ? 'bg-gradient-to-r from-pink-500 to-rose-600 text-white shadow-lg shadow-pink-200' 
                            : 'text-gray-600 hover:bg-pink-50 hover:text-pink-700'"
                    >
                        <svg class="w-5 h-5 mr-3 transition-colors" :class="isActive('admin.products') ? 'text-white' : 'text-gray-400 group-hover:text-pink-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                        All Products
                    </Link>

                    <Link 
                        :href="route('admin.stock-opname.index')" 
                        class="flex items-center px-4 py-2.5 text-sm font-medium rounded-xl transition-all duration-200 group relative overflow-hidden"
                        :class="route().current('admin.stock-opname.*') 
                            ? 'bg-gradient-to-r from-pink-500 to-rose-600 text-white shadow-lg shadow-pink-200' 
                            : 'text-gray-600 hover:bg-pink-50 hover:text-pink-700'"
                    >
                        <svg 
                            class="w-5 h-5 mr-3 transition-colors" 
                            :class="route().current('admin.stock-opname.*') ? 'text-white' : 'text-gray-400 group-hover:text-pink-600'" 
                            xmlns="http://www.w3.org/2000/svg" 
                            fill="none" 
                            viewBox="0 0 24 24" 
                            stroke="currentColor" 
                            stroke-width="2"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                        </svg>
                        
                        Stock Opname
                    </Link>
                </div>
            </div>

            <div v-if="can(['super_admin', 'warehouse'])">
                <p class="px-4 text-[10px] font-extrabold text-gray-400 uppercase tracking-widest mb-3">Sales</p>
                <div class="space-y-1">
                    <Link 
                        :href="route('admin.orders.index')" 
                        class="flex items-center px-4 py-2.5 text-sm font-medium rounded-xl transition-all duration-200 group relative overflow-hidden"
                        :class="isActive('admin.orders') 
                            ? 'bg-gradient-to-r from-pink-500 to-rose-600 text-white shadow-lg shadow-pink-200' 
                            : 'text-gray-600 hover:bg-pink-50 hover:text-pink-700'"
                    >
                        <svg class="w-5 h-5 mr-3 transition-colors" :class="isActive('admin.orders') ? 'text-white' : 'text-gray-400 group-hover:text-pink-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                        </svg>
                        Orders
                    </Link>
                    <Link 
                        :href="route('admin.returns.index')" 
                        class="flex items-center px-4 py-2.5 text-sm font-medium rounded-xl transition-all duration-200 group relative overflow-hidden"
                        :class="isActive('admin.returns') 
                            ? 'bg-gradient-to-r from-pink-500 to-rose-600 text-white shadow-lg shadow-pink-200' 
                            : 'text-gray-600 hover:bg-pink-50 hover:text-pink-700'"
                    >
                        <svg class="w-5 h-5 mr-3 transition-colors" :class="isActive('admin.returns') ? 'text-white' : 'text-gray-400 group-hover:text-pink-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                        Returns
                    </Link>
                </div>
            </div>

            <div v-if="can(['super_admin', 'marketing'])">
                <p class="px-4 text-[10px] font-extrabold text-gray-400 uppercase tracking-widest mb-3">Marketing</p>
                <div class="space-y-1">
                    <Link 
                        :href="route('admin.banners.index')" 
                        class="flex items-center px-4 py-2.5 text-sm font-medium rounded-xl transition-all duration-200 group relative overflow-hidden"
                        :class="isActive('admin.banners') 
                            ? 'bg-gradient-to-r from-pink-500 to-rose-600 text-white shadow-lg shadow-pink-200' 
                            : 'text-gray-600 hover:bg-pink-50 hover:text-pink-700'"
                    >
                        <svg class="w-5 h-5 mr-3 transition-colors" :class="isActive('admin.banners') ? 'text-white' : 'text-gray-400 group-hover:text-pink-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        Banners & Sliders
                    </Link>

                    <Link 
                        :href="route('admin.rewards.index')" 
                        class="flex items-center px-4 py-2.5 text-sm font-medium rounded-xl transition-all duration-200 group relative overflow-hidden"
                        :class="isActive('admin.rewards') 
                            ? 'bg-gradient-to-r from-pink-500 to-rose-600 text-white shadow-lg shadow-pink-200' 
                            : 'text-gray-600 hover:bg-pink-50 hover:text-pink-700'"
                    >
                        <svg class="w-5 h-5 mr-3 transition-colors" :class="isActive('admin.rewards') ? 'text-white' : 'text-gray-400 group-hover:text-pink-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"></path></svg>
                        Rewards & Vouchers
                    </Link>

                    <Link 
                        :href="route('admin.reviews.index')" 
                        class="flex items-center px-4 py-2.5 text-sm font-medium rounded-xl transition-all duration-200 group relative overflow-hidden"
                        :class="isActive('admin.reviews') 
                            ? 'bg-gradient-to-r from-pink-500 to-rose-600 text-white shadow-lg shadow-pink-200' 
                            : 'text-gray-600 hover:bg-pink-50 hover:text-pink-700'"
                    >
                        <svg class="w-5 h-5 mr-3 transition-colors" :class="isActive('admin.reviews') ? 'text-white' : 'text-gray-400 group-hover:text-pink-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path>
                        </svg>
                        Customer Reviews
                    </Link>

                    <Link 
                        :href="route('admin.skin-analysis.index')" 
                        class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 group mb-1"
                        :class="route().current('admin.skin-analysis.*') 
                            ? 'bg-rose-600 text-white shadow-lg shadow-rose-200' 
                            : 'text-gray-600 hover:bg-rose-50 hover:text-rose-700'"
                    >
                        <svg 
                            xmlns="http://www.w3.org/2000/svg" 
                            viewBox="0 0 24 24" 
                            fill="none" 
                            stroke="currentColor" 
                            stroke-width="2" 
                            stroke-linecap="round" 
                            stroke-linejoin="round" 
                            class="w-5 h-5 transition-colors"
                            :class="route().current('admin.skin-analysis.*') ? 'text-white' : 'text-gray-400 group-hover:text-rose-600'"
                        >
                            <path d="M22 12h-4l-3 9L9 3l-3 9H2" />
                        </svg>
                        
                        <span class="font-medium text-sm">Skin Analysis</span>
                    </Link>
                </div>
            </div>

            <div v-if="can(['super_admin', 'marketing'])">
                <p class="px-4 text-[10px] font-extrabold text-gray-400 uppercase tracking-widest mb-3">Analytics</p>
                <div class="space-y-1">
                    
                    <Link 
                        :href="route('admin.reports.index')" 
                        class="flex items-center px-4 py-2.5 text-sm font-medium rounded-xl transition-all duration-200 group relative overflow-hidden"
                        :class="isActive('admin.reports') 
                            ? 'bg-gradient-to-r from-pink-500 to-rose-600 text-white shadow-lg shadow-pink-200' 
                            : 'text-gray-600 hover:bg-pink-50 hover:text-pink-700'"
                    >
                        <svg class="w-5 h-5 mr-3 transition-colors" :class="isActive('admin.reports') ? 'text-white' : 'text-gray-400 group-hover:text-pink-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                        Centralized Reports
                    </Link>

                </div>
            </div>

            <div v-if="can(['super_admin', 'marketing'])">
                <p class="px-4 text-[10px] font-extrabold text-gray-400 uppercase tracking-widest mb-3">People</p>
                <div class="space-y-1">
                    
                    <Link 
                        v-if="can(['super_admin', 'marketing'])"
                        :href="route('admin.customers.index')" 
                        class="flex items-center px-4 py-2.5 text-sm font-medium rounded-xl transition-all duration-200 group relative overflow-hidden"
                        :class="isActive('admin.customers') 
                            ? 'bg-gradient-to-r from-pink-500 to-rose-600 text-white shadow-lg shadow-pink-200' 
                            : 'text-gray-600 hover:bg-pink-50 hover:text-pink-700'"
                    >
                        <svg class="w-5 h-5 mr-3 transition-colors" :class="isActive('admin.customers') ? 'text-white' : 'text-gray-400 group-hover:text-pink-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                        Customers
                    </Link>

                    <Link 
                        v-if="can(['super_admin', 'marketing'])"
                        :href="route('admin.ban-requests.index')" 
                        class="flex items-center px-4 py-2.5 text-sm font-medium rounded-xl transition-all duration-200 group relative overflow-hidden"
                        :class="isActive('admin.ban-requests') 
                            ? 'bg-gradient-to-r from-pink-500 to-rose-600 text-white shadow-lg shadow-pink-200' 
                            : 'text-gray-600 hover:bg-pink-50 hover:text-pink-700'"
                    >
                        <svg class="w-5 h-5 mr-3 transition-colors" :class="isActive('admin.ban-requests') ? 'text-white' : 'text-gray-400 group-hover:text-pink-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.618 5.984A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016zM12 9v2m0 4h.01"></path>
                        </svg>
                        Ban Requests
                    </Link>

                    <Link 
                        v-if="can(['super_admin'])"
                        :href="route('admin.staff.index')" 
                        class="flex items-center px-4 py-2.5 text-sm font-medium rounded-xl transition-all duration-200 group relative overflow-hidden mb-1"
                        :class="route().current('admin.staff.*') 
                            ? 'bg-gradient-to-r from-pink-500 to-rose-600 text-white shadow-lg shadow-pink-200' 
                            : 'text-slate-600 hover:bg-pink-50 hover:text-pink-700'"
                    >
                        <svg 
                            class="w-5 h-5 mr-3 transition-colors" 
                            :class="route().current('admin.staff.*') ? 'text-white' : 'text-slate-400 group-hover:text-pink-600'" 
                            xmlns="http://www.w3.org/2000/svg" 
                            fill="none" 
                            viewBox="0 0 24 24" 
                            stroke="currentColor" 
                            stroke-width="2"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        
                        Staff & Roles
                    </Link>
                </div>
            </div>

        </nav>

        <div class="p-4 border-t border-gray-50 bg-gray-50/30 shrink-0">
            <Link 
                :href="route('admin.logout')" 
                method="post" 
                as="button" 
                class="w-full flex items-center justify-center px-4 py-3 text-sm font-bold text-red-600 bg-red-50 hover:bg-red-100 rounded-xl transition-all duration-200 border border-transparent hover:border-red-200 shadow-sm"
            >
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                Sign Out
            </Link>
        </div>
    </aside>
</template>