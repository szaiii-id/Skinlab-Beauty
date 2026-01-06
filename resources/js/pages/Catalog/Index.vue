<script setup>
import { Head, Link, router } from '@inertiajs/vue3'; 
import { ref, computed, onMounted, onUnmounted, nextTick, watch } from 'vue';
import AppNavbarLayout from '@/layouts/app/AppNavbarLayout.vue';
import ProductCard from '@/components/ProductCard.vue';

defineOptions({ layout: AppNavbarLayout });

const props = defineProps({
    products: [Object, Array], 
    filterTitle: { type: String, default: null },
    bannerImage: { type: String, default: null } 
});

// --- HELPER ---
const extractProducts = (data) => {
    if (!data) return [];
    if (Array.isArray(data)) return data; 
    if (data.data && Array.isArray(data.data)) return data.data; 
    return [];
};

// --- STATE ---
const allProducts = ref(extractProducts(props.products));
const nextUrl = ref(props.products?.links?.next || null);

const isLoading = ref(false);
const autoLoadAttempts = ref(0);
const maxAutoLoadAttempts = 3;
const observerTarget = ref(null);
let observer = null;

// --- WATCHER ---
watch(() => props.products, (newVal) => {
    const page = newVal?.current_page || newVal?.meta?.current_page || 1;
    
    if (page === 1) {
        allProducts.value = extractProducts(newVal);
        nextUrl.value = newVal?.links?.next || null;
        isLoading.value = false;
        autoLoadAttempts.value = 0;
        if (nextUrl.value) checkIfContentIsShort();
    } else {
        nextUrl.value = newVal?.links?.next || null;
    }
}, { deep: true });

// --- FUNGSI PEMBERSIH URL ---
const cleanUrl = () => {
    if (typeof window !== 'undefined') {
        const url = new URL(window.location.href);
        url.searchParams.delete('page'); 
        window.history.replaceState({}, '', url.toString());
    }
};

// --- INFINITE SCROLL ---
const checkIfContentIsShort = async () => {
    if (!nextUrl.value || isLoading.value || autoLoadAttempts.value >= maxAutoLoadAttempts) return;
    await nextTick();
    setTimeout(() => {
        if (!observerTarget.value) return; 
        const isPageShort = document.body.offsetHeight < window.innerHeight * 1.5;
        if (isPageShort && nextUrl.value) {
            autoLoadAttempts.value++;
            loadMoreProducts();
        }
    }, 200);
};

const loadMoreProducts = () => {
    if (!nextUrl.value || isLoading.value) return;
    isLoading.value = true;
    
    router.get(nextUrl.value, {}, {
        preserveScroll: true,
        preserveState: true,
        only: ['products'], 
        onSuccess: (page) => {
            const newProducts = page.props.products;
            const newItems = extractProducts(newProducts);
            
            if (newItems.length > 0) {
                const currentIds = new Set(allProducts.value.map(p => p.id));
                const uniqueItems = newItems.filter(p => !currentIds.has(p.id));
                allProducts.value.push(...uniqueItems);
                
                nextUrl.value = newProducts.links?.next || null;
                
                cleanUrl();

                if (nextUrl.value) checkIfContentIsShort();
            } else {
                nextUrl.value = null;
            }
        },
        onError: () => { isLoading.value = false; },
        onFinish: () => { isLoading.value = false; }
    });
};

onMounted(() => {
    const url = new URL(window.location.href);
    if (url.searchParams.has('page') && url.searchParams.get('page') > 1) {
        url.searchParams.delete('page');
        window.location.replace(url.toString());
        return; 
    }

    if (observerTarget.value) {
        observer = new IntersectionObserver((entries) => {
            if (entries[0].isIntersecting && nextUrl.value && !isLoading.value) {
                loadMoreProducts();
            }
        }, { rootMargin: '200px' });
        observer.observe(observerTarget.value);
    }
    
    if ((allProducts.value?.length || 0) <= 8) {
        checkIfContentIsShort();
    }
});

onUnmounted(() => { if (observer) observer.disconnect(); });

const hasMorePages = computed(() => !!nextUrl.value);
const isListEmpty = computed(() => (allProducts.value?.length || 0) === 0);
</script>

<template>
    <Head :title="filterTitle || 'Product Catalog'" /> 

    <div class="bg-rose-50 min-h-screen">
        <div class="max-w-7xl mx-auto py-8 md:py-12 px-4 sm:px-6 lg:px-8">
            
            <div class="text-center mb-6 md:mb-8">
                <h1 class="text-2xl md:text-4xl font-light text-gray-900 tracking-tight">
                    {{ filterTitle || 'Our Product Collection' }}
                </h1>
            </div>

            <div v-if="isListEmpty" class="text-center py-20 bg-white rounded-xl shadow-sm border border-gray-100">
                <h3 class="text-lg font-medium text-gray-900">No products found.</h3>
                <Link href="/" class="px-6 py-2 bg-rose-600 text-white rounded-full mt-4 inline-block font-medium">
                    Back to Home
                </Link>
            </div>

            <div v-else>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 md:gap-6">
                    <ProductCard 
                        v-for="product in allProducts" 
                        :key="product.id" 
                        :product="product" 
                    />
                </div>

                <div class="mt-8 md:mt-12 text-center min-h-[50px]">
                    <div v-if="hasMorePages" ref="observerTarget" class="flex justify-center py-4">
                         <div v-if="isLoading" class="flex items-center space-x-2 text-rose-600">
                            <svg class="animate-spin h-5 w-5" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            <span class="text-sm font-medium">Loading more...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>