<script setup>
import { ref, watch } from 'vue';
import { Head, router, Link } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { 
    Search, Filter, Activity, Droplets, Sun, Wind, 
    ChevronRight, Sparkles, Loader2, Gift 
} from 'lucide-vue-next';
import Swal from 'sweetalert2';

// Import the Modal Component
import GiftVoucherModal from '@/components/GiftVoucherModal.vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    profiles: Object,
    filters: Object,
    counts: Object,
    giftRewards: Array // Data from Controller
});

// State
const search = ref(props.filters.search || '');
const typeFilter = ref(props.filters.type || 'all');
const searchDebounce = ref(null);

// Bulk Action State
const selectedIds = ref(new Set()); // We use Set for efficient toggling
const isProcessingBulk = ref(false);
const showGiftModal = ref(false); 

// Watchers for Search & Filter
watch([search, typeFilter], () => {
    clearTimeout(searchDebounce.value);
    searchDebounce.value = setTimeout(() => {
        router.get(route('admin.skin-analysis.index'), { 
            search: search.value, 
            type: typeFilter.value 
        }, { 
            preserveState: true, 
            replace: true,
            onBefore: () => selectedIds.value.clear()
        });
    }, 300);
});

// Checkbox Logic
const toggleSelectAll = () => {
    if (selectedIds.value.size === props.profiles.data.length) {
        selectedIds.value.clear();
    } else {
        props.profiles.data.forEach(p => selectedIds.value.add(p.id));
    }
};

const toggleSelect = (id) => {
    if (selectedIds.value.has(id)) selectedIds.value.delete(id);
    else selectedIds.value.add(id);
};

// Bulk Recommend Logic (AI Recommendation)
const submitBulkRecommend = () => {
    Swal.fire({
        title: `Auto-Recommend to ${selectedIds.value.size} Users?`,
        html: `<p class="text-sm text-slate-600">The system will automatically select the <b>Best Product</b> matching each user's Skin Type and send a notification.</p>`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, Run AI!',
        confirmButtonColor: '#e11d48',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            isProcessingBulk.value = true;
            router.post(route('admin.skin-analysis.bulk-recommend'), {
                ids: Array.from(selectedIds.value) // Convert Set to Array
            }, {
                onSuccess: () => {
                    selectedIds.value.clear();
                    Swal.fire('Success!', 'Bulk recommendations have been sent.', 'success');
                },
                onError: () => Swal.fire('Error', 'Failed to process request.', 'error'),
                onFinish: () => isProcessingBulk.value = false
            });
        }
    });
};

// Helper: Skin Type Colors
const getSkinTypeClass = (type) => {
    switch(type) {
        case 'Oily Skin': return 'bg-amber-50 text-amber-700 border-amber-100 ring-1 ring-amber-500/10';
        case 'Dry Skin': return 'bg-sky-50 text-sky-700 border-sky-100 ring-1 ring-sky-500/10';
        case 'Combination Skin': return 'bg-violet-50 text-violet-700 border-violet-100 ring-1 ring-violet-500/10';
        case 'Normal Skin': return 'bg-emerald-50 text-emerald-700 border-emerald-100 ring-1 ring-emerald-500/10';
        default: return 'bg-slate-50 text-slate-600 border-slate-100 ring-1 ring-slate-500/10';
    }
};

const formatDate = (date) => new Date(date).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
</script>

<template>
    <Head title="Skin Analysis Database" />

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-10">
            <div>
                <h1 class="text-3xl font-bold text-slate-800 tracking-tight flex items-center gap-3">
                    <div class="p-2 bg-rose-50 rounded-xl border border-rose-100">
                        <Activity class="w-6 h-6 text-rose-500" />
                    </div>
                    Skin Analysis Database
                </h1>
                <p class="text-slate-500 mt-2 ml-14 text-sm max-w-2xl">
                    Monitor skin profiles, track diagnostics, and provide expert consultations.
                </p>
            </div>

            <div v-if="selectedIds.size > 0" class="flex items-center gap-3 bg-white border border-rose-200 p-2 pl-5 rounded-xl shadow-lg shadow-rose-100 animate-in slide-in-from-right-5 fade-in">
                <span class="text-sm font-bold text-rose-700 mr-2">{{ selectedIds.size }} Selected</span>
                
                <button 
                    @click="showGiftModal = true"
                    class="flex items-center gap-2 px-4 py-2 bg-white border border-rose-200 text-rose-600 hover:bg-rose-50 rounded-lg text-xs font-bold transition-all shadow-sm"
                >
                    <Gift class="w-4 h-4" /> Send Gift
                </button>

                <div class="h-6 w-px bg-slate-200"></div>

                <button 
                    @click="submitBulkRecommend" 
                    :disabled="isProcessingBulk"
                    class="flex items-center gap-2 px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-lg text-xs font-bold shadow-md transition-all disabled:opacity-70 disabled:cursor-not-allowed"
                >
                    <Loader2 v-if="isProcessingBulk" class="w-4 h-4 animate-spin" />
                    <Sparkles v-else class="w-4 h-4 text-yellow-200" />
                    {{ isProcessingBulk ? 'Processing...' : 'Auto Recommend (AI)' }}
                </button>
            </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-5 mb-10">
            <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm hover:shadow-md transition-shadow group">
                <div class="flex justify-between items-start mb-2">
                    <div class="p-2.5 bg-rose-50 rounded-xl text-rose-500 group-hover:bg-rose-500 group-hover:text-white transition-colors">
                        <Activity class="w-5 h-5"/>
                    </div>
                    <span class="text-xs font-semibold text-rose-600 bg-rose-50 px-2 py-1 rounded-full">Total</span>
                </div>
                <p class="text-3xl font-bold text-slate-800">{{ counts.total }}</p>
                <p class="text-xs text-slate-400 mt-1 font-medium">Analyzed Profiles</p>
            </div>

            <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm hover:shadow-md transition-shadow group">
                <div class="flex justify-between items-start mb-2">
                    <div class="p-2.5 bg-amber-50 rounded-xl text-amber-500 group-hover:bg-amber-500 group-hover:text-white transition-colors">
                        <Sun class="w-5 h-5"/>
                    </div>
                    <span class="text-xs font-semibold text-amber-600 bg-amber-50 px-2 py-1 rounded-full">Oily</span>
                </div>
                <p class="text-3xl font-bold text-slate-800">{{ counts.oily }}</p>
                <p class="text-xs text-slate-400 mt-1 font-medium">Customers</p>
            </div>

            <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm hover:shadow-md transition-shadow group">
                <div class="flex justify-between items-start mb-2">
                    <div class="p-2.5 bg-sky-50 rounded-xl text-sky-500 group-hover:bg-sky-500 group-hover:text-white transition-colors">
                        <Droplets class="w-5 h-5"/>
                    </div>
                    <span class="text-xs font-semibold text-sky-600 bg-sky-50 px-2 py-1 rounded-full">Dry</span>
                </div>
                <p class="text-3xl font-bold text-slate-800">{{ counts.dry }}</p>
                <p class="text-xs text-slate-400 mt-1 font-medium">Customers</p>
            </div>

            <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm hover:shadow-md transition-shadow group">
                <div class="flex justify-between items-start mb-2">
                    <div class="p-2.5 bg-violet-50 rounded-xl text-violet-500 group-hover:bg-violet-500 group-hover:text-white transition-colors">
                        <Wind class="w-5 h-5"/>
                    </div>
                    <span class="text-xs font-semibold text-violet-600 bg-violet-50 px-2 py-1 rounded-full">Combo</span>
                </div>
                <p class="text-3xl font-bold text-slate-800">{{ counts.combination }}</p>
                <p class="text-xs text-slate-400 mt-1 font-medium">Customers</p>
            </div>
        </div>

        <div class="flex flex-col sm:flex-row items-center justify-between gap-4 mb-6">
            <div class="relative w-full sm:w-80 group">
                <Search class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 group-focus-within:text-rose-500 transition-colors" />
                <input 
                    v-model="search" 
                    type="text" 
                    placeholder="Search by name or email..." 
                    class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 bg-white text-sm font-medium text-slate-700 placeholder-slate-400 focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500 shadow-sm transition-all"
                >
            </div>
            
            <div class="relative w-full sm:w-56 group">
                <Filter class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 group-focus-within:text-rose-500 transition-colors" />
                <select 
                    v-model="typeFilter" 
                    class="w-full pl-10 pr-10 py-2.5 rounded-xl border border-slate-200 bg-white text-sm font-medium text-slate-700 focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500 shadow-sm appearance-none cursor-pointer hover:border-slate-300 transition-all"
                >
                    <option value="all">All Skin Types</option>
                    <option value="Oily Skin">Oily Skin</option>
                    <option value="Dry Skin">Dry Skin</option>
                    <option value="Combination Skin">Combination</option>
                    <option value="Normal Skin">Normal Skin</option>
                </select>
                <div class="absolute right-3.5 top-1/2 -translate-y-1/2 pointer-events-none">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100">
                    <thead class="bg-slate-50/80">
                        <tr>
                            <th class="px-6 py-4 w-10">
                                <input 
                                    type="checkbox" 
                                    :checked="selectedIds.size === profiles.data.length && profiles.data.length > 0"
                                    @change="toggleSelectAll"
                                    class="rounded border-slate-300 text-rose-600 focus:ring-rose-500 w-4 h-4 cursor-pointer"
                                >
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Customer Profile</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Diagnosis</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Primary Concerns</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Last Analysis</th>
                            <th class="px-6 py-4 text-right text-xs font-bold text-slate-400 uppercase tracking-wider">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        <tr v-for="profile in profiles.data" :key="profile.id" 
                            class="transition-colors group"
                            :class="selectedIds.has(profile.id) ? 'bg-rose-50/30' : 'hover:bg-slate-50/50'">
                            
                            <td class="px-6 py-4">
                                <input 
                                    type="checkbox" 
                                    :checked="selectedIds.has(profile.id)"
                                    @change="toggleSelect(profile.id)"
                                    class="rounded border-slate-300 text-rose-600 focus:ring-rose-500 w-4 h-4 cursor-pointer"
                                >
                            </td>

                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3.5">
                                    <div class="w-10 h-10 rounded-full bg-gradient-to-br from-slate-100 to-slate-200 flex items-center justify-center text-sm font-bold text-slate-600 shadow-inner border border-white">
                                        {{ profile.user.name.charAt(0).toUpperCase() }}
                                    </div>
                                    <div>
                                        <p class="text-sm font-bold text-slate-800 group-hover:text-rose-600 transition-colors">{{ profile.user.name }}</p>
                                        <p class="text-xs text-slate-500 font-medium">{{ profile.user.email }}</p>
                                    </div>
                                </div>
                            </td>

                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold border" :class="getSkinTypeClass(profile.skin_type)">
                                    {{ profile.skin_type }}
                                </span>
                            </td>

                            <td class="px-6 py-4">
                                <div class="flex flex-wrap gap-1.5">
                                    <span v-for="(concern, idx) in profile.skin_concerns.slice(0, 2)" :key="idx" 
                                          class="text-[11px] font-medium bg-slate-50 text-slate-600 px-2 py-0.5 rounded-md border border-slate-200">
                                        {{ concern }}
                                    </span>
                                    <span v-if="profile.skin_concerns.length > 2" class="text-[10px] font-bold text-slate-400 py-0.5 px-1">
                                        +{{ profile.skin_concerns.length - 2 }}
                                    </span>
                                </div>
                            </td>

                            <td class="px-6 py-4 text-sm font-medium text-slate-500">
                                {{ formatDate(profile.updated_at) }}
                            </td>

                            <td class="px-6 py-4 text-right">
                                <Link :href="route('admin.skin-analysis.show', profile.id)" 
                                      class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg text-xs font-bold hover:border-rose-200 hover:text-rose-600 hover:shadow-sm transition-all group/btn">
                                    Analyze 
                                    <ChevronRight class="w-3.5 h-3.5 text-slate-400 group-hover/btn:text-rose-500 transition-colors" />
                                </Link>
                            </td>
                        </tr>
                        
                        <tr v-if="profiles.data.length === 0">
                            <td colspan="6" class="px-6 py-16 text-center">
                                <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4 border border-slate-100">
                                    <Search class="w-8 h-8 text-slate-300" />
                                </div>
                                <h3 class="text-sm font-bold text-slate-900">No Profiles Found</h3>
                                <p class="text-xs text-slate-500 mt-1">Try adjusting your search or filters.</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div v-if="profiles.links.length > 3" class="px-6 py-4 border-t border-slate-100 bg-slate-50/30 flex justify-center">
                <div class="flex gap-1">
                    <Link 
                        v-for="(link, i) in profiles.links" 
                        :key="i"
                        :href="link.url || '#'"
                        v-html="link.label"
                        class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all"
                        :class="link.active 
                            ? 'bg-rose-500 text-white shadow-md shadow-rose-200' 
                            : 'text-slate-500 hover:bg-white hover:text-slate-700'"
                    />
                </div>
            </div>
        </div>
    </div>

    <GiftVoucherModal 
        :show="showGiftModal"
        :users="Array.from(selectedIds)" 
        :rewards="giftRewards" 
        :submit-url="route('admin.customers.send-gift')" 
        @close="showGiftModal = false"
        @success="() => { selectedIds.clear(); }"
    />
</template>