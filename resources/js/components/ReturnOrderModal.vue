<script setup>
import { useForm } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import { X, UploadCloud, CheckCircle2, AlertCircle } from 'lucide-vue-next';

const props = defineProps({
    show: Boolean,
    order: Object
});

const emit = defineEmits(['close', 'success']);

const form = useForm({
    reason: 'Damaged / Not Working',
    description: '',
    solution: 'refund',
    evidence: null,
});

const submitReturn = () => {
    // 1. Validate Evidence
    if (!form.evidence) {
        Swal.fire({
            icon: 'warning',
            title: 'Missing Evidence',
            text: 'Please upload a photo or video proof.',
            confirmButtonColor: '#ea580c'
        });
        return;
    }

    // 2. Validate Order ID
    if (!props.order?.id) {
        Swal.fire('Error', 'Order data is missing. Please refresh.', 'error');
        return;
    }

    // 3. Send Request
    const url = route('orders.return', props.order.id); 

    form.post(url, {
        preserveScroll: true,
        forceFormData: true, 
        onSuccess: () => {
            emit('success');
            emit('close');
            form.reset();
            Swal.fire({
                icon: 'success',
                title: 'Request Submitted',
                text: 'Your return request has been sent for approval.',
                confirmButtonColor: '#ea580c'
            });
        },
        onError: (errors) => {
            console.error("Validation Errors:", errors);
            const firstError = Object.values(errors)[0];
            Swal.fire({
                icon: 'error',
                title: 'Submission Failed',
                text: firstError || 'Please check your input.',
                confirmButtonColor: '#ef4444'
            });
        }
    });
};
</script>

<template>
    <div v-if="show" class="fixed inset-0 bg-gray-900/70 backdrop-blur-sm z-[100] flex items-center justify-center p-4 transition-all">
        
        <div class="bg-white w-full max-w-lg rounded-2xl shadow-2xl flex flex-col max-h-[90vh] animate-in zoom-in-95 duration-200 border border-gray-200">
            
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-white rounded-t-2xl shrink-0">
                <div>
                    <h3 class="font-bold text-xl text-gray-900">Request Return</h3>
                    <p class="text-sm text-gray-500 mt-0.5">Order #{{ order?.order_number || order?.id }}</p>
                </div>
                <button @click="$emit('close')" class="p-2 bg-gray-50 hover:bg-gray-100 text-gray-500 hover:text-red-600 rounded-full transition-colors">
                    <X class="w-5 h-5" />
                </button>
            </div>

            <div class="p-6 overflow-y-auto flex-1 custom-scrollbar bg-white">
                
                <div class="mb-6">
                    <label class="block text-sm font-bold text-gray-800 mb-2">Why are you returning this?</label>
                    <div class="relative">
                        <select v-model="form.reason" class="w-full appearance-none bg-gray-50 border border-gray-300 text-gray-900 font-medium text-sm rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-orange-500 block p-3.5 pr-8 transition-shadow cursor-pointer">
                            <option>Damaged / Not Working</option>
                            <option>Product Mismatch (Wrong Item)</option>
                            <option>Incomplete Package</option>
                            <option>Defective Quality</option>
                        </select>
                        <div class="absolute inset-y-0 right-0 flex items-center px-3 pointer-events-none text-gray-500">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </div>
                    </div>
                    <p v-if="form.errors.reason" class="text-red-600 text-xs mt-1">{{ form.errors.reason }}</p>
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-bold text-gray-800 mb-2">Description</label>
                    <textarea 
                        v-model="form.description" 
                        rows="3"
                        class="w-full bg-gray-50 border border-gray-300 rounded-xl p-3 text-sm text-gray-900 placeholder:text-gray-400 focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-shadow"
                        placeholder="Please describe the issue in detail (min 10 chars)..."
                    ></textarea>
                    <p v-if="form.errors.description" class="text-red-600 text-xs mt-1">{{ form.errors.description }}</p>
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-bold text-gray-800 mb-3">Preferred Solution</label>
                    <div class="grid grid-cols-2 gap-4">
                        <label class="relative cursor-pointer group">
                            <input type="radio" value="refund" v-model="form.solution" class="peer sr-only">
                            <div class="p-4 rounded-xl border-2 border-gray-200 bg-white peer-checked:border-orange-500 peer-checked:bg-orange-50 hover:border-orange-300 transition-all text-center h-full flex flex-col items-center justify-center gap-2">
                                <div class="w-10 h-10 rounded-full bg-orange-100 flex items-center justify-center text-orange-600">
                                    <span class="font-bold text-lg">$</span>
                                </div>
                                <span class="text-sm font-bold text-gray-700 peer-checked:text-orange-800">Refund Money</span>
                            </div>
                            <div class="absolute top-3 right-3 opacity-0 peer-checked:opacity-100 transition-opacity">
                                <CheckCircle2 class="w-5 h-5 text-orange-600 fill-white" />
                            </div>
                        </label>

                        <label class="relative cursor-pointer group">
                            <input type="radio" value="exchange" v-model="form.solution" class="peer sr-only">
                            <div class="p-4 rounded-xl border-2 border-gray-200 bg-white peer-checked:border-orange-500 peer-checked:bg-orange-50 hover:border-orange-300 transition-all text-center h-full flex flex-col items-center justify-center gap-2">
                                <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                </div>
                                <span class="text-sm font-bold text-gray-700 peer-checked:text-orange-800">Exchange Item</span>
                            </div>
                            <div class="absolute top-3 right-3 opacity-0 peer-checked:opacity-100 transition-opacity">
                                <CheckCircle2 class="w-5 h-5 text-orange-600 fill-white" />
                            </div>
                        </label>
                    </div>
                </div>

                <div class="mb-2">
                    <label class="block text-sm font-bold text-gray-800 mb-2">Upload Evidence</label>
                    <div class="relative group">
                        <input type="file" @input="form.evidence = $event.target.files[0]" 
                            class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" 
                            accept="image/*,video/*"
                        />
                        <div class="border-2 border-dashed border-gray-300 rounded-xl p-6 flex flex-col items-center justify-center bg-gray-50 group-hover:bg-orange-50 group-hover:border-orange-300 transition-colors"
                             :class="{'border-red-500 bg-red-50': form.errors.evidence}">
                            
                            <div v-if="!form.evidence">
                                <div class="w-12 h-12 bg-white rounded-full shadow-sm flex items-center justify-center mb-3 mx-auto border border-gray-200">
                                    <UploadCloud class="w-6 h-6 text-gray-400 group-hover:text-orange-500" />
                                </div>
                                <p class="text-sm font-bold text-gray-700 text-center">Click to upload proof</p>
                                <p class="text-xs text-gray-500 mt-1 text-center">Photo or Video (Max 50MB)</p>
                            </div>
                            
                            <div v-else class="flex items-center gap-3 w-full">
                                <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                    <CheckCircle2 class="w-6 h-6 text-orange-600" />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-bold text-gray-900 truncate">{{ form.evidence.name }}</p>
                                    <p class="text-xs text-gray-500">{{ (form.evidence.size / 1024 / 1024).toFixed(2) }} MB</p>
                                </div>
                                <button @click.prevent="form.evidence = null" class="text-gray-400 hover:text-red-500 z-20 p-2">
                                    <X class="w-5 h-5" />
                                </button>
                            </div>
                        </div>
                    </div>
                    <p v-if="form.errors.evidence" class="text-red-600 text-xs mt-1 flex items-center gap-1 font-medium">
                        <AlertCircle class="w-3 h-3" /> {{ form.errors.evidence }}
                    </p>
                    
                    <div v-if="form.progress" class="mt-3">
                        <div class="flex justify-between text-xs font-bold text-gray-600 mb-1">
                            <span>Uploading...</span>
                            <span>{{ form.progress.percentage }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2 overflow-hidden">
                            <div class="bg-orange-500 h-2 rounded-full transition-all duration-300" :style="{ width: form.progress.percentage + '%' }"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-gray-50 px-6 py-4 border-t border-gray-100 flex justify-end gap-3 rounded-b-2xl shrink-0">
                <button @click="$emit('close')" class="px-5 py-2.5 bg-white border border-gray-300 text-gray-700 rounded-xl hover:bg-gray-100 hover:text-gray-900 text-sm font-bold transition-all shadow-sm">
                    Cancel
                </button>
                <button @click="submitReturn" :disabled="form.processing" class="px-5 py-2.5 bg-gradient-to-r from-orange-500 to-orange-600 text-white rounded-xl hover:from-orange-600 hover:to-orange-700 text-sm font-bold shadow-md shadow-orange-200 disabled:opacity-70 disabled:cursor-not-allowed flex items-center gap-2 transition-all">
                    <span v-if="form.processing" class="animate-spin">⏳</span>
                    {{ form.processing ? 'Submitting...' : 'Submit Request' }}
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Ensure Scrollbar looks good inside modal */
.custom-scrollbar::-webkit-scrollbar { width: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background-color: #e5e7eb; border-radius: 20px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background-color: #d1d5db; }
</style>