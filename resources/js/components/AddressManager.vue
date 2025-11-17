<!-- [file name]: components/AddressManager.vue -->
<template>
    <div class="address-manager">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-semibold text-gray-900">Alamat Saya</h3>
            <button
                @click="openCreateForm"
                class="px-4 py-2 bg-rose-600 text-white rounded-lg hover:bg-rose-700 transition-colors flex items-center space-x-2"
            >
                <span>+</span>
                <span>Tambah Alamat Baru</span>
            </button>
        </div>

        <!-- Error Message -->
        <div v-if="error" class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
            <p class="text-red-800">{{ error }}</p>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="text-center py-8">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-rose-600 mx-auto"></div>
            <p class="text-gray-600 mt-2">Memuat alamat...</p>
        </div>

        <!-- Address List -->
        <div v-else class="space-y-4">
            <div
                v-for="address in addresses"
                :key="address.id"
                class="border rounded-lg p-4 transition-all duration-200 cursor-pointer"
                :class="address.is_default 
                    ? 'border-rose-500 bg-rose-50 shadow-sm' 
                    : 'border-gray-200 hover:border-rose-300 hover:shadow-sm'
                "
                @click="selectAddress(address)"
            >
                <div class="flex justify-between items-start">
                    <div class="flex-1">
                        <div class="flex items-center space-x-2 mb-2">
                            <h4 class="font-semibold text-gray-900">{{ address.receiver_name }}</h4>
                            <span
                                v-if="address.is_default"
                                class="px-2 py-1 bg-rose-100 text-rose-800 text-xs font-medium rounded-full"
                            >
                                Default
                            </span>
                            <span class="px-2 py-1 bg-gray-100 text-gray-600 text-xs rounded-full capitalize">
                                {{ address.type }}
                            </span>
                        </div>
                        
                        <p class="text-gray-600 text-sm mb-1 flex items-center">
                            <span class="mr-2">📞</span>
                            {{ address.phone_number }}
                        </p>
                        
                        <p class="text-gray-600 text-sm mb-2 leading-relaxed">
                            {{ address.full_address }}, 
                            <span v-if="address.district">{{ address.district.name }}, </span>
                            {{ address.city.name }}, 
                            {{ address.province.name }} - 
                            {{ address.postal_code }}
                        </p>
                    </div>
                    
                    <div class="flex space-x-2 ml-4">
                        <button
                            @click.stop="openEditForm(address)"
                            class="px-3 py-1 text-blue-600 hover:text-blue-800 text-sm font-medium border border-blue-200 rounded hover:bg-blue-50 transition-colors"
                        >
                            Edit
                        </button>
                        <button
                            v-if="!address.is_default"
                            @click.stop="setAsDefault(address.id)"
                            class="px-3 py-1 text-rose-600 hover:text-rose-800 text-sm font-medium border border-rose-200 rounded hover:bg-rose-50 transition-colors"
                        >
                            Jadikan Default
                        </button>
                        <button
                            @click.stop="openDeleteModal(address)"
                            class="px-3 py-1 text-red-600 hover:text-red-800 text-sm font-medium border border-red-200 rounded hover:bg-red-50 transition-colors"
                        >
                            Hapus
                        </button>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div
                v-if="addresses.length === 0"
                class="text-center py-12 border-2 border-dashed border-gray-300 rounded-lg"
            >
                <div class="text-gray-400 mb-3">
                    <svg class="w-12 h-12 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <p class="text-gray-500 mb-4">Belum ada alamat yang disimpan</p>
                <button
                    @click="openCreateForm"
                    class="text-rose-600 hover:text-rose-700 font-medium"
                >
                    Tambah alamat pertama
                </button>
            </div>
        </div>

        <!-- Address Form Modal -->
        <AddressFormModal
            :show="showForm"
            :address="editingAddress"
            @close="closeForm"
            @saved="handleSaved"
        />

        <!-- Delete Confirmation Modal -->
        <DeleteConfirmationModal
            :show="showDeleteModal"
            :address="deletingAddress"
            @close="closeDeleteModal"
            @confirmed="handleDeleteConfirmed"
        />
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useAddress } from '@/composables/useAddress'
import AddressFormModal from './AddressFormModal.vue'
import DeleteConfirmationModal from './DeleteConfirmationModal.vue'

const emit = defineEmits(['address-selected'])

const { 
    addresses, 
    loading, 
    error, 
    loadAddresses, 
    deleteAddress, 
    setDefaultAddress 
} = useAddress()

const showForm = ref(false)
const editingAddress = ref(null)
const showDeleteModal = ref(false)
const deletingAddress = ref(null)

const openCreateForm = () => {
    editingAddress.value = null
    showForm.value = true
}

const openEditForm = (address) => {
    editingAddress.value = address
    showForm.value = true
}

const closeForm = () => {
    showForm.value = false
    editingAddress.value = null
}

const openDeleteModal = (address) => {
    deletingAddress.value = address
    showDeleteModal.value = true
}

const closeDeleteModal = () => {
    showDeleteModal.value = false
    deletingAddress.value = null
}

const handleSaved = () => {
    closeForm()
    loadAddresses()
}

const handleDeleteConfirmed = async (address) => {
    const result = await deleteAddress(address.id)
    if (result.success) {
        closeDeleteModal()
        loadAddresses()
    }
}

const setAsDefault = async (id) => {
    const result = await setDefaultAddress(id)
    if (result.success) {
        loadAddresses()
    }
}

const selectAddress = (address) => {
    emit('address-selected', address)
}

onMounted(() => {
    loadAddresses()
})
</script>