<script setup>
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import AddressList from '@/pages/Profile/AddressList.vue'; 
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import { 
    User, Mail, Lock, Save, Trash2, CheckCircle2, 
    AlertCircle, X, ChevronDown 
} from 'lucide-vue-next';

defineOptions({ layout: DashboardLayout });

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
    setTimeout(() => {
        if(passwordInput.value) passwordInput.value.focus();
    }, 250);
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
    <Head title="My Profile" />

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 text-gray-800 pb-24 sm:pb-8">
        
        <div class="mb-6 sm:mb-8">
            <h1 class="text-xl sm:text-2xl font-light text-gray-900">
                Account <span class="font-bold text-rose-600">Settings</span>
            </h1>
            <p class="text-gray-500 text-xs sm:text-sm mt-1">Manage your account information, addresses, and security.</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 sm:gap-8">
            
            <div class="lg:col-span-2 space-y-6 sm:space-y-8">
                
                <div class="bg-white p-5 sm:p-6 rounded-2xl shadow-sm border border-gray-100">
                    <div class="flex items-center gap-3 mb-5 sm:mb-6 border-b border-gray-100 pb-4">
                        <div class="p-2 bg-rose-100 text-rose-600 rounded-lg">
                            <User class="w-5 h-5" />
                        </div>
                        <div>
                            <h2 class="text-base sm:text-lg font-bold text-gray-900">Personal Information</h2>
                            <p class="text-[10px] sm:text-xs text-gray-500">Update your display name and contact details.</p>
                        </div>
                    </div>

                    <form @submit.prevent="updateProfileInformation" class="space-y-5 sm:space-y-6">
                        <div>
                            <label class="block text-xs sm:text-sm font-bold text-gray-700 mb-1.5">Full Name</label>
                            <div class="relative">
                                <User class="absolute left-3 top-3 w-5 h-5 text-gray-400" />
                                <input 
                                    v-model="formInfo.name"
                                    type="text" 
                                    class="w-full pl-10 pr-4 py-2.5 border rounded-xl focus:outline-none transition-all text-sm"
                                    :class="formInfo.errors.name ? 'border-red-500 focus:border-red-500 focus:ring-2 focus:ring-red-200' : 'border-gray-200 focus:border-rose-500 focus:ring-2 focus:ring-rose-100'"
                                    placeholder="Enter your full name"
                                    required
                                >
                            </div>
                            <p v-if="formInfo.errors.name" class="text-xs text-red-600 mt-1 font-medium">{{ formInfo.errors.name }}</p>
                        </div>

                        <div>
                            <label class="block text-xs sm:text-sm font-bold text-gray-700 mb-1.5">Email Address</label>
                            <div class="relative">
                                <Mail class="absolute left-3 top-3 w-5 h-5 text-gray-400" />
                                <input 
                                    v-model="formInfo.email"
                                    type="email" 
                                    disabled
                                    class="w-full pl-10 pr-4 py-2.5 border border-gray-200 rounded-xl bg-gray-50 text-gray-500 cursor-not-allowed focus:ring-0 transition-colors text-sm"
                                >
                            </div>
                            <div class="mt-2 flex items-start gap-2 text-[10px] sm:text-xs text-gray-500 bg-gray-50 p-2 rounded-lg border border-gray-100">
                                <AlertCircle class="w-4 h-4 text-gray-400 shrink-0" />
                                <p>Email cannot be changed for security reasons.</p>
                            </div>
                        </div>

                        <div class="flex flex-col sm:flex-row items-center gap-3 sm:gap-4 pt-2">
                            <button 
                                :disabled="formInfo.processing"
                                type="submit" 
                                class="w-full sm:w-auto flex justify-center items-center gap-2 bg-rose-600 text-white px-6 py-2.5 rounded-xl font-bold shadow-lg shadow-rose-200 hover:bg-rose-700 active:scale-95 transition-all disabled:opacity-50 text-sm"
                            >
                                <Save class="w-4 h-4" />
                                <span>{{ formInfo.processing ? 'Saving...' : 'Save Changes' }}</span>
                            </button>
                            
                            <Transition
                                enter-active-class="transition ease-in-out"
                                enter-from-class="opacity-0"
                                leave-active-class="transition ease-in-out"
                                leave-to-class="opacity-0"
                            >
                                <p v-if="formInfo.recentlySuccessful" class="text-sm text-green-600 flex items-center gap-1 font-bold bg-green-50 px-3 py-1 rounded-full border border-green-100">
                                    <CheckCircle2 class="w-4 h-4" /> Saved.
                                </p>
                            </Transition>
                        </div>
                    </form>
                </div>

                <AddressList />

                <div class="bg-white p-5 sm:p-6 rounded-2xl shadow-sm border border-gray-100">
                    <div class="flex items-center gap-3 mb-5 sm:mb-6 border-b border-gray-100 pb-4">
                        <div class="p-2 bg-rose-100 text-rose-600 rounded-lg">
                            <Lock class="w-5 h-5" />
                        </div>
                        <div>
                            <h2 class="text-base sm:text-lg font-bold text-gray-900">Change Password</h2>
                            <p class="text-[10px] sm:text-xs text-gray-500">Ensure your account is using a long, random password.</p>
                        </div>
                    </div>

                    <form @submit.prevent="updatePassword" class="space-y-4 sm:space-y-5">
                        
                        <div>
                            <label class="block text-xs sm:text-sm font-bold text-gray-700 mb-1.5">Current Password</label>
                            <input 
                                v-model="formPassword.current_password" 
                                type="password" 
                                class="w-full px-4 py-2.5 border rounded-xl focus:outline-none transition-all text-sm"
                                :class="formPassword.errors.current_password ? 'border-red-500 focus:border-red-500 focus:ring-2 focus:ring-red-200' : 'border-gray-200 focus:border-rose-500 focus:ring-2 focus:ring-rose-100'"
                                placeholder="Enter current password"
                            >
                            <p v-if="formPassword.errors.current_password" class="text-xs text-red-600 mt-1 font-medium">{{ formPassword.errors.current_password }}</p>
                        </div>

                        <div>
                            <label class="block text-xs sm:text-sm font-bold text-gray-700 mb-1.5">New Password</label>
                            <input 
                                v-model="formPassword.password" 
                                type="password" 
                                class="w-full px-4 py-2.5 border rounded-xl focus:outline-none transition-all text-sm"
                                :class="formPassword.errors.password ? 'border-red-500 focus:border-red-500 focus:ring-2 focus:ring-red-200' : 'border-gray-200 focus:border-rose-500 focus:ring-2 focus:ring-rose-100'"
                                placeholder="Enter new password"
                            >
                            <p v-if="formPassword.errors.password" class="text-xs text-red-600 mt-1 font-medium">{{ formPassword.errors.password }}</p>
                        </div>

                        <div>
                            <label class="block text-xs sm:text-sm font-bold text-gray-700 mb-1.5">Confirm Password</label>
                            <input 
                                v-model="formPassword.password_confirmation" 
                                type="password" 
                                class="w-full px-4 py-2.5 border rounded-xl focus:outline-none transition-all text-sm"
                                :class="formPassword.errors.password_confirmation ? 'border-red-500 focus:border-red-500 focus:ring-2 focus:ring-red-200' : 'border-gray-200 focus:border-rose-500 focus:ring-2 focus:ring-rose-100'"
                                placeholder="Retype new password"
                            >
                            <p v-if="formPassword.errors.password_confirmation" class="text-xs text-red-600 mt-1 font-medium">{{ formPassword.errors.password_confirmation }}</p>
                        </div>

                        <div class="pt-2">
                            <button 
                                :disabled="formPassword.processing" 
                                type="submit" 
                                class="w-full sm:w-auto flex justify-center items-center gap-2 bg-gray-900 text-white px-6 py-2.5 rounded-xl font-bold shadow-md hover:bg-black transition-all active:scale-95 disabled:opacity-50 text-sm"
                            >
                                <Save class="w-4 h-4" /> 
                                <span>{{ formPassword.processing ? 'Updating...' : 'Update Password' }}</span>
                            </button>
                            
                            <Transition enter-active-class="transition ease-in-out" enter-from-class="opacity-0" leave-active-class="transition ease-in-out" leave-to-class="opacity-0">
                                <p v-if="formPassword.recentlySuccessful" class="text-sm text-green-600 mt-3 font-bold flex items-center gap-1">
                                    <CheckCircle2 class="w-4 h-4"/> Password updated successfully.
                                </p>
                            </Transition>
                        </div>
                    </form>
                </div>
            </div>

            <div class="lg:col-span-1">
                <div class="bg-red-50 p-5 sm:p-6 rounded-2xl border border-red-100 lg:sticky lg:top-8">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="p-2 bg-red-100 text-red-600 rounded-lg">
                            <Trash2 class="w-5 h-5" />
                        </div>
                        <h2 class="text-base sm:text-lg font-bold text-red-800">Delete Account</h2>
                    </div>
                    
                    <p class="text-xs sm:text-sm text-red-600/80 mb-6 leading-relaxed">
                        Once your account is deleted, all of its resources and data will be permanently deleted.
                    </p>

                    <button 
                        @click="confirmUserDeletion" 
                        class="w-full bg-white border border-red-200 text-red-600 px-4 py-3 rounded-xl hover:bg-red-600 hover:text-white transition-all font-bold text-sm shadow-sm active:scale-95"
                    >
                        Delete My Account
                    </button>
                </div>
            </div>
        </div>

        <div v-if="confirmingUserDeletion">
            
            <div class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 transition-opacity" @click.self="closeModal"></div>

            <div class="hidden sm:flex fixed inset-0 z-50 items-center justify-center p-4 pointer-events-none">
                <div class="bg-white w-full max-w-md rounded-2xl shadow-2xl p-6 animate-fade-in-up border border-gray-100 pointer-events-auto">
                    <div class="flex justify-between items-start mb-4">
                        <h2 class="text-lg font-bold text-gray-900">Are you sure?</h2>
                        <button @click="closeModal" class="text-gray-400 hover:text-gray-600"><X class="w-5 h-5"/></button>
                    </div>
                    
                    <p class="text-sm text-gray-500 mb-6 leading-relaxed">
                        Please enter your password to confirm deletion. This action cannot be undone.
                    </p>
                    
                    <div class="mb-6">
                        <input 
                            ref="passwordInput" 
                            v-model="formDelete.password" 
                            type="password" 
                            class="w-full px-4 py-3 border rounded-xl focus:outline-none transition-all text-gray-900 placeholder-gray-400"
                            :class="formDelete.errors.password ? 'border-red-500 focus:border-red-500 focus:ring-2 focus:ring-red-200' : 'border-gray-300 focus:border-red-500 focus:ring-2 focus:ring-red-100'"
                            placeholder="Enter your password" 
                            @keyup.enter="deleteUser" 
                        />
                        <p v-if="formDelete.errors.password" class="text-xs text-red-600 mt-2 font-bold">{{ formDelete.errors.password }}</p>
                    </div>
                    
                    <div class="flex justify-end gap-3">
                        <button @click="closeModal" class="px-5 py-2.5 bg-gray-100 text-gray-700 rounded-xl hover:bg-gray-200 transition text-sm font-bold">
                            Cancel
                        </button>
                        <button @click="deleteUser" :disabled="formDelete.processing" class="px-5 py-2.5 bg-red-600 text-white rounded-xl hover:bg-red-700 transition text-sm font-bold shadow-lg shadow-red-200 disabled:opacity-50 flex items-center gap-2">
                            <span v-if="formDelete.processing">Deleting...</span>
                            <span v-else>Delete Account</span>
                        </button>
                    </div>
                </div>
            </div>

            <div class="sm:hidden fixed inset-x-0 bottom-0 z-50 flex flex-col max-h-[90vh] animate-in slide-in-from-bottom duration-300">
                <div class="bg-white rounded-t-[2rem] shadow-[0_-10px_40px_rgba(0,0,0,0.2)] flex flex-col flex-1 p-6">
                    
                    <div class="w-full flex justify-center mb-6" @click="closeModal">
                        <div class="w-12 h-1.5 bg-gray-300 rounded-full"></div>
                    </div>

                    <h2 class="text-xl font-bold text-gray-900 mb-2">Delete Account?</h2>
                    <p class="text-sm text-gray-500 mb-6">Enter password to confirm. This cannot be undone.</p>

                    <div class="mb-6">
                        <input 
                            v-model="formDelete.password" 
                            type="password" 
                            class="w-full px-4 py-3.5 border rounded-xl focus:outline-none transition-all text-gray-900"
                            :class="formDelete.errors.password ? 'border-red-500 focus:ring-2 focus:ring-red-200' : 'border-gray-200 focus:border-red-500 focus:ring-2 focus:ring-red-100'"
                            placeholder="Password" 
                        />
                        <p v-if="formDelete.errors.password" class="text-xs text-red-600 mt-2 font-bold">{{ formDelete.errors.password }}</p>
                    </div>

                    <div class="flex flex-col gap-3">
                        <button @click="deleteUser" :disabled="formDelete.processing" class="w-full py-3.5 bg-red-600 text-white rounded-xl font-bold shadow-lg shadow-red-200 flex justify-center items-center gap-2 active:scale-95 transition-transform">
                            <span v-if="formDelete.processing">Deleting...</span>
                            <span v-else>Confirm Delete</span>
                        </button>
                        <button @click="closeModal" class="w-full py-3.5 bg-gray-100 text-gray-700 rounded-xl font-bold active:scale-95 transition-transform">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </div>
</template>

<style scoped>
.animate-fade-in-up { animation: fadeInUp 0.3s ease-out forwards; }
@keyframes fadeInUp { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
</style>