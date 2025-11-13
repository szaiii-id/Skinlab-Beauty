<script setup lang="ts">
import { ref, watch, computed, onMounted, onUnmounted } from 'vue';
import axios from 'axios';
import { Search } from 'lucide-vue-next';
import debounce from 'lodash.debounce';
import { Link } from '@inertiajs/vue3';
import { useFormatting } from '@/composables/useFormatting';

const query = ref('');
const searchResults = ref([]);
const isLoading = ref(false);
const isDropdownOpen = ref(false);
const searchContainer = ref<HTMLElement | null>(null);

const { formatCurrency } = useFormatting();

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
    } catch (e) {
        console.error("Instant Search failed", e);
        searchResults.value = [];
    } finally {
        isLoading.value = false;
    }
};

const debouncedFetch = debounce(fetchResults, 300);

watch(query, () => {
    debouncedFetch();
});

const showDropdown = computed(() => 
    isDropdownOpen.value && query.value.length > 0 && (searchResults.value.length > 0 || isLoading.value)
);

const resetSearch = () => {
    query.value = '';
    searchResults.value = [];
    isDropdownOpen.value = false;
    isLoading.value = false;
};

const handleViewAllClick = () => {
    resetSearch();
};

const handleClickOutside = (event: Event) => {
    if (searchContainer.value && !searchContainer.value.contains(event.target as Node)) {
        isDropdownOpen.value = false;
    }
};

const handleEscapeKey = (event: KeyboardEvent) => {
    if (event.key === 'Escape') {
        isDropdownOpen.value = false;
    }
};

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
        <!-- Search Input - UKURAN DIPERKECIL -->
        <div class="relative">
            <input
                type="text"
                v-model="query"
                @focus="isDropdownOpen = true"
                placeholder="Search products..."
                class="w-full pl-10 pr-4 py-2 rounded-full border border-gray-300 bg-white text-gray-900 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:border-rose-500 shadow-sm transition-all duration-200 text-sm"
            />
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <Search class="h-4 w-4 text-gray-400" />
            </div>
        </div>

        <!-- Dropdown Results -->
        <div 
            v-if="showDropdown" 
            class="absolute top-full left-0 right-0 mt-1 bg-white border border-gray-200 rounded-lg shadow-xl z-50 overflow-hidden"
        >
            <!-- Loading State -->
            <div v-if="isLoading" class="p-3 text-center">
                <div class="flex items-center justify-center space-x-1">
                    <div class="w-2 h-2 bg-rose-500 rounded-full animate-bounce"></div>
                    <div class="w-2 h-2 bg-rose-500 rounded-full animate-bounce" style="animation-delay: 0.1s"></div>
                    <div class="w-2 h-2 bg-rose-500 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
                </div>
                <p class="mt-1 text-xs text-rose-600">Searching...</p>
            </div>
            
            <!-- No Results -->
            <div v-else-if="searchResults.length === 0 && query.length > 0" class="p-3 text-center">
                <p class="text-xs text-gray-500">No results found for</p>
                <p class="text-xs text-gray-700 font-medium">"{{ query }}"</p>
            </div>
            
            <!-- Hasil Pencarian -->
            <div v-else class="max-h-64 overflow-y-auto">
                <div class="divide-y divide-gray-100">
                    <Link 
                        v-for="product in searchResults" 
                        :key="product.id" 
                        :href="`/products/${product.slug}/${product.id}`" 
                        class="flex items-center p-2 hover:bg-rose-50 transition-colors duration-150 group"
                        @click="resetSearch"
                    >
                        <div class="flex-shrink-0">
                            <img 
                                :src="product.image_url || '/images/default-product.png'" 
                                :alt="product.name"
                                class="w-8 h-8 object-cover rounded border border-gray-200 group-hover:border-rose-200"
                            />
                        </div>
                        <div class="ml-3 flex-1 min-w-0">
                            <p class="text-xs font-medium text-gray-900 truncate group-hover:text-rose-700">
                                {{ product.name }}
                            </p>
                            <p class="text-xs text-rose-600 font-semibold">
                                {{ formatCurrency(product.variants[0]?.price || product.price) }}
                            </p>
                        </div>
                    </Link>
                </div>
                
                <!-- View All Link -->
                <div class="border-t border-gray-100 bg-gray-50 p-2">
                    <Link 
                        :href="`/search?q=${encodeURIComponent(query)}`" 
                        class="flex items-center justify-center w-full py-1 px-3 bg-white border border-rose-600 text-rose-600 rounded text-xs font-semibold hover:bg-rose-600 hover:text-white transition-colors duration-150 group"
                        @click="handleViewAllClick"
                    >
                        <span>View All Results</span>
                        <svg class="w-3 h-3 ml-1 group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </Link>
                </div>
            </div>
        </div>

        <!-- Backdrop untuk mobile -->
        <div 
            v-if="showDropdown" 
            class="fixed inset-0 bg-black bg-opacity-10 z-40 md:hidden"
            @click="isDropdownOpen = false"
        ></div>
    </div>
</template>