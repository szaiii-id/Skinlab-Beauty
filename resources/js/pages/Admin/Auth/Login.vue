<script setup lang="ts">
import { ref } from 'vue';
import { useForm, Head } from '@inertiajs/vue3';

// 1. Initialize Form State
const form = useForm({
    email: '',
    password: '',
});

// 2. Local State
const showPassword = ref(false);
const clientErrors = ref({ email: '', password: '' });

// 3. Client-side Validation
const validateForm = () => {
    let isValid = true;
    clientErrors.value.email = '';
    clientErrors.value.password = '';

    if (!form.email) {
        clientErrors.value.email = 'Official email is required.';
        isValid = false;
    }

    if (!form.password) {
        clientErrors.value.password = 'Password is required.';
        isValid = false;
    }

    return isValid;
};

// 4. Submit Handler
const submit = () => {
    if (!validateForm()) return;

    form.post(route('admin.login.submit'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Admin Portal Access" />

    <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-pink-50 to-rose-100 relative overflow-hidden">
        
        <div class="absolute top-0 left-0 w-full h-full overflow-hidden z-0 pointer-events-none">
            <div class="absolute -top-[20%] -left-[10%] w-[50%] h-[50%] rounded-full bg-pink-300/20 blur-3xl animate-pulse-slow"></div>
            <div class="absolute top-[40%] -right-[10%] w-[40%] h-[40%] rounded-full bg-rose-400/20 blur-3xl animate-pulse-slow" style="animation-delay: 1s;"></div>
        </div>

        <div class="relative z-10 w-full max-w-sm px-6">
            
            <div class="text-center mb-10 group cursor-default animate-fade-in-down">
                
                <h1 class="text-3xl md:text-4xl font-extrabold tracking-tighter uppercase leading-tight transition-all duration-500 transform group-hover:scale-105 bg-clip-text text-transparent bg-gradient-to-r from-pink-600 to-rose-800 drop-shadow-sm">
                    SKIN LAB BEAUTY
                </h1>
                
                <div class="mt-3 relative inline-block overflow-hidden rounded-full shadow-md group-hover:shadow-lg transition-shadow duration-300">
                    <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/40 to-transparent -translate-x-[150%] group-hover:animate-[shimmer_1.5s_infinite] z-10"></div>
                    
                    <span class="relative z-0 inline-block px-5 py-1.5 bg-gradient-to-r from-pink-600 to-rose-600 text-white text-[10px] font-bold tracking-[0.3em] uppercase">
                        Center
                    </span>
                </div>
            </div>

            <div class="bg-white/80 backdrop-blur-xl border border-white/60 shadow-[0_8px_30px_rgb(0,0,0,0.04)] rounded-2xl p-8 animate-fade-in-up" style="animation-delay: 0.2s; opacity: 0; animation-fill-mode: forwards;">
                
                <form @submit.prevent="submit" class="space-y-6">
                    
                    <div class="space-y-1.5">
                        <label for="email" class="text-xs font-bold text-gray-500 uppercase tracking-wide">
                            Access ID / Email
                        </label>
                        <input
                            id="email"
                            v-model="form.email"
                            type="email"
                            autofocus
                            placeholder="staff@skinlabbeauty.com"
                            class="w-full px-4 py-3 bg-white border rounded-xl text-gray-900 font-medium text-sm transition-all duration-200 outline-none placeholder:text-gray-400 shadow-sm"
                            :class="[
                                form.errors.email || clientErrors.email
                                    ? 'border-red-500 focus:border-red-500 focus:ring-4 focus:ring-red-500/10' 
                                    : 'border-pink-100 focus:border-pink-500 focus:ring-4 focus:ring-pink-500/10 hover:border-pink-300'
                            ]"
                        />
                        <p v-if="form.errors.email || clientErrors.email" class="text-xs text-red-500 font-bold ml-1">
                            {{ form.errors.email || clientErrors.email }}
                        </p>
                    </div>

                    <div class="space-y-1.5">
                        <label for="password" class="text-xs font-bold text-gray-500 uppercase tracking-wide">
                            Secure Key
                        </label>
                        <div class="relative">
                            <input
                                id="password"
                                v-model="form.password"
                                :type="showPassword ? 'text' : 'password'"
                                placeholder="••••••••"
                                class="w-full px-4 py-3 bg-white border rounded-xl text-gray-900 font-medium text-sm transition-all duration-200 outline-none placeholder:text-gray-400 shadow-sm pr-10"
                                :class="[
                                    form.errors.password || clientErrors.password
                                        ? 'border-red-500 focus:border-red-500 focus:ring-4 focus:ring-red-500/10' 
                                        : 'border-pink-100 focus:border-pink-500 focus:ring-4 focus:ring-pink-500/10 hover:border-pink-300'
                                ]"
                            />
                            <button
                                type="button"
                                @click="showPassword = !showPassword"
                                class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-pink-600 focus:outline-none transition-colors"
                            >
                                <svg v-if="showPassword" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                <svg v-else class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L7.757 7.757M9.878 9.878l2.121-2.121" /></svg>
                            </button>
                        </div>
                        <p v-if="form.errors.password || clientErrors.password" class="text-xs text-red-500 font-bold ml-1">
                            {{ form.errors.password || clientErrors.password }}
                        </p>
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full relative group overflow-hidden rounded-xl p-[1px] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-pink-500 mt-2"
                    >
                        <span class="absolute inset-0 bg-gradient-to-r from-pink-600 to-rose-600 group-hover:from-pink-500 group-hover:to-rose-500 transition-all duration-300"></span>
                        <div class="relative bg-transparent h-full w-full px-4 py-3.5 flex items-center justify-center">
                            
                            <svg v-if="form.processing" class="animate-spin -ml-1 mr-2 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>

                            <span class="text-white font-bold text-sm tracking-widest uppercase">
                                {{ form.processing ? 'Verifying...' : 'Login' }}
                            </span>
                        </div>
                    </button>

                </form>
            </div>

            <div class="mt-8 text-center animate-fade-in-up" style="animation-delay: 0.4s; opacity: 0; animation-fill-mode: forwards;">
                <p class="text-[10px] text-gray-500 font-medium uppercase tracking-wider">
                    Restricted Area &bullet; Authorized Access Only
                </p>
            </div>

        </div>
    </div>
</template>

<style scoped>
/* ANIMATIONS */
@keyframes fade-in-down {
    0% { opacity: 0; transform: translateY(-20px); }
    100% { opacity: 1; transform: translateY(0); }
}
.animate-fade-in-down { animation: fade-in-down 0.8s ease-out forwards; }

@keyframes fade-in-up {
    0% { opacity: 0; transform: translateY(20px); }
    100% { opacity: 1; transform: translateY(0); }
}
.animate-fade-in-up { animation: fade-in-up 0.8s ease-out forwards; }

@keyframes shimmer {
    100% { transform: translateX(150%); }
}

@keyframes pulse-slow {
    0%, 100% { opacity: 0.4; transform: scale(1); }
    50% { opacity: 0.7; transform: scale(1.05); }
}
.animate-pulse-slow { animation: pulse-slow 8s infinite ease-in-out; }
</style>