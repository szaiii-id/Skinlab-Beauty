// [file name]: composables/useRegions.js
import { ref } from 'vue'
import axios from 'axios'

export const useRegions = () => {
    const provinces = ref([])
    const cities = ref([])
    const districts = ref([])
    const loading = ref(false)
    const error = ref(null)

    const api = axios.create({
        baseURL: '/api',
        withCredentials: true,
    })

    const loadProvinces = async () => {
        loading.value = true
        error.value = null
        try {
            const response = await api.get('/regions/provinces')
            provinces.value = response.data.data
        } catch (err) {
            error.value = err.response?.data?.message || 'Gagal memuat provinsi'
            console.error('Error loading provinces:', err)
        } finally {
            loading.value = false
        }
    }

    const loadCities = async (provinceCode) => {
        if (!provinceCode) {
            cities.value = []
            return
        }
        
        loading.value = true
        error.value = null
        try {
            // PERBAIKAN: Sesuai dengan route list
            const response = await api.get(`/regions/cities/${provinceCode}`)
            cities.value = response.data.data
        } catch (err) {
            error.value = err.response?.data?.message || 'Gagal memuat kota'
            console.error('Error loading cities:', err)
        } finally {
            loading.value = false
        }
    }

    const loadDistricts = async (cityCode) => {
        if (!cityCode) {
            districts.value = []
            return
        }
        
        loading.value = true
        error.value = null
        try {
            // PERBAIKAN: Sesuai dengan route list
            const response = await api.get(`/regions/districts/${cityCode}`)
            districts.value = response.data.data
        } catch (err) {
            error.value = err.response?.data?.message || 'Gagal memuat kecamatan'
            console.error('Error loading districts:', err)
        } finally {
            loading.value = false
        }
    }

    return {
        provinces,
        cities,
        districts,
        loading,
        error,
        loadProvinces,
        loadCities,
        loadDistricts
    }
}