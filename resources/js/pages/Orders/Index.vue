<script setup>
import { Head, router, Link } from '@inertiajs/vue3';
import { ref, watch, onMounted, onUnmounted, computed } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import { Truck, Package, Clock, CheckCircle, Star } from 'lucide-vue-next';

// Components
import CancelOrderModal from '@/components/CancelOrderModal.vue';
import ReturnOrderModal from '@/components/ReturnOrderModal.vue';
import ReviewModal from '@/components/RiviewModal.vue';
import TrackingModal from '@/components/TrackingModal.vue';

defineOptions({ layout: DashboardLayout });

const props = defineProps({
    orders: [Array, Object], 
    currentStatus: String
});

// --- STATE MANAGEMENT ---
const allOrders = ref(props.orders.data || props.orders);
const nextUrl = ref(props.orders.links?.next || null);
const isLoading = ref(false);
const observerTarget = ref(null);
let observer = null;

// --- MODAL STATE ---
const showCancelModal = ref(false);
const showReturnModal = ref(false);
const showTrackingModal = ref(false);
const showReviewModal = ref(false);

const selectedOrder = ref(null);
const trackingData = ref(null);
const isLoadingTrack = ref(false);
const reviewData = ref({ product: null, orderId: null });

// --- WATCHERS ---
watch(() => props.orders, (newVal) => {
    allOrders.value = newVal.data || newVal;
    nextUrl.value = newVal.links?.next || null;
}, { deep: true });

// --- INFINITE SCROLL ---
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
const formatDate = (date) => new Date(date).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });

// Status Colors
const getStatusClass = (status) => {
    const s = status.toLowerCase();
    
    if (s.includes('pickup') && s.includes('scheduled')) {
        return 'bg-pink-100 text-pink-800 border-pink-200';
    }

    const map = {
        pending: 'bg-amber-100 text-amber-800 border-amber-200',
        paid: 'bg-blue-100 text-blue-800 border-blue-200',
        processing: 'bg-blue-100 text-blue-800 border-blue-200',
        shipped: 'bg-purple-100 text-purple-800 border-purple-200',
        completed: 'bg-emerald-100 text-emerald-800 border-emerald-200',
        cancelled: 'bg-red-100 text-red-800 border-red-200',
        canceled: 'bg-red-100 text-red-800 border-red-200',
        cancellation_requested: 'bg-orange-100 text-orange-800 border-orange-200',
        return_requested: 'bg-rose-100 text-rose-800 border-rose-200',
        returned: 'bg-orange-100 text-orange-800 border-orange-200' // Added for returned status
    };
    return map[s] || 'bg-gray-100 text-gray-800';
};

// Status Labels (English)
const getStatusLabel = (status) => {
    const s = status.toLowerCase();
    if (s.includes('pickup') && s.includes('scheduled')) return 'Pickup Scheduled';
    return s.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
};

// --- ACTIONS ---
const filterStatus = (status) => {
    router.get(route('orders.index'), { status }, { preserveState: true });
};

const openCancel = (order) => {
    selectedOrder.value = order;
    showCancelModal.value = true;
};

const openReturn = (order) => {
    selectedOrder.value = order;
    showReturnModal.value = true;
};

// Track Package Logic (Calls API)
const openTracking = async (order) => {
    const trackingNo = order.shipping_tracking_number || order.resi_number;
    
    if (!trackingNo) return;

    showTrackingModal.value = true;
    isLoadingTrack.value = true;
    trackingData.value = null;
    
    try {
        // Calling the API route defined in web.php
        const response = await axios.get(`/api/orders/${order.id}/track`);
        trackingData.value = response.data.data;
    } catch (e) {
        console.error(e);
        trackingData.value = { error: "Failed to load tracking data." };
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
            onError: () => Swal.fire('Error', 'Payment failed or cancelled.', 'error')
        });
    }
};

// Confirm Order Received
const confirmReceived = (order) => {
    Swal.fire({
        title: 'Order Received?',
        text: "Are you sure you have received the order? This will mark the order as Completed.",
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, Received',
        confirmButtonColor: '#059669', 
        cancelButtonText: 'Cancel',
        cancelButtonColor: '#6b7280'
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(route('orders.complete', order.id), {}, {
                onSuccess: () => {
                    Swal.fire('Success!', 'Order marked as completed.', 'success');
                }
            });
        }
    });
};

const tabs = [
    { id: 'all', label: 'All' },
    { id: 'pending', label: 'Unpaid' },
    { id: 'processing', label: 'Processing' },
    { id: 'shipped', label: 'Shipped' },
    { id: 'completed', label: 'Completed' },
    { id: 'cancelled', label: 'Cancelled' },
    { id: 'return_requested', label: 'Return' }
];
</script>

<template>
    <Head title="My Orders" />

    <div class="min-h-screen bg-gray-50/50 py-8">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">My Orders</h1>
                    <p class="text-gray-500 mt-1">Manage and track your recent purchases</p>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-1 mb-6 overflow-x-auto no-scrollbar">
                <div class="flex space-x-1 min-w-max">
                    <button 
                        v-for="tab in tabs" 
                        :key="tab.id"
                        @click="filterStatus(tab.id)"
                        class="px-4 py-2.5 rounded-lg text-sm font-semibold transition-all duration-200"
                        :class="currentStatus === tab.id 
                            ? 'bg-rose-600 text-white shadow-md' 
                            : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'"
                    >
                        {{ tab.label }}
                    </button>
                </div>
            </div>

            <div v-if="allOrders.length === 0" class="bg-white rounded-2xl shadow-sm border border-gray-200 p-16 text-center">
                <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <Package class="w-10 h-10 text-gray-400" />
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-2">No orders found</h3>
                <p class="text-gray-500 mb-6">Looks like you haven't placed any orders yet.</p>
                <Link :href="route('products.index')" class="inline-flex items-center px-6 py-3 bg-rose-600 text-white font-bold rounded-xl hover:bg-rose-700 transition-colors shadow-lg shadow-rose-200">
                    Start Shopping
                </Link>
            </div>

            <div v-else class="space-y-6">
                <div v-for="order in allOrders" :key="order.id" class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow duration-300">
                    
                    <div class="p-5 border-b border-gray-100 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-gray-50/50">
                        <div class="flex gap-4">
                            <div class="p-3 bg-white rounded-xl border border-gray-200 shadow-sm">
                                <Package class="w-6 h-6 text-rose-600" />
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="font-mono font-bold text-gray-900 text-lg">#{{ order.order_number }}</span>
                                </div>
                                <div class="flex items-center gap-3 mt-1 text-sm text-gray-500">
                                    <span class="flex items-center gap-1"><Clock class="w-3.5 h-3.5" /> {{ formatDate(order.created_at) }}</span>
                                    <span>•</span>
                                    <span>{{ order.items.length }} Items</span>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-col items-end gap-2">
                            <span class="px-3 py-1 rounded-full text-xs font-bold border uppercase tracking-wide" :class="getStatusClass(order.order_status)">
                                {{ getStatusLabel(order.order_status) }}
                            </span>
                             <div v-if="order.shipping_tracking_number" class="flex items-center gap-1.5 text-xs font-medium text-blue-600 bg-blue-50 px-2 py-1 rounded-lg border border-blue-100">
                                <Truck class="w-3 h-3" />
                                <span class="font-mono">{{ order.shipping_tracking_number }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="p-6">
                        <div class="space-y-6">
                            <div v-for="item in order.items" :key="item.id" class="flex gap-4 group">
                                <div class="w-20 h-20 rounded-xl bg-gray-100 flex-shrink-0 overflow-hidden border border-gray-200">
                                    <img :src="item.product_variant?.product?.image_url || '/images/placeholder.png'" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h4 class="font-bold text-gray-900 text-base truncate">{{ item.product_name }}</h4>
                                    <p class="text-sm text-gray-500 mt-1">{{ item.variant_name }}</p>
                                    <div class="flex items-center gap-2 mt-2">
                                        <span class="text-sm font-medium text-gray-900">{{ formatCurrency(item.price) }}</span>
                                        <span class="text-xs text-gray-400">x</span>
                                        <span class="text-sm font-bold text-gray-900">{{ item.quantity }}</span>
                                    </div>
                                </div>
                                
                                <div class="text-right flex flex-col items-end justify-between">
                                    <p class="font-bold text-gray-900">{{ formatCurrency(item.subtotal) }}</p>
                                    
                                    <button 
                                        v-if="order.order_status === 'completed'"
                                        @click="openReview(item, order.id)"
                                        class="mt-2 px-3 py-1.5 bg-white border border-rose-200 text-rose-600 rounded-lg text-xs font-bold hover:bg-rose-600 hover:text-white hover:border-rose-600 transition-all shadow-sm flex items-center gap-1.5 group/btn"
                                    >
                                        <Star class="w-3.5 h-3.5 group-hover/btn:fill-current" />
                                        Review Product
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex flex-col sm:flex-row justify-between items-center gap-4">
                        <div class="text-sm text-gray-600">
                            <span class="block">Total Order: <span class="font-bold text-gray-900 text-lg ml-1">{{ formatCurrency(order.total_amount) }}</span></span>
                        </div>

                        <div class="flex flex-wrap gap-3">
                            <button v-if="order.order_status === 'pending' && order.snap_token" 
                                @click="payNow(order.snap_token)"
                                class="px-5 py-2.5 bg-rose-600 text-white rounded-xl hover:bg-rose-700 font-bold text-sm shadow-lg shadow-rose-200 transition-all flex items-center gap-2">
                                Pay Now
                            </button>

                            <button v-if="(order.order_status === 'shipped' || order.order_status === 'completed' || order.order_status.includes('pickup')) && order.shipping_tracking_number" 
                                @click="openTracking(order)"
                                class="px-5 py-2.5 bg-white border border-gray-300 text-gray-700 rounded-xl hover:bg-gray-50 font-bold text-sm transition-all flex items-center gap-2">
                                <Truck class="w-4 h-4" /> Track Package
                            </button>

                            <button 
                                v-if="order.order_status === 'shipped'"
                                @click="confirmReceived(order)"
                                class="px-5 py-2.5 bg-emerald-600 text-white rounded-xl hover:bg-emerald-700 font-bold text-sm shadow-lg shadow-emerald-200 transition-all flex items-center gap-2"
                            >
                                <CheckCircle class="w-4 h-4" /> Order Received
                            </button>

                            <button 
                                v-if="['pending', 'paid', 'processing'].includes(order.order_status)"
                                @click="openCancel(order)"
                                class="px-5 py-2.5 bg-white border border-gray-300 text-red-600 rounded-xl hover:bg-red-50 hover:border-red-200 font-bold text-sm transition-all"
                            >
                                Cancel Order
                            </button>
                            
                            <button 
                                v-if="order.order_status === 'completed' && !order.return_request"
                                @click="openReturn(order)"
                                class="px-5 py-2.5 bg-white border border-orange-300 text-orange-600 rounded-xl hover:bg-orange-50 font-bold text-sm transition-all"
                            >
                                Request Return
                            </button>

                            <div 
                                v-else-if="order.return_request?.status === 'rejected'"
                                class="px-5 py-2.5 bg-red-50 border border-red-200 text-red-600 rounded-xl font-bold text-sm cursor-help flex items-center gap-1"
                                :title="order.return_request.admin_note || 'Return request denied'"
                            >
                                <span class="text-xs">✕</span> Return Rejected
                            </div>

                            <div 
                                v-else-if="order.return_request?.status === 'pending'"
                                class="px-5 py-2.5 bg-amber-50 border border-amber-200 text-amber-600 rounded-xl font-bold text-sm cursor-wait flex items-center gap-1"
                            >
                                <span class="text-xs">⏳</span> Return Pending
                            </div>

                            <div 
                                v-else-if="order.return_request?.status === 'approved'"
                                class="px-5 py-2.5 bg-emerald-50 border border-emerald-200 text-emerald-600 rounded-xl font-bold text-sm flex items-center gap-1"
                            >
                                <span class="text-xs">✓</span> Return Approved
                            </div>

                        </div>
                    </div>
                    
                    <div v-if="order.order_status === 'cancelled' && order.notes" class="px-6 py-3 bg-red-50 border-t border-red-100 text-sm">
                        <span class="font-bold text-red-700">Cancellation Reason:</span>
                        <span class="text-red-600 ml-1">{{ order.notes }}</span>
                    </div>

                </div>

                <div class="mt-8 text-center pb-8">
                    <div v-if="hasMorePages" ref="observerTarget" class="h-10 flex items-center justify-center">
                         <div class="w-6 h-6 border-2 border-gray-200 border-t-rose-600 rounded-full animate-spin"></div>
                    </div>
                    <div v-if="!hasMorePages && !isLoading && allOrders.length > 0" class="text-gray-400 text-xs font-medium uppercase tracking-wider">
                        All orders loaded
                    </div>
                </div>
            </div>
        </div>

        <CancelOrderModal :show="showCancelModal" :order="selectedOrder" @close="showCancelModal = false" @success="showCancelModal = false" />
        <ReturnOrderModal :show="showReturnModal" :order="selectedOrder" @close="showReturnModal = false" @success="showReturnModal = false" />
        <ReviewModal :show="showReviewModal" :product="reviewData.product" :orderId="reviewData.orderId" @close="showReviewModal = false" />
        
        <TrackingModal 
            :show="showTrackingModal" 
            :loading="isLoadingTrack" 
            :data="trackingData" 
            @close="showTrackingModal = false" 
        />
    </div>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>