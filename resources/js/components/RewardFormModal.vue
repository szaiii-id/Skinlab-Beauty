<script setup>
import { ref, watch, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { X, Ticket, Gift, Percent, DollarSign, Plus, Trash2, Calendar, Users, Target } from 'lucide-vue-next';
import Swal from 'sweetalert2';

const props = defineProps({
    show: Boolean,
    isEdit: Boolean,
    initialData: Object
});

const emit = defineEmits(['close']);

// --- FORM SETUP ---
const form = useForm({
    id: null,
    name: '',
    description: '',
    image: null,
    image_preview_url: null,
    points_required: 0,
    type: 'percent', // 'fixed' atau 'percent' saja (Free Shipping DIHAPUS)
    value: 0,
    min_spend: 0,
    stock: 999,
    max_per_user: 0,
    validity_days: 30,
    is_claim_only: false,
    is_active: true,
    _method: 'post'
});

const formatCurrency = (amount) => new Intl.NumberFormat('id-ID', {
    style: 'currency', currency: 'IDR', minimumFractionDigits: 0, maximumFractionDigits: 0
}).format(amount);

// --- WATCHERS ---
watch(() => props.show, (newVal) => {
    if (newVal) {
        if (props.isEdit && props.initialData) {
            const data = props.initialData;
            form.id = data.id;
            form.name = data.name;
            form.description = data.description;
            form.points_required = data.points_required;
            form.type = data.type;
            form.value = data.value;
            form.min_spend = data.min_spend;
            form.stock = data.stock;
            form.max_per_user = data.max_per_user;
            form.validity_days = data.validity_days;
            form.is_claim_only = !!data.is_claim_only;
            form.is_active = !!data.is_active;
            form.image_preview_url = data.image ? `/storage/${data.image}` : null;
            form._method = 'put';
        } else {
            form.reset();
            form.image_preview_url = null;
            form._method = 'post';
            form.points_required = 100;
            form.stock = 100;
            form.validity_days = 30;
        }
    }
});

const handleImageUpload = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.image = file;
        form.image_preview_url = URL.createObjectURL(file);
    } else {
        form.image = null;
        form.image_preview_url = null;
    }
};

const submit = () => {
    const url = props.isEdit ? route('admin.rewards.update', form.id) : route('admin.rewards.store');
    
    form.post(url, {
        forceFormData: true,
        onSuccess: () => {
            emit('close');
            form.reset();
            Swal.fire({
                icon: 'success',
                title: 'Saved!',
                text: 'Reward data has been updated.',
                confirmButtonColor: '#e11d48'
            });
        },
        onError: (errors) => {
            console.error(errors);
            Swal.fire({
                icon: 'error',
                title: 'Oops...',
                text: 'Please check the form for errors.',
                confirmButtonColor: '#e11d48'
            });
        }
    });
};
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/60 backdrop-blur-sm p-4 transition-opacity">
        <div class="bg-white rounded-[1.5rem] w-full max-w-3xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh] animate-in fade-in zoom-in duration-300 ring-1 ring-white/50">
            
            <div class="px-8 py-5 border-b border-gray-100 flex justify-between items-center bg-white shrink-0">
                <div>
                    <h3 class="font-black text-xl text-gray-900 tracking-tight flex items-center gap-2">
                        <span class="bg-rose-100 p-1.5 rounded-lg"><Ticket class="w-5 h-5 text-rose-600" /></span>
                        {{ isEdit ? 'Edit Reward' : 'Create New Reward' }}
                    </h3>
                    <p class="text-xs text-gray-400 mt-0.5 ml-9">Configure loyalty points and voucher details.</p>
                </div>
                <button @click="$emit('close')" class="bg-gray-50 hover:bg-gray-100 p-2 rounded-full transition-colors text-gray-400 hover:text-rose-500">
                    <X class="w-5 h-5" />
                </button>
            </div>

            <form @submit.prevent="submit" class="p-8 space-y-6 overflow-y-auto custom-scrollbar bg-[#FAFAFA]">
                
                <div class="grid md:grid-cols-3 gap-6">
                    <div class="md:col-span-2 space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wide mb-1.5">Reward Name</label>
                            <input v-model="form.name" type="text" placeholder="e.g. 10k Discount Voucher" class="w-full rounded-xl border border-gray-200 bg-white text-gray-900 py-3 px-4 focus:ring-rose-500 focus:border-rose-500 shadow-sm transition-all h-12">
                            <p v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wide mb-1.5">Description (Internal)</label>
                            <textarea v-model="form.description" placeholder="Short description..." class="w-full rounded-xl border border-gray-200 bg-white text-gray-900 py-3 px-4 focus:ring-rose-500 focus:border-rose-500 shadow-sm text-sm min-h-[80px]"></textarea>
                        </div>
                    </div>
                    
                    <div class="md:col-span-1">
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wide mb-1.5">Image/Icon</label>
                        <div class="relative w-full aspect-square border-2 border-dashed border-gray-300 rounded-xl flex items-center justify-center cursor-pointer overflow-hidden bg-white hover:bg-gray-50 transition-colors">
                            <img v-if="form.image_preview_url" :src="form.image_preview_url" class="absolute inset-0 w-full h-full object-cover">
                            <div v-else class="text-center text-gray-400">
                                <Gift class="w-8 h-8 mx-auto mb-1" />
                                <span class="text-xs font-bold">Upload</span>
                            </div>
                            <input type="file" @change="handleImageUpload" class="absolute inset-0 opacity-0 cursor-pointer" accept="image/*">
                        </div>
                    </div>
                </div>

                <div class="p-6 bg-white rounded-2xl border border-gray-100 shadow-sm space-y-5">
                    <h4 class="font-bold text-gray-900 text-lg border-b border-gray-100 pb-3 flex items-center gap-2">
                        <DollarSign class="w-5 h-5 text-rose-500" /> Pricing & Value
                    </h4>
                    
                    <div class="grid md:grid-cols-3 gap-6">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wide mb-1.5">Points Cost</label>
                            <div class="relative">
                                <input v-model="form.points_required" type="number" min="0" class="w-full rounded-xl border border-gray-200 bg-white text-gray-900 py-3 pl-4 pr-12 focus:ring-amber-500 focus:border-amber-500 shadow-sm text-lg font-bold text-center h-12">
                                <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 text-xs font-bold">PTS</span>
                            </div>
                            <p v-if="form.errors.points_required" class="text-red-500 text-xs mt-1">{{ form.errors.points_required }}</p>
                        </div>
                        
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wide mb-1.5">Discount Type</label>
                            <select v-model="form.type" class="w-full rounded-xl border border-gray-200 bg-white text-gray-900 py-3 px-4 focus:ring-rose-500 focus:border-rose-500 shadow-sm text-sm font-medium h-12 appearance-none cursor-pointer">
                                <option value="percent">Percentage (%)</option>
                                <option value="fixed">Fixed Amount (IDR)</option>
                                </select>
                        </div>
                        
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wide mb-1.5">Value</label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 font-bold text-xs">
                                    {{ form.type === 'percent' ? '%' : 'IDR' }}
                                </span>
                                <input v-model="form.value" type="number" min="0" class="w-full rounded-xl border border-gray-200 text-gray-900 py-3 pl-10 pr-4 focus:ring-rose-500 focus:border-rose-500 shadow-sm text-lg font-bold h-12">
                            </div>
                            <p v-if="form.errors.value" class="text-red-500 text-xs mt-1">{{ form.errors.value }}</p>
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wide mb-1.5">Minimum Purchase Requirement</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 font-bold text-xs">IDR</span>
                            <input v-model="form.min_spend" type="number" min="0" class="w-full rounded-xl border border-gray-200 text-gray-900 py-3 pl-10 pr-4 focus:ring-rose-500 focus:border-rose-500 shadow-sm text-lg font-bold h-12">
                        </div>
                        <p class="text-xs text-gray-400 mt-1 font-medium">Preview: {{ formatCurrency(form.min_spend) }}</p>
                    </div>
                </div>

                <div class="p-6 bg-white rounded-2xl border border-gray-100 shadow-sm space-y-5">
                    <h4 class="font-bold text-gray-900 text-lg border-b border-gray-100 pb-3 flex items-center gap-2">
                        <Target class="w-5 h-5 text-emerald-500" /> Rules & Limits
                    </h4>

                    <div class="grid md:grid-cols-3 gap-6">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wide mb-1.5 flex items-center gap-1"><Plus class="w-3 h-3" /> Stock</label>
                            <input v-model="form.stock" type="number" min="1" class="w-full rounded-xl border border-gray-200 bg-white text-gray-900 py-3 px-4 focus:ring-rose-500 focus:border-rose-500 shadow-sm text-lg font-bold text-center h-12">
                        </div>
                        
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wide mb-1.5 flex items-center gap-1"><Users class="w-3 h-3" /> Max/User</label>
                            <input v-model="form.max_per_user" type="number" min="0" placeholder="0 = Unlimited" class="w-full rounded-xl border border-gray-200 bg-white text-gray-900 py-3 px-4 focus:ring-rose-500 focus:border-rose-500 shadow-sm text-lg font-bold text-center h-12">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wide mb-1.5 flex items-center gap-1"><Calendar class="w-3 h-3" /> Validity</label>
                            <div class="relative">
                                <input v-model="form.validity_days" type="number" min="1" class="w-full rounded-xl border border-gray-200 bg-white text-gray-900 py-3 px-4 focus:ring-rose-500 focus:border-rose-500 shadow-sm text-lg font-bold text-center h-12">
                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs font-bold">DAYS</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex items-center justify-between bg-gray-50 p-4 rounded-xl border border-gray-200">
                        <label class="text-sm font-bold text-gray-800 flex items-center gap-2 cursor-pointer">
                            <Target class="w-4 h-4 text-rose-500" /> Redemption Mode
                        </label>
                        
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" v-model="form.is_claim_only" class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:bg-rose-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all shadow-inner"></div>
                            <span class="ml-3 text-sm font-medium text-gray-700">Gift/Claim Only</span>
                        </label>
                    </div>

                    <div class="flex items-center justify-between pt-2">
                        <label class="text-sm font-bold text-gray-800 flex items-center gap-2">
                            <Check class="w-4 h-4 text-emerald-500" /> Active Status
                        </label>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" v-model="form.is_active" class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:bg-emerald-500 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all shadow-sm"></div>
                        </label>
                    </div>
                </div>

                <div class="flex gap-3 pt-2 border-t border-gray-100 mt-4">
                    <button v-if="isEdit" type="button" @click="$emit('delete')" class="w-14 flex items-center justify-center bg-white text-red-500 rounded-2xl border-2 border-red-100 hover:bg-red-50 hover:border-red-200 transition-colors shadow-sm">
                        <Trash2 class="w-5 h-5" />
                    </button>
                    
                    <button type="submit" :disabled="form.processing" class="flex-1 bg-rose-600 text-white py-4 rounded-2xl font-bold text-sm hover:bg-rose-700 disabled:opacity-50 shadow-lg shadow-rose-200 transition-all active:scale-[0.98]">
                        <span v-if="form.processing">Saving...</span>
                        <span v-else>{{ isEdit ? 'Save Changes' : 'Create Reward' }}</span>
                    </button>
                </div>

            </form>
        </div>
    </div>
</template>