<script setup>
import { ref, onMounted } from 'vue';
import { useAddress } from '@/composables/useAddress'; // Pastikan path ini benar sesuai struktur project Anda
import AddressFormModal from '@/components/AddressFormModal.vue'; // Gunakan komponen modal yang Anda kirim
import DeleteConfirmationModal from '@/components/DeleteConfirmationModal.vue'; // Pastikan komponen ini ada
import { MapPin, Plus, Pencil, Trash2, Home, Briefcase, Star } from 'lucide-vue-next';

const { 
    addresses, 
    loading, 
    loadAddresses, 
    deleteAddress, 
    setDefaultAddress 
} = useAddress();

const showForm = ref(false);
const editingAddress = ref(null);
const showDeleteModal = ref(false);
const deletingAddress = ref(null);

// --- MODAL HANDLERS ---
const openCreateForm = () => {
    editingAddress.value = null;
    showForm.value = true;
};

const openEditForm = (address) => {
    editingAddress.value = address;
    showForm.value = true;
};

const closeForm = () => {
    showForm.value = false;
    editingAddress.value = null;
};

const handleSaved = () => {
    closeForm();
    loadAddresses();
};

// --- DELETE HANDLERS ---
const openDeleteModal = (address) => {
    deletingAddress.value = address;
    showDeleteModal.value = true;
};

const closeDeleteModal = () => {
    showDeleteModal.value = false;
    deletingAddress.value = null;
};

const handleDeleteConfirmed = async (address) => {
    const result = await deleteAddress(address.id);
    if (result.success) {
        closeDeleteModal();
        loadAddresses();
    }
};

// --- SET DEFAULT HANDLER ---
const handleSetDefault = async (id) => {
    await setDefaultAddress(id);
    await loadAddresses();
};

onMounted(() => {
    loadAddresses();
});
</script>

<template>
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4 border-b border-gray-100 pb-4">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-rose-100 text-rose-600 rounded-lg">
                    <MapPin class="w-5 h-5" />
                </div>
                <div>
                    <h2 class="text-lg font-bold text-gray-800">Daftar Alamat</h2>
                    <p class="text-xs text-gray-500">Kelola alamat pengiriman Anda.</p>
                </div>
            </div>
            <button 
                @click="openCreateForm"
                class="flex items-center gap-2 bg-rose-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-rose-700 transition shadow-md hover:shadow-lg"
            >
                <Plus class="w-4 h-4" />
                <span>Tambah Alamat</span>
            </button>
        </div>

        <div v-if="loading" class="py-8 text-center">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-rose-600 mx-auto mb-2"></div>
            <p class="text-sm text-gray-500">Memuat alamat...</p>
        </div>

        <div v-else-if="addresses.length === 0" class="text-center py-10 border-2 border-dashed border-gray-100 rounded-xl bg-gray-50">
            <MapPin class="w-10 h-10 text-gray-300 mx-auto mb-3" />
            <p class="text-gray-500 font-medium">Belum ada alamat tersimpan</p>
            <p class="text-xs text-gray-400 mb-4">Tambahkan alamat untuk memudahkan checkout.</p>
            <button @click="openCreateForm" class="text-rose-600 text-sm font-bold hover:underline">
                Tambah Alamat Sekarang
            </button>
        </div>

        <div v-else class="grid grid-cols-1 gap-4">
            <div 
                v-for="address in addresses" 
                :key="address.id"
                class="relative border rounded-xl p-4 transition-all hover:shadow-md"
                :class="address.is_default ? 'border-rose-200 bg-rose-50/50' : 'border-gray-200 bg-white'"
            >
                <div v-if="address.is_default" class="absolute top-4 right-4">
                    <span class="flex items-center gap-1 bg-rose-100 text-rose-700 text-[10px] font-bold px-2 py-1 rounded-full uppercase tracking-wide">
                        <Star class="w-3 h-3 fill-rose-700" /> Utama
                    </span>
                </div>

                <div class="flex items-start gap-4">
                    <div class="mt-1 p-2 rounded-full" :class="address.is_default ? 'bg-white text-rose-600' : 'bg-gray-100 text-gray-500'">
                        <Home v-if="address.type === 'home'" class="w-5 h-5" />
                        <Briefcase v-else-if="address.type === 'office'" class="w-5 h-5" />
                        <MapPin v-else class="w-5 h-5" />
                    </div>

                    <div class="flex-1 pr-16"> <div class="flex items-center gap-2 mb-1">
                            <span class="text-xs font-bold uppercase text-gray-500 tracking-wider">{{ address.type }}</span>
                        </div>
                        <h4 class="font-bold text-gray-900 text-base mb-1">{{ address.receiver_name }}</h4>
                        <p class="text-sm text-gray-600 mb-2">{{ address.phone_number }}</p>
                        <p class="text-sm text-gray-600 leading-relaxed">
                            {{ address.full_address }}
                        </p>
                        <p class="text-sm text-gray-500 mt-1">
                            {{ address.district?.name }}, {{ address.city?.name }}, {{ address.province?.name }} - {{ address.postal_code }}
                        </p>
                    </div>
                </div>

                <div class="flex justify-end items-center gap-3 mt-4 pt-4 border-t" :class="address.is_default ? 'border-rose-200' : 'border-gray-100'">
                    <button 
                        v-if="!address.is_default"
                        @click="handleSetDefault(address.id)"
                        class="text-xs font-medium text-gray-500 hover:text-rose-600 mr-auto transition-colors"
                    >
                        Jadikan Utama
                    </button>

                    <button 
                        @click="openEditForm(address)" 
                        class="flex items-center gap-1 text-sm text-blue-600 hover:text-blue-700 font-medium px-2 py-1 rounded hover:bg-blue-50 transition"
                    >
                        <Pencil class="w-3.5 h-3.5" /> Edit
                    </button>

                    <button 
                        @click="openDeleteModal(address)" 
                        class="flex items-center gap-1 text-sm text-red-600 hover:text-red-700 font-medium px-2 py-1 rounded hover:bg-red-50 transition"
                    >
                        <Trash2 class="w-3.5 h-3.5" /> Hapus
                    </button>
                </div>
            </div>
        </div>

        <AddressFormModal
            :show="showForm"
            :address="editingAddress"
            @close="closeForm"
            @saved="handleSaved"
        />

        <DeleteConfirmationModal
            :show="showDeleteModal"
            :address="deletingAddress"
            @close="closeDeleteModal"
            @confirmed="handleDeleteConfirmed"
        />
    </div>
</template>