<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { 
    ArrowLeft, Printer, Truck, User, MapPin, Package, 
    CreditCard, CheckCircle, Clock, XCircle, CalendarClock, Loader2, AlertTriangle, Calendar,
    Home
} from 'lucide-vue-next';
import Swal from 'sweetalert2';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    order: Object,
});

// State Loading
const isPrinting = ref(false);
const isBooking = ref(false);

// Form Update Status Manual
const form = useForm({
    order_status: props.order.order_status,
    resi_number: props.order.resi_number || '',
});

// State Schedule Pickup Modal
const showScheduleModal = ref(false);
const scheduleForm = useForm({
    order_ids: [props.order.id],
    pickup_date: new Date().toISOString().split('T')[0],
    pickup_time: '16:00',
    pickup_vehicle: 'Motor'
});

// Helper Classes - TEMA PINK ROSE IMPROVED CONTRAST
const getStatusClass = (status) => {
    const normalized = status.toLowerCase().replace(/_/g, ' ');
    
    if (normalized.includes('pickup') && normalized.includes('scheduled')) {
        return 'bg-pink-100 text-pink-800 border-pink-300 shadow-sm';
    }
    
    switch(status.toLowerCase()) {
        case 'pending': return 'bg-amber-100 text-amber-800 border-amber-300 shadow-sm';
        case 'processing': return 'bg-blue-100 text-blue-800 border-blue-300 shadow-sm';
        case 'schedule_pickup': 
            return 'bg-pink-100 text-pink-800 border-pink-300 shadow-sm';
        case 'shipped': return 'bg-violet-100 text-violet-800 border-violet-300 shadow-sm';
        case 'completed': return 'bg-emerald-100 text-emerald-800 border-emerald-300 shadow-sm';
        case 'cancelled': return 'bg-rose-100 text-rose-800 border-rose-300 shadow-sm';
        default: return 'bg-gray-100 text-gray-800 border-gray-300 shadow-sm';
    }
};

// Format helpers
const formatCurrency = (val) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val);
const formatDate = (date) => new Date(date).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });

// Computed: Normalize status untuk kondisi
const normalizedStatus = computed(() => {
    const status = props.order.order_status.toLowerCase().replace(/_/g, ' ');
    
    // Mapping semua variasi pickup scheduled
    if (status.includes('pickup') && status.includes('scheduled')) {
        return 'pickup_scheduled';
    }
    
    return status;
});

// Computed: Status display text
const statusDisplay = computed(() => {
    const status = props.order.order_status;
    if (status === 'PICKUP_SCHEDULED') {
        return 'Pickup Scheduled';
    }
    return status.replace(/_/g, ' ');
});

// Computed: Check permissions
const canPrintLabel = computed(() => {
    return props.order.shipping_tracking_number && 
           (normalizedStatus.value === 'pickup_scheduled' || 
            normalizedStatus.value === 'shipped' || 
            normalizedStatus.value === 'completed');
});

const canMarkAsShipped = computed(() => {
    return normalizedStatus.value === 'pickup_scheduled';
});

const canMarkAsCompleted = computed(() => {
    return props.order.order_status === 'shipped';
});

const isPickupScheduled = computed(() => {
    return normalizedStatus.value === 'pickup_scheduled';
});

// --- ACTIONS ---

// 1. Print Label
const printLabel = () => {
    if (!canPrintLabel.value) {
        Swal.fire({
            icon: 'warning',
            title: 'Cannot Print Label',
            html: `Order must be in <strong>"Pickup Scheduled"</strong> status first.<br>
                  Please schedule pickup before printing label.`,
            confirmButtonText: 'OK',
            confirmButtonColor: '#db2777',
            background: '#fdf2f8',
            color: '#831843'
        });
        return;
    }
    
    isPrinting.value = true;
    const labelUrl = route('admin.orders.label', props.order.id);
    window.open(labelUrl, '_blank');
    
    setTimeout(() => {
        isPrinting.value = false;
    }, 1000);
};

// 2. Request Booking (Get Resi)
const requestBooking = () => {
    Swal.fire({
        title: 'Get Resi Number?',
        text: `Requesting AWB from ${props.order.shipping_courier || 'Courier'}...`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, Get Resi',
        confirmButtonColor: '#db2777',
        cancelButtonColor: '#6b7280',
        background: '#fdf2f8',
        color: '#831843'
    }).then((result) => {
        if (result.isConfirmed) {
            isBooking.value = true;
            router.post(route('admin.orders.book', props.order.id), {}, {
                onSuccess: () => Swal.fire({
                    title: 'Success!',
                    text: 'Resi Generated!',
                    icon: 'success',
                    confirmButtonColor: '#db2777',
                    background: '#fdf2f8',
                    color: '#831843'
                }),
                onError: (err) => Swal.fire({
                    title: 'Failed',
                    text: err.message || 'Booking failed.',
                    icon: 'error',
                    confirmButtonColor: '#db2777',
                    background: '#fdf2f8',
                    color: '#831843'
                }),
                onFinish: () => isBooking.value = false
            });
        }
    });
};

// 3. Submit Schedule Pickup
const submitSchedule = () => {
    if (!props.order.shipping_tracking_number) {
        Swal.fire({
            icon: 'error',
            title: 'No Tracking Number',
            text: 'Please get resi number first before scheduling pickup.',
            confirmButtonColor: '#db2777',
            background: '#fdf2f8',
            color: '#831843'
        });
        return;
    }
    
    scheduleForm.post(route('admin.orders.schedule-pickup'), {
        onSuccess: () => {
            showScheduleModal.value = false;
            Swal.fire({
                title: 'Scheduled!',
                text: 'Courier pickup has been arranged.',
                icon: 'success',
                confirmButtonColor: '#db2777',
                background: '#fdf2f8',
                color: '#831843'
            });
        },
        onError: (err) => Swal.fire({
            title: 'Error',
            text: err.message || 'Failed to schedule pickup.',
            icon: 'error',
            confirmButtonColor: '#db2777',
            background: '#fdf2f8',
            color: '#831843'
        })
    });
};

// 4. Mark as Shipped - HANYA DARI schedule_pickup
const markAsShipped = () => {
    if (!canMarkAsShipped.value) {
        Swal.fire({
            icon: 'warning',
            title: 'Cannot Mark as Shipped',
            html: `Order must be in <strong>"Pickup Scheduled"</strong> status first.`,
            confirmButtonText: 'OK',
            confirmButtonColor: '#db2777',
            background: '#fdf2f8',
            color: '#831843'
        });
        return;
    }
    
    Swal.fire({
        title: 'Mark as Shipped?',
        text: 'This will confirm the courier has picked up the package.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, Mark as Shipped',
        confirmButtonColor: '#7c3aed',
        cancelButtonColor: '#6b7280',
        background: '#fdf2f8',
        color: '#831843'
    }).then((result) => {
        if (result.isConfirmed) {
            router.put(route('admin.orders.update', props.order.id), {
                order_status: 'shipped',
                resi_number: props.order.shipping_tracking_number || props.order.resi_number
            }, {
                onSuccess: () => {
                    Swal.fire({
                        title: 'Success!',
                        text: 'Order marked as Shipped!',
                        icon: 'success',
                        confirmButtonColor: '#db2777',
                        background: '#fdf2f8',
                        color: '#831843'
                    });
                    router.reload({ only: ['order'] });
                },
                onError: (errors) => {
                    Swal.fire({
                        title: 'Error',
                        text: errors.order_status || 'Failed to update.',
                        icon: 'error',
                        confirmButtonColor: '#db2777',
                        background: '#fdf2f8',
                        color: '#831843'
                    });
                }
            });
        }
    });
};

// 5. Complete Order
const markAsCompleted = () => {
    if (!canMarkAsCompleted.value) {
        Swal.fire({
            icon: 'warning',
            title: 'Cannot Complete Order',
            html: `Order must be <strong>"Shipped"</strong> before completing.`,
            confirmButtonText: 'OK',
            confirmButtonColor: '#db2777',
            background: '#fdf2f8',
            color: '#831843'
        });
        return;
    }
    
    Swal.fire({ 
        title: 'Complete Order?', 
        text: 'This will confirm the customer has received the goods.',
        icon: 'question', 
        showCancelButton: true, 
        confirmButtonText: 'Yes, Complete',
        confirmButtonColor: '#059669',
        cancelButtonColor: '#6b7280',
        background: '#fdf2f8',
        color: '#831843'
    }).then((res) => {
        if (res.isConfirmed) {
            form.order_status = 'completed';
            form.put(route('admin.orders.update', props.order.id), {
                onSuccess: () => Swal.fire({
                    title: 'Success!',
                    text: 'Order completed!',
                    icon: 'success',
                    confirmButtonColor: '#db2777',
                    background: '#fdf2f8',
                    color: '#831843'
                }),
                onError: (errors) => {
                    Swal.fire({
                        title: 'Error',
                        text: errors.order_status || 'Failed to update.',
                        icon: 'error',
                        confirmButtonColor: '#db2777',
                        background: '#fdf2f8',
                        color: '#831843'
                    });
                }
            });
        }
    });
};
</script>

<template>
    <Head :title="`Order #${order.order_number}`" />

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- HEADER SECTION - IMPROVED READABILITY -->
        <div class="mb-8">
            <!-- Navigation Breadcrumb -->
            <div class="flex items-center gap-2 text-sm mb-4 text-gray-600">
                <Link 
                    :href="route('admin.dashboard')" 
                    class="text-pink-700 hover:text-pink-800 transition-colors flex items-center gap-1 font-medium"
                >
                    <Home class="w-4 h-4" />
                    Dashboard
                </Link>
                <span class="text-gray-400">/</span>
                <Link 
                    :href="route('admin.orders.index')" 
                    class="text-pink-700 hover:text-pink-800 transition-colors flex items-center gap-1 font-medium"
                >
                    Orders
                </Link>
                <span class="text-gray-400">/</span>
                <span class="text-gray-700 font-semibold">Order #{{ order.order_number }}</span>
            </div>

            <div class="bg-gradient-to-r from-pink-50 to-rose-50 rounded-2xl border border-pink-200 p-6 shadow-md">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="flex-1">
                        <div class="flex items-center gap-4">
                            <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-pink-600 to-rose-600 flex items-center justify-center shadow-lg">
                                <Package class="w-7 h-7 text-white" />
                            </div>
                            <div>
                                <h1 class="text-2xl md:text-3xl font-bold text-gray-900">Order #{{ order.order_number }}</h1>
                                <div class="flex flex-col sm:flex-row sm:items-center gap-2 mt-2">
                                    <span class="px-4 py-1.5 rounded-full text-sm font-bold border-2 uppercase tracking-wide shadow-sm" :class="getStatusClass(order.order_status)">
                                        <span v-if="isPickupScheduled" class="flex items-center gap-1.5">
                                            <Calendar class="w-3.5 h-3.5" /> {{ statusDisplay }}
                                        </span>
                                        <span v-else>{{ statusDisplay }}</span>
                                    </span>
                                    <span class="text-sm text-gray-600 font-medium">• Placed on {{ formatDate(order.created_at) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex flex-wrap gap-3">
                        <!-- Print Label Button - HANYA SATU -->
                        <button 
                            v-if="canPrintLabel"
                            @click="printLabel" 
                            :disabled="isPrinting"
                            class="px-5 py-2.5 bg-gradient-to-r from-pink-600 to-rose-600 text-white font-bold rounded-xl shadow-md hover:shadow-lg hover:from-pink-700 hover:to-rose-700 transition-all duration-300 flex items-center gap-2 disabled:opacity-60 disabled:cursor-not-allowed"
                            :title="'AWB: ' + order.shipping_tracking_number"
                        >
                            <Loader2 v-if="isPrinting" class="w-4 h-4 animate-spin" />
                            <Printer v-else class="w-4 h-4" /> 
                            {{ isPrinting ? 'Opening...' : 'Print Label' }}
                        </button>
                        
                        <!-- Back to Orders Button -->
                        <Link 
                            :href="route('admin.orders.index')" 
                            class="px-5 py-2.5 bg-white border-2 border-pink-300 text-pink-700 font-bold rounded-xl shadow-sm hover:bg-pink-50 hover:border-pink-400 transition-all duration-300 flex items-center gap-2"
                        >
                            Back to Orders
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <!-- MAIN CONTENT -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                <!-- Order Items -->
                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-pink-50 to-transparent flex justify-between items-center">
                        <h3 class="font-bold text-gray-900 text-lg flex items-center gap-3">
                            <div class="w-9 h-9 rounded-lg bg-pink-100 flex items-center justify-center border border-pink-200">
                                <Package class="w-5 h-5 text-pink-600" />
                            </div>
                            Order Items
                        </h3>
                        <span class="px-3 py-1 bg-pink-100 text-pink-700 rounded-full text-xs font-bold border border-pink-200">{{ order.items.length }} Items</span>
                    </div>
                    <div class="divide-y divide-gray-100">
                        <div v-for="item in order.items" :key="item.id" class="p-5 flex gap-4 hover:bg-pink-50/40 transition-colors">
                            <div class="w-18 h-18 bg-pink-100 rounded-lg overflow-hidden shrink-0 border border-pink-200">
                                <img :src="item.product_variant?.product?.image_url || '/images/placeholder.png'" 
                                     :alt="item.product_name"
                                     class="w-full h-full object-cover">
                            </div>
                            <div class="flex-1">
                                <h4 class="font-bold text-gray-900 text-base">{{ item.product_name }}</h4>
                                <p class="text-sm text-gray-600 mt-0.5">{{ item.variant_name }}</p>
                                <div class="mt-2 flex items-center gap-2 text-sm">
                                    <span class="font-medium text-gray-700">{{ formatCurrency(item.price) }}</span>
                                    <span class="text-gray-400">×</span>
                                    <span class="px-2.5 py-0.5 bg-pink-100 text-pink-700 rounded-md font-bold text-sm border border-pink-200">{{ item.quantity }}</span>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="font-bold text-gray-900 text-lg">{{ formatCurrency(item.subtotal) }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment Details -->
                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6">
                    <h3 class="font-bold text-gray-900 text-lg mb-5 flex items-center gap-3">
                        <div class="w-9 h-9 rounded-lg bg-emerald-100 flex items-center justify-center border border-emerald-200">
                            <CreditCard class="w-5 h-5 text-emerald-600" />
                        </div>
                        Payment Details
                    </h3>
                    <div class="space-y-4 text-base">
                        <div class="flex justify-between text-gray-700">
                            <span class="font-medium">Subtotal</span>
                            <span class="font-semibold">{{ formatCurrency(order.subtotal) }}</span>
                        </div>
                        <div class="flex justify-between text-gray-700">
                            <span class="font-medium">Shipping Cost</span>
                            <span class="font-semibold text-blue-700">+ {{ formatCurrency(order.shipping_cost) }}</span>
                        </div>
                        <div v-if="order.discount_amount > 0" class="flex justify-between text-rose-700 font-semibold">
                            <span>Discount ({{ order.voucher_code }})</span>
                            <span>- {{ formatCurrency(order.discount_amount) }}</span>
                        </div>
                        <div class="border-t-2 border-dashed border-pink-300 pt-4 flex justify-between items-center">
                            <span class="font-extrabold text-gray-900 text-lg">Grand Total</span>
                            <span class="font-extrabold text-pink-700 text-xl">{{ formatCurrency(order.total_amount) }}</span>
                        </div>
                    </div>
                    <div class="mt-7 flex items-center justify-between p-4 bg-gradient-to-r from-pink-50 to-rose-50 rounded-xl border border-pink-200">
                        <div>
                            <p class="text-xs text-pink-700 uppercase font-bold tracking-wider">Payment Method</p>
                            <p class="font-bold text-gray-900 text-base capitalize mt-1">{{ order.payment_method.replace('_', ' ') }}</p>
                        </div>
                        <div class="text-right">
                            <span v-if="order.payment_status === 'paid'" class="px-4 py-1.5 bg-emerald-100 text-emerald-800 rounded-full text-sm font-bold flex items-center gap-1.5 border border-emerald-300 shadow-sm">
                                <CheckCircle class="w-4 h-4" /> PAID
                            </span>
                            <span v-else-if="order.payment_status === 'unpaid'" class="px-4 py-1.5 bg-amber-100 text-amber-800 rounded-full text-sm font-bold flex items-center gap-1.5 border border-amber-300 shadow-sm">
                                <Clock class="w-4 h-4" /> UNPAID
                            </span>
                            <span v-else class="px-4 py-1.5 bg-rose-100 text-rose-800 rounded-full text-sm font-bold flex items-center gap-1.5 border border-rose-300 shadow-sm">
                                <XCircle class="w-4 h-4" /> {{ order.payment_status.toUpperCase() }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SIDEBAR -->
            <div class="space-y-6">
                <!-- Shipping Actions -->
                <div class="bg-white rounded-2xl border border-pink-200 shadow-sm p-6 relative overflow-hidden">
                    <div class="absolute -top-12 -right-12 w-40 h-40 bg-gradient-to-br from-pink-100 to-rose-100 rounded-full blur-2xl opacity-50"></div>
                    <h3 class="font-bold text-gray-900 text-lg mb-5 relative z-10">Shipping Actions</h3>
                    
                    <div class="space-y-5 relative z-10">
                        <!-- STATUS: processing - belum ada resi -->
                        <div v-if="!order.shipping_tracking_number && normalizedStatus === 'processing'">
                            <button 
                                @click="requestBooking" 
                                :disabled="isBooking"
                                class="w-full py-3.5 bg-gradient-to-r from-pink-600 to-rose-600 text-white font-bold rounded-xl shadow-md hover:shadow-lg hover:from-pink-700 hover:to-rose-700 transition-all duration-300 flex items-center justify-center gap-2.5 text-base"
                            >
                                <Loader2 v-if="isBooking" class="w-5 h-5 animate-spin" />
                                <Truck v-else class="w-5 h-5" /> 
                                {{ isBooking ? 'Booking to Courier...' : 'Get Resi (Booking)' }}
                            </button>
                        </div>

                        <!-- STATUS: processing - sudah ada resi, bisa schedule pickup -->
                        <div v-else-if="normalizedStatus === 'processing' && order.shipping_tracking_number">
                            <div class="bg-gradient-to-br from-blue-50 to-pink-50 p-4 rounded-xl border border-blue-200 mb-4">
                                <p class="text-xs text-blue-700 uppercase font-bold tracking-wider mb-2">Ready for Pickup</p>
                                <div class="flex items-center gap-2.5 mb-2">
                                    <Truck class="w-5 h-5 text-blue-600" />
                                    <p class="text-lg font-bold text-blue-900">AWB: {{ order.shipping_tracking_number }}</p>
                                </div>
                                <p class="text-sm text-blue-700 mb-3">{{ order.shipping_courier }}</p>
                                
                                <div class="pt-4 border-t border-blue-200">
                                    <p class="text-sm text-gray-700 mb-3">Schedule courier pickup:</p>
                                    <button 
                                        @click="showScheduleModal = true"
                                        class="w-full py-2.5 bg-gradient-to-r from-pink-600 to-rose-600 text-white font-bold rounded-lg hover:from-pink-700 hover:to-rose-700 transition-all duration-300 flex items-center justify-center gap-2"
                                    >
                                        <CalendarClock class="w-4.5 h-4.5" /> 
                                        Schedule Pickup
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- STATUS: pickup_scheduled - sudah dijadwalkan -->
                        <div v-else-if="isPickupScheduled">
                            <div class="bg-gradient-to-br from-pink-50 to-violet-50 p-5 rounded-xl border border-pink-200 mb-5">
                                <p class="text-xs text-pink-700 uppercase font-bold tracking-wider mb-2">Scheduled for Pickup</p>
                                <div class="flex items-center gap-2.5 mb-3">
                                    <Calendar class="w-5 h-5 text-pink-600" />
                                    <p class="text-lg font-bold text-pink-900">Pickup Scheduled</p>
                                </div>
                                
                                <div class="mb-4">
                                    <p class="text-xs text-pink-700 uppercase font-bold tracking-wider mb-1.5">Tracking Number (AWB)</p>
                                    <div class="flex items-center gap-2.5 mb-1">
                                        <Truck class="w-5 h-5 text-blue-600" />
                                        <p class="text-xl font-mono font-bold text-blue-900">{{ order.shipping_tracking_number }}</p>
                                    </div>
                                    <p class="text-sm text-blue-700">{{ order.shipping_courier }}</p>
                                </div>
                                
                                <!-- ACTION: Mark as Shipped -->
                                <div class="pt-4 border-t border-pink-200">
                                    <p class="text-sm text-gray-700 mb-3">Has courier picked up the package?</p>
                                    <button 
                                        @click="markAsShipped"
                                        class="w-full py-3 bg-gradient-to-r from-violet-600 to-purple-600 text-white font-bold rounded-lg hover:from-violet-700 hover:to-purple-700 transition-all duration-300 text-base"
                                    >
                                        ✓ Mark as Shipped
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Reschedule Pickup Button -->
                            <button 
                                @click="showScheduleModal = true"
                                class="w-full py-3.5 bg-gradient-to-r from-pink-600 to-rose-600 text-white font-bold rounded-xl shadow-md hover:shadow-lg hover:from-pink-700 hover:to-rose-700 transition-all duration-300 flex items-center justify-center gap-2.5 text-base"
                            >
                                <CalendarClock class="w-5 h-5" /> 
                                Reschedule Pickup
                            </button>
                        </div>

                        <!-- STATUS: shipped - sudah diambil kurir -->
                        <div v-else-if="normalizedStatus === 'shipped'">
                            <div class="bg-gradient-to-br from-violet-50 to-purple-50 p-5 rounded-xl border border-violet-200 mb-5">
                                <p class="text-xs text-violet-700 uppercase font-bold tracking-wider mb-2">Shipped</p>
                                <div class="flex items-center gap-2.5 mb-2">
                                    <Truck class="w-5 h-5 text-violet-600" />
                                    <p class="text-xl font-mono font-bold text-violet-900">{{ order.shipping_tracking_number }}</p>
                                </div>
                                <p class="text-sm text-violet-700 mb-3">{{ order.shipping_courier }}</p>
                                
                                <div class="pt-4 border-t border-violet-200">
                                    <button 
                                        @click="markAsCompleted"
                                        class="w-full py-3.5 bg-gradient-to-r from-emerald-600 to-green-600 text-white font-bold rounded-xl hover:shadow-lg hover:from-emerald-700 hover:to-green-700 transition-all duration-300 text-base"
                                    >
                                        ✓ Mark as Completed
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- STATUS: completed -->
                        <div v-else-if="normalizedStatus === 'completed'">
                            <div class="bg-gradient-to-br from-emerald-50 to-green-50 p-5 rounded-xl border border-emerald-200 mb-4">
                                <p class="text-xs text-emerald-700 uppercase font-bold tracking-wider mb-2">Completed</p>
                                <div class="flex items-center gap-2.5 mb-3">
                                    <CheckCircle class="w-6 h-6 text-emerald-600" />
                                    <p class="text-lg font-bold text-emerald-900">Order Delivered</p>
                                </div>
                                <div class="mb-2">
                                    <p class="text-sm font-medium text-emerald-800">Tracking: {{ order.shipping_tracking_number }}</p>
                                    <p class="text-sm text-emerald-700">{{ order.shipping_courier }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- STATUS: cancelled -->
                        <div v-else-if="normalizedStatus === 'cancelled'" class="text-center py-5 bg-rose-50 rounded-xl border-2 border-dashed border-rose-300">
                            <XCircle class="w-10 h-10 text-rose-400 mx-auto mb-2.5" />
                            <p class="font-bold text-rose-800 text-base">Order Cancelled</p>
                        </div>

                        <!-- STATUS: pending -->
                        <div v-else-if="normalizedStatus === 'pending'" class="text-center py-5 bg-amber-50 rounded-xl border-2 border-dashed border-amber-300">
                            <Clock class="w-10 h-10 text-amber-500 mx-auto mb-2.5" />
                            <p class="font-bold text-amber-800 text-base">Pending Payment</p>
                            <p class="text-sm text-amber-700">Waiting for customer payment</p>
                        </div>

                        <!-- STATUS: lainnya (fallback) -->
                        <div v-else class="text-center py-5 bg-gray-50 rounded-xl border-2 border-dashed border-gray-300">
                            <AlertTriangle class="w-10 h-10 text-gray-400 mx-auto mb-2.5" />
                            <p class="font-bold text-gray-700 text-base">Status: {{ order.order_status }}</p>
                            <p class="text-sm text-gray-600">Manual action may be required</p>
                        </div>
                    </div>
                </div>

                <!-- Customer Info -->
                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6">
                    <h3 class="font-bold text-gray-900 text-lg mb-5 flex items-center gap-3">
                        <div class="w-9 h-9 rounded-lg bg-pink-100 flex items-center justify-center border border-pink-200">
                            <User class="w-5 h-5 text-pink-600" />
                        </div>
                        Customer
                    </h3>
                    <div class="flex items-center gap-3.5 mb-4">
                        <div class="w-12 h-12 rounded-full bg-gradient-to-br from-pink-500 to-rose-600 flex items-center justify-center font-bold text-white shadow">
                            {{ order.user?.name.charAt(0) }}
                        </div>
                        <div>
                            <p class="font-bold text-gray-900 text-base">{{ order.user?.name }}</p>
                            <p class="text-sm text-gray-600 mt-0.5">{{ order.user?.email }}</p>
                        </div>
                    </div>
                    <div v-if="order.user?.phone_number" class="text-sm text-pink-700 font-semibold flex items-center gap-1.5">
                        <span>📱</span> {{ order.user.phone_number }}
                    </div>
                </div>

                <!-- Shipping Address -->
                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6">
                    <h3 class="font-bold text-gray-900 text-lg mb-5 flex items-center gap-3">
                        <div class="w-9 h-9 rounded-lg bg-pink-100 flex items-center justify-center border border-pink-200">
                            <MapPin class="w-5 h-5 text-pink-600" />
                        </div>
                        Shipping Address
                    </h3>
                    <div class="text-sm text-gray-700 space-y-3">
                        <p class="font-bold text-gray-900 text-base">{{ order.shipping_address?.receiver_name }}</p>
                        <p class="text-pink-700 font-medium flex items-center gap-1.5">
                            <span>📞</span> {{ order.shipping_address?.phone_number }}
                        </p>
                        <div class="mt-3 p-4 bg-pink-50 rounded-lg border border-pink-200">
                            <p class="leading-relaxed text-gray-800">{{ order.shipping_address?.full_address }}</p>
                            <p class="mt-2 text-gray-700">{{ order.shipping_address?.district?.name }}, {{ order.shipping_address?.city?.name }}</p>
                            <p class="text-gray-700">{{ order.shipping_address?.province?.name }} - {{ order.shipping_address?.postal_code }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Schedule Pickup Modal -->
    <div v-if="showScheduleModal" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/70 backdrop-blur-sm p-4">
        <div class="bg-white rounded-2xl w-full max-w-md shadow-2xl p-6 animate-in zoom-in-95 border border-pink-200">
            <div class="flex items-center justify-between mb-7">
                <div class="flex items-center gap-3.5">
                    <div class="w-12 h-12 rounded-full bg-gradient-to-br from-pink-100 to-rose-100 flex items-center justify-center border border-pink-200">
                        <CalendarClock class="w-6 h-6 text-pink-600" />
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-gray-900">Schedule Pickup</h3>
                        <p class="text-sm text-gray-600">Call courier to pick up package.</p>
                    </div>
                </div>
                <button @click="showScheduleModal = false" class="text-gray-500 hover:text-pink-700 transition-colors text-xl">✕</button>
            </div>
            
            <div class="space-y-5">
                <div>
                    <label class="block text-sm font-semibold text-gray-800 mb-2">Pickup Date</label>
                    <input 
                        v-model="scheduleForm.pickup_date" 
                        type="date" 
                        class="w-full rounded-lg border-2 border-gray-300 bg-white text-gray-900 focus:ring-2 focus:ring-pink-500 focus:border-pink-500 text-base py-2.5 px-3 shadow-sm"
                        :min="new Date().toISOString().split('T')[0]"
                    >
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-800 mb-2">Pickup Time</label>
                    <input 
                        v-model="scheduleForm.pickup_time" 
                        type="time" 
                        class="w-full rounded-lg border-2 border-gray-300 bg-white text-gray-900 focus:ring-2 focus:ring-pink-500 focus:border-pink-500 text-base py-2.5 px-3 shadow-sm"
                    >
                    <p class="text-xs text-gray-600 mt-2">Must be at least 90 mins from now.</p>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-800 mb-2">Vehicle Type</label>
                    <select 
                        v-model="scheduleForm.pickup_vehicle" 
                        class="w-full rounded-lg border-2 border-gray-300 bg-white text-gray-900 focus:ring-2 focus:ring-pink-500 focus:border-pink-500 text-base py-2.5 px-3 shadow-sm"
                    >
                        <option value="Motor">Motorcycle (&lt; 5kg)</option>
                        <option value="Mobil">Car (Bulk / Medium)</option>
                        <option value="Truk">Truck (Large / Heavy)</option>
                    </select>
                </div>
                
                <!-- Order Info -->
                <div class="mt-5 p-4 bg-gradient-to-r from-pink-50 to-rose-50 rounded-lg border-2 border-pink-200">
                    <p class="text-sm font-semibold text-gray-900">Order: #{{ order.order_number }}</p>
                    <p class="text-sm text-pink-700 font-medium mt-0.5">AWB: {{ order.shipping_tracking_number }}</p>
                </div>
            </div>
            
            <div class="flex gap-3 mt-8">
                <button 
                    @click="showScheduleModal = false" 
                    class="flex-1 py-3 border-2 border-gray-300 rounded-lg text-gray-800 font-bold hover:bg-pink-50 hover:border-pink-400 transition-colors text-base"
                >
                    Cancel
                </button>
                <button 
                    @click="submitSchedule" 
                    :disabled="scheduleForm.processing"
                    class="flex-1 py-3 bg-gradient-to-r from-pink-600 to-rose-600 text-white rounded-lg font-bold hover:from-pink-700 hover:to-rose-700 transition-all duration-300 shadow-md hover:shadow-lg flex items-center justify-center gap-2.5 text-base"
                >
                    <Loader2 v-if="scheduleForm.processing" class="w-5 h-5 animate-spin" />
                    {{ scheduleForm.processing ? 'Scheduling...' : 'Confirm Schedule' }}
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.animate-in { animation: animateIn 0.2s ease-out; }
@keyframes animateIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
.zoom-in { animation: zoomIn 0.3s ease-out; }
@keyframes zoomIn { from { opacity: 0; transform: scale(0.95); } to { opacity: 1; transform: scale(1); } }
</style>