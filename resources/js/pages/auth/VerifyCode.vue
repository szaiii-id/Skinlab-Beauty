<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';

interface Props {
    email: string;
    status?: string;
}

const props = defineProps<Props>();

const code = ref<string[]>(['', '', '', '', '', '']);
const isSubmitting = ref(false);

const codeValue = computed(() => code.value.join(''));
const isCodeComplete = computed(() => codeValue.value.length === 6);

const submitCode = async () => {
    if (!isCodeComplete.value || isSubmitting.value) return;
    
    isSubmitting.value = true;
    
    try {
        await router.post('/email/verify', {
            code: codeValue.value,
            email: props.email
        });
    } finally {
        isSubmitting.value = false;
    }
};

const resendCode = async () => {
    await router.post('/email/verification-notification');
};

const focusNext = (index: number, event: Event) => {
    const input = event.target as HTMLInputElement;
    
    if (input.value && index < 5) {
        const nextInput = document.getElementById(`code-${index + 1}`) as HTMLInputElement;
        nextInput?.focus();
    }
    
    if (isCodeComplete.value) {
        submitCode();
    }
};

const focusPrev = (index: number, event: KeyboardEvent) => {
    if (event.key === 'Backspace' && !code.value[index] && index > 0) {
        const prevInput = document.getElementById(`code-${index - 1}`) as HTMLInputElement;
        prevInput?.focus();
    }
};

onMounted(() => {
    const firstInput = document.getElementById('code-0') as HTMLInputElement;
    firstInput?.focus();
});
</script>

<template>
    <AuthLayout
        title="Verify your email"
        :description="`Enter the 6-digit code sent to ${email}`"
    >
        <Head title="Email verification" />

        <div
            v-if="status"
            class="mb-6 rounded-lg bg-green-50 p-4 text-sm text-green-700 border border-green-200"
        >
            {{ status }}
        </div>

        <div class="space-y-6">
            <!-- Code Input -->
            <div class="text-center">
                <div class="flex justify-center gap-3">
                    <input
                        v-for="(_, index) in 6"
                        :key="index"
                        :id="`code-${index}`"
                        v-model="code[index]"
                        type="text"
                        maxlength="1"
                        inputmode="numeric"
                        pattern="[0-9]*"
                        autocomplete="one-time-code"
                        :disabled="isSubmitting"
                        class="h-14 w-14 rounded-lg border border-gray-300 text-center text-xl font-semibold focus:border-rose-500 focus:outline-none focus:ring-2 focus:ring-rose-200 text-gray-900 transition-all duration-200"
                        @input="(e) => focusNext(index, e)"
                        @keydown="(e) => focusPrev(index, e as KeyboardEvent)"
                    />
                </div>

                <!-- Error Display -->
                <div v-if="$page.props.errors?.code" class="mt-4">
                    <div class="rounded-lg bg-red-50 p-4 text-sm text-red-700 border border-red-200">
                        {{ $page.props.errors.code }}
                    </div>
                </div>
            </div>

            <!-- Verify Button -->
            <Button
                @click="submitCode"
                :disabled="!isCodeComplete || isSubmitting"
                class="w-full bg-rose-600 hover:bg-rose-700 text-white font-medium py-3 px-4 rounded-lg transition-all duration-300 transform hover:scale-105 shadow-lg"
                size="lg"
            >
                <Spinner v-if="isSubmitting" class="mr-2 h-4 w-4" />
                {{ isSubmitting ? 'Verifying...' : 'Verify Email' }}
            </Button>

            <!-- Resend Code -->
            <div class="text-center">
                <button
                    @click="resendCode"
                    :disabled="isSubmitting"
                    class="text-sm text-rose-600 hover:text-rose-700 font-medium transition-colors duration-200"
                >
                    Didn't receive code? Resend
                </button>
            </div>
        </div>
    </AuthLayout>
</template>