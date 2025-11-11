<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { Swiper, SwiperSlide } from 'swiper/vue';
import { Autoplay, Pagination, Navigation } from 'swiper/modules';

// Import CSS Swiper (asumsi sudah ada di app.ts)
import 'swiper/css';
import 'swiper/css/pagination';
import 'swiper/css/navigation';

const slides = computed(() => usePage().props.promoBanners || []);
const modules = [Autoplay, Pagination, Navigation];
</script>

<template>
    <div v-if="slides.length > 0" class="relative w-full overflow-hidden rounded-xl shadow-lg">
        
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
                <Link :href="slide.link_url">
                    
                    <div class="relative w-full min-h-96 md:min-h-[400px] bg-rose-100 flex items-center justify-center">
                        <img 
                            :src="slide.image_url" 
                            :alt="slide.title" 
                            class="w-full h-full object-cover absolute top-0 left-0" 
                        />
                    </div>

                    <div class="absolute inset-0 flex items-center justify-center text-white p-4" 
                         style="background: linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.4));">
                        <div class="text-center">
                            <h2 class="text-4xl md:text-5xl font-extrabold drop-shadow-md">
                                {{ slide.title }}
                            </h2>
                            <p class="mt-2 text-lg md:text-xl drop-shadow-md">
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
/* Kustomisasi CSS untuk tema Swiper */
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