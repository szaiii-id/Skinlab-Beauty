<script setup>
import { Head, router, Link, useForm } from '@inertiajs/vue3';
// 1. TAMBAHAN IMPORT: onMounted, onUnmounted, nextTick, computed
import { ref, watch, onMounted, onUnmounted, nextTick, computed } from 'vue'; 
import axios from 'axios';
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import ReviewModal from '@/components/RiviewModal.vue'; 

defineOptions({ layout: DashboardLayout });

const props = defineProps({
    // Pastikan backend mengirim data paginate (Object), bukan Array biasa
    orders: [Array, Object], 
    currentStatus: String
});

// ==========================================
// [BARU] LOGIC INFINITE SCROLL
// ==========================================
// Menampung semua data order (gabungan halaman 1, 2, dst)
const allOrders = ref(props.orders.data || props.orders);
const nextUrl = ref(props.orders.links?.next || null);
const isLoading = ref(false);
const observerTarget = ref(null);
let observer = null;

// Watcher: Jika Tab berubah (misal dari Pending ke Paid), reset list
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
        only: ['orders'], // Hanya ambil data orders untuk efisiensi
        onSuccess: (page) => {
            const newOrders = page.props.orders;
            if (newOrders.data && newOrders.data.length > 0) {
                // Gabungkan data lama dengan data baru
                allOrders.value.push(...newOrders.data);
                nextUrl.value = newOrders.links?.next;
            }
        },
        onFinish: () => {
            isLoading.value = false;
        }
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
// ==========================================
// END LOGIC INFINITE SCROLL
// ==========================================


// --- STATE POPUP CANCEL ---
const showCancelModal = ref(false);
const selectedOrderToCancel = ref(null);

// Pilihan Alasan Cancel
const cancelOptions = [
    "Ingin mengubah alamat pengiriman",
    "Ingin mengubah rincian pesanan (warna/ukuran)",
    "Lupa memasukkan kode voucher/diskon",
    "Berubah pikiran / Tidak jadi beli",
    "Lainnya"
];

const selectedReason = ref(cancelOptions[0]); 
const customReason = ref(''); 

const cancelForm = useForm({
    reason: '' 
});

// ==========================================
// STATE POPUP RETUR
// ==========================================
const showReturnModal = ref(false);
const selectedOrderToReturn = ref(null);

// Form Retur
const returnForm = useForm({
    reason: 'Barang Rusak / Tidak Berfungsi',
    description: '',
    solution: 'refund',
    evidence: null,
});

// --- FORMATTER ---
const formatCurrency = (amount) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency', currency: 'IDR', minimumFractionDigits: 0, maximumFractionDigits: 0
    }).format(amount);
};

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('id-ID', {
        day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit'
    });
};

// --- TABS & FILTER ---
const tabs = [
    { id: 'all', label: 'Semua' },
    { id: 'pending', label: 'Belum Bayar' },
    { id: 'paid', label: 'Diproses' },
    { id: 'shipped', label: 'Dikirim' },
    { id: 'completed', label: 'Selesai' },
    { id: 'canceled', label: 'Dibatalkan' },
    { id: 'return_requested', label: 'Pengajuan Retur' }
];

const filterStatus = (status) => {
    router.get('/orders', { status }, { preserveState: true });
};

// --- STATUS BADGE ---
const getStatusClass = (status) => {
    switch(status) {
        case 'pending': return 'bg-yellow-100 text-yellow-800 border-yellow-200';
        case 'paid': return 'bg-blue-100 text-blue-800 border-blue-200';
        case 'shipped': return 'bg-purple-100 text-purple-800 border-purple-200';
        case 'completed': return 'bg-green-100 text-green-800 border-green-200';
        case 'canceled': return 'bg-red-100 text-red-800 border-red-200';
        case 'cancellation_requested': return 'bg-orange-100 text-orange-800 border-orange-200';
        case 'return_requested': return 'bg-rose-100 text-rose-800 border-rose-200';
        default: return 'bg-gray-100 text-gray-800 border-gray-200';
    }
};

const getStatusLabel = (status) => {
    switch(status) {
        case 'pending': return 'Menunggu Pembayaran';
        case 'paid': return 'Sedang Diproses';
        case 'shipped': return 'Sedang Dikirim';
        case 'completed': return 'Selesai';
        case 'canceled': return 'Dibatalkan';
        case 'cancellation_requested': return 'Menunggu Persetujuan Batal';
        case 'return_requested': return 'Mengajukan Retur';
        default: return status;
    }
};

// --- LOGIC CANCEL ---
const openCancelModal = (order) => {
    selectedOrderToCancel.value = order;
    selectedReason.value = cancelOptions[0];
    customReason.value = '';
    cancelForm.clearErrors();
    showCancelModal.value = true;
};

const submitCancel = () => {
    if (!selectedOrderToCancel.value) return;

    if (selectedReason.value === 'Lainnya') {
        if (!customReason.value.trim()) {
            alert("Harap isi alasan pembatalan jika memilih 'Lainnya'.");
            return;
        }
        cancelForm.reason = customReason.value;
    } else {
        cancelForm.reason = selectedReason.value;
    }

    cancelForm.post(`/orders/${selectedOrderToCancel.value.id}/cancel`, {
        preserveScroll: true,
        onSuccess: () => {
            showCancelModal.value = false;
            selectedOrderToCancel.value = null;
            cancelForm.reset();
            customReason.value = '';
        }
    });
};

// --- LOGIC RETUR ---
const openReturnModal = (order) => {
    selectedOrderToReturn.value = order;
    returnForm.reset();
    returnForm.clearErrors();
    showReturnModal.value = true;
};

const submitReturn = () => {
    if (!selectedOrderToReturn.value) return;
    if (!returnForm.evidence) {
        alert("Wajib upload bukti video/foto.");
        return;
    }

    returnForm.post(`/orders/${selectedOrderToReturn.value.id}/return`, {
        preserveScroll: true,
        onSuccess: () => {
            showReturnModal.value = false;
            selectedOrderToReturn.value = null;
            returnForm.reset();
        }
    });
};

// --- TRACKING & REVIEW & PAYMENT ---
const showTrackingModal = ref(false);
const trackingData = ref(null);
const isLoadingTrack = ref(false);
const trackingError = ref('');

const openTracking = async (order) => {
    if (!order.shipping_tracking_number) return;
    showTrackingModal.value = true;
    isLoadingTrack.value = true;
    trackingData.value = null;
    trackingError.value = '';
    try {
        const response = await axios.get(`/api/orders/${order.id}/track`);
        trackingData.value = response.data.data;
    } catch (error) {
        trackingError.value = error.response?.data?.message || "Gagal melacak paket.";
    } finally {
        isLoadingTrack.value = false;
    }
};

const showReviewModal = ref(false);
const selectedProduct = ref(null);
const selectedOrderId = ref(null);
const openReview = (item, orderId) => {
    selectedProduct.value = {
        id: item.product_variant?.product_id || item.product_id, 
        name: item.product_name,
        image: item.product_variant?.product?.image_url
    };
    selectedOrderId.value = orderId;
    showReviewModal.value = true;
};

const payNow = (snapToken) => {
    if (window.snap && snapToken) {
        window.snap.pay(snapToken, {
            onSuccess: () => router.reload(),
            onPending: () => router.reload(),
            onError: () => alert("Pembayaran Gagal")
        });
    }
};
</script>

<template>
    <Head title="Pesanan Saya" />

    <div class="min-h-screen bg-gradient-to-br from-rose-50 to-pink-50 py-8">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <div class="mb-8">
                <h1 class="text-3xl font-light text-gray-900 mb-2">Pesanan Saya</h1>
                <p class="text-gray-600">Riwayat belanja Anda di SkinLabBeauty</p>
            </div>

            <div class="bg-white rounded-2xl shadow border border-gray-100 p-2 mb-6 overflow-x-auto">
                <div class="flex space-x-2 min-w-max">
                    <button 
                        v-for="tab in tabs" 
                        :key="tab.id"
                        @click="filterStatus(tab.id)"
                        class="px-6 py-2 rounded-xl text-sm font-medium transition-all duration-200"
                        :class="currentStatus === tab.id 
                            ? 'bg-rose-600 text-white shadow-md shadow-rose-200' 
                            : 'text-gray-600 hover:bg-rose-50'"
                    >
                        {{ tab.label }}
                    </button>
                </div>
            </div>

            <div v-if="allOrders.length === 0" class="bg-white rounded-2xl shadow border border-gray-100 p-12 text-center">
                <div class="text-6xl mb-4">📦</div>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">Belum ada pesanan</h3>
                <Link href="/" class="inline-flex items-center px-6 py-3 bg-rose-600 text-white font-semibold rounded-lg hover:bg-rose-700 transition-colors mt-4">
                    Mulai Belanja
                </Link>
            </div>

            <div v-else class="space-y-6">
                <div v-for="order in allOrders" :key="order.id" class="bg-white rounded-2xl shadow border border-gray-100 overflow-hidden">
                    
                    <div class="p-6 border-b border-gray-100 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-gray-50">
                        <div>
                            <div class="flex items-center gap-3 mb-1">
                                <span class="font-mono font-bold text-gray-900 text-lg">{{ order.order_number }}</span>
                                <span class="text-sm text-gray-500">{{ formatDate(order.created_at) }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="px-3 py-1 rounded-full text-xs font-bold border uppercase tracking-wide" :class="getStatusClass(order.order_status)">
                                    {{ getStatusLabel(order.order_status) }}
                                </span>
                                <span v-if="order.shipping_tracking_number" class="text-sm font-mono text-gray-600 bg-gray-200 px-2 py-1 rounded">
                                    Resi: {{ order.shipping_tracking_number }}
                                </span>
                            </div>
                        </div>

                        <div class="flex gap-3">
                            <button v-if="order.order_status === 'pending' && order.snap_token" 
                                @click="payNow(order.snap_token)"
                                class="px-4 py-2 bg-rose-600 text-white rounded-lg hover:bg-rose-700 text-sm font-bold shadow-lg shadow-rose-200">
                                Bayar Sekarang
                            </button>

                            <button v-if="['shipped', 'completed'].includes(order.order_status) && order.shipping_tracking_number" 
                                @click="openTracking(order)"
                                class="px-4 py-2 bg-white border border-rose-600 text-rose-600 rounded-lg hover:bg-rose-50 text-sm font-bold flex items-center gap-2">
                                🚚 Lacak Paket
                            </button>

                            <button 
                                v-if="['pending', 'paid'].includes(order.order_status)"
                                @click="openCancelModal(order)"
                                class="px-4 py-2 bg-white border border-gray-300 text-gray-600 rounded-lg hover:bg-gray-50 hover:text-red-600 hover:border-red-300 text-sm font-medium transition-colors"
                            >
                                Batalkan
                            </button>
                            
                            <button 
                                v-if="order.order_status === 'completed'"
                                @click="openReturnModal(order)"
                                class="px-4 py-2 bg-white border border-orange-300 text-orange-600 rounded-lg hover:bg-orange-50 hover:text-orange-700 text-sm font-medium transition-colors"
                            >
                                Ajukan Retur
                            </button>
                        </div>
                    </div>

                    <div class="p-6">
                        <div class="space-y-4">
                            <div v-for="item in order.items" :key="item.id" class="flex items-center gap-4">
                                <img :src="item.product_variant?.product?.image_url || '/images/default-product.png'" class="w-16 h-16 rounded-lg bg-gray-100 object-cover border border-gray-200" />
                                <div class="flex-1">
                                    <h4 class="font-semibold text-gray-900">{{ item.product_name }}</h4>
                                    <p class="text-sm text-gray-500">Qty: {{ item.quantity }}</p>
                                </div>
                                <div class="text-right flex flex-col items-end gap-2">
                                    <p class="font-bold text-gray-900">{{ formatCurrency(item.price) }}</p>
                                    <button 
                                        v-if="order.order_status === 'completed'"
                                        @click="openReview(item, order.id)"
                                        class="text-xs font-bold text-rose-600 hover:text-rose-700 border border-rose-200 bg-rose-50 px-3 py-1.5 rounded-lg transition-colors hover:bg-rose-100"
                                    >
                                        ⭐ Beri Ulasan
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-between items-center">
                        <div class="text-sm text-gray-600">
                            Kurir: <span class="font-bold uppercase">{{ order.shipping_courier || 'Reguler' }}</span>
                        </div>
                        <div class="text-right">
                            <span class="text-sm text-gray-500 mr-2">Total Pesanan</span>
                            <span class="text-xl font-bold text-rose-600">{{ formatCurrency(order.total_amount) }}</span>
                        </div>
                    </div>
                </div>

                <div class="mt-8 text-center pb-8">
                    <div v-if="hasMorePages" ref="observerTarget" class="h-10 flex items-center justify-center">
                         <div class="w-6 h-6 border-2 border-rose-200 border-t-rose-600 rounded-full animate-spin"></div>
                    </div>
                    <div v-if="isLoading" class="flex justify-center items-center space-x-3 text-rose-600 py-4">
                        <span class="text-sm">Memuat pesanan lainnya...</span>
                    </div>
                    <div v-if="!hasMorePages && !isLoading && allOrders.length > 0" class="text-gray-400 text-sm">
                        🎉 Semua pesanan sudah ditampilkan
                    </div>
                </div>
            </div>
        </div>

        <div v-if="showCancelModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4" @click.self="showCancelModal = false">
            <div class="bg-white w-full max-w-lg rounded-2xl shadow-2xl overflow-hidden animate-fade-in-up">
                
                <div class="bg-red-50 border-b border-red-100 px-6 py-4">
                    <h3 class="font-bold text-lg text-red-700">Pembatalan Pesanan</h3>
                </div>

                <div class="p-6">
                    <p class="text-gray-600 mb-4 text-sm">
                        Mohon pilih alasan pembatalan untuk pesanan <span class="font-bold text-gray-900">{{ selectedOrderToCancel?.order_number }}</span>:
                        <span v-if="selectedOrderToCancel?.order_status === 'paid'" class="block mt-2 text-orange-600 font-semibold text-xs bg-orange-50 p-2 rounded border border-orange-200">
                            ⚠️ Dana sudah masuk. Pembatalan memerlukan persetujuan Admin untuk proses pengembalian dana.
                        </span>
                    </p>

                    <div class="space-y-3 mb-4">
                        <div v-for="(option, index) in cancelOptions" :key="index" class="flex items-center">
                            <input type="radio" :id="'cancel-option-' + index" :value="option" v-model="selectedReason" class="w-4 h-4 text-rose-600 border-gray-300 focus:ring-rose-500">
                            <label :for="'cancel-option-' + index" class="ml-2 text-sm text-gray-900 cursor-pointer">
                                {{ option }}
                            </label>
                        </div>
                    </div>

                    <div v-if="selectedReason === 'Lainnya'" class="mb-4 animate-fade-in-up">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tuliskan Alasan Anda</label>
                        <textarea 
                            v-model="customReason" 
                            rows="2" 
                            class="w-full border-gray-300 rounded-lg shadow-sm focus:border-rose-500 focus:ring-rose-500 text-sm text-gray-900 bg-white" 
                            placeholder="Jelaskan alasan pembatalan...">
                        </textarea>
                        <p v-if="cancelForm.errors.reason" class="text-red-500 text-xs mt-1">{{ cancelForm.errors.reason }}</p>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                        <button @click="showCancelModal = false" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 text-sm font-medium">Tidak Jadi</button>
                        <button @click="submitCancel" :disabled="cancelForm.processing" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 text-sm font-bold shadow-lg shadow-red-200 disabled:opacity-50 flex items-center gap-2">
                            <span v-if="cancelForm.processing">Memproses...</span>
                            <span v-else>Konfirmasi Batal</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="showReturnModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4" @click.self="showReturnModal = false">
            <div class="bg-white w-full max-w-lg rounded-2xl shadow-2xl overflow-hidden animate-fade-in-up">
                
                <div class="bg-orange-50 border-b border-orange-100 px-6 py-4">
                    <h3 class="font-bold text-lg text-orange-800">Ajukan Pengembalian Barang</h3>
                </div>

                <div class="p-6 max-h-[80vh] overflow-y-auto">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Alasan Pengembalian</label>
                        <select v-model="returnForm.reason" class="w-full border-gray-300 rounded-lg text-sm focus:ring-orange-500 focus:border-orange-500 text-gray-900 bg-white">
                            <option>Barang Rusak / Tidak Berfungsi</option>
                            <option>Produk Tidak Sesuai Foto</option>
                            <option>Salah Kirim Barang</option>
                            <option>Paket Tidak Lengkap</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Solusi yang Diinginkan</label>
                        <div class="flex gap-4">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" value="refund" v-model="returnForm.solution" class="text-orange-600 focus:ring-orange-500">
                                <span class="text-sm text-gray-900">Refund Dana</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" value="exchange" v-model="returnForm.solution" class="text-orange-600 focus:ring-orange-500">
                                <span class="text-sm text-gray-900">Tukar Barang Baru</span>
                            </label>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Detail Masalah</label>
                        <textarea v-model="returnForm.description" rows="3" 
                            class="w-full border-gray-300 rounded-lg text-sm focus:ring-orange-500 focus:border-orange-500 text-gray-900 bg-white" 
                            placeholder="Ceritakan detail kerusakan...">
                        </textarea>
                        <p v-if="returnForm.errors.description" class="text-red-500 text-xs mt-1">{{ returnForm.errors.description }}</p>
                    </div>

                    <div class="bg-gray-50 p-4 rounded-lg border border-dashed border-gray-300 mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Upload Video Unboxing (Wajib)
                        </label>
                        <input 
                            type="file" 
                            accept="image/*,video/mp4,video/x-m4v,video/*"
                            @input="returnForm.evidence = $event.target.files[0]" 
                            class="block w-full text-sm text-gray-900 file:mr-4 file:py-2 file:px-4 file:rounded-full file:bg-orange-100 file:text-orange-700 file:border-0 hover:file:bg-orange-200 cursor-pointer"
                        />
                        <p class="text-xs text-gray-500 mt-2">Max size: 50MB. Format: MP4, MOV, JPG.</p>
                        <p v-if="returnForm.errors.evidence" class="text-red-500 text-xs mt-1">{{ returnForm.errors.evidence }}</p>

                        <div v-if="returnForm.progress" class="w-full bg-gray-200 rounded-full h-2.5 mt-3">
                            <div class="bg-orange-600 h-2.5 rounded-full transition-all duration-300" :style="{ width: returnForm.progress.percentage + '%' }"></div>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                        <button @click="showReturnModal = false" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 text-sm font-medium">Batal</button>
                        <button 
                            @click="submitReturn" 
                            :disabled="returnForm.processing"
                            class="px-4 py-2 bg-orange-600 text-white rounded-lg font-bold hover:bg-orange-700 disabled:opacity-50 flex items-center gap-2 text-sm"
                        >
                            <span v-if="returnForm.processing">Mengupload...</span>
                            <span v-else>Kirim Pengajuan</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="showTrackingModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4" @click.self="showTrackingModal = false">
            <div class="bg-white w-full max-w-lg rounded-2xl shadow-2xl overflow-hidden animate-fade-in-up">
                <div class="bg-rose-600 px-6 py-4 flex justify-between items-center text-white">
                    <h3 class="font-bold text-lg flex items-center gap-2">
                        <span>🚚</span> Lacak Paket
                    </h3>
                    <button @click="showTrackingModal = false" class="hover:bg-rose-700 rounded-full p-1">✕</button>
                </div>
                <div class="p-6 max-h-[70vh] overflow-y-auto">
                    <div v-if="trackingData">
                         <div class="bg-gray-50 p-4 rounded-xl border border-gray-200 text-center mb-6">
                            <p class="font-mono text-2xl font-bold text-gray-900">{{ trackingData.airway_bill }}</p>
                        </div>
                        <div class="space-y-4">
                             <div v-for="(log, index) in trackingData.history" :key="index">
                                <p class="text-xs text-gray-400">{{ log.date }}</p>
                                <p class="font-medium text-gray-800">{{ log.desc }}</p>
                             </div>
                        </div>
                    </div>
                    <div v-else class="text-center py-4 text-gray-500">Memuat...</div>
                </div>
            </div>
        </div>

        <ReviewModal 
            :show="showReviewModal" 
            :product="selectedProduct" 
            :orderId="selectedOrderId" 
            @close="showReviewModal = false" 
        />

    </div>
</template>

<style scoped>
.animate-fade-in-up { animation: fadeInUp 0.3s ease-out forwards; }
@keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
</style>