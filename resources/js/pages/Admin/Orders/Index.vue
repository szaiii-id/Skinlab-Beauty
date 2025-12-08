<script setup>
import { ref, watch, computed } from 'vue';
import { Head, router, Link } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { 
    Search, Filter, ShoppingBag, Truck, CheckCircle, XCircle, 
    Clock, ChevronRight, Eye, AlertTriangle, FileText, Loader2
} from 'lucide-vue-next';
import Swal from 'sweetalert2';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    orders: Object,
    filters: Object,
});

// State
const search = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status || 'all');
const searchDebounce = ref(null);

// State Bulk Action
const selectedOrders = ref(new Set());
const isSelectAllPage = ref(false);
const isBulkBooking = ref(false);

// State Cancel Modal
const showCancelModal = ref(false);
const selectedOrder = ref(null);
const cancelReason = ref('');
const isSubmitting = ref(false);

// Formatters
const formatCurrency = (val) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val);
const formatDate = (date) => new Date(date).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });

// Tabs
const tabs = [
    { id: 'all', label: 'All Orders', icon: FileText },
    { id: 'pending', label: 'Pending', icon: Clock },
    { id: 'processing', label: 'Processing', icon: ShoppingBag },
    { id: 'shipped', label: 'Shipped', icon: Truck },
    { id: 'completed', label: 'Completed', icon: CheckCircle },
    { id: 'cancelled', label: 'Cancelled', icon: XCircle },
];

const getStatusClass = (status) => {
    switch(status) {
        case 'pending': return 'bg-yellow-100 text-yellow-700 border-yellow-200';
        case 'processing': return 'bg-blue-100 text-blue-700 border-blue-200';
        case 'shipped': return 'bg-purple-100 text-purple-700 border-purple-200';
        case 'completed': return 'bg-emerald-100 text-emerald-700 border-emerald-200';
        case 'cancelled': return 'bg-red-100 text-red-700 border-red-200';
        default: return 'bg-gray-100 text-gray-700 border-gray-200';
    }
};

// Watchers
watch([search, statusFilter], () => {
    clearTimeout(searchDebounce.value);
    searchDebounce.value = setTimeout(() => {
        router.get(route('admin.orders.index'), { 
            search: search.value, 
            status: statusFilter.value 
        }, {
            preserveState: true, replace: true,
            onBefore: () => { selectedOrders.value.clear(); isSelectAllPage.value = false; }
        });
    }, 300);
});

// Selection Logic
const toggleSelectOrder = (id) => {
    if (selectedOrders.value.has(id)) selectedOrders.value.delete(id);
    else selectedOrders.value.add(id);
    updateSelectAllState();
};

const toggleSelectAllPage = () => {
    if (isSelectAllPage.value) selectedOrders.value.clear();
    else props.orders.data.forEach(o => selectedOrders.value.add(o.id));
    isSelectAllPage.value = !isSelectAllPage.value;
};

const updateSelectAllState = () => {
    if (props.orders.data.length === 0) { isSelectAllPage.value = false; return; }
    isSelectAllPage.value = props.orders.data.every(o => selectedOrders.value.has(o.id));
};

// Actions
const bulkRequestPickup = () => {
    Swal.fire({
        title: `Request Pickup for ${selectedOrders.value.size} Orders?`,
        text: "Only orders with 'Processing' status will be booked.",
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, Book Now',
        confirmButtonColor: '#2563eb'
    }).then((result) => {
        if (result.isConfirmed) {
            isBulkBooking.value = true;
            router.post(route('admin.orders.bulk-book'), {
                ids: Array.from(selectedOrders.value)
            }, {
                onSuccess: () => {
                    selectedOrders.value.clear();
                    isSelectAllPage.value = false;
                    Swal.fire('Success', 'Bulk booking process completed.', 'success');
                },
                onError: () => Swal.fire('Error', 'Some orders failed to book.', 'error'),
                onFinish: () => isBulkBooking.value = false
            });
        }
    });
};

const openCancelModal = (order) => {
    selectedOrder.value = order;
    cancelReason.value = '';
    showCancelModal.value = true;
};

const submitCancel = () => {
    if (!cancelReason.value || cancelReason.value.length < 5) {
        Swal.fire({ icon: 'warning', title: 'Reason Required', text: 'Min 5 chars.' });
        return;
    }
    isSubmitting.value = true;
    router.post(route('admin.orders.cancel', selectedOrder.value.id), { reason: cancelReason.value }, {
        onSuccess: () => { showCancelModal.value = false; Swal.fire('Cancelled', 'Order cancelled.', 'success'); },
        onFinish: () => isSubmitting.value = false
    });
};
</script>

<template>
    <Head title="Order Management" />

    <div class="max-w-7xl mx-auto">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-8">
            <div>
                <h1 class="text-3xl font-extrabold bg-gradient-to-r from-pink-600 to-rose-600 text-transparent bg-clip-text">
                    Order Management
                </h1>
                <div class="flex items-center gap-2 mt-1">
                    <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-gray-100 text-gray-600">
                        {{ orders.total }} orders
                    </span>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                
                <div v-if="selectedOrders.size > 0" class="flex items-center gap-2 animate-in fade-in slide-in-from-bottom-2">
                    <button 
                        @click="bulkRequestPickup"
                        :disabled="isBulkBooking"
                        class="h-11 px-4 bg-blue-600 text-white font-bold rounded-xl hover:bg-blue-700 transition-all flex items-center gap-2 shadow-sm"
                    >
                        <Loader2 v-if="isBulkBooking" class="w-4 h-4 animate-spin" />
                        <Truck v-else class="w-4 h-4" />
                        Request Pickup ({{ selectedOrders.size }})
                    </button>
                    <div class="w-px h-8 bg-gray-300 mx-1"></div>
                </div>

                <div class="relative group w-full md:w-64">
                    <Search class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                    <input 
                        v-model="search" type="text" placeholder="Search Order ID..." 
                        class="h-11 pl-10 pr-4 w-full border border-gray-200 rounded-xl text-sm font-medium focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500 shadow-sm transition-all"
                    >
                </div>
            </div>
        </div>

        <div class="flex overflow-x-auto pb-1 mb-6 gap-2 no-scrollbar">
            <button v-for="tab in tabs" :key="tab.id" @click="statusFilter = tab.id"
                class="px-4 py-2.5 rounded-lg font-medium text-sm flex items-center gap-2 transition-all whitespace-nowrap border"
                :class="statusFilter === tab.id ? 'bg-rose-50 text-rose-700 border-rose-200 shadow-sm' : 'bg-white text-gray-600 border-transparent hover:bg-gray-50'">
                <component :is="tab.icon" class="w-4 h-4" /> {{ tab.label }}
            </button>
        </div>

        <div class="bg-white rounded-2xl border border-gray-100 shadow-xl shadow-gray-100/50 overflow-hidden">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50/80">
                    <tr>
                        <th class="px-6 py-4 w-10">
                            <input type="checkbox" :checked="isSelectAllPage" @change="toggleSelectAllPage" class="rounded border-gray-300 text-rose-600 focus:ring-rose-500 w-4 h-4 cursor-pointer">
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-extrabold text-gray-400 uppercase tracking-wider">Order Details</th>
                        <th class="px-6 py-4 text-left text-xs font-extrabold text-gray-400 uppercase tracking-wider">Customer</th>
                        <th class="px-6 py-4 text-left text-xs font-extrabold text-gray-400 uppercase tracking-wider">Total & Payment</th>
                        <th class="px-6 py-4 text-left text-xs font-extrabold text-gray-400 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-4 text-right text-xs font-extrabold text-gray-400 uppercase tracking-wider">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr v-for="order in orders.data" :key="order.id" class="hover:bg-rose-50/30 transition-colors group">
                        <td class="px-6 py-4">
                            <input type="checkbox" :checked="selectedOrders.has(order.id)" @change="toggleSelectOrder(order.id)" class="rounded border-gray-300 text-rose-600 focus:ring-rose-500 w-4 h-4 cursor-pointer">
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex flex-col">
                                <span class="font-bold text-gray-900 text-sm">#{{ order.order_number }}</span>
                                <span class="text-xs text-gray-500 mt-0.5 flex items-center gap-1"><Clock class="w-3 h-3" /> {{ formatDate(order.created_at) }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center text-xs font-bold text-gray-600">{{ order.user?.name?.charAt(0) || 'U' }}</div>
                                <div>
                                    <p class="text-sm font-bold text-gray-900">{{ order.user?.name || 'Guest' }}</p>
                                    <p class="text-xs text-gray-500">{{ order.user?.email }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="font-bold text-gray-900 text-sm">{{ formatCurrency(order.total_amount) }}</div>
                            <div class="text-xs mt-1 px-2 py-0.5 rounded-full w-fit font-medium uppercase tracking-wide border"
                                :class="order.payment_status === 'paid' ? 'bg-emerald-50 text-emerald-700 border-emerald-100' : 'bg-gray-100 text-gray-600 border-gray-200'">
                                {{ order.payment_status }}
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-3 py-1 rounded-full text-xs font-bold border flex items-center gap-1 w-fit uppercase tracking-wider"
                                :class="getStatusClass(order.order_status)">
                                <span class="w-1.5 h-1.5 rounded-full" 
                                    :class="{
                                        'bg-yellow-500': order.order_status === 'pending',
                                        'bg-blue-500': order.order_status === 'processing',
                                        'bg-purple-500': order.order_status === 'shipped',
                                        'bg-emerald-500': order.order_status === 'completed',
                                        'bg-red-500': order.order_status === 'cancelled'
                                    }"></span>
                                {{ order.order_status }}
                            </span>
                            <div v-if="order.order_status === 'shipped' && order.resi_number" class="mt-1 text-[10px] text-gray-500 font-mono">
                                AWB: {{ order.resi_number }}
                            </div>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <button v-if="!['completed', 'cancelled', 'shipped'].includes(order.order_status)"
                                    @click="openCancelModal(order)"
                                    class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-all"
                                    title="Cancel Order">
                                    <XCircle class="w-5 h-5" />
                                </button>
                                <Link :href="route('admin.orders.show', order.id)" 
                                    class="flex items-center gap-1 px-3 py-1.5 text-xs font-bold text-white bg-gray-900 rounded-lg hover:bg-gray-800 transition-all shadow-sm group">
                                    View <ChevronRight class="w-3 h-3 group-hover:translate-x-0.5 transition-transform" />
                                </Link>
                            </div>
                        </td>
                    </tr>
                    
                    <tr v-if="orders.data.length === 0">
                        <td colspan="6" class="px-6 py-16 text-center">
                            <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4 border border-gray-100">
                                <ShoppingBag class="w-8 h-8 text-gray-300" />
                            </div>
                            <h3 class="text-lg font-bold text-gray-900">No orders found</h3>
                            <p class="text-sm text-gray-500 mt-1">Try adjusting your search or filters.</p>
                        </td>
                    </tr>
                </tbody>
            </table>

            <div v-if="orders.links.length > 3" class="px-6 py-4 border-t border-gray-100 bg-gray-50/50">
                <div class="flex justify-end gap-1">
                    <Link v-for="(link, k) in orders.links" :key="k" :href="link.url || '#'" 
                        class="px-3 py-1.5 rounded-lg text-xs font-medium transition-all"
                        :class="link.active ? 'bg-rose-600 text-white shadow-sm' : 'text-gray-600 hover:bg-white'"
                        v-html="link.label" />
                </div>
            </div>
        </div>
    </div>

    <div v-if="showCancelModal" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
        <div class="bg-white rounded-2xl w-full max-w-md shadow-2xl p-6 animate-in zoom-in-95">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center"><AlertTriangle class="w-5 h-5 text-red-600" /></div>
                <div>
                    <h3 class="text-lg font-bold text-gray-900">Cancel Order #{{ selectedOrder?.order_number }}</h3>
                    <p class="text-xs text-gray-500">This action will restore stock quantity.</p>
                </div>
            </div>
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Cancellation Reason *</label>
                <textarea v-model="cancelReason" rows="3" 
                    class="w-full rounded-xl border-gray-300 focus:border-red-500 focus:ring-red-500 text-sm text-gray-900 placeholder-gray-400"
                    placeholder="e.g. Customer request, Out of stock, Fraud..."></textarea>
                <p class="text-xs text-gray-500 mt-1">This note will be visible in the order history.</p>
            </div>
            <div class="flex gap-3">
                <button @click="showCancelModal = false" class="flex-1 py-2.5 border border-gray-300 rounded-xl text-sm font-bold text-gray-700 hover:bg-gray-50 transition-colors">Close</button>
                <button @click="submitCancel" :disabled="isSubmitting || !cancelReason" class="flex-1 py-2.5 bg-red-600 text-white rounded-xl text-sm font-bold hover:bg-red-700 transition-colors shadow-lg shadow-red-200 flex items-center justify-center gap-2">
                    <Loader2 v-if="isSubmitting" class="w-4 h-4 animate-spin" />
                    {{ isSubmitting ? 'Processing...' : 'Confirm Cancel' }}
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>