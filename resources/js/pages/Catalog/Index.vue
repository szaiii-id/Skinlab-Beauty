<script setup>
import { Head, Link, router } from '@inertiajs/vue3'; 
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue';
import AppNavbarLayout from '@/layouts/app/AppNavbarLayout.vue';
import ProductCard from '@/components/ProductCard.vue';

defineOptions({ layout: AppNavbarLayout });

const props = defineProps({
    products: Object,
    filterTitle: { type: String, default: null } 
});

// State management untuk infinite scroll
const allProducts = ref(props.products.data || []);
const nextUrl = ref(props.products.links.next);
const isLoading = ref(false);
const autoLoadAttempts = ref(0);
const maxAutoLoadAttempts = 3;

const observerTarget = ref(null);
let observer = null;

// Fungsi untuk cek apakah konten halaman pendek
const checkIfContentIsShort = async () => {
    if (!nextUrl.value || isLoading.value || autoLoadAttempts.value >= maxAutoLoadAttempts) return;

    await nextTick();
    
    setTimeout(() => {
        const viewportHeight = window.innerHeight;
        const documentHeight = document.body.offsetHeight;
        const isPageShort = documentHeight < viewportHeight * 1.5;
        
        if (isPageShort && nextUrl.value) {
            autoLoadAttempts.value++;
            loadMoreProducts();
        }
    }, 200);
};

// Fungsi load more products
const loadMoreProducts = () => {
    if (!nextUrl.value || isLoading.value) return;

    isLoading.value = true;
    
    router.get(nextUrl.value, {}, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: (page) => {
            const newProducts = page.props.products;
            
            if (newProducts.data && newProducts.data.length > 0) {
                allProducts.value.push(...newProducts.data);
                nextUrl.value = newProducts.links.next;
                
                // Cek lagi apakah masih perlu auto-load
                if (nextUrl.value) {
                    checkIfContentIsShort();
                }
            }
        },
        onError: () => {
            isLoading.value = false;
        },
        onFinish: () => {
            isLoading.value = false;
        }
    });
};

// Setup Intersection Observer untuk infinite scroll
onMounted(() => {
    // Observer untuk detect scroll ke bawah
    if (observerTarget.value) {
        observer = new IntersectionObserver((entries) => {
            if (entries[0].isIntersecting && nextUrl.value && !isLoading.value) {
                loadMoreProducts();
            }
        }, {
            rootMargin: '100px'
        });
        
        observer.observe(observerTarget.value);
    }
    
    // Initial check untuk auto-load
    checkIfContentIsShort();
});

onUnmounted(() => {
    if (observer) {
        observer.disconnect();
    }
});

// Computed properties
const hasMorePages = computed(() => !!nextUrl.value);
const isListEmpty = computed(() => allProducts.value.length === 0);
</script>

<template>
    <Head :title="filterTitle || 'Product Catalog'" /> 

    <div class="bg-rose-50 min-h-screen">
        <div class="max-w-7xl mx-auto py-12 sm:px-6 lg:px-8">
            
            <!-- Header -->
            <div class="text-center mb-12">
                <h1 class="text-4xl font-light text-gray-900 tracking-tight">
                    {{ filterTitle || 'Our Product Collection' }}
                </h1>
                <p v-if="!filterTitle" class="text-gray-500 mt-3 text-lg">
                    Discover your beauty with Skin Lab.
                </p>
            </div>

            <!-- Empty state -->
            <div v-if="isListEmpty" class="text-center py-12">
                <p class="text-gray-600 text-lg">No products found.</p>
                <Link href="/catalog" class="text-rose-600 hover:text-rose-700 mt-4 inline-block">
                    Browse all products
                </Link>
            </div>

            <!-- Product grid -->
            <div v-else>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                    <ProductCard 
                        v-for="product in allProducts" 
                        :key="`product-${product.id}-${allProducts.length}`" 
                        :product="product" 
                    />
                </div>

                <!-- Loading section -->
                <div class="mt-8 text-center">
                    <!-- Element trigger untuk intersection observer -->
                    <div v-if="hasMorePages" ref="observerTarget" class="h-10 flex items-center justify-center">
                        <div class="w-6 h-6 border-2 border-rose-200 border-t-rose-600 rounded-full animate-spin"></div>
                    </div>
                    
                    <!-- Loading indicator -->
                    <div v-if="isLoading" class="flex justify-center items-center space-x-3 text-rose-600 py-4">
                        <svg class="animate-spin h-5 w-5" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span class="text-sm">Loading more products...</span>
                    </div>
                </div>
                
                <!-- End of catalog message -->
                <div v-if="!hasMorePages && !isListEmpty" class="text-center mt-8 text-gray-500 text-sm py-6 border-t border-gray-200">
                    🎉 You've reached the end of our collection
                </div>
            </div>
        </div>
    </div>
</template>