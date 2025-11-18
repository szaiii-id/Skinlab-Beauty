<!-- [file name]: components/AddressFormModal.vue -->
<template>
    <div
        v-if="show"
        class="fixed inset-0 flex items-center justify-center p-4 z-50"
        @click="close"
    >
        <!-- Overlay dengan efek blur -->
        <div class="absolute inset-0 bg-white/80 backdrop-blur-sm"></div>
        
        <!-- Modal Content -->
        <div 
            class="relative bg-white rounded-2xl shadow-2xl w-full max-w-4xl max-h-[95vh] overflow-hidden border border-gray-100"
            @click.stop
        >
            <!-- Header -->
            <div class="bg-gradient-to-r from-rose-50 to-pink-50 px-6 py-4 border-b border-rose-100">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="w-8 h-8 bg-rose-100 rounded-lg flex items-center justify-center">
                            <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900">
                            {{ address ? 'Edit Alamat' : 'Tambah Alamat Baru' }}
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

            <!-- Form Content -->
            <div class="overflow-y-auto max-h-[calc(95vh-80px)]">
                <form @submit.prevent="handleSubmit" class="p-6 space-y-6">
                    <!-- Informasi Penerima -->
                    <div class="space-y-4">
                        <div class="flex items-center space-x-2">
                            <div class="w-2 h-2 bg-rose-400 rounded-full"></div>
                            <h4 class="text-sm font-semibold text-gray-700">Informasi Penerima</h4>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <label class="block text-sm font-medium text-gray-700">
                                    Nama Penerima *
                                </label>
                                <input
                                    v-model="form.receiver_name"
                                    type="text"
                                    required
                                    class="w-full px-4 py-3 bg-white border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-rose-500 focus:border-rose-500 transition-all duration-200 placeholder:text-gray-400 text-gray-900"
                                    placeholder="Nama lengkap penerima"
                                />
                            </div>

                            <div class="space-y-2">
                                <label class="block text-sm font-medium text-gray-700">
                                    Nomor Telepon *
                                </label>
                                <input
                                    v-model="form.phone_number"
                                    type="tel"
                                    required
                                    class="w-full px-4 py-3 bg-white border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-rose-500 focus:border-rose-500 transition-all duration-200 placeholder:text-gray-400 text-gray-900"
                                    placeholder="08123456789"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Alamat Lengkap -->
                    <div class="space-y-4">
                        <div class="flex items-center space-x-2">
                            <div class="w-2 h-2 bg-rose-400 rounded-full"></div>
                            <h4 class="text-sm font-semibold text-gray-700">Alamat Lengkap</h4>
                        </div>

                        <!-- Region Selection -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <!-- Province -->
                            <div class="space-y-2">
                                <label class="block text-sm font-medium text-gray-700">
                                    Provinsi *
                                </label>
                                <select
                                    v-model="form.province_code"
                                    @change="onProvinceChange"
                                    required
                                    class="w-full px-4 py-3 bg-white border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-rose-500 focus:border-rose-500 transition-all duration-200 appearance-none cursor-pointer text-gray-900"
                                >
                                    <option value="">Pilih Provinsi</option>
                                    <option 
                                        v-for="province in provinces" 
                                        :key="province.code" 
                                        :value="province.code"
                                    >
                                        {{ province.name }}
                                    </option>
                                </select>
                            </div>

                            <!-- City -->
                            <div class="space-y-2">
                                <label class="block text-sm font-medium text-gray-700">
                                    Kota/Kabupaten *
                                </label>
                                <select
                                    v-model="form.city_code"
                                    @change="onCityChange"
                                    required
                                    :disabled="!form.province_code"
                                    class="w-full px-4 py-3 bg-white border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-rose-500 focus:border-rose-500 transition-all duration-200 appearance-none cursor-pointer text-gray-900 disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    <option value="">Pilih Kota</option>
                                    <option 
                                        v-for="city in cities" 
                                        :key="city.code" 
                                        :value="city.code"
                                    >
                                        {{ city.name }}
                                    </option>
                                </select>
                            </div>

                            <!-- District -->
                            <div class="space-y-2">
                                <label class="block text-sm font-medium text-gray-700">
                                    Kecamatan *
                                </label>
                                <select
                                    v-model="form.district_code"
                                    @change="onDistrictChange"
                                    required
                                    :disabled="!form.city_code"
                                    class="w-full px-4 py-3 bg-white border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-rose-500 focus:border-rose-500 transition-all duration-200 appearance-none cursor-pointer text-gray-900 disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    <option value="">Pilih Kecamatan</option>
                                    <option 
                                        v-for="district in districts" 
                                        :key="district.code" 
                                        :value="district.code"
                                    >
                                        {{ district.name }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <!-- Full Address -->
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">
                                Alamat Lengkap (Jalan, Nomor Rumah, RT/RW) *
                            </label>
                            <textarea
                                v-model="form.full_address"
                                required
                                rows="3"
                                class="w-full px-4 py-3 bg-white border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-rose-500 focus:border-rose-500 transition-all duration-200 placeholder:text-gray-400 resize-none text-gray-900"
                                placeholder="Contoh: Jl. Merdeka No. 123, RT 01/RW 02, Gedung ABC, Lantai 3..."
                            ></textarea>
                        </div>
                    </div>

                    <!-- Peta Lokasi & Postal Code -->
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-2">
                                <div class="w-2 h-2 bg-blue-400 rounded-full"></div>
                                <h4 class="text-sm font-semibold text-gray-700">Pilih di Peta & Kode Pos</h4>
                            </div>
                            <div class="text-xs text-gray-500">
                                Postal code otomatis dari peta
                            </div>
                        </div>

                        <MapPicker
                            @location-selected="handleMapLocation"
                            :initial-lat="form.latitude || -6.2088"
                            :initial-lng="form.longitude || 106.8456"
                        />

                        <!-- Postal Code dari OSM -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <label class="block text-sm font-medium text-gray-700">
                                    Kode Pos *
                                </label>
                                <div class="relative">
                                    <input
                                        v-model="form.postal_code"
                                        type="text"
                                        maxlength="5"
                                        required
                                        class="w-full px-4 py-3 bg-white border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-rose-500 focus:border-rose-500 transition-all duration-200 placeholder:text-gray-400 text-gray-900"
                                        placeholder="Akan terisi otomatis dari peta"
                                        :class="postalCodeClass"
                                    />
                                    
                                    <!-- Auto-fill Indicator -->
                                    <div v-if="form.postal_code && !postalCodeEdited" class="absolute right-3 top-3">
                                        <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    </div>
                                    <div v-else-if="form.postal_code && postalCodeEdited" class="absolute right-3 top-3">
                                        <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </div>
                                </div>
                                <p class="text-xs text-gray-500">
                                    <span v-if="!postalCodeEdited && form.postal_code">
                                        ✅ Terisi otomatis dari peta
                                    </span>
                                    <span v-else-if="postalCodeEdited">
                                        ✏️ Anda mengedit manual
                                    </span>
                                    <span v-else>
                                        Pilih lokasi di peta untuk mengisi kode pos otomatis
                                    </span>
                                </p>
                            </div>

                            <div class="space-y-2">
                                <label class="block text-sm font-medium text-gray-700">
                                    Tipe Alamat
                                </label>
                                <select
                                    v-model="form.type"
                                    class="w-full px-4 py-3 bg-white border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-rose-500 focus:border-rose-500 transition-all duration-200 appearance-none cursor-pointer text-gray-900"
                                >
                                    <option value="home">🏠 Rumah</option>
                                    <option value="office">🏢 Kantor</option>
                                    <option value="other">📦 Lainnya</option>
                                </select>
                            </div>
                        </div>

                        
                    </div>

                    <!-- Default Address Toggle -->
                    <div class="flex items-center space-x-3 p-4 bg-rose-50 rounded-xl border border-rose-100">
                        <input
                            v-model="form.is_default"
                            type="checkbox"
                            id="is_default"
                            class="w-5 h-5 text-rose-600 border-gray-300 rounded-lg focus:ring-2 focus:ring-rose-500 focus:ring-offset-2 cursor-pointer"
                        />
                        <label for="is_default" class="text-sm font-medium text-gray-700 cursor-pointer">
                            Jadikan alamat utama
                        </label>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex space-x-3 pt-4 border-t border-gray-100">
                        <button
                            type="button"
                            @click="close"
                            class="flex-1 px-6 py-3 bg-white border border-gray-300 text-gray-700 rounded-xl hover:bg-gray-50 hover:border-gray-400 active:scale-95 transition-all duration-200 font-medium"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="submitting || !isFormValid"
                            class="flex-1 px-6 py-3 bg-gradient-to-r from-rose-600 to-pink-600 text-white rounded-xl hover:from-rose-700 hover:to-pink-700 disabled:from-gray-400 disabled:to-gray-400 disabled:cursor-not-allowed active:scale-95 transition-all duration-200 font-medium shadow-lg shadow-rose-200"
                        >
                            <div class="flex items-center justify-center space-x-2">
                                <svg v-if="submitting" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span>{{ submitting ? 'Menyimpan...' : (address ? 'Update Alamat' : 'Simpan Alamat') }}</span>
                            </div>
                        </button>
                    </div>

                    <!-- Error Message -->
                    <div v-if="submitError" class="p-4 bg-red-50 border border-red-200 rounded-xl">
                        <div class="flex items-start space-x-2">
                            <svg class="w-5 h-5 text-red-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <div>
                                <p class="text-red-800 text-sm font-medium">Error</p>
                                <p class="text-red-700 text-sm mt-1">{{ submitError }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Success Message -->
                    <div v-if="submitSuccess" class="p-4 bg-green-50 border border-green-200 rounded-xl">
                        <div class="flex items-start space-x-2">
                            <svg class="w-5 h-5 text-green-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <div>
                                <p class="text-green-800 text-sm font-medium">Sukses</p>
                                <p class="text-green-700 text-sm mt-1">{{ submitSuccess }}</p>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, watch, onMounted, computed } from 'vue'
import { useRegions } from '@/composables/useRegions'
import { useAddress } from '@/composables/useAddress'
import MapPicker from './MapPicker.vue'

// Component props
const props = defineProps({
    show: Boolean,
    address: Object
})

// Component events
const emit = defineEmits(['close', 'saved'])

// Composable functions
const { provinces, cities, districts, loadProvinces, loadCities, loadDistricts } = useRegions()
const { createAddress, updateAddress } = useAddress()

// Reactive states
const submitting = ref(false)
const submitError = ref('')
const submitSuccess = ref('')
const postalCodeEdited = ref(false)

// Form data
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
    latitude: '',
    longitude: ''
})

// Computed property untuk validasi form
const isFormValid = computed(() => {
    const isValid = Boolean(
        form.receiver_name?.trim() && 
        form.phone_number?.trim() && 
        form.province_code && 
        form.city_code && 
        form.district_code && 
        form.full_address?.trim() && 
        form.postal_code?.trim() &&
        /^\d{5}$/.test(form.postal_code) // Validasi format kode pos
    )
    
    return isValid
})

// Computed untuk styling postal code
const postalCodeClass = computed(() => {
    if (!form.postal_code) return ''
    if (!/^\d{5}$/.test(form.postal_code)) {
        return 'border-red-300 focus:border-red-500 focus:ring-red-500'
    }
    if (postalCodeEdited.value) {
        return 'border-blue-300 focus:border-blue-500 focus:ring-blue-500'
    }
    return 'border-green-300 focus:border-green-500 focus:ring-green-500'
})

// Close modal function
const close = () => {
    submitError.value = ''
    submitSuccess.value = ''
    postalCodeEdited.value = false
    emit('close')
}

// Submit handler
const handleSubmit = async (event) => {
    event.preventDefault()
    
    if (!isFormValid.value) {
        submitError.value = 'Harap isi semua field yang wajib diisi dengan benar'
        return
    }
    
    await submitToAPI()
}

// API submission function
const submitToAPI = async () => {
    submitting.value = true
    submitError.value = ''
    submitSuccess.value = ''
    
    try {
        const submitData = {
            receiver_name: form.receiver_name.trim(),
            phone_number: form.phone_number.trim(),
            province_code: form.province_code,
            city_code: form.city_code,
            district_code: form.district_code,
            full_address: form.full_address.trim(),
            postal_code: form.postal_code.trim(),
            type: form.type,
            is_default: form.is_default,
            latitude: form.latitude || null,
            longitude: form.longitude || null
        }
        
        let result
        
        if (props.address) {
            result = await updateAddress(props.address.id, submitData)
        } else {
            result = await createAddress(submitData)
        }

        if (result.success) {
            submitSuccess.value = props.address ? 'Alamat berhasil diperbarui!' : 'Alamat berhasil dibuat!'
            
            setTimeout(() => {
                emit('saved')
            }, 1500)
            
        } else {
            if (result.error) {
                submitError.value = result.error
            } else if (result.message) {
                submitError.value = result.message
            } else {
                submitError.value = 'Gagal menyimpan alamat. Silakan coba lagi.'
            }
        }
        
    } catch (error) {
        console.error('❌ Exception in submitToAPI:', error)
        
        if (error.response) {
            const errorMessage = error.response.data?.message || 
                               error.response.data?.error || 
                               `Server error (${error.response.status})`
            
            submitError.value = errorMessage
            
        } else if (error.request) {
            submitError.value = 'Koneksi error: Tidak dapat terhubung ke server.'
        } else {
            submitError.value = 'Error tidak terduga: ' + error.message
        }
    } finally {
        submitting.value = false
    }
}

// Handle map location selection - SEKARANG DENGAN POSTAL CODE
const handleMapLocation = (location) => {
    form.latitude = location.lat
    form.longitude = location.lng
    
    // Auto-fill postal code dari OSM jika ada
    if (location.postal_code && !postalCodeEdited.value) {
        form.postal_code = location.postal_code
    }
}

// Region change handlers
const onProvinceChange = async () => {
    form.city_code = ''
    form.district_code = ''
    cities.value = []
    districts.value = []
    
    if (form.province_code) {
        await loadCities(form.province_code)
    }
}

const onCityChange = async () => {
    form.district_code = ''
    districts.value = []
    
    if (form.city_code) {
        await loadDistricts(form.city_code)
    }
}

const onDistrictChange = () => {
    // Reset postal code edited state ketika ganti kecamatan
    postalCodeEdited.value = false
}

// Track postal code manual edit
watch(() => form.postal_code, (newVal, oldVal) => {
    // Jika user mengedit manual (bukan dari auto-fill OSM)
    if (oldVal && newVal !== oldVal && !postalCodeEdited.value) {
        postalCodeEdited.value = true
    }
})

// Watch for modal show/hide
watch(() => props.show, async (newVal) => {
    if (newVal) {
        if (provinces.value.length === 0) {
            await loadProvinces()
        }
        
        submitError.value = ''
        submitSuccess.value = ''
        postalCodeEdited.value = false
        
        if (props.address) {
            Object.assign(form, {
                receiver_name: props.address.receiver_name || '',
                phone_number: props.address.phone_number || '',
                province_code: props.address.province_code || '',
                city_code: props.address.city_code || '',
                district_code: props.address.district_code || '',
                full_address: props.address.full_address || '',
                postal_code: props.address.postal_code || '',
                type: props.address.type || 'home',
                is_default: Boolean(props.address.is_default),
                latitude: props.address.latitude || '',
                longitude: props.address.longitude || ''
            })
            
            // Mark as edited jika postal code sudah ada
            if (props.address.postal_code) {
                postalCodeEdited.value = true
            }
            
            if (props.address.province_code) {
                await loadCities(props.address.province_code)
                if (props.address.city_code) {
                    await loadDistricts(props.address.city_code)
                }
            }
        } else {
            resetForm()
        }
    }
})

// Reset form function
const resetForm = () => {
    Object.assign(form, {
        receiver_name: '',
        phone_number: '',
        province_code: '',
        city_code: '',
        district_code: '',
        full_address: '',
        postal_code: '',
        type: 'home',
        is_default: false,
        latitude: '',
        longitude: ''
    })
    
    cities.value = []
    districts.value = []
    postalCodeEdited.value = false
}

// Load provinces on component mount
onMounted(() => {
    loadProvinces()
})
</script>

<style scoped>
/* Custom scrollbar for modal */
.overflow-y-auto::-webkit-scrollbar {
    width: 6px;
}

.overflow-y-auto::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
    background: #e5e5e5;
    border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: #d4d4d4;
}
</style>