<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { update } from '@/routes/password';
import { Form, Head } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps<{
    token: string;
    email: string;
}>();

const inputEmail = ref(props.email);
</script>

<template>
    <AuthLayout
        title="Reset password"
        description="Please enter your new password below"
    >
        <Head title="Reset password" />

        <Form
            v-bind="update.form()"
            :transform="(data) => ({ ...data, token, email })"
            :reset-on-success="['password', 'password_confirmation']"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-6">
                <div class="grid gap-2">
                    <Label for="email" class="text-gray-700 font-medium">Email</Label>
                    <Input
                        id="email"
                        type="email"
                        name="email"
                        autocomplete="email"
                        v-model="inputEmail"
                        class="mt-1 block w-full border-gray-300 bg-gray-50 text-gray-500"
                        readonly
                    />
                    <InputError :message="errors.email" class="mt-2" />
                </div>

                <div class="grid gap-2">
                    <Label for="password" class="text-gray-700 font-medium">Password</Label>
                    <Input
                        id="password"
                        type="password"
                        name="password"
                        autocomplete="new-password"
                        class="mt-1 block w-full border-gray-300 focus:border-rose-500 focus:ring-rose-500"
                        autofocus
                        placeholder="Password"
                    />
                    <InputError :message="errors.password" />
                </div>

                <div class="grid gap-2">
                    <Label for="password_confirmation" class="text-gray-700 font-medium">
                        Confirm Password
                    </Label>
                    <Input
                        id="password_confirmation"
                        type="password"
                        name="password_confirmation"
                        autocomplete="new-password"
                        class="mt-1 block w-full border-gray-300 focus:border-rose-500 focus:ring-rose-500"
                        placeholder="Confirm password"
                    />
                    <InputError :message="errors.password_confirmation" />
                </div>

                <Button
                    type="submit"
                    class="mt-4 w-full bg-rose-600 hover:bg-rose-700 text-white font-medium py-3 px-4 rounded-lg transition-all duration-300 transform hover:scale-105 shadow-lg"
                    :disabled="processing"
                    data-test="reset-password-button"
                >
                    <Spinner v-if="processing" />
                    Reset password
                </Button>
            </div>
        </Form>
    </AuthLayout>
</template>