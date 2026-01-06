<script setup>
import { ref, watch, computed, onMounted, onUnmounted } from 'vue';
import axios from 'axios';
import { Search, X, Loader2, ArrowRight } from 'lucide-vue-next'; 
import debounce from 'lodash.debounce';
import { Link, router } from '@inertiajs/vue3';
import { useFormatting } from '@/composables/useFormatting';

const { formatCurrency } = useFormatting();

const query = ref('');
const searchResults = ref([]);
const isLoading = ref(false);
const isDropdownOpen = ref(false);
const searchContainer = ref(null);

const fetchResults = async () => {
    if (!query.value.trim()) {
        searchResults.value = [];
        return; 
    }
    
    isLoading.value = true;
    
    try {
        const response = await axios.get('/api/instant-search', {
            params: { q: query.value }
        });
        searchResults.value = response.data; 
    } catch (error) {
        console.error("Search failed", error);
        searchResults.value = [];
    } finally {
        isLoading.value = false;
    }
};

const debouncedFetch = debounce(fetchResults, 300);

watch(query, (newVal) => {
    if (newVal) {
        isDropdownOpen.value = true;
        debouncedFetch();
    } else {
        isDropdownOpen.value = false;
        searchResults.value = [];
    }
});

const resetSearch = () => {
    query.value = '';
    searchResults.value = [];
    isDropdownOpen.value = false;
};

const handleViewAllResults = () => {
    if (query.value.trim()) {
        router.get('/search', { q: query.value }, {
            onSuccess: () => {
                isDropdownOpen.value = false; 
            }
        });
    }
};

const handleClickOutside = (event) => {
    if (searchContainer.value && !searchContainer.value.contains(event.target)) {
        isDropdownOpen.value = false;
    }
};

const handleEscapeKey = (event) => {
    if (event.key === 'Escape') isDropdownOpen.value = false;
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
    document.addEventListener('keydown', handleEscapeKey);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
    document.removeEventListener('keydown', handleEscapeKey);
});

const showDropdown = computed(() => isDropdownOpen.value && query.value.length > 0);
const hasResults = computed(() => searchResults.value.length > 0);
</script>

<template>
    <div ref="searchContainer" class="relative w-full max-w-lg mx-auto"> 
        <div class="relative group">
            <input
                type="text"
                v-model="query"
                @focus="isDropdownOpen = true"
                @keyup.enter="handleViewAllResults"
                placeholder="Search products..."
                class="w-full pl-10 pr-10 py-2 rounded-full border border-gray-200 bg-gray-100/50 text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-rose-500/10 focus:border-rose-500 focus:bg-white transition-all duration-300 text-sm shadow-sm"
            />
            
            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400 group-focus-within:text-rose-500 transition-colors">
                <Search class="h-4 w-4" /> </div>

            <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                <div v-if="isLoading">
                    <Loader2 class="h-4 w-4 text-rose-500 animate-spin" />
                </div>
                <button
                    v-else-if="query"
                    @click="resetSearch"
                    class="text-gray-400 hover:text-gray-600 transition-colors p-1 rounded-full hover:bg-gray-200"
                >
                    <X class="h-3.5 w-3.5" />
                </button>
            </div>
        </div>

        <transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0 translate-y-2 scale-95"
            enter-to-class="opacity-100 translate-y-0 scale-100"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100 translate-y-0 scale-100"
            leave-to-class="opacity-0 translate-y-2 scale-95"
        >
            <div 
                v-if="showDropdown" 
                class="absolute top-full left-0 right-0 mt-2 bg-white border border-gray-100 rounded-2xl shadow-xl z-50 overflow-hidden ring-1 ring-black/5"
            >
                <div v-if="!isLoading && !hasResults" class="p-8 text-center">
                    <p class="text-sm text-gray-900 font-medium">No products found</p>
                    <p class="text-xs text-gray-500 mt-1">Try a different keyword</p>
                </div>
                
                <div v-else class="max-h-[300px] overflow-y-auto custom-scrollbar">
                    <div v-if="isLoading && !hasResults" class="p-4 space-y-4">
                        <div v-for="i in 3" :key="i" class="flex items-center gap-3 animate-pulse">
                            <div class="w-10 h-10 bg-gray-100 rounded-lg"></div>
                            <div class="flex-1 space-y-2">
                                <div class="h-3 bg-gray-100 rounded w-3/4"></div>
                                <div class="h-2 bg-gray-100 rounded w-1/4"></div>
                            </div>
                        </div>
                    </div>

                    <div v-else>
                        <p class="px-4 py-2.5 text-[10px] font-bold text-gray-400 uppercase tracking-wider bg-gray-50/80 sticky top-0 backdrop-blur-md z-10 border-b border-gray-100">
                            Suggestions
                        </p>
                        
                        <Link 
                            v-for="product in searchResults" 
                            :key="product.id" 
                            :href="`/products/${product.slug}/${product.id}`" 
                            class="flex items-center gap-3 px-4 py-3 hover:bg-rose-50/50 transition-colors group border-b border-gray-50 last:border-0"
                            @click="resetSearch"
                        >
                            <div class="shrink-0 relative">
                                <img 
                                    :src="product.image_url || '/images/placeholder.png'" 
                                    :alt="product.name"
                                    class="w-10 h-10 object-cover rounded-lg border border-gray-100 group-hover:border-rose-200 transition-colors"
                                />
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-800 truncate group-hover:text-rose-600 transition-colors">
                                    {{ product.name }}
                                </p>
                                <p class="text-xs text-rose-600 font-bold mt-0.5">
                                    {{ formatCurrency(product.variants?.[0]?.price || product.price || 0) }}
                                </p>
                            </div>
                        </Link>
                    </div>
                </div>
                
                <div v-if="hasResults" class="p-2 bg-gray-50/50 border-t border-gray-100 backdrop-blur-sm">
                    <button
                        @click="handleViewAllResults"
                        class="w-full py-2.5 px-4 bg-rose-600 text-white rounded-xl text-xs font-bold hover:bg-rose-700 transition-all flex items-center justify-center gap-2 shadow-sm hover:shadow-md"
                    >
                        <span>View All {{ searchResults.length }} Results</span>
                        <ArrowRight class="w-3.5 h-3.5" />
                    </button>
                </div>
            </div>
        </transition>
    </div>
</template>

<style scoped>
/* Scrollbar Cantik Minimalis */
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #e2e8f0; /* Gray-200 */
    border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #cbd5e1; /* Gray-300 */
}
</style>