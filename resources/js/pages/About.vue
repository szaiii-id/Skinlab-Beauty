<script setup>
import { Head } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import AppNavbarLayout from '@/layouts/app/AppNavbarLayout.vue';

defineOptions({
    layout: AppNavbarLayout
});

// State untuk animasi counter
const counters = ref({
    products: 0,
    customers: 0,
    years: 0
});

const targetCounters = {
    products: 150,
    customers: 10000,
    years: 2
};

// Animasi counter
const animateCounters = () => {
    const duration = 2000; // 2 detik
    const steps = 60;
    const stepDuration = duration / steps;

    Object.keys(targetCounters).forEach(key => {
        let current = 0;
        const increment = targetCounters[key] / steps;
        
        const timer = setInterval(() => {
            current += increment;
            if (current >= targetCounters[key]) {
                current = targetCounters[key];
                clearInterval(timer);
            }
            counters.value[key] = Math.floor(current);
        }, stepDuration);
    });
};

// Intersection Observer untuk animasi scroll
const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
};

const animatedElements = ref([]);

onMounted(() => {
    // Start counter animation
    animateCounters();

    // Setup intersection observer untuk animasi scroll
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-fade-in-up');
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);

    // Observe semua elements dengan class animate-on-scroll
    document.querySelectorAll('.animate-on-scroll').forEach(el => {
        observer.observe(el);
    });
});
</script>

<template>
    <Head title="About SkinLab Beauty" />

    <section class="relative bg-gradient-to-br from-rose-50 to-pink-100 py-12 lg:py-20 overflow-hidden">
        <div class="absolute inset-0 bg-white/20"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-12 items-center">
                <div class="animate-on-scroll text-center lg:text-left order-2 lg:order-1">
                    <h1 class="text-4xl lg:text-5xl xl:text-6xl font-light text-gray-900 mb-4 lg:mb-6 leading-tight">
                        Beauty Through<br>
                        <span class="text-rose-600 font-normal">Science & Care</span>
                    </h1>
                    <p class="text-base lg:text-xl text-gray-600 mb-6 lg:mb-8 leading-relaxed max-w-2xl mx-auto lg:mx-0">
                        Where innovative skincare meets personalized beauty experiences. 
                        We're committed to transforming your routine into a ritual of self-care.
                    </p>
                    <div class="flex flex-col sm:flex-row flex-wrap gap-3 lg:gap-4 justify-center lg:justify-start">
                        <button class="bg-rose-600 text-white px-6 lg:px-8 py-3 rounded-full font-medium hover:bg-rose-700 transition-all duration-300 transform hover:scale-105 shadow-lg w-full sm:w-auto text-sm lg:text-base">
                            Discover Our Story
                        </button>
                        <button class="border border-rose-600 text-rose-600 px-6 lg:px-8 py-3 rounded-full font-medium hover:bg-rose-600 hover:text-white transition-all duration-300 w-full sm:w-auto text-sm lg:text-base">
                            Meet Our Team
                        </button>
                    </div>
                </div>
                <div class="animate-on-scroll order-1 lg:order-2 mb-8 lg:mb-0">
                    <div class="relative">
                        <div class="absolute -inset-4 bg-gradient-to-r from-rose-400 to-pink-400 rounded-2xl blur-lg opacity-20"></div>
                        <img 
                            src="/images/about-hero.jpg" 
                            alt="SkinLab Beauty Products"
                            class="relative rounded-2xl shadow-2xl w-full h-64 sm:h-80 lg:h-96 object-cover"
                        />
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-12 lg:py-16 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 text-center divide-y md:divide-y-0 divide-gray-100">
                <div class="animate-on-scroll p-6">
                    <div class="text-4xl lg:text-5xl font-bold text-rose-600 mb-2">
                        {{ counters.products }}+
                    </div>
                    <div class="text-gray-600 font-medium">Curated Products</div>
                    <p class="text-sm text-gray-500 mt-2">Meticulously selected for quality</p>
                </div>
                <div class="animate-on-scroll p-6">
                    <div class="text-4xl lg:text-5xl font-bold text-rose-600 mb-2">
                        {{ counters.customers.toLocaleString() }}+
                    </div>
                    <div class="text-gray-600 font-medium">Happy Customers</div>
                    <p class="text-sm text-gray-500 mt-2">Transforming skincare routines</p>
                </div>
                <div class="animate-on-scroll p-6">
                    <div class="text-4xl lg:text-5xl font-bold text-rose-600 mb-2">
                        {{ counters.years }}+
                    </div>
                    <div class="text-gray-600 font-medium">Years of Excellence</div>
                    <p class="text-sm text-gray-500 mt-2">Dedicated to beauty innovation</p>
                </div>
            </div>
        </div>
    </section>

    <section class="py-12 lg:py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 lg:gap-16 items-center">
                <div class="animate-on-scroll">
                    <div class="space-y-6 text-center lg:text-left">
                        <div class="inline-flex items-center px-4 py-2 rounded-full bg-rose-100 text-rose-600 text-sm font-medium mb-4">
                            Our Journey
                        </div>
                        <h2 class="text-3xl lg:text-4xl font-light text-gray-900">
                            Redefining Beauty Standards
                        </h2>
                        <p class="text-base lg:text-lg text-gray-600 leading-relaxed">
                            Founded with a vision to bridge the gap between scientific innovation and 
                            holistic beauty care, SkinLab Beauty emerged from a simple belief: 
                            everyone deserves access to effective, safe, and luxurious skincare.
                        </p>
                        <p class="text-base lg:text-lg text-gray-600 leading-relaxed">
                            Our team of skincare experts, chemists, and beauty enthusiasts work 
                            tirelessly to curate products that deliver real results while embracing 
                            the joy of self-care rituals.
                        </p>
                        <div class="pt-6">
                            <div class="flex flex-wrap justify-center lg:justify-start gap-4 text-sm text-gray-500">
                                <div class="flex items-center space-x-1 bg-white px-3 py-1.5 rounded-lg shadow-sm">
                                    <svg class="w-4 h-4 text-rose-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                    <span>Science-Backed</span>
                                </div>
                                <div class="flex items-center space-x-1 bg-white px-3 py-1.5 rounded-lg shadow-sm">
                                    <svg class="w-4 h-4 text-rose-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                    <span>Cruelty-Free</span>
                                </div>
                                <div class="flex items-center space-x-1 bg-white px-3 py-1.5 rounded-lg shadow-sm">
                                    <svg class="w-4 h-4 text-rose-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                    <span>Clean Ingredients</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="animate-on-scroll">
                    <div class="grid grid-cols-2 gap-3 lg:gap-4">
                        <div class="space-y-3 lg:space-y-4">
                            <img 
                                src="/images/about-1.jpg" 
                                alt="Skincare Laboratory"
                                class="rounded-2xl shadow-lg w-full h-32 sm:h-48 object-cover"
                            />
                            <img 
                                src="/images/about-2.jpg" 
                                alt="Product Testing"
                                class="rounded-2xl shadow-lg w-full h-24 sm:h-32 object-cover"
                            />
                        </div>
                        <div class="space-y-3 lg:space-y-4 pt-4 lg:pt-8">
                            <img 
                                src="/images/about-3.jpg" 
                                alt="Natural Ingredients"
                                class="rounded-2xl shadow-lg w-full h-24 sm:h-32 object-cover"
                            />
                            <img 
                                src="/images/about-4.jpg" 
                                alt="Beauty Routine"
                                class="rounded-2xl shadow-lg w-full h-32 sm:h-48 object-cover"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-12 lg:py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-10 lg:mb-16 animate-on-scroll">
                <div class="inline-flex items-center px-4 py-2 rounded-full bg-rose-100 text-rose-600 text-sm font-medium mb-4">
                    Our Promise
                </div>
                <h2 class="text-3xl lg:text-4xl font-light text-gray-900 mb-6">
                    Values That Define Us
                </h2>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 lg:gap-8">
                <div class="animate-on-scroll group p-6 lg:p-8 rounded-2xl bg-gradient-to-br from-rose-50 to-pink-50 hover:from-rose-100 hover:to-pink-100 transition-all duration-300 cursor-pointer">
                    <div class="w-12 h-12 bg-rose-600 rounded-lg flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-4">Scientific Innovation</h3>
                    <p class="text-gray-600 leading-relaxed text-sm lg:text-base">
                        We combine cutting-edge research with proven ingredients to create 
                        products that deliver visible, lasting results.
                    </p>
                </div>
                
                <div class="animate-on-scroll group p-6 lg:p-8 rounded-2xl bg-gradient-to-br from-rose-50 to-pink-50 hover:from-rose-100 hover:to-pink-100 transition-all duration-300 cursor-pointer">
                    <div class="w-12 h-12 bg-rose-600 rounded-lg flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-4">Personalized Care</h3>
                    <p class="text-gray-600 leading-relaxed text-sm lg:text-base">
                        Your skin is unique. We provide personalized recommendations and 
                        support to help you find your perfect routine.
                    </p>
                </div>
                
                <div class="animate-on-scroll group p-6 lg:p-8 rounded-2xl bg-gradient-to-br from-rose-50 to-pink-50 hover:from-rose-100 hover:to-pink-100 transition-all duration-300 cursor-pointer">
                    <div class="w-12 h-12 bg-rose-600 rounded-lg flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9v-9m0-9v9"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-4">Sustainable Beauty</h3>
                    <p class="text-gray-600 leading-relaxed text-sm lg:text-base">
                        Committed to eco-friendly practices, from responsibly sourced ingredients 
                        to sustainable packaging solutions.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <section class="py-16 lg:py-20 bg-gradient-to-r from-rose-600 to-pink-600 text-white">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <div class="animate-on-scroll">
                <h2 class="text-3xl lg:text-4xl font-light mb-4 lg:mb-6">
                    Ready to Transform Your Skin?
                </h2>
                <p class="text-lg lg:text-xl text-rose-100 mb-8 max-w-2xl mx-auto">
                    Join thousands of customers who have discovered their perfect skincare routine with SkinLab Beauty.
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <button class="bg-white text-rose-600 px-8 py-4 rounded-full font-semibold hover:bg-rose-50 transition-all duration-300 transform hover:scale-105 shadow-lg w-full sm:w-auto">
                        Shop Our Collection
                    </button>
                    <button class="border border-white text-white px-8 py-4 rounded-full font-semibold hover:bg-white hover:text-rose-600 transition-all duration-300 w-full sm:w-auto">
                        Get Personalized Advice
                    </button>
                </div>
            </div>
        </div>
    </section>
</template>

<style scoped>
/* Animasi untuk scroll */
.animate-on-scroll {
    opacity: 0;
    transform: translateY(30px);
    transition: all 0.6s ease-out;
}

.animate-fade-in-up {
    opacity: 1;
    transform: translateY(0);
}

/* Smooth transitions untuk hover effects */
.group:hover .group-hover\:scale-110 {
    transform: scale(1.1);
}
</style>