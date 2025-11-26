<script setup>
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import AddressList from '@/pages/Profile/AddressList.vue'; 
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import { 
    User, 
    Mail, 
    Lock, 
    Save, 
    Trash2,
    CheckCircle2,
    AlertCircle
} from 'lucide-vue-next';

defineOptions({ layout: DashboardLayout });

const props = defineProps({
    status: String,
});

const user = usePage().props.auth.user;

// --- FORM 1: UPDATE PROFILE INFO ---
const formInfo = useForm({
    name: user.name,
    email: user.email,
});

const updateProfileInformation = () => {
    formInfo.patch(route('profile.update'), {
        preserveScroll: true,
    });
};

// --- FORM 2: UPDATE PASSWORD ---
const formPassword = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const updatePassword = () => {
    formPassword.put(route('user-password.update'), { 
        preserveScroll: true,
        onSuccess: () => formPassword.reset(),
        onError: () => {
            if (formPassword.errors.password) {
                formPassword.reset('password', 'password_confirmation');
            }
            if (formPassword.errors.current_password) {
                formPassword.reset('current_password');
            }
        },
    });
};

// --- FORM 3: DELETE ACCOUNT ---
const confirmingUserDeletion = ref(false);
const passwordInput = ref(null);
const formDelete = useForm({ password: '' });

const confirmUserDeletion = () => {
    confirmingUserDeletion.value = true;
    setTimeout(() => passwordInput.value.focus(), 250);
};

const deleteUser = () => {
    formDelete.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => passwordInput.value.focus(),
    });
};

const closeModal = () => {
    confirmingUserDeletion.value = false;
    formDelete.reset();
};
</script>

<template>
    <Head title="Profil Saya" />

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 text-gray-800">
        
        <div class="mb-8">
            <h1 class="text-2xl font-light text-gray-900">
                Pengaturan <span class="font-semibold text-rose-600">Profil</span>
            </h1>
            <p class="text-gray-500 text-sm mt-1">Kelola informasi akun, alamat, dan keamanan Anda.</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <div class="lg:col-span-2 space-y-8">
                
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                    <div class="flex items-center gap-3 mb-6 border-b border-gray-100 pb-4">
                        <div class="p-2 bg-rose-100 text-rose-600 rounded-lg">
                            <User class="w-5 h-5" />
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-gray-900">Informasi Pribadi</h2>
                            <p class="text-xs text-gray-500">Update nama tampilan Anda.</p>
                        </div>
                    </div>

                    <form @submit.prevent="updateProfileInformation" class="space-y-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap</label>
                            <div class="relative">
                                <User class="absolute left-3 top-2.5 w-5 h-5 text-gray-400" />
                                <input 
                                    v-model="formInfo.name"
                                    type="text" 
                                    class="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-rose-500 focus:border-rose-500 transition-colors bg-white text-gray-900 placeholder-gray-400"
                                    required
                                >
                            </div>
                            <p v-if="formInfo.errors.name" class="text-xs text-red-600 mt-1">{{ formInfo.errors.name }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Alamat Email</label>
                            <div class="relative">
                                <Mail class="absolute left-3 top-2.5 w-5 h-5 text-gray-400" />
                                <input 
                                    v-model="formInfo.email"
                                    type="email" 
                                    disabled
                                    class="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-lg bg-gray-100 text-gray-500 cursor-not-allowed focus:ring-0 transition-colors"
                                >
                            </div>
                            <div class="mt-2 flex items-start gap-2 text-xs text-gray-500 bg-gray-50 p-2 rounded border border-gray-100">
                                <AlertCircle class="w-4 h-4 text-gray-400 shrink-0" />
                                <p>Email terdaftar tidak dapat diubah demi keamanan akun.</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-4 pt-2">
                            <button 
                                :disabled="formInfo.processing"
                                type="submit" 
                                class="flex items-center gap-2 bg-rose-600 text-white px-5 py-2 rounded-lg shadow-md hover:bg-rose-700 transition disabled:opacity-50"
                            >
                                <Save class="w-4 h-4" />
                                <span>Simpan Perubahan</span>
                            </button>
                            
                            <Transition
                                enter-active-class="transition ease-in-out"
                                enter-from-class="opacity-0"
                                leave-active-class="transition ease-in-out"
                                leave-to-class="opacity-0"
                            >
                                <p v-if="formInfo.recentlySuccessful" class="text-sm text-green-600 flex items-center gap-1 font-medium">
                                    <CheckCircle2 class="w-4 h-4" /> Tersimpan.
                                </p>
                            </Transition>
                        </div>
                    </form>
                </div>

                <AddressList />

                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                    <div class="flex items-center gap-3 mb-6 border-b border-gray-100 pb-4">
                        <div class="p-2 bg-rose-100 text-rose-600 rounded-lg">
                            <Lock class="w-5 h-5" />
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-gray-900">Ubah Password</h2>
                            <p class="text-xs text-gray-500">Amankan akun Anda dengan password yang kuat.</p>
                        </div>
                    </div>

                    <form @submit.prevent="updatePassword" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Password Saat Ini</label>
                            <input v-model="formPassword.current_password" type="password" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-rose-500 focus:border-rose-500 transition-colors bg-white text-gray-900 placeholder-gray-400">
                            <p v-if="formPassword.errors.current_password" class="text-xs text-red-600 mt-1">{{ formPassword.errors.current_password }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Password Baru</label>
                            <input v-model="formPassword.password" type="password" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-rose-500 focus:border-rose-500 transition-colors bg-white text-gray-900 placeholder-gray-400">
                            <p v-if="formPassword.errors.password" class="text-xs text-red-600 mt-1">{{ formPassword.errors.password }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Konfirmasi Password</label>
                            <input v-model="formPassword.password_confirmation" type="password" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-rose-500 focus:border-rose-500 transition-colors bg-white text-gray-900 placeholder-gray-400">
                            <p v-if="formPassword.errors.password_confirmation" class="text-xs text-red-600 mt-1">{{ formPassword.errors.password_confirmation }}</p>
                        </div>

                        <div class="pt-2">
                            <button :disabled="formPassword.processing" type="submit" class="flex items-center gap-2 bg-gray-800 text-white px-5 py-2 rounded-lg shadow hover:bg-gray-900 transition disabled:opacity-50">
                                <Save class="w-4 h-4" /> <span>Update Password</span>
                            </button>
                            <Transition enter-active-class="transition ease-in-out" enter-from-class="opacity-0" leave-active-class="transition ease-in-out" leave-to-class="opacity-0">
                                <p v-if="formPassword.recentlySuccessful" class="text-sm text-green-600 mt-2 font-medium">Password berhasil diperbarui.</p>
                            </Transition>
                        </div>
                    </form>
                </div>
            </div>

            <div class="lg:col-span-1">
                <div class="bg-red-50 p-6 rounded-2xl border border-red-100 sticky top-8">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="p-2 bg-red-100 text-red-600 rounded-lg">
                            <Trash2 class="w-5 h-5" />
                        </div>
                        <h2 class="text-lg font-bold text-red-800">Hapus Akun</h2>
                    </div>
                    
                    <p class="text-sm text-red-600 mb-6 leading-relaxed">
                        Setelah akun dihapus, semua data riwayat pesanan, poin, dan analisis kulit akan hilang permanen.
                    </p>

                    <button @click="confirmUserDeletion" class="w-full bg-white border border-red-200 text-red-600 px-4 py-2 rounded-lg hover:bg-red-600 hover:text-white transition font-medium text-sm shadow-sm">
                        Hapus Akun Saya
                    </button>
                </div>
            </div>
        </div>

        <div v-if="confirmingUserDeletion" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
            <div class="bg-white w-full max-w-md rounded-2xl shadow-xl p-6 animate-fade-in-up">
                <h2 class="text-lg font-bold text-gray-900 mb-2">Apakah Anda yakin?</h2>
                <p class="text-sm text-gray-500 mb-6">Masukkan password Anda untuk mengonfirmasi penghapusan akun.</p>
                <div class="mb-6">
                    <input ref="passwordInput" v-model="formDelete.password" type="password" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 bg-white text-gray-900" placeholder="Password" @keyup.enter="deleteUser" />
                    <p v-if="formDelete.errors.password" class="text-xs text-red-600 mt-1">{{ formDelete.errors.password }}</p>
                </div>
                <div class="flex justify-end gap-3">
                    <button @click="closeModal" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition text-sm font-medium">Batal</button>
                    <button @click="deleteUser" :disabled="formDelete.processing" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition text-sm font-medium shadow-md">Hapus Akun</button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.animate-fade-in-up { animation: fadeInUp 0.3s ease-out forwards; }
@keyframes fadeInUp { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
</style>