<script setup>
import { ref } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { 
    ArrowLeft, Printer, Truck, User, MapPin, Package, 
    CreditCard, CheckCircle, Clock, XCircle, CalendarClock, Loader2, AlertTriangle 
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
    pickup_time: '14:00',
    pickup_vehicle: 'Motor'
});

// Helper Classes
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

const formatCurrency = (val) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val);
const formatDate = (date) => new Date(date).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });

// --- ACTIONS ---

// 1. Print Label - FIXED
const printLabel = () => {
    if (!props.order.shipping_tracking_number) {
        Swal.fire({
            icon: 'warning',
            title: 'No Tracking Number',
            html: `This order has no AWB number.<br>Please click <strong>"Get Resi"</strong> first.`,
            confirmButtonText: 'OK'
        });
        return;
    }
    
    isPrinting.value = true;
    
    // Buka route ke controller printLabel
    const labelUrl = route('admin.orders.label', props.order.id);
    window.open(labelUrl, '_blank');
    
    // Reset loading setelah 1 detik
    setTimeout(() => {
        isPrinting.value = false;
    }, 1000);
};

// 2. Request Booking (Get Resi) - TERPISAH dari Schedule
const requestBooking = () => {
    Swal.fire({
        title: 'Get Resi Number?',
        text: `Requesting AWB from ${props.order.shipping_courier || 'Courier'}...`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, Get Resi',
        confirmButtonColor: '#2563eb'
    }).then((result) => {
        if (result.isConfirmed) {
            isBooking.value = true;
            router.post(route('admin.orders.book', props.order.id), {}, {
                onSuccess: () => Swal.fire('Success', 'Resi Generated & Order Shipped!', 'success'),
                onError: (err) => Swal.fire('Failed', err.message || 'Booking failed.', 'error'),
                onFinish: () => isBooking.value = false
            });
        }
    });
};

// 3. Submit Schedule Pickup - MANUAL, TERPISAH
const submitSchedule = () => {
    scheduleForm.post(route('admin.orders.schedule-pickup'), {
        onSuccess: () => {
            showScheduleModal.value = false;
            Swal.fire('Scheduled!', 'Courier pickup has been arranged.', 'success');
        },
        onError: (err) => Swal.fire('Error', 'Failed to schedule pickup.', 'error')
    });
};

// 4. Update Manual Resi
const updateResi = () => {
    if (!form.resi_number) return;
    form.order_status = 'shipped';
    form.put(route('admin.orders.update', props.order.id), {
        onSuccess: () => Swal.fire('Success', 'Order marked as Shipped!', 'success')
    });
};

// 5. Complete Order
const markAsCompleted = () => {
    Swal.fire({ 
        title: 'Complete Order?', 
        text: 'This will confirm the customer has received the goods.',
        icon: 'question', 
        showCancelButton: true, 
        confirmButtonText: 'Yes, Complete',
        confirmButtonColor: '#10b981'
    }).then((res) => {
        if (res.isConfirmed) {
            form.order_status = 'completed';
            form.put(route('admin.orders.update', props.order.id));
        }
    });
};
</script>

<template>
    <Head :title="`Order #${order.order_number}`" />

    <div class="max-w-7xl mx-auto">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
            <div>
                <Link :href="route('admin.orders.index')" class="text-sm text-gray-500 hover:text-gray-700 flex items-center gap-1 mb-2">
                    <ArrowLeft class="w-4 h-4" /> Back to Orders
                </Link>
                <div class="flex items-center gap-3">
                    <h1 class="text-3xl font-extrabold text-gray-900">Order #{{ order.order_number }}</h1>
                    <span class="px-3 py-1 rounded-full text-xs font-bold border uppercase tracking-wide" :class="getStatusClass(order.order_status)">
                        {{ order.order_status }}
                    </span>
                </div>
                <p class="text-sm text-gray-500 mt-1">Placed on {{ formatDate(order.created_at) }}</p>
            </div>

            <div class="flex gap-3">
                <button 
                    v-if="order.shipping_tracking_number" 
                    @click="printLabel" 
                    :disabled="isPrinting"
                    class="px-4 py-2 bg-white border border-gray-300 text-gray-700 font-bold rounded-xl shadow-sm hover:bg-gray-50 flex items-center gap-2 transition-all disabled:opacity-50 disabled:cursor-not-allowed min-w-[120px]"
                    :title="'AWB: ' + order.shipping_tracking_number"
                >
                    <Loader2 v-if="isPrinting" class="w-4 h-4 animate-spin" />
                    <Printer v-else class="w-4 h-4" /> 
                    {{ isPrinting ? 'Opening...' : 'Print Label' }}
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-2 space-y-6">
                <!-- Order Items -->
                <div class="bg-white rounded-2xl border border-gray-100 shadow-xl shadow-gray-100/50 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-50 bg-gray-50/50 flex justify-between items-center">
                        <h3 class="font-bold text-gray-900 flex items-center gap-2"><Package class="w-5 h-5 text-rose-500" /> Order Items</h3>
                        <span class="text-xs font-medium text-gray-500">{{ order.items.length }} Items</span>
                    </div>
                    <div class="divide-y divide-gray-50">
                        <div v-for="item in order.items" :key="item.id" class="p-6 flex gap-4">
                            <div class="w-16 h-16 bg-gray-100 rounded-lg overflow-hidden shrink-0 border border-gray-200">
                                <img :src="item.product_variant?.product?.image_url || '/images/placeholder.png'" 
                                     :alt="item.product_name"
                                     class="w-full h-full object-cover">
                            </div>
                            <div class="flex-1">
                                <h4 class="font-bold text-gray-900">{{ item.product_name }}</h4>
                                <p class="text-sm text-gray-500">{{ item.variant_name }}</p>
                                <div class="mt-1 flex items-center gap-2 text-sm">
                                    <span class="font-mono text-gray-600">{{ formatCurrency(item.price) }}</span>
                                    <span class="text-gray-400">x</span>
                                    <span class="font-bold text-gray-900">{{ item.quantity }}</span>
                                </div>
                            </div>
                            <div class="text-right font-bold text-gray-900">{{ formatCurrency(item.subtotal) }}</div>
                        </div>
                    </div>
                </div>

                <!-- Payment Details -->
                <div class="bg-white rounded-2xl border border-gray-100 shadow-xl shadow-gray-100/50 p-6">
                    <h3 class="font-bold text-gray-900 mb-4 flex items-center gap-2"><CreditCard class="w-5 h-5 text-emerald-500" /> Payment Details</h3>
                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between text-gray-600"><span>Subtotal</span><span>{{ formatCurrency(order.subtotal) }}</span></div>
                        <div class="flex justify-between text-gray-600"><span>Shipping Cost</span><span>+ {{ formatCurrency(order.shipping_cost) }}</span></div>
                        <div v-if="order.discount_amount > 0" class="flex justify-between text-rose-600 font-medium"><span>Discount ({{ order.voucher_code }})</span><span>- {{ formatCurrency(order.discount_amount) }}</span></div>
                        <div class="border-t border-dashed border-gray-200 pt-3 flex justify-between items-center">
                            <span class="font-extrabold text-gray-900 text-base">Grand Total</span>
                            <span class="font-extrabold text-rose-600 text-lg">{{ formatCurrency(order.total_amount) }}</span>
                        </div>
                    </div>
                    <div class="mt-6 flex items-center justify-between p-4 bg-gray-50 rounded-xl border border-gray-100">
                        <div>
                            <p class="text-xs text-gray-500 uppercase font-bold tracking-wider">Payment Method</p>
                            <p class="font-bold text-gray-900 capitalize mt-0.5">{{ order.payment_method.replace('_', ' ') }}</p>
                        </div>
                        <div class="text-right">
                            <span v-if="order.payment_status === 'paid'" class="px-3 py-1 bg-emerald-100 text-emerald-700 rounded-full text-xs font-bold flex items-center gap-1"><CheckCircle class="w-3 h-3" /> PAID</span>
                            <span v-else-if="order.payment_status === 'unpaid'" class="px-3 py-1 bg-yellow-100 text-yellow-700 rounded-full text-xs font-bold flex items-center gap-1"><Clock class="w-3 h-3" /> UNPAID</span>
                            <span v-else class="px-3 py-1 bg-red-100 text-red-700 rounded-full text-xs font-bold flex items-center gap-1"><XCircle class="w-3 h-3" /> {{ order.payment_status.toUpperCase() }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <!-- Shipping Actions -->
                <div class="bg-white rounded-2xl border border-blue-200 shadow-lg shadow-blue-50 p-6 relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-24 h-24 bg-blue-50 rounded-full -mr-10 -mt-10 blur-xl"></div>
                    <h3 class="font-bold text-gray-900 mb-4 relative z-10">Shipping Actions</h3>
                    
                    <div class="space-y-4 relative z-10">
                        <div v-if="!order.shipping_tracking_number && order.order_status !== 'cancelled'">
                            <button 
                                @click="requestBooking" 
                                :disabled="isBooking"
                                class="w-full py-3 bg-blue-600 text-white font-bold rounded-xl hover:bg-blue-700 transition-colors shadow-sm flex items-center justify-center gap-2"
                            >
                                <Loader2 v-if="isBooking" class="w-4 h-4 animate-spin" />
                                <Truck v-else class="w-4 h-4" /> 
                                {{ isBooking ? 'Booking to Courier...' : 'Get Resi (Booking)' }}
                            </button>
                            
                            <div class="flex items-center gap-2 text-xs text-gray-400 font-bold uppercase tracking-widest justify-center my-3">
                                <span class="h-px w-8 bg-gray-200"></span> OR <span class="h-px w-8 bg-gray-200"></span>
                            </div>

                            <div class="flex gap-2">
                                <input v-model="form.resi_number" type="text" placeholder="Manual Resi / AWB" class="flex-1 rounded-lg border-gray-300 text-sm focus:ring-blue-500 focus:border-blue-500">
                                <button @click="updateResi" class="px-3 bg-gray-100 text-gray-700 font-bold rounded-lg text-xs hover:bg-gray-200 transition-colors">Save</button>
                            </div>
                        </div>

                        <div v-else-if="order.shipping_tracking_number">
                            <div class="bg-blue-50 p-4 rounded-xl border border-blue-100 mb-4">
                                <p class="text-xs text-blue-600 uppercase font-bold tracking-wider">Tracking Number (AWB)</p>
                                <div class="flex items-center gap-2 mt-1">
                                    <Truck class="w-4 h-4 text-blue-500" />
                                    <p class="text-xl font-mono font-bold text-blue-900">{{ order.shipping_tracking_number }}</p>
                                </div>
                                <p class="text-xs text-blue-500 mt-1 pl-6">{{ order.shipping_courier }}</p>
                                
                                <!-- Warning jika belum di-schedule -->
                                <div v-if="order.order_status === 'shipped'" class="mt-3 p-2 bg-amber-50 border border-amber-200 rounded-lg">
                                    <p class="text-xs text-amber-700 flex items-center gap-1">
                                        <AlertTriangle class="w-3 h-3" />
                                        <span class="font-medium">Pickup Required:</span> Schedule pickup before printing label
                                    </p>
                                </div>
                            </div>
                            
                            <!-- Schedule Pickup Button -->
                            <button 
                                @click="showScheduleModal = true"
                                class="w-full py-3 bg-emerald-600 text-white font-bold rounded-xl hover:bg-emerald-700 transition-colors shadow-sm flex items-center justify-center gap-2 mb-2"
                            >
                                <CalendarClock class="w-5 h-5" /> 
                                Schedule Pickup
                            </button>
                            
                            <!-- Print Label Button (di sini juga) -->
                            <button 
                                @click="printLabel"
                                :disabled="isPrinting"
                                class="w-full py-3 bg-blue-600 text-white font-bold rounded-xl hover:bg-blue-700 transition-colors shadow-sm flex items-center justify-center gap-2 mb-2"
                            >
                                <Loader2 v-if="isPrinting" class="w-4 h-4 animate-spin" />
                                <Printer v-else class="w-4 h-4" /> 
                                {{ isPrinting ? 'Opening...' : 'Print Label' }}
                            </button>
                            
                            <button 
                                v-if="order.order_status === 'shipped'"
                                @click="markAsCompleted"
                                class="w-full mt-2 py-2.5 bg-white border border-gray-300 text-gray-700 font-bold rounded-xl hover:bg-gray-50 transition-colors"
                            >
                                Mark as Completed
                            </button>
                        </div>

                        <div v-else class="text-center py-4 text-gray-500 bg-gray-50 rounded-xl border border-dashed border-gray-300">
                            <XCircle class="w-8 h-8 text-gray-300 mx-auto mb-2" />
                            <p>Order is cancelled.</p>
                        </div>
                    </div>
                </div>

                <!-- Customer Info -->
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                    <h3 class="font-bold text-gray-900 mb-4 flex items-center gap-2"><User class="w-5 h-5 text-gray-400" /> Customer</h3>
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center font-bold text-gray-500">{{ order.user?.name.charAt(0) }}</div>
                        <div>
                            <p class="font-bold text-gray-900 text-sm">{{ order.user?.name }}</p>
                            <p class="text-xs text-gray-500">{{ order.user?.email }}</p>
                        </div>
                    </div>
                    <div v-if="order.user?.phone_number" class="text-sm text-gray-600">{{ order.user.phone_number }}</div>
                </div>

                <!-- Shipping Address -->
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                    <h3 class="font-bold text-gray-900 mb-4 flex items-center gap-2"><MapPin class="w-5 h-5 text-gray-400" /> Shipping Address</h3>
                    <div class="text-sm text-gray-600 space-y-1">
                        <p class="font-bold text-gray-900">{{ order.shipping_address?.receiver_name }}</p>
                        <p>{{ order.shipping_address?.phone_number }}</p>
                        <p class="mt-2 leading-relaxed">{{ order.shipping_address?.full_address }}</p>
                        <p>{{ order.shipping_address?.district?.name }}, {{ order.shipping_address?.city?.name }}</p>
                        <p>{{ order.shipping_address?.province?.name }} - {{ order.shipping_address?.postal_code }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Schedule Pickup Modal - FIXED VERSION -->
    <div v-if="showScheduleModal" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
        <div class="bg-white rounded-2xl w-full max-w-md shadow-2xl p-6 animate-in zoom-in-95">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-emerald-100 flex items-center justify-center">
                        <CalendarClock class="w-5 h-5 text-emerald-600" />
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-gray-900">Schedule Pickup</h3>
                        <p class="text-sm text-gray-500">Call courier to pick up package.</p>
                    </div>
                </div>
                <button @click="showScheduleModal = false" class="text-gray-400 hover:text-gray-600">✕</button>
            </div>
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Pickup Date</label>
                    <input 
                        v-model="scheduleForm.pickup_date" 
                        type="date" 
                        class="w-full rounded-lg border-gray-300 focus:ring-emerald-500 focus:border-emerald-500 text-sm"
                        :min="new Date().toISOString().split('T')[0]"
                    >
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Pickup Time</label>
                    <input 
                        v-model="scheduleForm.pickup_time" 
                        type="time" 
                        class="w-full rounded-lg border-gray-300 focus:ring-emerald-500 focus:border-emerald-500 text-sm"
                    >
                    <p class="text-xs text-gray-500 mt-1">Must be at least 90 mins from now.</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Vehicle Type</label>
                    <select 
                        v-model="scheduleForm.pickup_vehicle" 
                        class="w-full rounded-lg border-gray-300 focus:ring-emerald-500 focus:border-emerald-500 text-sm"
                    >
                        <option value="Motor">Motorcycle (&lt; 5kg)</option>
                        <option value="Mobil">Car (Bulk / Medium)</option>
                        <option value="Truk">Truck (Large / Heavy)</option>
                    </select>
                </div>
                
                <!-- Order Info -->
                <div class="mt-4 p-3 bg-gray-50 rounded-lg border border-gray-200">
                    <p class="text-sm font-medium text-gray-900">Order: #{{ order.order_number }}</p>
                    <p class="text-xs text-gray-600">AWB: {{ order.shipping_tracking_number }}</p>
                </div>
            </div>
            
            <div class="flex gap-3 mt-6">
                <button 
                    @click="showScheduleModal = false" 
                    class="flex-1 py-2.5 border border-gray-300 rounded-lg text-gray-700 font-bold hover:bg-gray-50 transition-colors"
                >
                    Cancel
                </button>
                <button 
                    @click="submitSchedule" 
                    :disabled="scheduleForm.processing"
                    class="flex-1 py-2.5 bg-emerald-600 text-white rounded-lg font-bold hover:bg-emerald-700 transition-colors shadow-lg shadow-emerald-100 flex items-center justify-center gap-2"
                >
                    <Loader2 v-if="scheduleForm.processing" class="w-4 h-4 animate-spin" />
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