<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { useForm, Head } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps<{
    token: string;
    email: string;
}>();

// 1. Initialize Form
// We automatically fill email & token from the URL props
const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

// 2. Visibility Toggles
const showPassword = ref(false);
const showConfirmPassword = ref(false);

// 3. Submit Handler
const submit = () => {
    form.post('/reset-password', {
        onFinish: () => {
            form.reset('password', 'password_confirmation');
        },
    });
};

const togglePasswordVisibility = () => {
    showPassword.value = !showPassword.value;
};

const toggleConfirmPasswordVisibility = () => {
    showConfirmPassword.value = !showConfirmPassword.value;
};
</script>

<template>
    <AuthLayout
        title="Reset Password"
        description="Securely create a new password for your account."
    >
        <Head title="Reset Password" />

        <form @submit.prevent="submit" class="flex flex-col gap-6 w-full">
            <div class="grid gap-6">
                
                <div class="grid gap-2">
                    <Label for="email" class="text-rose-700 font-medium text-base">
                        Email Address
                    </Label>
                    <Input
                        id="email"
                        type="email"
                        name="email"
                        v-model="form.email"
                        readonly
                        class="bg-gray-100 text-gray-500 border-gray-200 cursor-not-allowed focus:ring-0 focus:border-gray-200"
                        tabindex="-1"
                    />
                    <p class="text-xs text-gray-500">
                        This is the email associated with the reset request.
                    </p>
                    <InputError :message="form.errors.email" />
                </div>

                <div class="grid gap-2">
                    <Label for="password" class="text-rose-700 font-medium text-base">
                        New Password <span class="text-red-500">*</span>
                    </Label>
                    <div class="relative">
                        <Input
                            id="password"
                            :type="showPassword ? 'text' : 'password'"
                            name="password"
                            v-model="form.password"
                            required
                            autofocus
                            autocomplete="new-password"
                            placeholder="Enter new password"
                            class="transition-all duration-200 pr-10"
                            :class="{ 
                                'border-red-500 focus:border-red-500 focus:ring-red-500 bg-red-50/10': form.errors.password,
                                'border-gray-300 focus:border-rose-500 focus:ring-rose-500': !form.errors.password
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
                    <InputError :message="form.errors.password" />
                </div>

                <div class="grid gap-2">
                    <Label for="password_confirmation" class="text-rose-700 font-medium text-base">
                        Confirm Password <span class="text-red-500">*</span>
                    </Label>
                    <div class="relative">
                        <Input
                            id="password_confirmation"
                            :type="showConfirmPassword ? 'text' : 'password'"
                            name="password_confirmation"
                            v-model="form.password_confirmation"
                            required
                            autocomplete="new-password"
                            placeholder="Confirm new password"
                            class="transition-all duration-200 pr-10"
                            :class="{ 
                                'border-red-500 focus:border-red-500 focus:ring-red-500 bg-red-50/10': form.errors.password_confirmation,
                                'border-gray-300 focus:border-rose-500 focus:ring-rose-500': !form.errors.password_confirmation
                            }"
                        />
                        <button
                            type="button"
                            class="absolute inset-y-0 right-0 flex items-center pr-3 text-rose-400 hover:text-rose-600 transition-colors duration-200 focus:outline-none"
                            @click="toggleConfirmPasswordVisibility"
                            tabindex="-1"
                        >
                            <span class="sr-only">
                                {{ showConfirmPassword ? 'Hide password' : 'Show password' }}
                            </span>
                            <svg v-if="showConfirmPassword" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L7.757 7.757M9.878 9.878l2.121-2.121" /></svg>
                            <svg v-else class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                        </button>
                    </div>
                    <InputError :message="form.errors.password_confirmation" />
                </div>

                <Button
                    type="submit"
                    class="w-full mt-2 bg-gradient-to-r from-rose-500 to-pink-500 hover:from-rose-600 hover:to-pink-600 text-white font-medium py-3 px-4 rounded-xl transition-all duration-300 transform hover:scale-[1.02] shadow-lg hover:shadow-xl border-0 disabled:opacity-70 disabled:cursor-not-allowed"
                    :disabled="form.processing"
                    size="lg"
                    data-test="reset-password-button"
                >
                    <Spinner v-if="form.processing" class="mr-2 h-5 w-5" />
                    {{ form.processing ? 'Resetting Password...' : 'Reset Password' }}
                </Button>
            </div>
        </form>
    </AuthLayout>
</template>