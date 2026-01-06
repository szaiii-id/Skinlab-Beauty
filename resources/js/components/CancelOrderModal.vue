<script setup>
import { useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import Swal from 'sweetalert2';
import { X, AlertCircle, CheckCircle2, FileText, ChevronDown } from 'lucide-vue-next';

const props = defineProps({
    show: Boolean,
    order: Object
});

const emit = defineEmits(['close', 'success']);

const cancelOptions = [
    "Want to change shipping address",
    "Want to modify order details (color/size)",
    "Forgot to use voucher code",
    "Changed mind / Not buying anymore",
    "Other"
];

const selectedReason = ref(cancelOptions[0]);
const customReason = ref('');

const form = useForm({
    reason: ''
});

// Reset form when modal opens/closes
watch(() => props.show, (val) => {
    if (val) {
        selectedReason.value = cancelOptions[0];
        customReason.value = '';
        form.reset();
    }
});

const submitCancel = () => {
    // Validate "Other" reason
    if (selectedReason.value === 'Other') {
        if (!customReason.value.trim() || customReason.value.length < 5) {
            form.setError('reason', 'Please provide a detailed reason (minimum 5 characters).');
            return;
        }
        form.reason = customReason.value;
    } else {
        form.reason = selectedReason.value;
    }

    form.post(route('orders.cancel', props.order.id), {
        preserveScroll: true,
        onSuccess: () => {
            emit('success');
            emit('close');
            Swal.fire({
                title: 'Request Submitted!',
                text: 'Cancellation request is being processed.',
                icon: 'success',
                confirmButtonColor: '#e11d48'
            });
        },
        onError: () => {
            Swal.fire('Failed', 'An error occurred while canceling the order.', 'error');
        }
    });
};
</script>

<template>
    <div v-if="show">
        
        <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm z-[100] transition-opacity" @click="$emit('close')"></div>

        <div class="hidden md:flex fixed inset-0 z-[101] items-center justify-center p-4 pointer-events-none">
            <div class="bg-white w-full max-w-lg rounded-3xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh] animate-in zoom-in-95 duration-200 ring-1 ring-gray-100 pointer-events-auto">
                
                <div class="px-8 py-6 border-b border-gray-100 flex justify-between items-center bg-white shrink-0">
                    <div>
                        <h3 class="text-xl font-black text-gray-900 flex items-center gap-2">
                            Cancel Order
                        </h3>
                        <p class="text-xs text-gray-500 mt-1 font-medium">
                            Order <span class="font-mono text-rose-600 bg-rose-50 px-1.5 py-0.5 rounded">#{{ order?.order_number }}</span>
                        </p>
                    </div>
                    <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600 hover:bg-gray-100 p-2 rounded-full transition-colors">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <div class="p-8 overflow-y-auto custom-scrollbar bg-white">
                    
                    <div v-if="['paid', 'processing'].includes(order?.order_status)" class="mb-6 bg-amber-50 border border-amber-200 rounded-xl p-4 flex gap-3">
                        <div class="bg-amber-100 p-2 rounded-full h-fit text-amber-600">
                            <AlertCircle class="w-5 h-5" />
                        </div>
                        <div>
                            <h4 class="font-bold text-amber-800 text-sm">Approval Required</h4>
                            <p class="text-xs text-amber-700 mt-1 leading-relaxed">
                                Since the order is paid or processing, cancellation requires admin approval for stock & fund refund.
                            </p>
                        </div>
                    </div>

                    <p class="text-sm font-bold text-gray-700 mb-4">Why do you want to cancel?</p>

                    <div class="space-y-3 mb-6">
                        <div v-for="(option, index) in cancelOptions" :key="index">
                            <label class="relative flex items-center justify-between p-4 rounded-xl border-2 cursor-pointer transition-all duration-200 group"
                                :class="selectedReason === option 
                                    ? 'border-rose-500 bg-rose-50/50' 
                                    : 'border-gray-200 hover:border-rose-200 hover:bg-gray-50'">
                                
                                <div class="flex items-center gap-3">
                                    <input type="radio" name="cancel_reason_desktop" :value="option" v-model="selectedReason" class="peer sr-only">
                                    
                                    <div class="w-5 h-5 rounded-full border-2 flex items-center justify-center transition-colors"
                                        :class="selectedReason === option ? 'border-rose-600 bg-rose-600' : 'border-gray-300 group-hover:border-rose-400'">
                                        <div class="w-2 h-2 bg-white rounded-full" v-if="selectedReason === option"></div>
                                    </div>
                                    
                                    <span class="text-sm font-medium transition-colors" 
                                        :class="selectedReason === option ? 'text-rose-900' : 'text-gray-600'">
                                        {{ option }}
                                    </span>
                                </div>

                                <CheckCircle2 v-if="selectedReason === option" class="w-5 h-5 text-rose-600 animate-in fade-in zoom-in" />
                            </label>
                        </div>
                    </div>

                    <div v-if="selectedReason === 'Other'" class="mb-2 animate-in slide-in-from-top-2 fade-in duration-300">
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2 ml-1">Reason Details</label>
                        <div class="relative">
                            <FileText class="absolute top-3.5 left-3.5 w-4 h-4 text-gray-400" />
                            <textarea 
                                v-model="customReason" 
                                rows="3" 
                                class="w-full pl-10 pr-4 py-3 rounded-xl border border-gray-300 focus:border-rose-500 focus:ring-4 focus:ring-rose-500/10 text-sm text-gray-900 placeholder-gray-400 transition-all resize-none shadow-sm"
                                placeholder="Please explain the reason..."
                            ></textarea>
                        </div>
                        <p v-if="form.errors.reason" class="text-xs text-red-500 mt-2 font-bold flex items-center gap-1">
                            <AlertCircle class="w-3 h-3" /> {{ form.errors.reason }}
                        </p>
                    </div>

                </div>

                <div class="px-8 py-5 bg-gray-50 border-t border-gray-100 flex justify-end gap-3 shrink-0">
                    <button 
                        @click="$emit('close')" 
                        class="px-5 py-2.5 text-sm font-bold text-gray-600 hover:bg-white hover:text-gray-900 hover:shadow-sm border border-transparent hover:border-gray-200 rounded-xl transition-all"
                    >
                        Cancel
                    </button>
                    <button 
                        @click="submitCancel" 
                        :disabled="form.processing"
                        class="px-6 py-2.5 text-sm font-bold text-white bg-gradient-to-r from-rose-600 to-pink-600 rounded-xl hover:shadow-lg hover:shadow-rose-200 hover:-translate-y-0.5 active:translate-y-0 transition-all flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <span v-if="form.processing" class="animate-spin">⏳</span>
                        {{ form.processing ? 'Processing...' : 'Confirm Cancellation' }}
                    </button>
                </div>
            </div>
        </div>

        <div class="md:hidden fixed inset-x-0 bottom-0 z-[101] flex flex-col max-h-[90vh] animate-in slide-in-from-bottom duration-300">
            <div class="bg-white rounded-t-[2rem] shadow-[0_-10px_40px_rgba(0,0,0,0.2)] flex flex-col flex-1 overflow-hidden">
                
                <div class="w-full flex justify-center pt-3 pb-2 bg-white" @click="$emit('close')">
                    <div class="w-12 h-1.5 bg-gray-300 rounded-full"></div>
                </div>

                <div class="px-5 pb-4 border-b border-gray-100 bg-white sticky top-0 z-10 flex justify-between items-center">
                    <div>
                        <h3 class="font-bold text-gray-900 text-lg">Cancel Order</h3>
                        <p class="text-xs text-gray-500">Order #{{ order?.order_number }}</p>
                    </div>
                    <button @click="$emit('close')" class="p-2 bg-gray-100 rounded-full text-gray-500">
                        <ChevronDown class="w-5 h-5" />
                    </button>
                </div>

                <div class="overflow-y-auto flex-1 bg-gray-50/30 p-5 space-y-5">
                    
                    <div v-if="['paid', 'processing'].includes(order?.order_status)" class="bg-amber-50 border border-amber-200 rounded-xl p-4 flex gap-3">
                        <div class="bg-amber-100 p-2 rounded-full h-fit text-amber-600">
                            <AlertCircle class="w-4 h-4" />
                        </div>
                        <div>
                            <h4 class="font-bold text-amber-800 text-xs uppercase tracking-wider">Approval Required</h4>
                            <p class="text-xs text-amber-700 mt-1">
                                Cancellation requires admin approval as payment is already processed.
                            </p>
                        </div>
                    </div>

                    <div>
                        <p class="text-xs font-bold text-gray-500 uppercase tracking-wide mb-3 ml-1">Select Reason</p>
                        <div class="space-y-3">
                            <label v-for="(option, index) in cancelOptions" :key="index" 
                                class="flex items-center justify-between p-4 bg-white rounded-xl border border-gray-100 shadow-sm active:scale-[0.98] transition-transform"
                                :class="{'ring-2 ring-rose-500 border-rose-500': selectedReason === option}">
                                
                                <div class="flex items-center gap-3">
                                    <input type="radio" name="cancel_reason_mobile" :value="option" v-model="selectedReason" class="hidden">
                                    <div class="w-5 h-5 rounded-full border-2 flex items-center justify-center"
                                        :class="selectedReason === option ? 'border-rose-600' : 'border-gray-300'">
                                        <div class="w-2.5 h-2.5 bg-rose-600 rounded-full" v-if="selectedReason === option"></div>
                                    </div>
                                    <span class="text-sm font-medium text-gray-900">{{ option }}</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div v-if="selectedReason === 'Other'" class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm animate-in fade-in slide-in-from-top-2">
                        <label class="block text-xs font-bold text-gray-800 mb-2">Detailed Reason</label>
                        <textarea 
                            v-model="customReason" 
                            rows="3" 
                            class="w-full bg-gray-50 border border-gray-200 rounded-xl p-3 text-sm text-gray-900 focus:ring-2 focus:ring-rose-500 focus:border-rose-500"
                            placeholder="Please explain why..."
                        ></textarea>
                        <p v-if="form.errors.reason" class="text-xs text-red-500 mt-2 font-bold">{{ form.errors.reason }}</p>
                    </div>

                </div>

                <div class="p-4 bg-white border-t border-gray-100">
                    <button 
                        @click="submitCancel" 
                        :disabled="form.processing"
                        class="w-full py-3.5 bg-gradient-to-r from-rose-600 to-pink-600 text-white rounded-xl font-bold shadow-lg shadow-rose-200 active:scale-95 transition-all disabled:opacity-50 disabled:shadow-none flex items-center justify-center gap-2"
                    >
                        <span v-if="form.processing" class="animate-spin">⏳</span>
                        {{ form.processing ? 'Processing...' : 'Confirm Cancellation' }}
                    </button>
                </div>

            </div>
        </div>

    </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: #f1f1f1; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 10px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #9ca3af; }
</style>