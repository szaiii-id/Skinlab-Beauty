<template>
    <div class="map-picker">
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
            
            <div 
                v-if="showSearchResults && searchResults.length > 0" 
                class="absolute z-50 w-full mt-2 bg-white border border-gray-300 rounded-xl shadow-xl max-h-60 overflow-y-auto"
                style="top: 100%;"
            >
                <div
                    v-for="(result, index) in searchResults"
                    :key="index"
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
                                {{ result.display_name }}
                            </p>
                            <p v-if="result.postcode" class="text-xs text-green-600 mt-1 font-mono">
                                📮 {{ result.postcode }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="map-container mb-4 relative z-0">
            <div id="map" ref="mapElement" class="w-full h-80 rounded-lg border border-gray-300 z-0"></div>
        </div>
        
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

        <div v-if="mapLoading" class="flex items-center justify-center py-8 bg-gray-50 rounded-lg border border-gray-200">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
            <p class="text-gray-700 ml-3 font-medium">Memuat peta...</p>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, nextTick } from 'vue'

const props = defineProps({
    // [FIX] Mengizinkan String agar tidak error "Invalid Prop" jika parent mengirim string kosong
    initialLat: {
        type: [Number, String],
        default: -6.2088
    },
    initialLng: {
        type: [Number, String],
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
    document.addEventListener('click', handleClickOutside)
    await nextTick()
    await initializeMap()
    
    // [FIX] Parse ke float untuk memastikan tipe data benar
    const initLat = parseFloat(props.initialLat)
    const initLng = parseFloat(props.initialLng)

    if (!isNaN(initLat) && !isNaN(initLng) && initLat !== 0) {
        setSelectedLocation(initLat, initLng)
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
        if (!mapElement.value) {
            mapLoading.value = false
            resolve()
            return
        }

        import('leaflet').then(L => {
            delete L.Icon.Default.prototype._getIconUrl
            L.Icon.Default.mergeOptions({
                iconRetinaUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-icon-2x.png',
                iconUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-icon.png',
                shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-shadow.png',
            })

            const lat = parseFloat(props.initialLat) || -6.2088
            const lng = parseFloat(props.initialLng) || 106.8456
            
            map.value = L.map(mapElement.value).setView([lat, lng], 13)

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors',
                maxZoom: 19
            }).addTo(map.value)

            map.value.on('click', (e) => {
                setSelectedLocation(e.latlng.lat, e.latlng.lng)
            })

            map.value.whenReady(() => {
                mapLoading.value = false
                resolve()
            })

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
        if (marker.value) {
            map.value.removeLayer(marker.value)
        }
        marker.value = L.marker([lat, lng]).addTo(map.value)
        map.value.setView([lat, lng], 16)
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

// [FIX 403 ERROR] GANTI NOMINATIM -> PHOTON KOMOOT
const searchLocation = async () => {
    searching.value = true
    searchResults.value = []
    showSearchResults.value = true

    try {
        // Photon API: Gratis dan tidak kena blokir CORS (403) seperti Nominatim
        const response = await fetch(
            `https://photon.komoot.io/api/?q=${encodeURIComponent(searchQuery.value)}&limit=5`
        )
        
        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`)
        
        const data = await response.json()
        
        // Mapping format Photon
        searchResults.value = data.features.map(f => ({
            display_name: [f.properties.name, f.properties.city, f.properties.country].filter(Boolean).join(', '),
            lat: f.geometry.coordinates[1],
            lon: f.geometry.coordinates[0],
            postcode: f.properties.postcode
        }))
        
    } catch (error) {
        console.error('Search error:', error)
        searchResults.value = []
    } finally {
        searching.value = false
    }
}

// [FIX HELPERS] Disesuaikan untuk data dari Photon
const getLocationName = (result) => result.display_name.split(',')[0]
const getLocationDetails = (result) => result.display_name
const getPostalCode = (result) => result.postcode || null

const selectSearchResult = (result) => {
    const lat = parseFloat(result.lat)
    const lng = parseFloat(result.lon)
    const postalCode = result.postcode
    
    setMarkerAndEmit(lat, lng, result.display_name, postalCode)
    
    searchQuery.value = result.display_name
    searchResults.value = []
    showSearchResults.value = false
}

const setMarkerAndEmit = (lat, lng, address, postalCode = null) => {
    if (!map.value) return
    
    import('leaflet').then(L => {
        if (marker.value) {
            map.value.removeLayer(marker.value)
        }
        marker.value = L.marker([lat, lng]).addTo(map.value)
        map.value.setView([lat, lng], 16)
        
        selectedLocation.value = {
            lat: lat,
            lng: lng,
            address: address,
            postal_code: postalCode
        }
        
        emit('location-selected', selectedLocation.value)
    })
}

// [FIX 403 ERROR] GANTI NOMINATIM -> BIGDATACLOUD
const reverseGeocode = async (lat, lng) => {
    
    try {
        // BigDataCloud API: Client-side geocoding gratis yang tidak kena CORS
        const response = await fetch(
            `https://api.bigdatacloud.net/data/reverse-geocode-client?latitude=${lat}&longitude=${lng}&localityLanguage=id`
        )
        const data = await response.json()
        
        // Format alamat
        const addressName = [data.locality, data.city, data.principalSubdivision, data.countryName]
            .filter(Boolean)
            .join(', ')

        const postalCode = data.postcode || null
        
        selectedLocation.value = {
            lat: lat,
            lng: lng,
            address: addressName,
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
        alert('Geolocation tidak didukung')
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
            alert('Tidak dapat mengakses lokasi')
            gettingLocation.value = false
        },
        { enableHighAccuracy: true }
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
    z-index: 0;
}
#map {
    min-height: 320px;
    background: #f8fafc;
}
:deep(.leaflet-container) {
    background: #f8fafc;
    font-family: 'Inter', sans-serif;
    font-size: 14px;
}
</style>