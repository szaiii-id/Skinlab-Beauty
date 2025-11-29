<script setup>
import { reactive, watch, computed, onMounted } from 'vue';
import { useRegions } from '@/composables/useRegions';
import { useAddress } from '@/composables/useAddress';
import MapPicker from './MapPicker.vue';

// Props & Emits
const props = defineProps({
    show: Boolean,
    address: Object
});

const emit = defineEmits(['close', 'saved']);

// Composables (Logic)
const { provinces, cities, districts, loadProvinces, loadCities, loadDistricts } = useRegions();
const { createAddress, updateAddress } = useAddress();

// State
const isSubmitting = reactive({ value: false });
const postalCodeEdited = reactive({ value: false });
const errorMessage = reactive({ value: '' });

// Form Data
const form = reactive({
    receiver_name: '',
    phone_number: '',
    province_code: '',
    city_code: '',
    district_code: '',
    full_address: '',
    postal_code: '',
    type: 'home',
    is_default: false,
    latitude: null,
    longitude: null
});

// Validation
const isFormValid = computed(() => {
    return (
        form.receiver_name &&
        form.phone_number &&
        form.province_code &&
        form.city_code &&
        form.district_code &&
        form.full_address &&
        form.postal_code
    );
});

// --- METHODS ---

const close = () => {
    errorMessage.value = '';
    emit('close');
};

const handleMapLocation = (location) => {
    form.latitude = location.lat;
    form.longitude = location.lng;
    
    // Auto-fill postal code
    if (location.postal_code && !postalCodeEdited.value) {
        form.postal_code = location.postal_code;
    }
};

const onProvinceChange = async () => {
    form.city_code = '';
    form.district_code = '';
    cities.value = [];
    districts.value = [];
    if (form.province_code) await loadCities(form.province_code);
};

const onCityChange = async () => {
    form.district_code = '';
    districts.value = [];
    if (form.city_code) await loadDistricts(form.city_code);
};

// Watchers
watch(() => props.show, async (isOpen) => {
    if (isOpen) {
        if (provinces.value.length === 0) await loadProvinces();

        if (props.address) {
            // Edit Mode
            Object.assign(form, props.address);
            form.is_default = Boolean(props.address.is_default);
            postalCodeEdited.value = true;

            if (form.province_code) await loadCities(form.province_code);
            if (form.city_code) await loadDistricts(form.city_code);
        } else {
            // Create Mode (Reset)
            Object.keys(form).forEach(key => form[key] = (key === 'is_default' ? false : ''));
            form.type = 'home';
            postalCodeEdited.value = false;
        }
    }
});

watch(() => form.postal_code, (newVal, oldVal) => {
    if (oldVal && newVal !== oldVal) postalCodeEdited.value = true;
});

const handleSubmit = async () => {
    isSubmitting.value = true;
    errorMessage.value = '';

    try {
        let result;
        if (props.address) {
            result = await updateAddress(props.address.id, form);
        } else {
            result = await createAddress(form);
        }

        if (result.success) {
            emit('saved');
            close();
        } else {
            errorMessage.value = result.message || 'Failed to save address.';
        }
    } catch (e) {
        errorMessage.value = 'An unexpected error occurred.';
    } finally {
        isSubmitting.value = false;
    }
};
</script>

<template>
    <div
        v-if="show"
        class="fixed inset-0 flex items-center justify-center p-4 z-[9999]"
        @click.self="close"
    >
        <div class="absolute inset-0 bg-white/80 backdrop-blur-sm transition-opacity"></div>
        
        <div 
            class="relative bg-white rounded-2xl shadow-2xl w-full max-w-4xl max-h-[95vh] flex flex-col border border-gray-100 overflow-hidden animate-in zoom-in-95 duration-200"
        >
            <div class="bg-gradient-to-r from-rose-50 to-pink-50 px-6 py-4 border-b border-rose-100 flex-shrink-0">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="w-8 h-8 bg-rose-100 rounded-lg flex items-center justify-center">
                            <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900">
                            {{ address ? 'Edit Address' : 'Add New Address' }}
                        </h3>
                    </div>
                    <button
                        @click="close"
                        class="w-8 h-8 bg-white border border-gray-200 rounded-lg flex items-center justify-center hover:bg-rose-50 hover:border-rose-200 transition-all duration-200 group"
                    >
                        <svg class="w-4 h-4 text-gray-500 group-hover:text-rose-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto p-6 custom-scrollbar">
                <form @submit.prevent="handleSubmit" class="space-y-6">
                    
                    <div class="space-y-4">
                        <div class="flex items-center space-x-2">
                            <div class="w-2 h-2 bg-rose-400 rounded-full"></div>
                            <h4 class="text-sm font-semibold text-gray-700">Recipient Information</h4>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <label class="block text-sm font-medium text-gray-700">Full Name *</label>
                                <input
                                    v-model="form.receiver_name"
                                    type="text"
                                    required
                                    class="w-full px-4 py-3 bg-white border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-rose-500 focus:border-rose-500 transition-all duration-200 placeholder:text-gray-400 text-gray-900"
                                    placeholder="Enter full name"
                                />
                            </div>

                            <div class="space-y-2">
                                <label class="block text-sm font-medium text-gray-700">Phone Number *</label>
                                <input
                                    v-model="form.phone_number"
                                    type="tel"
                                    required
                                    class="w-full px-4 py-3 bg-white border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-rose-500 focus:border-rose-500 transition-all duration-200 placeholder:text-gray-400 text-gray-900"
                                    placeholder="0812..."
                                />
                            </div>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div class="flex items-center space-x-2">
                            <div class="w-2 h-2 bg-rose-400 rounded-full"></div>
                            <h4 class="text-sm font-semibold text-gray-700">Address Details</h4>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="space-y-2">
                                <label class="block text-sm font-medium text-gray-700">Province *</label>
                                <select v-model="form.province_code" @change="onProvinceChange" required class="w-full px-4 py-3 bg-white border border-gray-300 rounded-xl focus:ring-rose-500 focus:border-rose-500 text-gray-900">
                                    <option value="" disabled>Select Province</option>
                                    <option v-for="p in provinces" :key="p.code" :value="p.code">{{ p.name }}</option>
                                </select>
                            </div>

                            <div class="space-y-2">
                                <label class="block text-sm font-medium text-gray-700">City *</label>
                                <select v-model="form.city_code" @change="onCityChange" :disabled="!form.province_code" required class="w-full px-4 py-3 bg-white border border-gray-300 rounded-xl focus:ring-rose-500 focus:border-rose-500 text-gray-900 disabled:bg-gray-50">
                                    <option value="" disabled>Select City</option>
                                    <option v-for="c in cities" :key="c.code" :value="c.code">{{ c.name }}</option>
                                </select>
                            </div>

                            <div class="space-y-2">
                                <label class="block text-sm font-medium text-gray-700">District *</label>
                                <select v-model="form.district_code" :disabled="!form.city_code" required class="w-full px-4 py-3 bg-white border border-gray-300 rounded-xl focus:ring-rose-500 focus:border-rose-500 text-gray-900 disabled:bg-gray-50">
                                    <option value="" disabled>Select District</option>
                                    <option v-for="d in districts" :key="d.code" :value="d.code">{{ d.name }}</option>
                                </select>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">Full Address *</label>
                            <textarea v-model="form.full_address" required rows="3" class="w-full px-4 py-3 bg-white border border-gray-300 rounded-xl focus:ring-rose-500 focus:border-rose-500 text-gray-900" placeholder="Street name, No. House, RT/RW..."></textarea>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-2">
                                <div class="w-2 h-2 bg-blue-400 rounded-full"></div>
                                <h4 class="text-sm font-semibold text-gray-700">Map & Postal Code</h4>
                            </div>
                            <div class="text-xs text-gray-500">Auto-filled from map</div>
                        </div>

                        <div class="border border-gray-300 rounded-xl overflow-hidden shadow-sm">
                            <MapPicker
                                @location-selected="handleMapLocation"
                                :initial-lat="form.latitude"
                                :initial-lng="form.longitude"
                            />
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <label class="block text-sm font-medium text-gray-700">Postal Code *</label>
                                <div class="relative">
                                    <input
                                        v-model="form.postal_code"
                                        type="text"
                                        maxlength="5"
                                        required
                                        class="w-full px-4 py-3 bg-white border rounded-xl focus:ring-2 focus:ring-rose-500 focus:border-rose-500 text-gray-900"
                                        :class="postalCodeEdited.value ? 'border-blue-300' : 'border-gray-300'"
                                        placeholder="Auto-filled"
                                    />
                                    <div v-if="form.postal_code && !postalCodeEdited.value" class="absolute right-3 top-3">
                                        <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-2">
                                <label class="block text-sm font-medium text-gray-700">Address Type</label>
                                <select v-model="form.type" class="w-full px-4 py-3 bg-white border border-gray-300 rounded-xl focus:ring-rose-500 text-gray-900">
                                    <option value="home">🏠 Home</option>
                                    <option value="office">🏢 Office</option>
                                    <option value="other">📦 Other</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center space-x-3 p-4 bg-rose-50 rounded-xl border border-rose-100">
                        <input v-model="form.is_default" type="checkbox" id="is_default" class="w-5 h-5 text-rose-600 border-gray-300 rounded focus:ring-rose-500 cursor-pointer" />
                        <label for="is_default" class="text-sm font-medium text-gray-700 cursor-pointer">Set as primary address</label>
                    </div>

                    <div v-if="errorMessage.value" class="p-4 bg-red-50 border border-red-200 rounded-xl text-red-700 text-sm">
                        {{ errorMessage.value }}
                    </div>

                </form>
            </div>

            <div class="p-6 border-t border-gray-100 bg-white flex space-x-3 rounded-b-2xl">
                <button
                    type="button"
                    @click="close"
                    class="flex-1 px-6 py-3 bg-white border border-gray-300 text-gray-700 rounded-xl hover:bg-gray-50 hover:border-gray-400 font-medium transition-all"
                >
                    Cancel
                </button>
                <button
                    type="button"
                    @click="handleSubmit"
                    :disabled="isSubmitting.value || !isFormValid"
                    class="flex-1 px-6 py-3 bg-gradient-to-r from-rose-600 to-pink-600 text-white rounded-xl hover:from-rose-700 hover:to-pink-700 disabled:from-gray-400 disabled:to-gray-400 disabled:cursor-not-allowed font-medium shadow-lg shadow-rose-200 transition-all flex items-center justify-center space-x-2"
                >
                    <span v-if="isSubmitting.value">Saving...</span>
                    <span v-else>{{ address ? 'Update Address' : 'Save Address' }}</span>
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 6px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #e5e7eb; border-radius: 4px; }
</style>