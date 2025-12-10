<script setup>
import { ref, watch, computed } from 'vue';
import { Head, router, Link } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import CancellationReviewModal from '@/components/CancellationReviewModal.vue';
import { 
    Search, Filter, ShoppingBag, Truck, CheckCircle, XCircle, 
    Clock, ChevronRight, Eye, AlertTriangle, FileText, Loader2,
    Calendar, Trash2, Ban, CalendarClock, CheckSquare, Package,
    Mail, MapPin, Phone, Printer, Check, AlertCircle
} from 'lucide-vue-next';
import Swal from 'sweetalert2';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    orders: Object,
    filters: Object,
    counts: Object
});

// State
const search = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status || 'all');
const searchDebounce = ref(null);

// State Bulk Action
const selectedOrders = ref(new Set());
const isSelectAllPage = ref(false);

// State Loading untuk masing-masing bulk action
const isGettingResi = ref(false);
const isSchedulingPickup = ref(false);
const isPrintingLabels = ref(false);
const isCancelling = ref(false);
const isMarkingShipped = ref(false);
const isMarkingCompleted = ref(false); // TAMBAH INI

// State Modal
const showCancelModal = ref(false);
const showScheduleModal = ref(false);

const showReviewModal = ref(false);
const reviewOrder = ref(null);

const openReviewModal = (order) => {
    reviewOrder.value = order;
    showReviewModal.value = true;
};

// Form Data
const cancelForm = ref({
    reason: '',
    order_ids: []
});

const scheduleForm = ref({
    order_ids: [],
    pickup_date: new Date().toISOString().split('T')[0],
    pickup_time: '16:00',
    pickup_vehicle: 'Motor'
});

// Formatters
const formatCurrency = (val) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val);
const formatDate = (date) => new Date(date).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });

// Tabs
const tabs = [
    { id: 'all', label: 'All Orders', icon: FileText },
    { id: 'pending', label: 'Pending', icon: Clock },
    { id: 'processing', label: 'Processing', icon: ShoppingBag },
    { id: 'schedule_pickup', label: 'Pickup Scheduled', icon: Calendar },
    { id: 'shipped', label: 'Shipped', icon: Truck },
    { id: 'completed', label: 'Completed', icon: CheckCircle },
    { id: 'cancellation_requested', label: 'Cancel Request', icon: AlertCircle },
    { id: 'cancelled', label: 'Cancelled', icon: XCircle },
];

const getStatusClass = (status) => {
    const normalized = status.toLowerCase().replace(/_/g, ' ');
    
    if (normalized.includes('pickup') && normalized.includes('scheduled')) {
        return 'bg-pink-100 text-pink-800 border-pink-200 shadow-sm';
    }
    
    switch(status.toLowerCase()) {
        case 'pending': return 'bg-yellow-100 text-yellow-800 border-yellow-200 shadow-sm';
        case 'processing': return 'bg-blue-100 text-blue-800 border-blue-200 shadow-sm';
        case 'shipped': return 'bg-purple-100 text-purple-800 border-purple-200 shadow-sm';
        case 'completed': return 'bg-emerald-100 text-emerald-800 border-emerald-200 shadow-sm';
        case 'cancellation_requested': return 'bg-orange-100 text-orange-800 border-orange-200 shadow-sm';
        case 'cancelled': return 'bg-red-100 text-red-800 border-red-200 shadow-sm';
        case 'canceled': return 'bg-red-100 text-red-800 border-red-200 shadow-sm';
        default: return 'bg-gray-100 text-gray-800 border-gray-200 shadow-sm';
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

// Helper Functions
const normalizeStatus = (status) => {
    const s = status.toLowerCase();
    if (s.includes('pickup') && s.includes('scheduled')) {
        return 'pickup_scheduled';
    }
    return s;
};

const canSelectOrder = (order) => {
    return !['completed', 'cancelled'].includes(order.order_status);
};

// Computed: Filter orders untuk berbagai bulk actions
const selectedOrderObjects = computed(() => {
    return Array.from(selectedOrders.value).map(id => 
        props.orders.data.find(o => o.id === id)
    ).filter(order => order);
});

// 1. Get Resi (Book Shipment) - untuk orders yang processing TANPA order_no Komerce
const ordersForGetResi = computed(() => {
    return selectedOrderObjects.value.filter(order => 
        order.order_status === 'processing' && !order.shipping_tracking_number
    );
});

const canGetResi = computed(() => {
    return ordersForGetResi.value.length > 0;
});

// 2. Schedule Pickup - untuk orders yang processing DENGAN order_no Komerce
const ordersForSchedulePickup = computed(() => {
    return selectedOrderObjects.value.filter(order => 
        order.order_status === 'processing' && order.shipping_tracking_number
    );
});

const canSchedulePickup = computed(() => {
    return ordersForSchedulePickup.value.length > 0;
});

// 3. Print Labels - untuk orders yang sudah di-schedule (pickup_scheduled)
const ordersForPrintLabels = computed(() => {
    return selectedOrderObjects.value.filter(order => 
        normalizeStatus(order.order_status) === 'pickup_scheduled' && order.shipping_tracking_number
    );
});

const canPrintLabels = computed(() => {
    return ordersForPrintLabels.value.length > 0;
});

// 4. Mark as Shipped - untuk orders yang pickup_scheduled
const ordersForMarkShipped = computed(() => {
    return selectedOrderObjects.value.filter(order => 
        normalizeStatus(order.order_status) === 'pickup_scheduled'
    );
});

const canMarkShipped = computed(() => {
    return ordersForMarkShipped.value.length > 0;
});

// 5. Mark as Completed - untuk orders yang shipped
const ordersForMarkCompleted = computed(() => {
    return selectedOrderObjects.value.filter(order => 
        order.order_status === 'shipped'
    );
});

const canMarkCompleted = computed(() => {
    return ordersForMarkCompleted.value.length > 0;
});

// 6. Cancel Orders - untuk orders yang belum completed/cancelled
const ordersForCancel = computed(() => {
    return selectedOrderObjects.value.filter(order => 
        !['completed', 'cancelled'].includes(order.order_status)
    );
});

const canCancel = computed(() => {
    return ordersForCancel.value.length > 0;
});

// Selection Logic
const toggleSelectOrder = (id) => {
    if (selectedOrders.value.has(id)) {
        selectedOrders.value.delete(id);
    } else {
        selectedOrders.value.add(id);
    }
    updateSelectAllState();
};

const toggleSelectAllPage = () => {
    if (isSelectAllPage.value) {
        selectedOrders.value.clear();
    } else {
        props.orders.data.forEach(order => {
            if (canSelectOrder(order)) {
                selectedOrders.value.add(order.id);
            }
        });
    }
    isSelectAllPage.value = !isSelectAllPage.value;
};

const updateSelectAllState = () => {
    if (props.orders.data.length === 0) { 
        isSelectAllPage.value = false; 
        return; 
    }
    
    const selectableOrders = props.orders.data.filter(canSelectOrder);
    
    if (selectableOrders.length === 0) {
        isSelectAllPage.value = false;
        return;
    }
    
    isSelectAllPage.value = selectableOrders.every(order => 
        selectedOrders.value.has(order.id)
    );
};

// BULK ACTIONS
const bulkGetResi = () => {
    if (!canGetResi.value) {
        Swal.fire({
            icon: 'warning',
            title: 'Tidak bisa Get Order Number',
            html: `Order harus dalam status <strong>"Processing"</strong> dan belum memiliki order number Komerce.`,
            confirmButtonText: 'OK',
            confirmButtonColor: '#ec4899',
            background: '#fdf2f8'
        });
        return;
    }

    const orderIds = ordersForGetResi.value.map(o => o.id);
    
    Swal.fire({
        title: `Get Order Number untuk ${orderIds.length} Order?`,
        html: `Sistem akan mendapatkan <strong>Order Number Komerce</strong>.<br>
               Status order tetap <strong>"Processing"</strong>.`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Ya, Get Order Number',
        confirmButtonColor: '#2563eb',
        cancelButtonColor: '#6b7280',
        background: '#fdf2f8'
    }).then((result) => {
        if (result.isConfirmed) {
            isGettingResi.value = true;
            
            router.post(route('admin.orders.bulk-book'), { ids: orderIds }, {
                onSuccess: () => {
                    selectedOrders.value.clear();
                    isSelectAllPage.value = false;
                    Swal.fire({
                        title: 'Berhasil!',
                        text: `${orderIds.length} order mendapatkan order number Komerce.`,
                        icon: 'success',
                        confirmButtonColor: '#ec4899',
                        background: '#fdf2f8'
                    });
                    router.reload({ only: ['orders'] });
                },
                onError: () => {
                    Swal.fire({
                        title: 'Error',
                        text: 'Gagal mendapatkan order number.',
                        icon: 'error',
                        confirmButtonColor: '#ec4899',
                        background: '#fdf2f8'
                    });
                },
                onFinish: () => isGettingResi.value = false
            });
        }
    });
};

const bulkSchedulePickup = () => {
    if (!canSchedulePickup.value) {
        Swal.fire({
            icon: 'warning',
            title: 'Tidak bisa Schedule Pickup',
            html: `Order harus:<br>
                   • Status <strong>"Processing"</strong><br>
                   • Sudah memiliki order number Komerce`,
            confirmButtonText: 'OK',
            confirmButtonColor: '#ec4899',
            background: '#fdf2f8'
        });
        return;
    }

    const orderIds = ordersForSchedulePickup.value.map(o => o.id);
    scheduleForm.value.order_ids = orderIds;
    showScheduleModal.value = true;
};

const submitSchedulePickup = () => {
    if (scheduleForm.value.order_ids.length === 0) return;
    
    isSchedulingPickup.value = true;
    
    router.post(route('admin.orders.schedule-pickup'), scheduleForm.value, {
        onSuccess: () => {
            showScheduleModal.value = false;
            selectedOrders.value.clear();
            isSelectAllPage.value = false;
            Swal.fire({
                title: 'Berhasil!',
                text: `Pickup berhasil dijadwalkan untuk ${scheduleForm.value.order_ids.length} order.`,
                icon: 'success',
                confirmButtonColor: '#ec4899',
                background: '#fdf2f8'
            });
            router.reload({ only: ['orders'] });
        },
        onError: (errors) => {
            Swal.fire({
                title: 'Error',
                text: errors.message || 'Gagal menjadwalkan pickup.',
                icon: 'error',
                confirmButtonColor: '#ec4899',
                background: '#fdf2f8'
            });
        },
        onFinish: () => {
            isSchedulingPickup.value = false;
        }
    });
};

const bulkPrintLabels = () => {
    if (!canPrintLabels.value) {
        Swal.fire({
            icon: 'warning',
            title: 'Tidak bisa Print Label',
            html: `Order harus:<br>
                   • Status <strong>"Pickup Scheduled"</strong><br>
                   • Sudah memiliki order number Komerce`,
            confirmButtonText: 'OK',
            confirmButtonColor: '#ec4899',
            background: '#fdf2f8'
        });
        return;
    }

    const orderIds = ordersForPrintLabels.value.map(o => o.id);
    
    Swal.fire({
        title: `Print Label untuk ${orderIds.length} Order?`,
        html: `Sistem akan generate PDF shipping label.<br>
               Masing-masing label akan terbuka di tab baru.`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Ya, Print Label',
        confirmButtonColor: '#7c3aed',
        cancelButtonColor: '#6b7280',
        background: '#fdf2f8'
    }).then((result) => {
        if (result.isConfirmed) {
            isPrintingLabels.value = true;
            
            // Buka label masing-masing order di tab baru
            orderIds.forEach(orderId => {
                const labelUrl = route('admin.orders.label', orderId);
                window.open(labelUrl, '_blank');
            });
            
            setTimeout(() => {
                isPrintingLabels.value = false;
                selectedOrders.value.clear();
                isSelectAllPage.value = false;
                
                Swal.fire({
                    title: 'Label Dibuka!',
                    text: `${orderIds.length} label dibuka di tab baru.`,
                    icon: 'success',
                    confirmButtonColor: '#ec4899',
                    background: '#fdf2f8'
                });
            }, 1000);
        }
    });
};

const bulkMarkShipped = () => {
    if (!canMarkShipped.value) {
        Swal.fire({
            icon: 'warning',
            title: 'Tidak bisa Mark as Shipped',
            html: `Order harus dalam status <strong>"Pickup Scheduled"</strong>.`,
            confirmButtonText: 'OK',
            confirmButtonColor: '#ec4899',
            background: '#fdf2f8'
        });
        return;
    }

    const eligibleOrders = ordersForMarkShipped.value;
    
    Swal.fire({
        title: `Mark as Shipped untuk ${eligibleOrders.length} Order?`,
        html: `Status akan berubah menjadi <strong>"Shipped"</strong>.<br>
               Konfirmasi bahwa kurir sudah mengambil paket.`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Ya, Mark as Shipped',
        confirmButtonColor: '#7c3aed',
        cancelButtonColor: '#6b7280',
        background: '#fdf2f8'
    }).then((result) => {
        if (result.isConfirmed) {
            isMarkingShipped.value = true;
            
            // Update masing-masing order SAMA SEPERTI DI SHOW
            const promises = eligibleOrders.map(order => {
                return router.put(route('admin.orders.update', order.id), {
                    order_status: 'shipped',
                    resi_number: order.shipping_tracking_number || '' // Kirim order_no untuk diupdate
                });
            });
            
            Promise.all(promises).then(() => {
                selectedOrders.value.clear();
                isSelectAllPage.value = false;
                Swal.fire({
                    title: 'Berhasil!',
                    text: `${eligibleOrders.length} order ditandai sebagai shipped.`,
                    icon: 'success',
                    confirmButtonColor: '#ec4899',
                    background: '#fdf2f8'
                });
                router.reload({ only: ['orders'] });
            }).catch((error) => {
                Swal.fire({
                    title: 'Error',
                    text: error.message || 'Gagal mengupdate beberapa order.',
                    icon: 'error',
                    confirmButtonColor: '#ec4899',
                    background: '#fdf2f8'
                });
            }).finally(() => {
                isMarkingShipped.value = false;
            });
        }
    });
};

// TAMBAH FUNCTION BARU: BULK MARK COMPLETED
const bulkMarkCompleted = () => {
    if (!canMarkCompleted.value) {
        Swal.fire({
            icon: 'warning',
            title: 'Tidak bisa Mark as Completed',
            html: `Order harus dalam status <strong>"Shipped"</strong>.`,
            confirmButtonText: 'OK',
            confirmButtonColor: '#ec4899',
            background: '#fdf2f8'
        });
        return;
    }

    const eligibleOrders = ordersForMarkCompleted.value;
    
    Swal.fire({
        title: `Mark as Completed untuk ${eligibleOrders.length} Order?`,
        html: `Status akan berubah menjadi <strong>"Completed"</strong>.<br>
               Konfirmasi bahwa customer sudah menerima paket.`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Ya, Mark as Completed',
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#6b7280',
        background: '#fdf2f8'
    }).then((result) => {
        if (result.isConfirmed) {
            isMarkingCompleted.value = true;
            
            // Update masing-masing order ke status completed
            const promises = eligibleOrders.map(order => {
                return router.put(route('admin.orders.update', order.id), {
                    order_status: 'completed'
                });
            });
            
            Promise.all(promises).then(() => {
                selectedOrders.value.clear();
                isSelectAllPage.value = false;
                Swal.fire({
                    title: 'Berhasil!',
                    text: `${eligibleOrders.length} order ditandai sebagai completed.`,
                    icon: 'success',
                    confirmButtonColor: '#ec4899',
                    background: '#fdf2f8'
                });
                router.reload({ only: ['orders'] });
            }).catch((error) => {
                Swal.fire({
                    title: 'Error',
                    text: error.message || 'Gagal mengupdate beberapa order.',
                    icon: 'error',
                    confirmButtonColor: '#ec4899',
                    background: '#fdf2f8'
                });
            }).finally(() => {
                isMarkingCompleted.value = false;
            });
        }
    });
};

const bulkCancel = () => {
    if (!canCancel.value) {
        Swal.fire({
            icon: 'warning',
            title: 'Tidak bisa Cancel',
            html: `Order tidak boleh dalam status <strong>"Completed"</strong> atau <strong>"Cancelled"</strong>.`,
            confirmButtonText: 'OK',
            confirmButtonColor: '#ec4899',
            background: '#fdf2f8'
        });
        return;
    }

    const orderIds = ordersForCancel.value.map(o => o.id);
    cancelForm.value.order_ids = orderIds;
    showCancelModal.value = true;
};

const submitCancel = () => {
    if (!cancelForm.value.reason || cancelForm.value.reason.length < 5) {
        Swal.fire({ 
            icon: 'warning', 
            title: 'Alasan Diperlukan', 
            text: 'Mohon berikan alasan pembatalan (minimal 5 karakter).',
            confirmButtonColor: '#ec4899',
            background: '#fdf2f8'
        });
        return;
    }

    isCancelling.value = true;
    
    router.post(route('admin.orders.bulk-cancel'), cancelForm.value, {
        onSuccess: () => {
            showCancelModal.value = false;
            cancelForm.value.reason = '';
            selectedOrders.value.clear();
            isSelectAllPage.value = false;
            Swal.fire({
                title: 'Berhasil!',
                text: `${cancelForm.value.order_ids.length} order dibatalkan.`,
                icon: 'success',
                confirmButtonColor: '#ec4899',
                background: '#fdf2f8'
            });
            router.reload({ only: ['orders'] });
        },
        onError: (errors) => {
            Swal.fire({
                title: 'Error',
                text: errors.message || 'Gagal membatalkan order.',
                icon: 'error',
                confirmButtonColor: '#ec4899',
                background: '#fdf2f8'
            });
        },
        onFinish: () => isCancelling.value = false
    });
};
</script>

<template>
    <Head title="Order Management" />

    <div class="max-w-7xl mx-auto">
        <!-- HEADER SECTION -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-8">
            <div>
                <h1 class="text-3xl font-extrabold bg-gradient-to-r from-pink-600 to-rose-600 text-transparent bg-clip-text">
                    Order Management
                </h1>
                <div class="flex items-center gap-2 mt-1">
                    <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-gray-100 text-gray-600">
                        {{ orders.total }} orders
                    </span>
                    <span v-if="statusFilter !== 'all'" class="text-xs font-bold px-2 py-0.5 rounded-full bg-pink-100 text-pink-700">
                        {{ tabs.find(t => t.id === statusFilter)?.label || statusFilter }}
                    </span>
                </div>
            </div>

            <!-- SEARCH & FILTER -->
            <div class="flex flex-wrap items-center gap-3">
                <!-- BULK ACTIONS SECTION -->
                <div v-if="selectedOrders.size > 0" class="flex flex-wrap items-center gap-2 animate-in fade-in slide-in-from-bottom-2">
                    <div class="text-sm font-medium text-pink-700 bg-pink-50 px-3 py-1.5 rounded-lg border border-pink-200">
                        {{ selectedOrders.size }} selected
                    </div>
                    
                    <!-- 1. GET ORDER NUMBER Button -->
                    <button 
                        v-if="canGetResi"
                        @click="bulkGetResi"
                        :disabled="isGettingResi"
                        class="h-11 px-4 bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-bold rounded-xl hover:from-blue-700 hover:to-indigo-700 transition-all flex items-center gap-2 shadow-sm disabled:opacity-50 disabled:cursor-not-allowed"
                        :title="`Get Komerce order number untuk ${ordersForGetResi.length} order`"
                    >
                        <Loader2 v-if="isGettingResi" class="w-4 h-4 animate-spin" />
                        <Package v-else class="w-4 h-4" />
                        Get Order No ({{ ordersForGetResi.length }})
                    </button>
                    
                    <!-- 2. SCHEDULE PICKUP Button -->
                    <button 
                        v-if="canSchedulePickup"
                        @click="bulkSchedulePickup"
                        :disabled="isSchedulingPickup"
                        class="h-11 px-4 bg-gradient-to-r from-pink-600 to-rose-600 text-white font-bold rounded-xl hover:from-pink-700 hover:to-rose-700 transition-all flex items-center gap-2 shadow-sm disabled:opacity-50 disabled:cursor-not-allowed"
                        :title="`Schedule pickup untuk ${ordersForSchedulePickup.length} order`"
                    >
                        <Loader2 v-if="isSchedulingPickup" class="w-4 h-4 animate-spin" />
                        <CalendarClock v-else class="w-4 h-4" />
                        Schedule Pickup ({{ ordersForSchedulePickup.length }})
                    </button>
                    
                    <!-- 3. PRINT LABELS Button -->
                    <button 
                        v-if="canPrintLabels"
                        @click="bulkPrintLabels"
                        :disabled="isPrintingLabels"
                        class="h-11 px-4 bg-gradient-to-r from-purple-600 to-violet-600 text-white font-bold rounded-xl hover:from-purple-700 hover:to-violet-700 transition-all flex items-center gap-2 shadow-sm disabled:opacity-50 disabled:cursor-not-allowed"
                        :title="`Print label untuk ${ordersForPrintLabels.length} order`"
                    >
                        <Loader2 v-if="isPrintingLabels" class="w-4 h-4 animate-spin" />
                        <Printer v-else class="w-4 h-4" />
                        Print Label ({{ ordersForPrintLabels.length }})
                    </button>
                    
                    <!-- 4. MARK AS SHIPPED Button -->
                    <button 
                        v-if="canMarkShipped"
                        @click="bulkMarkShipped"
                        :disabled="isMarkingShipped"
                        class="h-11 px-4 bg-gradient-to-r from-emerald-600 to-green-600 text-white font-bold rounded-xl hover:from-emerald-700 hover:to-green-700 transition-all flex items-center gap-2 shadow-sm disabled:opacity-50 disabled:cursor-not-allowed"
                        :title="`Mark as shipped untuk ${ordersForMarkShipped.length} order`"
                    >
                        <Loader2 v-if="isMarkingShipped" class="w-4 h-4 animate-spin" />
                        <CheckSquare v-else class="w-4 h-4" />
                        Mark Shipped ({{ ordersForMarkShipped.length }})
                    </button>
                    
                    <!-- 5. MARK AS COMPLETED Button (BARU) -->
                    <button 
                        v-if="canMarkCompleted"
                        @click="bulkMarkCompleted"
                        :disabled="isMarkingCompleted"
                        class="h-11 px-4 bg-gradient-to-r from-teal-600 to-cyan-600 text-white font-bold rounded-xl hover:from-teal-700 hover:to-cyan-700 transition-all flex items-center gap-2 shadow-sm disabled:opacity-50 disabled:cursor-not-allowed"
                        :title="`Mark as completed untuk ${ordersForMarkCompleted.length} order`"
                    >
                        <Loader2 v-if="isMarkingCompleted" class="w-4 h-4 animate-spin" />
                        <Check v-else class="w-4 h-4" />
                        Mark Completed ({{ ordersForMarkCompleted.length }})
                    </button>
                    
                    <!-- 6. CANCEL Button -->
                    <button 
                        v-if="canCancel"
                        @click="bulkCancel"
                        :disabled="isCancelling"
                        class="h-11 px-4 bg-gradient-to-r from-red-600 to-rose-600 text-white font-bold rounded-xl hover:from-red-700 hover:to-rose-700 transition-all flex items-center gap-2 shadow-sm disabled:opacity-50 disabled:cursor-not-allowed"
                        :title="`Cancel ${ordersForCancel.length} order`"
                    >
                        <Loader2 v-if="isCancelling" class="w-4 h-4 animate-spin" />
                        <Ban v-else class="w-4 h-4" />
                        Cancel ({{ ordersForCancel.length }})
                    </button>
                    
                    <!-- CLEAR SELECTION Button -->
                    <button 
                        @click="selectedOrders.clear(); isSelectAllPage = false;"
                        class="h-11 px-4 bg-white border border-gray-300 text-gray-700 font-bold rounded-xl hover:bg-gray-50 transition-all flex items-center gap-2 shadow-sm"
                    >
                        Clear
                    </button>
                </div>

                <!-- Search Box -->
                <div class="relative group w-full md:w-64">
                    <Search class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                    <input 
                        v-model="search" type="text" placeholder="Search Order ID..." 
                        class="h-11 pl-10 pr-4 w-full border-2 border-pink-300 bg-white text-gray-900 rounded-xl text-sm font-medium focus:ring-2 focus:ring-pink-500 focus:border-pink-500 shadow-sm transition-all"
                    >
                </div>
            </div>
        </div>

        <!-- TABS -->
        <div class="flex overflow-x-auto pb-1 mb-6 gap-2 no-scrollbar">
            <button v-for="tab in tabs" :key="tab.id" @click="statusFilter = tab.id"
                class="px-4 py-2.5 rounded-lg font-medium text-sm flex items-center gap-2 transition-all whitespace-nowrap border-2 relative"
                :class="statusFilter === tab.id 
                    ? (tab.id === 'schedule_pickup' 
                        ? 'bg-gradient-to-r from-pink-100 to-rose-100 text-pink-700 border-pink-300 shadow-sm' 
                        : 'bg-gradient-to-r from-rose-50 to-pink-50 text-rose-700 border-rose-300 shadow-sm')
                    : 'bg-white text-gray-600 border-transparent hover:bg-gray-50'">
                
                <component :is="tab.icon" class="w-4 h-4" /> 
                {{ tab.label }}

                <span v-if="!['all', 'completed', 'cancelled'].includes(tab.id) && counts && counts[tab.id] > 0" 
                    class="ml-1.5 bg-rose-600 text-white text-[10px] font-bold px-1.5 h-5 min-w-[20px] rounded-full flex items-center justify-center shadow-sm border border-white">
                    {{ counts[tab.id] }}
                </span>
            </button>
        </div>

        <!-- ORDERS TABLE -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-xl shadow-gray-100/50 overflow-hidden">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50/80">
                    <tr>
                        <th class="px-6 py-4 w-10">
                            <input type="checkbox" 
                                :checked="isSelectAllPage" 
                                @change="toggleSelectAllPage" 
                                class="rounded border-2 border-gray-300 text-pink-600 focus:ring-pink-500 w-4 h-4 cursor-pointer"
                                :disabled="orders.data.length === 0 || orders.data.every(o => !canSelectOrder(o))"
                                :title="orders.data.every(o => !canSelectOrder(o)) ? 'No selectable orders in this view' : ''"
                            >
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-extrabold text-gray-400 uppercase tracking-wider">Order Details</th>
                        <th class="px-6 py-4 text-left text-xs font-extrabold text-gray-400 uppercase tracking-wider">Customer</th>
                        <th class="px-6 py-4 text-left text-xs font-extrabold text-gray-400 uppercase tracking-wider">Total & Payment</th>
                        <th class="px-6 py-4 text-left text-xs font-extrabold text-gray-400 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-4 text-right text-xs font-extrabold text-gray-400 uppercase tracking-wider">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr v-for="order in orders.data" :key="order.id" class="hover:bg-pink-50/30 transition-colors group">
                        <td class="px-6 py-4">
                            <input 
                                type="checkbox" 
                                :checked="selectedOrders.has(order.id)" 
                                @change="toggleSelectOrder(order.id)" 
                                :disabled="!canSelectOrder(order)"
                                class="rounded border-2 border-gray-300 text-pink-600 focus:ring-pink-500 w-4 h-4 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
                                :title="!canSelectOrder(order) ? `Cannot select ${order.order_status} orders` : ''"
                            >
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex flex-col">
                                <span class="font-bold text-gray-900 text-sm">#{{ order.order_number }}</span>
                                <span class="text-xs text-gray-500 mt-0.5 flex items-center gap-1">
                                    <Clock class="w-3 h-3" /> {{ formatDate(order.created_at) }}
                                </span>
                                
                                <!-- Order Number Komerce -->
                                <div v-if="order.shipping_tracking_number" class="mt-2 flex flex-col gap-1">
                                    <div class="flex items-center gap-1">
                                        <Package class="w-3 h-3 text-blue-500" />
                                        <span class="text-[10px] font-mono text-blue-600 bg-blue-50 px-2 py-0.5 rounded">
                                            {{ order.shipping_tracking_number }}
                                        </span>
                                    </div>
                                    <span v-if="order.shipping_courier" class="text-[10px] text-gray-600">
                                        {{ order.shipping_courier }}
                                    </span>
                                </div>
                                
                                <!-- AWB dari Schedule Pickup -->
                                <div v-if="order.resi_number" class="mt-1">
                                    <div class="text-[10px] text-green-600 bg-green-50 px-2 py-0.5 rounded border border-green-200">
                                        🚚 AWB: {{ order.resi_number }}
                                    </div>
                                </div>
                                
                                <!-- Status Indicators -->
                                <div v-if="order.order_status === 'processing'" class="mt-2">
                                    <div v-if="!order.shipping_tracking_number" 
                                        class="text-[10px] text-amber-600 bg-amber-50 px-2 py-0.5 rounded border border-amber-200">
                                        ⚠️ Need Order Number
                                    </div>
                                    <div v-else 
                                        class="text-[10px] text-blue-600 bg-blue-50 px-2 py-0.5 rounded border border-blue-200">
                                        ✓ Ready for Schedule Pickup
                                    </div>
                                </div>
                                
                                <div v-if="normalizeStatus(order.order_status) === 'pickup_scheduled'" 
                                    class="mt-2 text-[10px] text-pink-600 bg-pink-50 px-2 py-0.5 rounded border border-pink-200">
                                    📅 Ready for Print Label
                                </div>
                                
                                <div v-if="order.order_status === 'shipped'" 
                                    class="mt-2 text-[10px] text-purple-600 bg-purple-50 px-2 py-0.5 rounded border border-purple-200">
                                    🚚 Ready for Mark as Completed
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-gradient-to-br from-pink-400 to-rose-500 flex items-center justify-center text-xs font-bold text-white">
                                    {{ order.user?.name?.charAt(0) || 'U' }}
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-gray-900">{{ order.user?.name || 'Guest' }}</p>
                                    <p class="text-xs text-gray-500 flex items-center gap-1">
                                        <Mail class="w-3 h-3" /> {{ order.user?.email }}
                                    </p>
                                    <p v-if="order.user?.phone_number" class="text-xs text-gray-500 flex items-center gap-1">
                                        <Phone class="w-3 h-3" /> {{ order.user.phone_number }}
                                    </p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="font-bold text-gray-900 text-sm">{{ formatCurrency(order.total_amount) }}</div>
                            <div class="text-xs mt-1 px-2 py-0.5 rounded-full w-fit font-medium uppercase tracking-wide border-2"
                                :class="order.payment_status === 'paid' 
                                    ? 'bg-emerald-100 text-emerald-800 border-emerald-300' 
                                    : 'bg-gray-100 text-gray-700 border-gray-300'">
                                {{ order.payment_status }}
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-3 py-1 rounded-full text-xs font-bold border-2 flex items-center gap-1 w-fit uppercase tracking-wider"
                                :class="getStatusClass(order.order_status)">
                                <span class="w-1.5 h-1.5 rounded-full" 
                                    :class="{
                                        'bg-yellow-500': order.order_status === 'pending',
                                        'bg-blue-500': order.order_status === 'processing',
                                        'bg-pink-500': normalizeStatus(order.order_status) === 'pickup_scheduled',
                                        'bg-purple-500': order.order_status === 'shipped',
                                        'bg-emerald-500': order.order_status === 'completed',
                                        'bg-red-500': order.order_status === 'cancelled'
                                    }"></span>
                                {{ order.order_status.replace('_', ' ') }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <button 
                                    v-if="order.order_status === 'cancellation_requested'"
                                    @click="openReviewModal(order)"
                                    class="flex items-center gap-1 px-3 py-1.5 text-xs font-bold text-white bg-orange-500 rounded-lg hover:bg-orange-600 transition-all shadow-sm mr-2"
                                >
                                    <Eye class="w-3 h-3" /> Review
                                </button>
                                <Link :href="route('admin.orders.show', order.id)" 
                                    class="flex items-center gap-1 px-3 py-1.5 text-xs font-bold text-white bg-gradient-to-r from-pink-600 to-rose-600 rounded-lg hover:from-pink-700 hover:to-rose-700 transition-all shadow-sm group">
                                    Detail <ChevronRight class="w-3 h-3 group-hover:translate-x-0.5 transition-transform" />
                                </Link>
                            </div>
                        </td>
                    </tr>
                    
                    <!-- EMPTY STATE -->
                    <tr v-if="orders.data.length === 0">
                        <td colspan="6" class="px-6 py-16 text-center">
                            <div class="w-16 h-16 bg-gradient-to-br from-pink-50 to-rose-50 rounded-full flex items-center justify-center mx-auto mb-4 border-2 border-pink-100">
                                <ShoppingBag class="w-8 h-8 text-pink-300" />
                            </div>
                            <h3 class="text-lg font-bold text-gray-900">No orders found</h3>
                            <p class="text-sm text-gray-500 mt-1">Try adjusting your search or filters.</p>
                            <button @click="statusFilter = 'all'; search = ''" 
                                class="mt-3 px-4 py-2 bg-gradient-to-r from-pink-50 to-rose-50 text-pink-700 rounded-lg text-sm font-bold border-2 border-pink-200 hover:border-pink-300 transition-all">
                                Reset Filters
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- PAGINATION -->
            <div v-if="orders.links.length > 3" class="px-6 py-4 border-t border-gray-100 bg-gray-50/50">
                <div class="flex justify-end gap-1">
                    <Link v-for="(link, k) in orders.links" :key="k" :href="link.url || '#'" 
                        class="px-3 py-1.5 rounded-lg text-xs font-medium transition-all border-2"
                        :class="link.active 
                            ? 'bg-gradient-to-r from-pink-600 to-rose-600 text-white border-pink-600 shadow-sm' 
                            : 'text-gray-600 border-transparent hover:bg-white hover:border-pink-200'"
                        v-html="link.label" />
                </div>
            </div>
        </div>
    </div>

    <!-- CANCEL MODAL -->
    <div v-if="showCancelModal" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/70 backdrop-blur-sm p-4">
        <div class="bg-white rounded-2xl w-full max-w-md shadow-2xl p-6 animate-in zoom-in-95 border-2 border-red-200">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-12 h-12 rounded-full bg-gradient-to-br from-red-100 to-rose-100 flex items-center justify-center border-2 border-red-200">
                    <Ban class="w-6 h-6 text-red-600" />
                </div>
                <div>
                    <h3 class="text-xl font-bold text-gray-900">
                        Cancel {{ cancelForm.order_ids.length }} Orders
                    </h3>
                    <p class="text-sm text-gray-600 mt-1">
                        This action will cancel selected orders and restore stock quantity.
                    </p>
                </div>
            </div>
            
            <div class="mb-6">
                <label class="block text-sm font-semibold text-gray-800 mb-2">
                    Cancellation Reason <span class="text-red-500">*</span>
                </label>
                <textarea v-model="cancelForm.reason" rows="4" 
                    class="w-full rounded-xl border-2 border-gray-300 bg-white text-gray-900 focus:border-red-500 focus:ring-red-500 text-sm placeholder-gray-400 p-3 resize-none"
                    placeholder="Please provide detailed reason for cancellation (minimum 5 characters)..."></textarea>
                <div class="flex justify-between items-center mt-2">
                    <p class="text-xs text-gray-500">This note will be visible in order history.</p>
                    <p class="text-xs" :class="cancelForm.reason.length >= 5 ? 'text-green-600' : 'text-red-600'">
                        {{ cancelForm.reason.length }}/5 characters
                    </p>
                </div>
            </div>
            
            <div class="flex gap-3">
                <button @click="showCancelModal = false; cancelForm.reason = '';" 
                    class="flex-1 py-3 border-2 border-gray-300 rounded-xl text-sm font-bold text-gray-700 hover:bg-gray-50 transition-colors">
                    Cancel
                </button>
                <button @click="submitCancel" 
                    :disabled="isCancelling || cancelForm.reason.length < 5" 
                    class="flex-1 py-3 bg-gradient-to-r from-red-600 to-rose-600 text-white rounded-xl text-sm font-bold hover:from-red-700 hover:to-rose-700 transition-all shadow-lg shadow-red-200 flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                    <Loader2 v-if="isCancelling" class="w-4 h-4 animate-spin" />
                    {{ isCancelling ? 'Processing...' : `Cancel ${cancelForm.order_ids.length} Orders` }}
                </button>
            </div>
        </div>
    </div>

    <!-- SCHEDULE PICKUP MODAL -->
    <div v-if="showScheduleModal" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/70 backdrop-blur-sm p-4">
        <div class="bg-white rounded-2xl w-full max-w-md shadow-2xl p-6 animate-in zoom-in-95 border-2 border-emerald-200">
            <div class="flex items-center justify-between mb-7">
                <div class="flex items-center gap-3.5">
                    <div class="w-12 h-12 rounded-full bg-gradient-to-br from-emerald-100 to-green-100 flex items-center justify-center border-2 border-emerald-200">
                        <CalendarClock class="w-6 h-6 text-emerald-600" />
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-gray-900">Schedule Physical Pickup</h3>
                        <p class="text-sm text-gray-600">Call courier to pick up packages.</p>
                    </div>
                </div>
                <button @click="showScheduleModal = false" class="text-gray-500 hover:text-emerald-700 transition-colors text-xl">✕</button>
            </div>
            
            <div class="space-y-5">
                <div>
                    <label class="block text-sm font-semibold text-gray-800 mb-2">Pickup Date</label>
                    <input 
                        v-model="scheduleForm.pickup_date" 
                        type="date" 
                        class="w-full rounded-lg border-2 border-gray-300 bg-white text-gray-900 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 text-base py-2.5 px-3 shadow-sm"
                        :min="new Date().toISOString().split('T')[0]"
                    >
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-800 mb-2">Pickup Time</label>
                    <input 
                        v-model="scheduleForm.pickup_time" 
                        type="time" 
                        class="w-full rounded-lg border-2 border-gray-300 bg-white text-gray-900 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 text-base py-2.5 px-3 shadow-sm"
                    >
                    <p class="text-xs text-gray-600 mt-2">Must be at least 90 mins from now.</p>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-800 mb-2">Vehicle Type</label>
                    <select 
                        v-model="scheduleForm.pickup_vehicle" 
                        class="w-full rounded-lg border-2 border-gray-300 bg-white text-gray-900 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 text-base py-2.5 px-3 shadow-sm"
                    >
                        <option value="Motor">Motorcycle (&lt; 5kg)</option>
                        <option value="Mobil">Car (Bulk / Medium)</option>
                        <option value="Truk">Truck (Large / Heavy)</option>
                    </select>
                </div>
                
                <!-- Order Info -->
                <div class="mt-5 p-4 bg-gradient-to-r from-emerald-50 to-green-50 rounded-lg border-2 border-emerald-200">
                    <p class="text-sm font-semibold text-gray-900 mb-2">{{ scheduleForm.order_ids.length }} Orders Ready for Schedule Pickup</p>
                    <div class="text-xs text-emerald-700 space-y-1 max-h-24 overflow-y-auto">
                        <div v-for="orderId in scheduleForm.order_ids" :key="orderId" class="flex items-center gap-2">
                            <span class="font-mono">#{{ orderId }}</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="flex gap-3 mt-8">
                <button 
                    @click="showScheduleModal = false" 
                    class="flex-1 py-3 border-2 border-gray-300 rounded-lg text-gray-800 font-bold hover:bg-emerald-50 hover:border-emerald-400 transition-colors text-base"
                >
                    Cancel
                </button>
                <button 
                    @click="submitSchedulePickup" 
                    :disabled="isSchedulingPickup"
                    class="flex-1 py-3 bg-gradient-to-r from-emerald-600 to-green-600 text-white rounded-lg font-bold hover:from-emerald-700 hover:to-green-700 transition-all duration-300 shadow-md hover:shadow-lg flex items-center justify-center gap-2.5 text-base"
                >
                    <Loader2 v-if="isSchedulingPickup" class="w-5 h-5 animate-spin" />
                    {{ isSchedulingPickup ? 'Scheduling...' : 'Schedule Pickup' }}
                </button>

            </div>
        </div>
    </div>
    <CancellationReviewModal 
        :show="showReviewModal" 
        :order="reviewOrder" 
        @close="showReviewModal = false" 
    />
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
.animate-in { animation: animateIn 0.2s ease-out; }
@keyframes animateIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
.zoom-in { animation: zoomIn 0.3s ease-out; }
@keyframes zoomIn { from { opacity: 0; transform: scale(0.95); } to { opacity: 1; transform: scale(1); } }
</style>