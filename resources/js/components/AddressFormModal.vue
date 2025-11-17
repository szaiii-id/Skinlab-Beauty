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
            class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[85vh] overflow-hidden border border-gray-100"
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

            <!-- Form Content -->
            <div class="overflow-y-auto max-h-[calc(85vh-80px)]">
                <form @submit.prevent="handleSubmit" class="p-6 space-y-6">
                    <!-- Debug Info -->
                    <div v-if="debug" class="p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                        <p class="text-sm text-yellow-800">
                            <strong>Debug Info:</strong><br>
                            Form Valid: {{ isFormValid }}<br>
                            Submitting: {{ submitting }}<br>
                            Province: {{ form.province_code }}<br>
                            City: {{ form.city_code }}<br>
                            District: {{ form.district_code }}
                        </p>
                    </div>

                    <!-- Receiver Name & Phone -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">
                                Receiver Name *
                            </label>
                            <input
                                v-model="form.receiver_name"
                                type="text"
                                required
                                class="w-full px-4 py-3 bg-white border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-rose-500 focus:border-rose-500 transition-all duration-200 placeholder:text-gray-400 text-gray-900"
                                placeholder="Full receiver name"
                                @input="handleInput"
                            />
                        </div>

                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">
                                Phone Number *
                            </label>
                            <input
                                v-model="form.phone_number"
                                type="tel"
                                required
                                class="w-full px-4 py-3 bg-white border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-rose-500 focus:border-rose-500 transition-all duration-200 placeholder:text-gray-400 text-gray-900"
                                placeholder="08123456789"
                                @input="handleInput"
                            />
                        </div>
                    </div>

                    <!-- Region Selection -->
                    <div class="space-y-4">
                        <div class="flex items-center space-x-2">
                            <div class="w-2 h-2 bg-rose-400 rounded-full"></div>
                            <h4 class="text-sm font-semibold text-gray-700">Location</h4>
                        </div>
                        
                        <!-- Province -->
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">
                                Province *
                            </label>
                            <select
                                v-model="form.province_code"
                                @change="onProvinceChange"
                                required
                                class="w-full px-4 py-3 bg-white border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-rose-500 focus:border-rose-500 transition-all duration-200 appearance-none cursor-pointer text-gray-900"
                            >
                                <option value="">Select Province</option>
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
                                City *
                            </label>
                            <select
                                v-model="form.city_code"
                                @change="onCityChange"
                                required
                                :disabled="!form.province_code"
                                class="w-full px-4 py-3 bg-white border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-rose-500 focus:border-rose-500 transition-all duration-200 appearance-none cursor-pointer text-gray-900 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <option value="">Select City</option>
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
                                District *
                            </label>
                            <select
                                v-model="form.district_code"
                                required
                                :disabled="!form.city_code"
                                class="w-full px-4 py-3 bg-white border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-rose-500 focus:border-rose-500 transition-all duration-200 appearance-none cursor-pointer text-gray-900 disabled:opacity-50 disabled:cursor-not-allowed"
                                @change="handleInput"
                            >
                                <option value="">Select District</option>
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
                            Full Address *
                        </label>
                        <textarea
                            v-model="form.full_address"
                            required
                            rows="3"
                            class="w-full px-4 py-3 bg-white border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-rose-500 focus:border-rose-500 transition-all duration-200 placeholder:text-gray-400 resize-none text-gray-900"
                            placeholder="Example: Jl. Merdeka No. 123, RT 01/RW 02, Building ABC..."
                            @input="handleInput"
                        ></textarea>
                    </div>

                    <!-- Postal Code & Type -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">
                                Postal Code *
                            </label>
                            <input
                                v-model="form.postal_code"
                                type="text"
                                required
                                class="w-full px-4 py-3 bg-white border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-rose-500 focus:border-rose-500 transition-all duration-200 placeholder:text-gray-400 text-gray-900"
                                placeholder="12345"
                                @input="handleInput"
                            />
                        </div>

                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">
                                Address Type
                            </label>
                            <select
                                v-model="form.type"
                                class="w-full px-4 py-3 bg-white border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-rose-500 focus:border-rose-500 transition-all duration-200 appearance-none cursor-pointer text-gray-900"
                                @change="handleInput"
                            >
                                <option value="home">🏠 Home</option>
                                <option value="office">🏢 Office</option>
                                <option value="other">📦 Other</option>
                            </select>
                        </div>
                    </div>

                    <!-- Default Address Toggle -->
                    <div class="flex items-center space-x-3 p-4 bg-rose-50 rounded-xl border border-rose-100">
                        <input
                            v-model="form.is_default"
                            type="checkbox"
                            id="is_default"
                            class="w-5 h-5 text-rose-600 border-gray-300 rounded-lg focus:ring-2 focus:ring-rose-500 focus:ring-offset-2 cursor-pointer"
                            @change="handleInput"
                        />
                        <label for="is_default" class="text-sm font-medium text-gray-700 cursor-pointer">
                            Set as default address
                        </label>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex space-x-3 pt-4 border-t border-gray-100">
                        <button
                            type="button"
                            @click="close"
                            class="flex-1 px-6 py-3 bg-white border border-gray-300 text-gray-700 rounded-xl hover:bg-gray-50 hover:border-gray-400 active:scale-95 transition-all duration-200 font-medium"
                        >
                            Cancel
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
                                <span>{{ submitting ? 'Saving...' : (address ? 'Update Address' : 'Save Address') }}</span>
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
                                <p class="text-green-800 text-sm font-medium">Success</p>
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

// Import composables
import { useRegions } from '@/composables/useRegions'
import { useAddress } from '@/composables/useAddress'

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
const debug = ref(false) // Set to false in production
const submitError = ref('')
const submitSuccess = ref('')

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
    is_default: false
})

// Debounce timer
let inputTimeout = null
let validationTimeout = null

// FIXED: Computed property untuk validasi form yang benar
const isFormValid = computed(() => {
    const isValid = Boolean(
        form.receiver_name?.trim() && 
        form.phone_number?.trim() && 
        form.province_code && 
        form.city_code && 
        form.district_code && 
        form.full_address?.trim() && 
        form.postal_code?.trim()
    )
    
    if (debug.value) {
        console.log('Form validation check:', {
            receiver_name: !!form.receiver_name?.trim(),
            phone_number: !!form.phone_number?.trim(),
            province_code: !!form.province_code,
            city_code: !!form.city_code,
            district_code: !!form.district_code,
            full_address: !!form.full_address?.trim(),
            postal_code: !!form.postal_code?.trim(),
            overall: isValid
        })
    }
    
    return isValid
})

// FIXED: Optimized input handler dengan debounce
const handleInput = () => {
    if (debug.value) {
        clearTimeout(inputTimeout)
        inputTimeout = setTimeout(() => {
            console.log('Form field updated:', JSON.parse(JSON.stringify(form)))
        }, 500)
    }
    
    // Clear errors when user starts typing
    if (submitError.value) {
        submitError.value = ''
    }
}

// Close modal function
const close = () => {
    submitError.value = ''
    submitSuccess.value = ''
    emit('close')
}

// FIXED: Single submit handler
const handleSubmit = async (event) => {
    event.preventDefault()
    
    if (debug.value) {
        console.log('=== SUBMIT BUTTON CLICKED ===')
        console.log('Form data:', JSON.parse(JSON.stringify(form)))
        console.log('Is form valid:', isFormValid.value)
        console.log('Submitting:', submitting.value)
    }
    
    // Final validation
    if (!isFormValid.value) {
        submitError.value = 'Please fill all required fields correctly'
        if (debug.value) {
            console.log('❌ Form validation failed - missing required fields')
        }
        return
    }
    
    await submitToAPI()
}

// FIXED: Improved API submission function
const submitToAPI = async () => {
    if (debug.value) {
        console.log('=== STARTING FORM SUBMISSION ===')
    }
    
    submitting.value = true
    submitError.value = ''
    submitSuccess.value = ''
    
    try {
        if (debug.value) {
            console.log('Calling API...')
        }
        
        // Prepare data for API
        const submitData = {
            receiver_name: form.receiver_name.trim(),
            phone_number: form.phone_number.trim(),
            province_code: form.province_code,
            city_code: form.city_code,
            district_code: form.district_code,
            full_address: form.full_address.trim(),
            postal_code: form.postal_code.trim(),
            type: form.type,
            is_default: form.is_default
        }
        
        if (debug.value) {
            console.log('Submitting data to API:', submitData)
        }
        
        let result
        
        if (props.address) {
            if (debug.value) {
                console.log('Updating address:', props.address.id)
            }
            result = await updateAddress(props.address.id, submitData)
        } else {
            if (debug.value) {
                console.log('Creating new address')
            }
            result = await createAddress(submitData)
        }

        if (debug.value) {
            console.log('API Response:', result)
        }
        
        // Handle API response
        if (result.success) {
            if (debug.value) {
                console.log('✅ Address saved successfully')
            }
            submitSuccess.value = props.address ? 'Address updated successfully!' : 'Address created successfully!'
            
            // Close modal after success
            setTimeout(() => {
                emit('saved')
            }, 1500)
            
        } else {
            console.error('❌ API returned error:', result)
            
            // Handle specific error messages from API
            if (result.error) {
                submitError.value = result.error
            } else if (result.message) {
                submitError.value = result.message
            } else {
                submitError.value = 'Failed to save address. Please try again.'
            }
            
            // Log additional error details for debugging
            if (result.details) {
                console.error('Error details:', result.details)
            }
        }
        
    } catch (error) {
        console.error('❌ Exception in submitToAPI:', error)
        
        // Enhanced error handling
        if (error.response) {
            // Server responded with error status
            console.error('Server error response:', {
                status: error.response.status,
                data: error.response.data
            })
            
            const errorMessage = error.response.data?.message || 
                               error.response.data?.error || 
                               `Server error (${error.response.status})`
            
            submitError.value = errorMessage
            
        } else if (error.request) {
            // Request was made but no response received
            console.error('No response received:', error.request)
            submitError.value = 'Network error: Unable to connect to server. Please check your connection.'
        } else {
            // Something else happened
            submitError.value = 'Unexpected error: ' + error.message
        }
    } finally {
        submitting.value = false
    }
}

// Handle province change
const onProvinceChange = async () => {
    if (debug.value) {
        console.log('Province changed to:', form.province_code)
    }
    
    // Reset dependent fields
    form.city_code = ''
    form.district_code = ''
    cities.value = []
    districts.value = []
    
    if (form.province_code) {
        await loadCities(form.province_code)
    }
    
    handleInput()
}

// Handle city change
const onCityChange = async () => {
    if (debug.value) {
        console.log('City changed to:', form.city_code)
    }
    
    // Reset dependent field
    form.district_code = ''
    districts.value = []
    
    if (form.city_code) {
        await loadDistricts(form.city_code)
    }
    
    handleInput()
}

// FIXED: Improved watch for modal show/hide
watch(() => props.show, async (newVal) => {
    if (newVal) {
        if (debug.value) {
            console.log('Modal opened')
        }
        
        // Load provinces if not already loaded
        if (provinces.value.length === 0) {
            await loadProvinces()
        }
        
        // Reset states
        submitError.value = ''
        submitSuccess.value = ''
        
        if (props.address) {
            if (debug.value) {
                console.log('Edit mode, populating form:', props.address)
            }
            
            // Populate form with address data
            Object.assign(form, {
                receiver_name: props.address.receiver_name || '',
                phone_number: props.address.phone_number || '',
                province_code: props.address.province_code || '',
                city_code: props.address.city_code || '',
                district_code: props.address.district_code || '',
                full_address: props.address.full_address || '',
                postal_code: props.address.postal_code || '',
                type: props.address.type || 'home',
                is_default: Boolean(props.address.is_default)
            })
            
            // Load cities and districts if we have province/city codes
            if (props.address.province_code) {
                await loadCities(props.address.province_code)
                if (props.address.city_code) {
                    await loadDistricts(props.address.city_code)
                }
            }
        } else {
            if (debug.value) {
                console.log('Create mode, resetting form')
            }
            
            // Reset form to initial state
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
        is_default: false
    })
    
    // Clear region data
    cities.value = []
    districts.value = []
}

// Load provinces on component mount
onMounted(() => {
    if (debug.value) {
        console.log('AddressFormModal mounted')
    }
    loadProvinces()
})

// Cleanup timeouts on unmount
import { onUnmounted } from 'vue'
onUnmounted(() => {
    clearTimeout(inputTimeout)
    clearTimeout(validationTimeout)
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