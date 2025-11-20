<script setup>
import { defineProps, defineEmits } from 'vue';

defineProps({ modelValue: { type: String, required: true } });
const emit = defineEmits(['update:modelValue']);
const selectMethod = (id) => emit('update:modelValue', id);

const paymentMethods = [
    { id: 'online_payment', name: 'Online Payment', description: 'QRIS, Transfer Bank (BCA, Mandiri, BNI)', icon: '🛡️' },
    { id: 'cod', name: 'Cash on Delivery', description: 'Bayar Tunai saat Sampai', icon: '💰' }
];
</script>

<template>
    <div class="space-y-3">
        <div v-for="method in paymentMethods" :key="method.id"
            class="flex items-start space-x-4 p-4 border rounded-lg cursor-pointer"
            :class="modelValue === method.id ? 'border-rose-500 bg-rose-50 ring-1 ring-rose-500' : 'border-gray-200'"
            @click="selectMethod(method.id)">
            <div class="w-5 h-5 mt-1 rounded-full border-2 flex items-center justify-center shrink-0"
                 :class="modelValue === method.id ? 'border-rose-500 bg-rose-500' : 'border-gray-300'">
                <div v-if="modelValue === method.id" class="w-2 h-2 bg-white rounded-full"></div>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <span class="text-xl">{{ method.icon }}</span>
                    <span class="font-bold text-black">{{ method.name }}</span>
                </div>
                <p class="text-sm text-gray-800 mt-1">{{ method.description }}</p>
            </div>
        </div>
    </div>
</template>