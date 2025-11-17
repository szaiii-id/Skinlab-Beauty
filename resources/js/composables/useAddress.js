// [file name]: composables/useAddress.js
import { ref } from 'vue'
import axios from 'axios'

export const useAddress = () => {
    const addresses = ref([])
    const loading = ref(false)
    const error = ref(null)

    const api = axios.create({
        baseURL: '/api',
        withCredentials: true,
    })

    const loadAddresses = async () => {
        loading.value = true
        error.value = null
        try {
            const response = await api.get('/addresses')
            addresses.value = response.data.data
        } catch (err) {
            error.value = err.response?.data?.message || 'Gagal memuat alamat'
            console.error('Error loading addresses:', err)
        } finally {
            loading.value = false
        }
    }


    const createAddress = async (addressData) => {
        loading.value = true
        error.value = null
        try {
            console.log('🟡 Sending address data to API:', addressData)
            
            const response = await api.post('/addresses', addressData)
            console.log('🟢 Create address success:', response.data)
            
            await loadAddresses()
            return { success: true, data: response.data }
        } catch (err) {
            console.error('🔴 Create address error:', err)
            console.error('🔴 Error response:', err.response)
            
            const errorMessage = err.response?.data?.message || 
                            err.response?.data?.error || 
                            'Gagal membuat alamat'
        
            error.value = errorMessage
            return { 
                success: false, 
                error: errorMessage,
                details: err.response?.data,
                errors: err.response?.data?.errors 
            }
        } finally {
            loading.value = false
        }
    }

    const updateAddress = async (id, addressData) => {
        loading.value = true
        error.value = null
        try {
            console.log('🟡 Updating address:', id, addressData)
            
            const response = await api.put(`/addresses/${id}`, addressData)
            console.log('🟢 Update address success:', response.data)
            
            await loadAddresses()
            return { success: true, data: response.data }
        } catch (err) {
            console.error('🔴 Update address error:', err)
            console.error('🔴 Error response:', err.response)
            
            const errorMessage = err.response?.data?.message || 
                            err.response?.data?.error || 
                            'Gagal mengupdate alamat'
        
            error.value = errorMessage
            return { 
                success: false, 
                error: errorMessage,
                details: err.response?.data,
                errors: err.response?.data?.errors 
            }
        } finally {
            loading.value = false
        }
    }

    const deleteAddress = async (id) => {
        loading.value = true
        error.value = null
        try {
            await api.delete(`/addresses/${id}`)
            await loadAddresses()
            return { success: true }
        } catch (err) {
            error.value = err.response?.data?.message || 'Gagal menghapus alamat'
            return { success: false, error: error.value }
        } finally {
            loading.value = false
        }
    }

    const setDefaultAddress = async (id) => {
        loading.value = true
        error.value = null
        try {
            await api.patch(`/addresses/${id}/set-default`)
            await loadAddresses()
            return { success: true }
        } catch (err) {
            error.value = err.response?.data?.message || 'Gagal mengatur alamat default'
            return { success: false, error: error.value }
        } finally {
            loading.value = false
        }
    }

    return {
        addresses,
        loading,
        error,
        loadAddresses,
        createAddress,
        updateAddress,
        deleteAddress,
        setDefaultAddress
    }
}