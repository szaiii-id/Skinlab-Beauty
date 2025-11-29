<script setup>
import { Head, router } from '@inertiajs/vue3';
import { ref, watch, onMounted, onUnmounted, computed } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';
import DashboardLayout from '@/layouts/DashboardLayout.vue';

// Components
import CancelOrderModal from '@/components/CancelOrderModal.vue';
import ReturnOrderModal from '@/components/ReturnOrderModal.vue';
import ReviewModal from '@/components/RiviewModal.vue';
import TrackingModal from '@/components/TrackingModal.vue'; // Reuse from Dashboard

defineOptions({ layout: DashboardLayout });

const props = defineProps({
    orders: [Array, Object], // Paginated object
    currentStatus: String
});

// --- INFINITE SCROLL LOGIC ---
const allOrders = ref(props.orders.data || props.orders);
const nextUrl = ref(props.orders.links?.next || null);
const isLoading = ref(false);
const observerTarget = ref(null);
let observer = null;

// Watch filter changes -> Reset list
watch(() => props.orders, (newVal) => {
    allOrders.value = newVal.data || newVal;
    nextUrl.value = newVal.links?.next || null;
}, { deep: true });

const loadMoreOrders = () => {
    if (!nextUrl.value || isLoading.value) return;
    isLoading.value = true;
    
    router.get(nextUrl.value, {}, {
        preserveState: true,
        preserveScroll: true,
        only: ['orders'],
        onSuccess: (page) => {
            const newOrders = page.props.orders;
            if (newOrders.data?.length) {
                allOrders.value.push(...newOrders.data);
                nextUrl.value = newOrders.links?.next;
            }
        },
        onFinish: () => { isLoading.value = false; }
    });
};

onMounted(() => {
    if (observerTarget.value) {
        observer = new IntersectionObserver((entries) => {
            if (entries[0].isIntersecting && nextUrl.value && !isLoading.value) {
                loadMoreOrders();
            }
        }, { rootMargin: '100px' });
        observer.observe(observerTarget.value);
    }
});

onUnmounted(() => {
    if (observer) observer.disconnect();
});

const hasMorePages = computed(() => !!nextUrl.value);

// --- HELPERS ---
const formatCurrency = (val) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val);
const formatDate = (date) => new Date(date).toLocaleDateString('en-US', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });

const getStatusClass = (status) => {
    const map = {
        pending: 'bg-amber-100 text-amber-800 border-amber-200',
        paid: 'bg-blue-100 text-blue-800 border-blue-200',
        shipped: 'bg-purple-100 text-purple-800 border-purple-200',
        completed: 'bg-green-100 text-green-800 border-green-200',
        canceled: 'bg-red-100 text-red-800 border-red-200',
        cancellation_requested: 'bg-orange-100 text-orange-800 border-orange-200',
        return_requested: 'bg-rose-100 text-rose-800 border-rose-200'
    };
    return map[status] || 'bg-gray-100 text-gray-800';
};

const getStatusLabel = (status) => {
    const map = {
        pending: 'Waiting for Payment',
        paid: 'Processing',
        shipped: 'Shipped',
        completed: 'Completed',
        canceled: 'Canceled',
        cancellation_requested: 'Cancellation Requested',
        return_requested: 'Return Requested'
    };
    return map[status] || status;
};

// --- MODAL STATE ---
const showCancelModal = ref(false);
const showReturnModal = ref(false);
const showTrackingModal = ref(false);
const showReviewModal = ref(false);

const selectedOrder = ref(null);
const trackingData = ref(null);
const isLoadingTrack = ref(false);
const reviewData = ref({ product: null, orderId: null });

// Actions
const filterStatus = (status) => {
    router.get('/orders', { status }, { preserveState: true });
};

const openCancel = (order) => {
    selectedOrder.value = order;
    showCancelModal.value = true;
};

const openReturn = (order) => {
    selectedOrder.value = order;
    showReturnModal.value = true;
};

const openTracking = async (order) => {
    if (!order.shipping_tracking_number) return;
    showTrackingModal.value = true;
    isLoadingTrack.value = true;
    trackingData.value = null;
    
    try {
        const response = await axios.get(`/api/orders/${order.id}/track`);
        trackingData.value = response.data.data;
    } catch (e) {
        trackingData.value = { error: "Failed to track package." };
    } finally {
        isLoadingTrack.value = false;
    }
};

const openReview = (item, orderId) => {
    reviewData.value = {
        product: {
            id: item.product_variant?.product_id || item.product_id,
            name: item.product_name,
            image: item.product_variant?.product?.image_url
        },
        orderId: orderId
    };
    showReviewModal.value = true;
};

const payNow = (snapToken) => {
    if (window.snap && snapToken) {
        window.snap.pay(snapToken, {
            onSuccess: () => router.reload(),
            onPending: () => router.reload(),
            onError: () => Swal.fire('Payment Failed', 'Transaction failed.', 'error')
        });
    }
};

// Tabs Data
const tabs = [
    { id: 'all', label: 'All' },
    { id: 'pending', label: 'Unpaid' },
    { id: 'paid', label: 'Processing' },
    { id: 'shipped', label: 'Shipped' },
    { id: 'completed', label: 'Completed' },
    { id: 'canceled', label: 'Canceled' },
    { id: 'return_requested', label: 'Return' }
];
</script>

<template>
    <Head title="My Orders" />

    <div class="min-h-screen bg-gradient-to-br from-rose-50 to-pink-50 py-8">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <div class="mb-8">
                <h1 class="text-3xl font-light text-gray-900 mb-2">My Orders</h1>
                <p class="text-gray-600">Track your shopping history</p>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-2 mb-6 overflow-x-auto no-scrollbar">
                <div class="flex space-x-2 min-w-max">
                    <button 
                        v-for="tab in tabs" 
                        :key="tab.id"
                        @click="filterStatus(tab.id)"
                        class="px-5 py-2 rounded-xl text-sm font-bold transition-all duration-200"
                        :class="currentStatus === tab.id 
                            ? 'bg-rose-600 text-white shadow-md shadow-rose-200' 
                            : 'text-gray-600 hover:bg-rose-50'"
                    >
                        {{ tab.label }}
                    </button>
                </div>
            </div>

            <div v-if="allOrders.length === 0" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-16 text-center">
                <div class="text-6xl mb-4 grayscale opacity-50">📦</div>
                <h3 class="text-xl font-bold text-gray-900 mb-2">No orders found</h3>
                <Link href="/catalog" class="inline-flex items-center px-6 py-3 bg-rose-600 text-white font-bold rounded-lg hover:bg-rose-700 transition-colors mt-4 shadow-lg shadow-rose-200">
                    Start Shopping
                </Link>
            </div>

            <div v-else class="space-y-6">
                <div v-for="order in allOrders" :key="order.id" class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition-shadow">
                    
                    <div class="p-5 border-b border-gray-100 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-gray-50/50">
                        <div>
                            <div class="flex items-center gap-3 mb-1">
                                <span class="font-mono font-bold text-gray-900 text-lg">#{{ order.order_number }}</span>
                                <span class="text-xs text-gray-500 font-medium">{{ formatDate(order.created_at) }}</span>
                            </div>
                            <div class="flex items-center gap-2 mt-1">
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold border uppercase tracking-wide" :class="getStatusClass(order.order_status)">
                                    {{ getStatusLabel(order.order_status) }}
                                </span>
                                <span v-if="order.shipping_tracking_number" class="text-[10px] font-mono text-gray-600 bg-gray-200 px-2 py-0.5 rounded border border-gray-300">
                                    AWB: {{ order.shipping_tracking_number }}
                                </span>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <button v-if="order.order_status === 'pending' && order.snap_token" 
                                @click="payNow(order.snap_token)"
                                class="px-4 py-2 bg-rose-600 text-white rounded-lg hover:bg-rose-700 text-xs font-bold shadow-lg shadow-rose-200 transition-all">
                                Pay Now
                            </button>

                            <button v-if="['shipped', 'completed'].includes(order.order_status) && order.shipping_tracking_number" 
                                @click="openTracking(order)"
                                class="px-4 py-2 bg-white border border-rose-600 text-rose-600 rounded-lg hover:bg-rose-50 text-xs font-bold flex items-center gap-1 transition-colors">
                                🚚 Track
                            </button>

                            <button 
                                v-if="['pending', 'paid'].includes(order.order_status)"
                                @click="openCancel(order)"
                                class="px-4 py-2 bg-white border border-gray-300 text-gray-600 rounded-lg hover:bg-red-50 hover:text-red-600 hover:border-red-200 text-xs font-bold transition-colors"
                            >
                                Cancel Order
                            </button>
                            
                            <button 
                                v-if="order.order_status === 'completed'"
                                @click="openReturn(order)"
                                class="px-4 py-2 bg-white border border-orange-300 text-orange-600 rounded-lg hover:bg-orange-50 hover:text-orange-700 text-xs font-bold transition-colors"
                            >
                                Request Return
                            </button>
                        </div>
                    </div>

                    <div class="p-6">
                        <div class="space-y-4">
                            <div v-for="item in order.items" :key="item.id" class="flex items-center gap-4">
                                <div class="w-16 h-16 rounded-lg bg-gray-100 flex-shrink-0 overflow-hidden border border-gray-200">
                                    <img :src="item.product_variant?.product?.image_url || '/images/default-product.png'" class="w-full h-full object-cover" />
                                </div>
                                <div class="flex-1">
                                    <h4 class="font-bold text-gray-900 text-sm line-clamp-1">{{ item.product_name }}</h4>
                                    <p class="text-xs text-gray-500 mt-0.5">Qty: {{ item.quantity }}</p>
                                </div>
                                <div class="text-right flex flex-col items-end gap-2">
                                    <p class="font-bold text-gray-900 text-sm">{{ formatCurrency(item.price) }}</p>
                                    <button 
                                        v-if="order.order_status === 'completed'"
                                        @click="openReview(item, order.id)"
                                        class="text-[10px] font-bold text-rose-600 hover:text-rose-700 border border-rose-200 bg-rose-50 px-3 py-1 rounded-full transition-colors hover:bg-rose-100 flex items-center gap-1"
                                    >
                                        ⭐ Review
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-between items-center">
                        <div class="text-xs text-gray-500 font-medium">
                            Courier: <span class="font-bold text-gray-700 uppercase">{{ order.shipping_courier || 'Reguler' }}</span>
                        </div>
                        <div class="text-right">
                            <span class="text-xs text-gray-500 mr-2">Total Amount</span>
                            <span class="text-lg font-extrabold text-rose-600">{{ formatCurrency(order.total_amount) }}</span>
                        </div>
                    </div>
                </div>

                <div class="mt-8 text-center pb-8">
                    <div v-if="hasMorePages" ref="observerTarget" class="h-10 flex items-center justify-center">
                         <div class="w-6 h-6 border-2 border-rose-200 border-t-rose-600 rounded-full animate-spin"></div>
                    </div>
                    <div v-if="isLoading" class="flex justify-center items-center space-x-3 text-rose-600 py-4">
                        <span class="text-sm font-medium">Loading more orders...</span>
                    </div>
                    <div v-if="!hasMorePages && !isLoading && allOrders.length > 0" class="text-gray-400 text-xs font-medium uppercase tracking-wider">
                        All orders loaded
                    </div>
                </div>
            </div>
        </div>

        <CancelOrderModal 
            :show="showCancelModal" 
            :order="selectedOrder" 
            @close="showCancelModal = false"
            @success="showCancelModal = false"
        />

        <ReturnOrderModal 
            :show="showReturnModal" 
            :order="selectedOrder" 
            @close="showReturnModal = false"
            @success="showReturnModal = false"
        />

        <TrackingModal 
            :show="showTrackingModal" 
            :loading="isLoadingTrack" 
            :data="trackingData" 
            @close="showTrackingModal = false" 
        />

        <ReviewModal 
            :show="showReviewModal" 
            :product="reviewData.product" 
            :orderId="reviewData.orderId" 
            @close="showReviewModal = false" 
        />

    </div>
</template>

<style scoped>
/* Hide Scrollbar for Tabs */
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>