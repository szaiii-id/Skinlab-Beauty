<script setup>
import { ref, onMounted, computed } from 'vue';
import { useAddress } from '@/composables/useAddress';
import AddressFormModal from '@/components/AddressFormModal.vue';
import DeleteConfirmationModal from '@/components/DeleteConfirmationModal.vue';
import { MapPin, Plus, Pencil, Trash2, Home, Briefcase, Star, ChevronDown, ChevronUp, CheckCircle2 } from 'lucide-vue-next';

// Use Composable
const { 
    addresses, 
    loading, 
    loadAddresses, 
    deleteAddress, 
    setDefaultAddress 
} = useAddress();

// State
const showForm = ref(false);
const editingAddress = ref(null);
const showDeleteModal = ref(false);
const deletingAddress = ref(null);
const showAllAddresses = ref(false);

// --- COMPUTED PROPERTIES ---
const defaultAddress = computed(() => {
    return addresses.value.find(addr => addr.is_default) || addresses.value[0];
});

const otherAddresses = computed(() => {
    if (!defaultAddress.value) return [];
    return addresses.value.filter(addr => addr.id !== defaultAddress.value.id);
});

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

const toggleShowAll = () => {
    showAllAddresses.value = !showAllAddresses.value;
};

onMounted(() => {
    loadAddresses();
});
</script>

<template>
    <div class="bg-white p-5 sm:p-6 rounded-2xl shadow-sm border border-gray-100">
        
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4 border-b border-gray-100 pb-4">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-rose-100 text-rose-600 rounded-lg">
                    <MapPin class="w-5 h-5" />
                </div>
                <div>
                    <h2 class="text-base sm:text-lg font-bold text-gray-900">Address Book</h2>
                    <p class="text-xs text-gray-500">Manage your shipping addresses.</p>
                </div>
            </div>
            <button 
                @click="openCreateForm"
                class="w-full sm:w-auto flex justify-center items-center gap-2 bg-rose-600 text-white px-4 py-2.5 rounded-xl text-sm font-bold hover:bg-rose-700 transition shadow-md hover:shadow-lg active:scale-95 sm:active:scale-100"
            >
                <Plus class="w-4 h-4" />
                <span>Add Address</span>
            </button>
        </div>

        <div v-if="loading" class="py-12 text-center">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-rose-600 mx-auto mb-3"></div>
            <p class="text-sm text-gray-500">Loading addresses...</p>
        </div>

        <div v-else-if="addresses.length === 0" class="text-center py-12 border-2 border-dashed border-gray-100 rounded-2xl bg-gray-50/50">
            <div class="w-12 h-12 bg-white rounded-full flex items-center justify-center mx-auto mb-3 shadow-sm border border-gray-100">
                <MapPin class="w-6 h-6 text-gray-300" />
            </div>
            <p class="text-gray-900 font-bold mb-1">No saved addresses</p>
            <p class="text-xs text-gray-400 mb-4">Add an address for faster checkout.</p>
            <button @click="openCreateForm" class="text-rose-600 text-sm font-bold hover:underline">
                Add Address Now
            </button>
        </div>

        <div v-else class="space-y-6">
            
            <div v-if="defaultAddress" class="relative border rounded-2xl p-5 transition-all hover:shadow-md border-rose-200 bg-rose-50/30">
                
                <div class="absolute top-4 right-4">
                    <span class="flex items-center gap-1 bg-rose-100 text-rose-700 text-[10px] font-bold px-2 py-1 rounded-full uppercase tracking-wide">
                        <Star class="w-3 h-3 fill-rose-700" /> Default
                    </span>
                </div>

                <div class="flex items-start gap-4">
                    <div class="mt-1 p-2 rounded-full bg-white text-rose-600 shadow-sm border border-rose-100 shrink-0">
                        <Home v-if="defaultAddress.type === 'home'" class="w-5 h-5" />
                        <Briefcase v-else-if="defaultAddress.type === 'office'" class="w-5 h-5" />
                        <MapPin v-else class="w-5 h-5" />
                    </div>

                    <div class="flex-1 pr-16 min-w-0">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="text-[10px] font-bold uppercase text-gray-500 bg-white px-2 py-0.5 rounded border border-gray-200 tracking-wider">
                                {{ defaultAddress.type }}
                            </span>
                        </div>
                        <h4 class="font-bold text-gray-900 text-base mb-1 truncate">{{ defaultAddress.receiver_name }}</h4>
                        <p class="text-sm text-gray-600 mb-2 font-medium">{{ defaultAddress.phone_number }}</p>
                        <p class="text-sm text-gray-600 leading-relaxed line-clamp-2">
                            {{ defaultAddress.full_address }}
                        </p>
                        <p class="text-xs text-gray-500 mt-1">
                            {{ defaultAddress.district?.name }}, {{ defaultAddress.city?.name }}, {{ defaultAddress.province?.name }} - {{ defaultAddress.postal_code }}
                        </p>
                    </div>
                </div>

                <div class="mt-4 pt-4 border-t border-rose-100">
                    <button @click="openEditForm(defaultAddress)" class="sm:hidden w-full flex items-center justify-center gap-2 text-sm bg-white border border-rose-200 text-rose-600 font-bold py-2.5 rounded-xl hover:bg-rose-50 transition active:scale-95 shadow-sm">
                        <Pencil class="w-4 h-4" /> Edit Address
                    </button>

                    <div class="hidden sm:flex justify-end">
                        <button @click="openEditForm(defaultAddress)" class="flex items-center gap-1 text-sm text-blue-600 hover:text-blue-700 font-bold px-3 py-1.5 rounded hover:bg-blue-50 transition">
                            <Pencil class="w-3.5 h-3.5" /> Edit
                        </button>
                    </div>
                </div>
            </div>

            <div v-if="otherAddresses.length > 0">
                <button 
                    @click="toggleShowAll"
                    class="w-full flex items-center justify-between px-4 py-3 bg-gray-50 hover:bg-gray-100 rounded-xl border border-gray-200 text-sm font-bold text-gray-600 transition-colors mb-4"
                >
                    <span>{{ showAllAddresses ? 'Hide Other Addresses' : `Show ${otherAddresses.length} Other Addresses` }}</span>
                    <component :is="showAllAddresses ? ChevronUp : ChevronDown" class="w-4 h-4 text-gray-500" />
                </button>

                <div v-if="showAllAddresses" class="space-y-4 animate-in slide-in-from-top-2 fade-in duration-300">
                    <div 
                        v-for="address in otherAddresses" 
                        :key="address.id"
                        class="relative border border-gray-200 bg-white rounded-2xl p-5 transition-all hover:shadow-md hover:border-gray-300"
                    >
                        <div class="flex items-start gap-4">
                            <div class="mt-1 p-2 rounded-full bg-gray-50 text-gray-500 shrink-0">
                                <Home v-if="address.type === 'home'" class="w-5 h-5" />
                                <Briefcase v-else-if="address.type === 'office'" class="w-5 h-5" />
                                <MapPin v-else class="w-5 h-5" />
                            </div>

                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="text-[10px] font-bold uppercase text-gray-500 bg-gray-100 px-2 py-0.5 rounded border border-gray-200 tracking-wider">
                                        {{ address.type }}
                                    </span>
                                </div>
                                <h4 class="font-bold text-gray-900 text-base mb-1 truncate">{{ address.receiver_name }}</h4>
                                <p class="text-sm text-gray-600 mb-2">{{ address.phone_number }}</p>
                                <p class="text-sm text-gray-600 leading-relaxed line-clamp-2">
                                    {{ address.full_address }}
                                </p>
                                <p class="text-xs text-gray-500 mt-1">
                                    {{ address.district?.name }}, {{ address.city?.name }}, {{ address.province?.name }} - {{ address.postal_code }}
                                </p>
                            </div>
                        </div>

                        <div class="mt-4 pt-4 border-t border-gray-100">
                            
                            <div class="grid grid-cols-3 gap-2 sm:hidden">
                                <button 
                                    @click="handleSetDefault(address.id)"
                                    class="col-span-1 flex flex-col items-center justify-center gap-1 bg-gray-50 hover:bg-gray-100 text-gray-600 rounded-lg py-2 border border-gray-200 active:scale-95 transition"
                                >
                                    <Star class="w-4 h-4" />
                                    <span class="text-[10px] font-bold">Default</span>
                                </button>
                                <button 
                                    @click="openEditForm(address)"
                                    class="col-span-1 flex flex-col items-center justify-center gap-1 bg-blue-50 hover:bg-blue-100 text-blue-600 rounded-lg py-2 border border-blue-100 active:scale-95 transition"
                                >
                                    <Pencil class="w-4 h-4" />
                                    <span class="text-[10px] font-bold">Edit</span>
                                </button>
                                <button 
                                    @click="openDeleteModal(address)"
                                    class="col-span-1 flex flex-col items-center justify-center gap-1 bg-red-50 hover:bg-red-100 text-red-600 rounded-lg py-2 border border-red-100 active:scale-95 transition"
                                >
                                    <Trash2 class="w-4 h-4" />
                                    <span class="text-[10px] font-bold">Delete</span>
                                </button>
                            </div>

                            <div class="hidden sm:flex justify-end items-center gap-3">
                                <button 
                                    @click="handleSetDefault(address.id)"
                                    class="text-xs font-bold text-gray-400 hover:text-rose-600 mr-auto transition-colors uppercase tracking-wide flex items-center gap-1"
                                >
                                    <Star class="w-3 h-3" /> Set Default
                                </button>

                                <button @click="openEditForm(address)" class="flex items-center gap-1 text-sm text-blue-600 hover:text-blue-700 font-bold px-3 py-1.5 rounded hover:bg-blue-50 transition">
                                    <Pencil class="w-3.5 h-3.5" /> Edit
                                </button>

                                <button @click="openDeleteModal(address)" class="flex items-center gap-1 text-sm text-red-600 hover:text-red-700 font-bold px-3 py-1.5 rounded hover:bg-red-50 transition">
                                    <Trash2 class="w-3.5 h-3.5" /> Delete
                                </button>
                            </div>

                        </div>
                    </div>
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