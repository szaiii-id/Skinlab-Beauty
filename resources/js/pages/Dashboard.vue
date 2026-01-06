<script setup>
import { ref, onMounted } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import axios from 'axios';
import DashboardLayout from '@/layouts/DashboardLayout.vue';

// Components
import DashboardHero from '@/components/DashboardHero.vue';
import DashboardStats from '@/components/DashboardStats.vue';
import RecentOrders from '@/components/RecentOrders.vue';
import TrackingModal from '@/components/TrackingModal.vue';

defineOptions({ layout: DashboardLayout });

const props = defineProps({
    auth: Object,
    recentOrders: Array,
    stats: Object,
    midtrans_client_key: String
});

// --- TRACKING LOGIC ---
const isTrackingModalOpen = ref(false);
const trackingData = ref(null);
const isLoadingTrack = ref(false);

const handleTrackPackage = async (order) => {
    if (!order.shipping_tracking_number) return;
    
    isTrackingModalOpen.value = true;
    isLoadingTrack.value = true;
    trackingData.value = null;

    try {
        const response = await axios.get(`/api/orders/${order.id}/track`);
        trackingData.value = response.data.data;
    } catch (e) {
        trackingData.value = { error: "Gagal melacak paket. Silakan coba lagi nanti." };
    } finally {
        isLoadingTrack.value = false;
    }
};

// --- PAYMENT LOGIC ---
const handlePayNow = (snapToken) => {
    if (window.snap && snapToken) {
        window.snap.pay(snapToken, {
            onSuccess: () => router.reload(),
            onPending: () => router.reload(),
            onError: () => alert("Pembayaran Gagal")
        });
    }
};

onMounted(() => {
    const script = document.createElement('script');
    script.src = 'https://app.sandbox.midtrans.com/snap/snap.js';
    script.setAttribute('data-client-key', props.midtrans_client_key);
    document.head.appendChild(script);
});
</script>

<template>
    <Head title="Dashboard" />

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 md:py-10">
        
        <div class="space-y-6 md:space-y-10">
            
            <DashboardHero 
                :user="auth.user" 
                :points="stats.points" 
            />

            <DashboardStats 
                :stats="stats" 
            />

            <RecentOrders 
                :orders="recentOrders" 
                @track="handleTrackPackage"
                @pay="handlePayNow"
            />
            
        </div>

        <TrackingModal 
            :show="isTrackingModalOpen" 
            :loading="isLoadingTrack"
            :data="trackingData"
            @close="isTrackingModalOpen = false"
        />

    </div>
</template>