<script setup>
import { Head } from '@inertiajs/vue3';
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import { ref } from 'vue';
import { Plus } from 'lucide-vue-next';
import { useRoutine } from '@/composables/useRoutine';

// Components
import RoutineCard from '@/components/RoutineCard.vue';
import RoutineFormModal from '@/components/RoutineFormModal.vue';
import DeleteConfirmationModal from '@/components/DeleteConfirmationModal.vue'; 
defineOptions({ layout: DashboardLayout });

const props = defineProps({
    routines: Array,
    storeProducts: Array
});

// Composable Logic
const { groupedRoutines, progress, toggleCheck, deleteSingle, deleteAll } = useRoutine(props);

// State
const showFormModal = ref(false);
const showDeleteModal = ref(false);
const isEditMode = ref(false);
const editingId = ref(null);
const editingData = ref(null);

const deleteData = ref({ type: 'single', id: null, name: '' });

// Actions
const openAdd = () => {
    isEditMode.value = false;
    editingId.value = null;
    editingData.value = null;
    showFormModal.value = true;
};

const openEdit = (slot, groupName) => {
    isEditMode.value = true;
    editingId.value = slot.id;
    editingData.value = { ...slot.original_data, name: groupName };
    showFormModal.value = true;
};

const confirmDelete = (slot, groupName, type = 'single') => {
    deleteData.value = { type, id: slot.id, name: groupName };
    showDeleteModal.value = true;
};

const handleDelete = () => {
    const onSuccess = () => { showDeleteModal.value = false; showFormModal.value = false; };
    
    if (deleteData.value.type === 'single') {
        deleteSingle(deleteData.value.id, onSuccess);
    } else {
        deleteAll(deleteData.value.id, onSuccess);
    }
};
</script>

<template>
    <Head title="Skincare Routine" />
    <div class="max-w-3xl mx-auto py-8 px-4 pb-24">
        
        <div class="bg-gradient-to-r from-rose-500 to-pink-600 rounded-3xl p-6 text-white shadow-lg mb-6 relative overflow-hidden">
            <div class="relative z-10 flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold">My Routine</h1>
                    <p class="text-rose-100 text-sm mt-1">Consistency = Glowing Skin ✨</p>
                </div>
                <div class="text-center bg-white/20 backdrop-blur-sm rounded-xl p-3 min-w-[80px]">
                    <span class="block text-2xl font-bold">{{ progress }}%</span>
                    <span class="text-[10px] uppercase tracking-wider opacity-80">Today</span>
                </div>
            </div>
            <div class="absolute top-0 right-0 w-32 h-32 bg-white opacity-10 rounded-full blur-2xl -mr-10 -mt-10"></div>
        </div>

        <button 
            @click="openAdd"
            class="w-full bg-white border-2 border-dashed border-rose-300 text-rose-600 font-bold py-4 rounded-2xl hover:bg-rose-50 hover:border-rose-400 hover:shadow-md transition-all flex items-center justify-center gap-2 mb-8 group"
        >
            <div class="bg-rose-100 p-1.5 rounded-full group-hover:bg-rose-200 transition-colors">
                <Plus class="w-5 h-5" />
            </div>
            <span class="text-lg">Add New Routine</span>
        </button>

        <div class="space-y-4">
            <div v-if="groupedRoutines.length === 0" class="text-center py-8">
                <p class="text-gray-400 text-sm">No routines yet. Start by adding one!</p>
            </div>

            <RoutineCard 
                v-for="(group, index) in groupedRoutines" 
                :key="index"
                :group="group"
                @toggle="toggleCheck"
                @edit="openEdit"
                @delete-single="(slot, name) => confirmDelete(slot, name, 'single')"
                @delete-all="(slot, name) => confirmDelete(slot, name, 'all')"
            />
        </div>

        <RoutineFormModal 
            :show="showFormModal"
            :is-edit="isEditMode"
            :editing-id="editingId"
            :initial-data="editingData"
            :store-products="storeProducts"
            @close="showFormModal = false"
            @delete="() => confirmDelete({id: editingId}, editingData.name, 'single')"
        />

        <div v-if="showDeleteModal" class="fixed inset-0 z-[60] flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
            <div class="bg-white rounded-2xl w-full max-w-sm p-6 text-center shadow-2xl">
                <h3 class="font-bold text-lg text-gray-900 mb-2">Delete Routine?</h3>
                <p class="text-sm text-gray-500 mb-6">
                    Are you sure you want to delete {{ deleteData.type === 'all' ? 'ALL schedules for' : 'this schedule for' }} 
                    <strong class="text-gray-800">{{ deleteData.name }}</strong>?
                </p>
                <div class="space-y-3">
                    <button @click="handleDelete" class="w-full bg-red-600 text-white font-bold py-3 rounded-xl hover:bg-red-700 transition-colors shadow-lg shadow-red-200">
                        Yes, Delete {{ deleteData.type === 'all' ? 'All' : '' }}
                    </button>
                    <button @click="showDeleteModal = false" class="w-full text-gray-400 font-medium hover:text-gray-600 py-2">
                        Cancel
                    </button>
                </div>
            </div>
        </div>

    </div>
</template>