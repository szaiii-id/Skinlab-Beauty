<script setup>
import { ref, watch, computed, onMounted, onUnmounted } from 'vue';
import axios from 'axios';
import { Search, X } from 'lucide-vue-next';
import debounce from 'lodash.debounce';
import { Link, router } from '@inertiajs/vue3';
import { useFormatting } from '@/composables/useFormatting';

const { formatCurrency } = useFormatting();

// State untuk search
const query = ref('');
const searchResults = ref([]);
const isLoading = ref(false);
const isDropdownOpen = ref(false);
const searchContainer = ref(null);

// Fetch hasil instant search
const fetchResults = async () => {
    if (!query.value.trim()) {
        searchResults.value = [];
        isDropdownOpen.value = false;
        return;
    }
    
    isLoading.value = true;
    isDropdownOpen.value = true;
    
    try {
        const response = await axios.get('/api/instant-search', {
            params: { q: query.value }
        });
        searchResults.value = response.data; 
    } catch (error) {
        console.error("Instant Search failed", error);
        searchResults.value = [];
    } finally {
        isLoading.value = false;
    }
};

const debouncedFetch = debounce(fetchResults, 300);

watch(query, () => {
    debouncedFetch();
});

// Reset search
const resetSearch = () => {
    query.value = '';
    searchResults.value = [];
    isDropdownOpen.value = false;
    isLoading.value = false;
};

// Clear search input
const clearSearch = () => {
    resetSearch();
};

// Handle view all results
const handleViewAllResults = () => {
    if (query.value.trim()) {
        router.get('/search', { q: query.value }, {
            onSuccess: () => {
                resetSearch();
            }
        });
    }
};

// Handle klik product
const handleProductClick = () => {
    resetSearch();
};

// Computed properties
const showDropdown = computed(() => 
    isDropdownOpen.value && query.value.length > 0 && (searchResults.value.length > 0 || isLoading.value)
);

const hasResults = computed(() => searchResults.value.length > 0);

// Click outside handler
const handleClickOutside = (event) => {
    if (searchContainer.value && !searchContainer.value.contains(event.target)) {
        isDropdownOpen.value = false;
    }
};

// Escape key handler
const handleEscapeKey = (event) => {
    if (event.key === 'Escape') {
        isDropdownOpen.value = false;
    }
};

// Event listeners
onMounted(() => {
    document.addEventListener('click', handleClickOutside);
    document.addEventListener('keydown', handleEscapeKey);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
    document.removeEventListener('keydown', handleEscapeKey);
});
</script>

<template>
    <div ref="searchContainer" class="relative w-full max-w-md mx-auto">
        <!-- Search Input -->
        <div class="relative">
            <input
                type="text"
                v-model="query"
                @focus="isDropdownOpen = true"
                placeholder="Search products..."
                class="w-full pl-10 pr-10 py-2 rounded-full border border-gray-300 bg-white text-gray-900 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:border-rose-500 shadow-sm transition-all duration-200 text-sm"
            />
            <!-- Search Icon -->
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <Search class="h-4 w-4 text-gray-400" />
            </div>
            <!-- Clear Button -->
            <button
                v-if="query"
                @click="clearSearch"
                class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 transition-colors"
            >
                <X class="h-4 w-4" />
            </button>
        </div>

        <!-- Dropdown Results -->
        <div 
            v-if="showDropdown" 
            class="absolute top-full left-0 right-0 mt-1 bg-white border border-gray-200 rounded-lg shadow-xl z-50 overflow-hidden"
        >
            <!-- Loading State -->
            <div v-if="isLoading" class="p-4 text-center">
                <div class="flex items-center justify-center space-x-2">
                    <div class="w-2 h-2 bg-rose-500 rounded-full animate-bounce"></div>
                    <div class="w-2 h-2 bg-rose-500 rounded-full animate-bounce" style="animation-delay: 0.1s"></div>
                    <div class="w-2 h-2 bg-rose-500 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
                </div>
                <p class="mt-2 text-xs text-gray-600">Searching products...</p>
            </div>
            
            <!-- No Results -->
            <div v-else-if="!hasResults && query.length > 0" class="p-4 text-center">
                <p class="text-xs text-gray-500">No results found for</p>
                <p class="text-xs text-gray-700 font-medium mt-1">"{{ query }}"</p>
            </div>
            
            <!-- Search Results -->
            <div v-else class="max-h-64 overflow-y-auto">
                <div class="divide-y divide-gray-100">
                    <Link 
                        v-for="product in searchResults" 
                        :key="product.id" 
                        :href="`/products/${product.slug}/${product.id}`" 
                        class="flex items-center p-3 hover:bg-rose-50 transition-colors duration-150 group"
                        @click="handleProductClick"
                    >
                        <div class="flex-shrink-0">
                            <img 
                                :src="product.image_url || '/images/default-product.png'" 
                                :alt="product.name"
                                class="w-10 h-10 object-cover rounded border border-gray-200 group-hover:border-rose-200"
                            />
                        </div>
                        <div class="ml-3 flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 truncate group-hover:text-rose-700">
                                {{ product.name }}
                            </p>
                            <p class="text-xs text-rose-600 font-semibold mt-1">
                                {{ formatCurrency(product.variants?.[0]?.price || product.price || 0) }}
                            </p>
                        </div>
                    </Link>
                </div>
                
                <!-- View All Results Button -->
                <div v-if="hasResults" class="border-t border-gray-100 bg-gray-50 p-3">
                    <button
                        @click="handleViewAllResults"
                        class="flex items-center justify-center w-full py-2 px-4 bg-rose-600 text-white rounded-lg text-sm font-semibold hover:bg-rose-700 transition-colors duration-150 group"
                    >
                        <span>View All Results</span>
                        <svg class="w-4 h-4 ml-2 group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile backdrop -->
        <div 
            v-if="showDropdown" 
            class="fixed inset-0 bg-black bg-opacity-10 z-40 md:hidden"
            @click="isDropdownOpen = false"
        ></div>
    </div>
</template>