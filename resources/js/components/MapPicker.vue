<!-- [file name]: components/MapPicker.vue -->
<template>
    <div class="map-picker">
        <!-- Search Box -->
        <div class="mb-4 relative">
            <div class="flex space-x-2">
                <div class="flex-1 relative">
                    <input
                        ref="searchInput"
                        v-model="searchQuery"
                        @input="onSearchInput"
                        @focus="showSearchResults = true"
                        type="text"
                        placeholder="Cari alamat, tempat, atau lokasi..."
                        class="w-full px-4 py-3 bg-white border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 placeholder:text-gray-500 text-gray-900"
                    />
                    <div v-if="searching" class="absolute right-3 top-3">
                        <div class="animate-spin rounded-full h-5 w-5 border-b-2 border-blue-600"></div>
                    </div>
                </div>
                <button
                    @click="useCurrentLocation"
                    class="px-4 py-3 bg-green-600 text-white rounded-xl hover:bg-green-700 transition-colors flex items-center space-x-2"
                    :disabled="gettingLocation"
                    title="Gunakan lokasi saya"
                >
                    <svg v-if="gettingLocation" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </button>
            </div>
            
            <!-- Search Results Dropdown -->
            <div 
                v-if="showSearchResults && searchResults.length > 0" 
                class="absolute z-50 w-full mt-2 bg-white border border-gray-300 rounded-xl shadow-xl max-h-60 overflow-y-auto"
                style="top: 100%;"
            >
                <div
                    v-for="result in searchResults"
                    :key="result.place_id"
                    @click="selectSearchResult(result)"
                    class="px-4 py-3 hover:bg-blue-50 cursor-pointer border-b border-gray-100 last:border-b-0 transition-colors group"
                >
                    <div class="flex items-start space-x-3">
                        <div class="w-6 h-6 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                            <svg class="w-3 h-3 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 group-hover:text-blue-700 truncate">
                                {{ getLocationName(result) }}
                            </p>
                            <p class="text-xs text-gray-600 group-hover:text-blue-600 mt-1 leading-relaxed">
                                {{ getLocationDetails(result) }}
                            </p>
                            <p v-if="getPostalCode(result)" class="text-xs text-green-600 mt-1 font-mono">
                                📮 {{ getPostalCode(result) }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Map Container -->
        <div class="map-container mb-4">
            <div id="map" ref="mapElement" class="w-full h-80 rounded-lg border border-gray-300"></div>
        </div>
        
        <!-- Selected Location Info -->
        <div v-if="selectedLocation" class="p-4 bg-green-50 rounded-lg border border-green-200">
            <div class="flex items-start space-x-3">
                <div class="w-6 h-6 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                    <svg class="w-3 h-3 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <p class="text-sm font-semibold text-green-800 mb-2">📍 Lokasi Terpilih</p>
                    <p class="text-sm text-green-700 leading-relaxed">{{ selectedLocation.address }}</p>
                    <div class="grid grid-cols-2 gap-3 mt-2 text-sm">
                        <div>
                            <p class="text-gray-600">Latitude</p>
                            <p class="font-mono text-green-700">{{ selectedLocation.lat.toFixed(6) }}</p>
                        </div>
                        <div>
                            <p class="text-gray-600">Longitude</p>
                            <p class="font-mono text-green-700">{{ selectedLocation.lng.toFixed(6) }}</p>
                        </div>
                    </div>
                    <p v-if="selectedLocation.postal_code" class="text-xs text-green-600 mt-2 font-mono">
                        📮 Postal Code: {{ selectedLocation.postal_code }}
                    </p>
                </div>
            </div>
        </div>

        <!-- No Selection Info -->
        <div v-else class="p-4 bg-gray-50 rounded-lg border border-gray-200">
            <div class="flex items-start space-x-3">
                <div class="w-6 h-6 bg-gray-100 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                    <svg class="w-3 h-3 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-800 mb-1">Belum ada lokasi terpilih</p>
                    <p class="text-sm text-gray-600 leading-relaxed">
                        Klik di peta atau gunakan pencarian untuk memilih lokasi. 
                        Postal code akan terisi otomatis.
                    </p>
                </div>
            </div>
        </div>

        <!-- Loading State -->
        <div v-if="mapLoading" class="flex items-center justify-center py-8 bg-gray-50 rounded-lg border border-gray-200">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
            <p class="text-gray-700 ml-3 font-medium">Memuat peta...</p>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, nextTick } from 'vue'

const props = defineProps({
    initialLat: {
        type: Number,
        default: -6.2088
    },
    initialLng: {
        type: Number,
        default: 106.8456
    }
})

const emit = defineEmits(['location-selected'])

// Reactive states
const map = ref(null)
const mapElement = ref(null)
const searchInput = ref(null)
const marker = ref(null)
const selectedLocation = ref(null)
const searchQuery = ref('')
const searchResults = ref([])
const searching = ref(false)
const gettingLocation = ref(false)
const mapLoading = ref(true)
const showSearchResults = ref(false)

// Debounce timer
let searchTimeout = null

// Close dropdown when clicking outside
const handleClickOutside = (event) => {
    const searchContainer = event.target.closest('.map-picker')
    if (!searchContainer) {
        showSearchResults.value = false
    }
}

onMounted(async () => {
    // Add click outside listener
    document.addEventListener('click', handleClickOutside)
    
    // Tunggu sampai DOM selesai render
    await nextTick()
    await initializeMap()
    
    // Jika ada initial coordinates, set marker
    if (props.initialLat && props.initialLng) {
        setSelectedLocation(props.initialLat, props.initialLng)
    }
})

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside)
    destroyMap()
    clearTimeout(searchTimeout)
})

const destroyMap = () => {
    if (map.value) {
        map.value.remove()
        map.value = null
    }
}

const initializeMap = () => {
    return new Promise((resolve) => {
        // Pastikan map element ada
        if (!mapElement.value) {
            mapLoading.value = false
            resolve()
            return
        }

        // Dynamically import Leaflet
        import('leaflet').then(L => {
            // Fix for default markers in Leaflet
            delete L.Icon.Default.prototype._getIconUrl
            L.Icon.Default.mergeOptions({
                iconRetinaUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-icon-2x.png',
                iconUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-icon.png',
                shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-shadow.png',
            })

            // Initialize map
            map.value = L.map(mapElement.value).setView([props.initialLat, props.initialLng], 13)

            // Add OpenStreetMap tiles
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors',
                maxZoom: 19
            }).addTo(map.value)

            // Add click event to map
            map.value.on('click', (e) => {
                setSelectedLocation(e.latlng.lat, e.latlng.lng)
            })

            // Handle map load event
            map.value.whenReady(() => {
                mapLoading.value = false
                resolve()
            })

            // Add initial marker jika ada koordinat
            if (props.initialLat && props.initialLng) {
                setSelectedLocation(props.initialLat, props.initialLng)
            }

        }).catch(error => {
            console.error('Error loading Leaflet:', error)
            mapLoading.value = false
            resolve()
        })
    })
}

const setSelectedLocation = (lat, lng) => {
    if (!map.value) return
    
    import('leaflet').then(L => {
        // Remove existing marker
        if (marker.value) {
            map.value.removeLayer(marker.value)
        }

        // Add new marker
        marker.value = L.marker([lat, lng]).addTo(map.value)
        
        // Center map on marker
        map.value.setView([lat, lng], 16)
        
        // Reverse geocode untuk dapat alamat lengkap + postal code
        reverseGeocode(lat, lng)
    })
}

const onSearchInput = () => {
    clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => {
        if (searchQuery.value.trim()) {
            searchLocation()
        } else {
            searchResults.value = []
            showSearchResults.value = false
        }
    }, 500)
}

const searchLocation = async () => {
    searching.value = true
    searchResults.value = []
    showSearchResults.value = true

    try {
        const response = await fetch(
            `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(searchQuery.value)}&limit=6&countrycodes=id&addressdetails=1`
        )
        
        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`)
        
        const data = await response.json()
        searchResults.value = data
        
    } catch (error) {
        console.error('Search error:', error)
        searchResults.value = []
    } finally {
        searching.value = false
    }
}

// Helper functions untuk format tampilan hasil pencarian
const getLocationName = (result) => {
    if (result.name && result.name !== result.display_name.split(',')[0]) {
        return result.name
    }
    return result.display_name.split(',')[0].trim()
}

const getLocationDetails = (result) => {
    const parts = result.display_name.split(',')
    return parts.slice(1, 3).map(part => part.trim()).join(', ')
}

// Extract postal code dari result OSM
const getPostalCode = (result) => {
    // Cari postal code dari address object
    if (result.address && result.address.postcode) {
        return result.address.postcode
    }
    return null
}

const selectSearchResult = (result) => {
    const lat = parseFloat(result.lat)
    const lng = parseFloat(result.lon)
    const postalCode = getPostalCode(result)
    
    setMarkerAndEmit(lat, lng, result.display_name, postalCode)
    
    searchQuery.value = result.display_name
    searchResults.value = []
    showSearchResults.value = false
}

const setMarkerAndEmit = (lat, lng, address, postalCode = null) => {
    if (!map.value) return
    
    import('leaflet').then(L => {
        // Remove existing marker
        if (marker.value) {
            map.value.removeLayer(marker.value)
        }

        // Add new marker
        marker.value = L.marker([lat, lng]).addTo(map.value)
        
        // Center map on marker
        map.value.setView([lat, lng], 16)
        
        // Set selected location
        selectedLocation.value = {
            lat: lat,
            lng: lng,
            address: address,
            postal_code: postalCode
        }
        
        // Emit ke parent
        emit('location-selected', selectedLocation.value)
    })
}

const reverseGeocode = async (lat, lng) => {
    try {
        const response = await fetch(
            `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1`
        )
        const data = await response.json()
        
        // Extract postal code dari reverse geocode result
        const postalCode = data.address?.postcode || null
        
        selectedLocation.value = {
            lat: lat,
            lng: lng,
            address: data.display_name,
            postal_code: postalCode
        }
        
        emit('location-selected', selectedLocation.value)
    } catch (error) {
        console.error('Reverse geocode error:', error)
    }
}

const useCurrentLocation = () => {
    gettingLocation.value = true
    
    if (!navigator.geolocation) {
        alert('Geolocation tidak didukung oleh browser Anda')
        gettingLocation.value = false
        return
    }

    navigator.geolocation.getCurrentPosition(
        (position) => {
            const lat = position.coords.latitude
            const lng = position.coords.longitude
            
            setSelectedLocation(lat, lng)
            gettingLocation.value = false
        },
        (error) => {
            console.error('Geolocation error:', error)
            let errorMessage = 'Tidak dapat mengakses lokasi Anda.'
            
            switch (error.code) {
                case error.PERMISSION_DENIED:
                    errorMessage = 'Izin lokasi ditolak. Silakan izinkan akses lokasi di browser Anda.'
                    break
                case error.POSITION_UNAVAILABLE:
                    errorMessage = 'Informasi lokasi tidak tersedia.'
                    break
                case error.TIMEOUT:
                    errorMessage = 'Permintaan lokasi timeout.'
                    break
            }
            
            alert(errorMessage)
            gettingLocation.value = false
        },
        {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 60000
        }
    )
}
</script>

<style scoped>
.map-picker {
    font-family: 'Inter', sans-serif;
    position: relative;
}

.map-container {
    position: relative;
}

#map {
    min-height: 320px;
    background: #f8fafc;
}

/* Custom scrollbar for search results */
.overflow-y-auto::-webkit-scrollbar {
    width: 6px;
}

.overflow-y-auto::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}

/* Leaflet map container styling */
:deep(.leaflet-container) {
    background: #f8fafc;
    font-family: 'Inter', sans-serif;
    font-size: 14px;
}

:deep(.leaflet-control-zoom) {
    border: none !important;
    border-radius: 8px !important;
    overflow: hidden;
}

:deep(.leaflet-control-zoom a) {
    background: white !important;
    border: 1px solid #e5e7eb !important;
    color: #374151 !important;
    font-weight: bold;
}

:deep(.leaflet-control-zoom a:hover) {
    background: #f3f4f6 !important;
}
</style>