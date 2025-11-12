<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
// Gunakan layout navbar Anda
import AppNavbarLayout from '@/layouts/app/AppNavbarLayout.vue';
import { Mail, Phone, MapPin } from 'lucide-vue-next';

defineOptions({
    layout: AppNavbarLayout
});

// Form handling untuk formulir kontak
const form = useForm({
    name: '',
    email: '',
    message: ''
});

const submitContactForm = () => {
    // Nanti, kita akan buat rute POST /contact untuk mengirim email
    // form.post('/contact', { ... });
    alert('Message sent (simulation)!');
    form.reset();
};
</script>

<template>
    <Head title="Contact Us" />

    <div class="bg-white py-16 md:py-24">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">

            <section class="text-center mb-16">
                <h1 class="text-5xl font-extrabold text-gray-900 tracking-tighter">
                    Get in Touch
                </h1>
                <p class="mt-4 text-lg text-gray-600 max-w-2xl mx-auto">
                    We'd love to hear from you. Whether you have a question about our products, 
                    your order, or just want to say hi, feel free to reach out.
                </p>
            </section>

            <section class="mb-16">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div>
                        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-rose-100 text-rose-600">
                            <Mail class="h-6 w-6" />
                        </div>
                        <h3 class="mt-4 text-xl font-semibold text-center text-gray-900">Email Us</h3>
                        <p class="mt-1 text-gray-600 text-center">
                            Reach our support team for any questions.
                        </p>
                        <a href="mailto:support@skinlab.test" class="block text-center mt-2 text-rose-600 hover:underline">
                            support@skinlab.test
                        </a>
                    </div>
                    
                    <div>
                        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-rose-100 text-rose-600">
                            <Phone class="h-6 w-6" />
                        </div>
                        <h3 class="mt-4 text-xl font-semibold text-center text-gray-900">Call Us</h3>
                        <p class="mt-1 text-gray-600 text-center">
                            Monday - Saturday
                        </p>
                        <p class="mt-1 text-gray-600 text-center">
                            09:00 AM - 05:00 PM
                        </p>
                        <p class="text-center mt-2 text-rose-600">
                            +62 123 456 789
                        </p>
                    </div>

                    <div>
                        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-rose-100 text-rose-600">
                            <MapPin class="h-6 w-6" />
                        </div>
                        <h3 class="mt-4 text-xl font-semibold text-center text-gray-900">Visit Us</h3>
                        <p class="mt-1 text-gray-600 text-center">
                            123 Beauty St, Jakarta, Indonesia
                        </p>
                    </div>
                </div>
            </section>

            <section class="max-w-2xl mx-auto">
                <div class="bg-gray-50 p-8 rounded-lg shadow-lg">
                    <h2 class="text-2xl font-semibold text-gray-900 text-center mb-6">Send us a message</h2>
                    <form @submit.prevent="submitContactForm" class="space-y-6">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700">Your Name</label>
                            <input 
                                v-model="form.name"
                                type="text" 
                                id="name" 
                                required
                                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-3 focus:ring-rose-500 focus:border-rose-500 text-gray-900"
                            />
                        </div>
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700">Your Email</label>
                            <input 
                                v-model="form.email"
                                type="email" 
                                id="email" 
                                required
                                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-3 focus:ring-rose-500 focus:border-rose-500 text-gray-900"
                            />
                        </div>
                        <div>
                            <label for="message" class="block text-sm font-medium text-gray-700">Message</label>
                            <textarea 
                                v-model="form.message"
                                id="message" 
                                rows="6" 
                                required
                                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-3 focus:ring-rose-500 focus:border-rose-500 text-gray-900"
                            ></textarea>
                        </div>
                        <div>
                            <button 
                                type="submit"
                                class="w-full px-6 py-3 bg-rose-600 text-white font-semibold rounded-md shadow-lg hover:bg-rose-700 transition-colors"
                            >
                                Send Message
                            </button>
                        </div>
                    </form>
                </div>
            </section>
            
        </div>
    </div>
</template>