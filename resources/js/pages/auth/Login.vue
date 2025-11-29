<script setup lang="ts">
import { ref } from 'vue';
import { useForm, Head, Link } from '@inertiajs/vue3';
import AuthLayout from '@/layouts/AuthLayout.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

defineProps<{
    status?: string;
    canResetPassword: boolean;
    canRegister: boolean;
}>();

// 1. Initialize Form
const form = useForm({
    email: '',
    password: '',
    remember: false,
});

// 2. Local State related to UI
const showPassword = ref(false);

// [BARU] State untuk menampung error validasi manual (Client Side)
const clientErrors = ref({
    email: '',
    password: ''
});

// 3. Validation Logic
const validateForm = () => {
    let isValid = true;
    
    // Reset error dulu
    clientErrors.value.email = '';
    clientErrors.value.password = '';

    // Cek Email
    if (!form.email) {
        clientErrors.value.email = 'Email address is required.';
        isValid = false;
    } else if (!/^\S+@\S+\.\S+$/.test(form.email)) {
        // Opsional: Cek format email sederhana
        clientErrors.value.email = 'Please enter a valid email address.';
        isValid = false;
    }

    // Cek Password
    if (!form.password) {
        clientErrors.value.password = 'Password is required.';
        isValid = false;
    }

    return isValid;
};

// 4. Submit Handler
const submitLogin = () => {
    // Jalankan validasi lokal dulu
    if (!validateForm()) {
        return; // Stop jika ada error, jangan kirim ke server
    }

    // Jika lolos, baru kirim ke Laravel
    form.post(route('login'), { 
        onFinish: () => {
            form.reset('password');
        },
    });
};

const togglePasswordVisibility = () => {
    showPassword.value = !showPassword.value;
};
</script>

<template>
    <AuthLayout
        title="Welcome back"
        description="Please enter your details to sign in"
    >
        <Head title="Log in" />

        <div
            v-if="status"
            class="mb-6 rounded-xl bg-green-50 p-4 text-sm text-green-700 border border-green-200 shadow-sm font-medium"
        >
            {{ status }}
        </div>

        <form @submit.prevent="submitLogin" class="flex flex-col gap-6" novalidate>
            
            <div class="grid gap-2">
                <Label for="email" class="text-rose-700 font-medium text-base">
                    Email Address 
                </Label>
                <Input
                    id="email"
                    v-model="form.email"
                    type="email"
                    autofocus
                    tabindex="1"
                    autocomplete="username"
                    placeholder="name@example.com"
                    class="transition-all duration-200"
                    :class="{ 
                        'border-red-500 focus:border-red-500 focus:ring-red-500 bg-red-50/10': form.errors.email || clientErrors.email,
                        'border-gray-300 focus:border-rose-500 focus:ring-rose-500': !form.errors.email && !clientErrors.email
                    }"
                />
                <InputError :message="form.errors.email || clientErrors.email" class="mt-1" />
            </div>

            <div class="grid gap-2">
                <div class="flex items-center justify-between">
                    <Label for="password" class="text-rose-700 font-medium text-base">
                        Password 
                    </Label>
                    
                    <Link
                        v-if="canResetPassword"
                        :href="route('password.request')" 
                        class="text-sm text-rose-600 hover:text-rose-800 font-medium hover:underline transition-colors"
                        tabindex="5"
                    >
                        Forgot password?
                    </Link>
                </div>
                
                <div class="relative">
                    <Input
                        id="password"
                        v-model="form.password"
                        :type="showPassword ? 'text' : 'password'"
                        tabindex="2"
                        autocomplete="current-password"
                        placeholder="your password"
                        class="transition-all duration-200 pr-10"
                        :class="{ 
                            'border-red-500 focus:border-red-500 focus:ring-red-500 bg-red-50/10': form.errors.password || clientErrors.password,
                            'border-gray-300 focus:border-rose-500 focus:ring-rose-500': !form.errors.password && !clientErrors.password
                        }"
                    />
                    
                    <button
                        type="button"
                        class="absolute inset-y-0 right-0 flex items-center pr-3 text-rose-400 hover:text-rose-600 transition-colors duration-200 focus:outline-none"
                        @click="togglePasswordVisibility"
                        tabindex="-1"
                    >
                        <span class="sr-only">
                            {{ showPassword ? 'Hide password' : 'Show password' }}
                        </span>
                        <svg v-if="showPassword" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L7.757 7.757M9.878 9.878l2.121-2.121" /></svg>
                        <svg v-else class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                    </button>
                </div>
                
                <InputError :message="form.errors.password || clientErrors.password" class="mt-1" />
            </div>

            <div class="flex items-center space-x-3 p-3 rounded-lg hover:bg-rose-50/50 transition-colors border border-transparent hover:border-rose-100 cursor-pointer" @click="form.remember = !form.remember">
                <Checkbox 
                    id="remember" 
                    v-model:checked="form.remember"
                    tabindex="3"
                    class="data-[state=checked]:bg-rose-600 border-gray-300"
                />
                <Label
                    for="remember"
                    class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70 text-gray-700 cursor-pointer"
                >
                    Remember me
                </Label>
            </div>

            <Button
                type="submit"
                class="mt-2 w-full bg-gradient-to-r from-rose-500 to-pink-500 hover:from-rose-600 hover:to-pink-600 text-white font-medium py-3 rounded-xl shadow-lg transition-all duration-300 transform hover:scale-[1.02] disabled:opacity-70 disabled:cursor-not-allowed disabled:transform-none text-base border-0"
                tabindex="4"
                :disabled="form.processing"
            >
                <Spinner v-if="form.processing" class="mr-2 h-5 w-5" />
                <span v-if="!form.processing">Sign in</span>
                <span v-else>Signing in...</span>
            </Button>

            <div
                v-if="canRegister"
                class="text-center text-sm text-gray-600 mt-2 pt-4 border-t border-rose-100"
            >
                Don't have an account?
                <Link 
                    :href="route('register')" 
                    tabindex="5" 
                    class="text-rose-600 hover:text-rose-800 font-bold hover:underline ml-1 transition-colors"
                >
                    Create an account
                </Link>
            </div>
        </form>
    </AuthLayout>
</template>