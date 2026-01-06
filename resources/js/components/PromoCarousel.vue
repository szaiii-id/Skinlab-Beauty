<script setup>
import { computed, onMounted } from 'vue'; 
import { Link, usePage } from '@inertiajs/vue3';
import { Swiper, SwiperSlide } from 'swiper/vue';
import { Autoplay, Pagination, Navigation } from 'swiper/modules';

// Import CSS Swiper
import 'swiper/css';
import 'swiper/css/pagination';
import 'swiper/css/navigation';

const page = usePage();

const slides = computed(() => {
    const banners = page.props.promoBanners;
    if (banners && banners.data) return banners.data;
    if (Array.isArray(banners)) return banners;
    return [];
});

const modules = [Autoplay, Pagination, Navigation];

onMounted(() => {
    // console.log("Data Banner:", slides.value); // Debugging optional
});
</script>

<template>
    <div v-if="slides && slides.length > 0" class="relative w-full overflow-hidden rounded-xl md:rounded-2xl shadow-sm md:shadow-md group">
        
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
            class="h-full w-full"
        >
            <SwiperSlide v-for="slide in slides" :key="slide.id" class="relative">
                <Link :href="route('products.promo', slide.id)" class="block h-full w-full">
                    
                    <div class="relative w-full min-h-[220px] sm:min-h-[300px] md:min-h-[400px] bg-gray-100 flex items-center justify-center overflow-hidden">
                        <img 
                            :src="slide.image" 
                            :alt="slide.title" 
                            class="w-full h-full object-cover absolute top-0 left-0 transition-transform duration-1000 group-hover:scale-105" 
                        />
                        
                        <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-black/20 to-transparent"></div>
                    </div>

                    <div class="absolute inset-0 flex items-end md:items-center justify-start md:justify-center p-6 md:p-12 pb-10">
                        <div class="text-left md:text-center w-full max-w-2xl">
                            <h2 class="text-2xl sm:text-3xl md:text-5xl font-black text-white drop-shadow-lg leading-tight mb-1 md:mb-3">
                                {{ slide.title }}
                            </h2>
                            <p class="text-sm sm:text-base md:text-xl text-white/90 font-medium drop-shadow-md mb-4 md:mb-6 line-clamp-2 md:line-clamp-none">
                                {{ slide.subtitle }}
                            </p>
                            
                            <button class="hidden sm:inline-block px-5 py-2 md:px-8 md:py-3 bg-white text-rose-600 text-xs md:text-sm font-bold uppercase tracking-widest rounded-full shadow-lg hover:bg-rose-50 hover:scale-105 transition-all">
                                Shop Collection
                            </button>
                        </div>
                    </div>
                </Link>
            </SwiperSlide>
        </Swiper>
    </div>
</template>

<style>
/* Custom Pagination Swiper agar warna Pink */
.swiper-pagination-bullet {
    background: #fff !important;
    opacity: 0.5;
    width: 8px;
    height: 8px;
    transition: all 0.3s;
}
.swiper-pagination-bullet-active {
    background: #f43f5e !important; /* Rose-500 */
    opacity: 1;
    width: 20px;
    border-radius: 4px;
}

/* Navigasi Panah: Hanya muncul saat hover di desktop */
.swiper-button-next,
.swiper-button-prev {
    color: #fff !important;
    background-color: rgba(0, 0, 0, 0.3);
    backdrop-filter: blur(4px);
    border-radius: 50%;
    width: 32px !important;
    height: 32px !important;
    opacity: 0; /* Hidden default */
    transition: opacity 0.3s;
}

/* Muncul saat parent di-hover (Hanya Desktop) */
@media (min-width: 768px) {
    .group:hover .swiper-button-next,
    .group:hover .swiper-button-prev {
        opacity: 1;
    }
}

.swiper-button-next:after,
.swiper-button-prev:after {
    font-size: 14px !important;
    font-weight: bold;
}
</style>