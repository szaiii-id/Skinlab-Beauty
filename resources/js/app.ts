import '../css/app.css';

import 'swiper/css';
import 'swiper/css/pagination'; 
import 'swiper/css/navigation'; 

// --- BAGIAN BARU: KONFIGURASI AXIOS (UNTUK FIX LOGIN/API) ---
import axios from 'axios';

// Definisi tipe agar TypeScript tidak error
declare global {
    interface Window {
        axios: typeof axios;
    }
}

window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
// Wajib TRUE agar cookie session terbaca di Ngrok/Localhost
window.axios.defaults.withCredentials = true; 
// ------------------------------------------------------------

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import { initializeTheme } from './composables/useAppearance';

// --- KODE ASLI ANDA TETAP ADA ---
import { ZiggyVue } from 'ziggy-js';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) =>
        resolvePageComponent(
            `./pages/${name}.vue`,
            import.meta.glob<DefineComponent>('./pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue) // Ziggy tetap ada agar route() di Vue jalan
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on page load...
initializeTheme();