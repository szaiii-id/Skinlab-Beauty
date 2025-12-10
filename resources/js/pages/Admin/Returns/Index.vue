<script setup>
import { ref, watch } from 'vue';
import { Head, router, Link } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Search, Eye, AlertCircle, CheckCircle, XCircle, RefreshCw } from 'lucide-vue-next';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    returns: Object,
    filters: Object
});

const search = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status || 'pending');

// Debounce Search
let timeout = null;
watch(search, (newVal) => {
    clearTimeout(timeout);
    timeout = setTimeout(() => {
        router.get(route('admin.returns.index'), { 
            search: newVal, 
            status: statusFilter.value 
        }, { preserveState: true, replace: true });
    }, 300);
});

// Watch Tab Change
watch(statusFilter, (newVal) => {
    router.get(route('admin.returns.index'), { 
        search: search.value, 
        status: newVal 
    }, { preserveState: true });
});

const getStatusColor = (status) => {
    switch(status) {
        case 'pending': return 'bg-amber-100 text-amber-800 border-amber-200';
        case 'approved': return 'bg-emerald-100 text-emerald-800 border-emerald-200';
        case 'rejected': return 'bg-red-100 text-red-800 border-red-200';
        default: return 'bg-gray-100 text-gray-800';
    }
};

const formatDate = (date) => new Date(date).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute:'2-digit' });

const tabs = [
    { id: 'all', label: 'All Requests' },
    { id: 'pending', label: 'Pending Review' },
    { id: 'approved', label: 'Approved' },
    { id: 'rejected', label: 'Rejected' },
];
</script>

<template>
    <Head title="Return Management" />

    <div class="max-w-7xl mx-auto">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
            <div>
                <h1 class="text-3xl font-extrabold text-gray-900 flex items-center gap-2">
                    <RefreshCw class="w-8 h-8 text-orange-600" />
                    Return Requests
                </h1>
                <p class="text-gray-500 mt-1">Manage refund and exchange requests</p>
            </div>

            <div class="relative w-full md:w-64">
                <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                <input 
                    v-model="search" 
                    type="text" 
                    placeholder="Search Order Number..." 
                    class="w-full pl-10 pr-4 py-2.5 rounded-xl border-gray-200 focus:border-orange-500 focus:ring-orange-500 transition-all text-sm"
                >
            </div>
        </div>

        <div class="flex overflow-x-auto pb-2 gap-2 mb-6 no-scrollbar">
            <button 
                v-for="tab in tabs" 
                :key="tab.id"
                @click="statusFilter = tab.id"
                class="px-4 py-2 rounded-lg text-sm font-bold whitespace-nowrap transition-all"
                :class="statusFilter === tab.id 
                    ? 'bg-orange-600 text-white shadow-md shadow-orange-200' 
                    : 'bg-white text-gray-600 hover:bg-orange-50'"
            >
                {{ tab.label }}
            </button>
        </div>

        <div class="bg-white rounded-2xl border border-gray-100 shadow-xl shadow-gray-100/50 overflow-hidden">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50/50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-400 uppercase">Order</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-400 uppercase">Customer</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-400 uppercase">Reason & Solution</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-400 uppercase">Status</th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-gray-400 uppercase">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr v-for="ret in returns.data" :key="ret.id" class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4">
                            <span class="font-mono font-bold text-gray-900">#{{ ret.order?.order_number }}</span>
                            <p class="text-xs text-gray-500 mt-1">{{ formatDate(ret.created_at) }}</p>
                        </td>
                        <td class="px-6 py-4">
                            <p class="font-bold text-gray-900 text-sm">{{ ret.order?.user?.name }}</p>
                            <p class="text-xs text-gray-500">{{ ret.order?.user?.email }}</p>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex flex-col gap-1">
                                <span class="text-sm font-medium text-gray-900">{{ ret.reason }}</span>
                                <span class="text-xs font-bold uppercase tracking-wider px-2 py-0.5 rounded border w-fit"
                                    :class="ret.solution === 'refund' ? 'bg-red-50 text-red-600 border-red-100' : 'bg-blue-50 text-blue-600 border-blue-100'">
                                    {{ ret.solution }}
                                </span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-3 py-1 rounded-full text-xs font-bold border uppercase tracking-wide flex items-center gap-1.5 w-fit" :class="getStatusColor(ret.status)">
                                <AlertCircle v-if="ret.status === 'pending'" class="w-3.5 h-3.5" />
                                <CheckCircle v-else-if="ret.status === 'approved'" class="w-3.5 h-3.5" />
                                <XCircle v-else class="w-3.5 h-3.5" />
                                {{ ret.status }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <Link :href="route('admin.returns.show', ret.id)" class="inline-flex items-center gap-1 px-3 py-1.5 bg-white border border-gray-200 rounded-lg text-sm font-bold text-gray-700 hover:bg-orange-50 hover:text-orange-600 hover:border-orange-200 transition-all">
                                <Eye class="w-4 h-4" /> Detail
                            </Link>
                        </td>
                    </tr>
                    
                    <tr v-if="returns.data.length === 0">
                        <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                            No return requests found.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>