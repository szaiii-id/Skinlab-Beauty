<script setup>
// --- FIX IMPORT DI SINI ---
import { computed, onMounted } from 'vue'; 
import { Link, usePage } from '@inertiajs/vue3';
import { Swiper, SwiperSlide } from 'swiper/vue';
import { Autoplay, Pagination, Navigation } from 'swiper/modules';

// Import CSS Swiper
import 'swiper/css';
import 'swiper/css/pagination';
import 'swiper/css/navigation';

const page = usePage();

// Logic aman untuk mengambil data slides
const slides = computed(() => {
    // Ambil data dari props Inertia
    const banners = page.props.promoBanners;
    
    // Cek 1: Jika format Resource (ada bungkus .data)
    if (banners && banners.data) {
        return banners.data;
    }
    // Cek 2: Jika format Array biasa
    if (Array.isArray(banners)) {
        return banners;
    }
    // Default kosong
    return [];
});

const modules = [Autoplay, Pagination, Navigation];

// --- DEBUGGING (Sekarang onMounted sudah di-import, jadi tidak akan error) ---
onMounted(() => {
    console.log("Data Banner dari Inertia:", page.props.promoBanners);
    console.log("Slides yang akan dirender:", slides.value);
});
</script>

<template>
    <div v-if="slides && slides.length > 0" class="relative w-full overflow-hidden rounded-xl shadow-lg">
        
        <Swiper
            :key="slides.length" 
            :modules="modules"
            :slides-per-view="1"
            :space-between="0"
            :loop="true"
            :autoplay="{
                delay: 5000, 
                disableOnInteraction: false,
            }"
            :pagination="{ clickable: true }"
            :navigation="true"
        >
            <SwiperSlide v-for="slide in slides" :key="slide.id" class="relative">
                <Link :href="route('products.promo', slide.id)">
                    
                    <div class="relative w-full min-h-96 md:min-h-[400px] bg-gray-100 flex items-center justify-center">
                        <img 
                            :src="slide.image" 
                            :alt="slide.title" 
                            class="w-full h-full object-cover absolute top-0 left-0" 
                        />
                    </div>

                    <div class="absolute inset-0 flex items-center justify-center text-white p-4" 
                         style="background: linear-gradient(rgba(0,0,0,0.2), rgba(0,0,0,0.5));">
                        <div class="text-center">
                            <h2 class="text-4xl md:text-5xl font-extrabold drop-shadow-md">
                                {{ slide.title }}
                            </h2>
                            <p class="mt-2 text-lg md:text-xl drop-shadow-md font-medium">
                                {{ slide.subtitle }}
                            </p>
                            <button class="mt-6 px-6 py-3 bg-white text-rose-600 font-semibold rounded-full shadow-lg hover:bg-gray-100 transition-colors">
                                Shop Now
                            </button>
                        </div>
                    </div>
                </Link>
            </SwiperSlide>
        </Swiper>
    </div>
</template>

<style>
.swiper {
    --swiper-navigation-color: #f43f5e;
    --swiper-pagination-color: #f43f5e;
}
.swiper-button-next,
.swiper-button-prev {
    color: rgba(255, 255, 255, 0.7) !important;
    background-color: rgba(0, 0, 0, 0.2);
    border-radius: 50%;
    width: 40px !important;
    height: 40px !important;
}
.swiper-button-next:after,
.swiper-button-prev:after {
    font-size: 18px !important;
    font-weight: bold;
}
</style>