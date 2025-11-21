<script setup>
import { ref, computed, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import { useToast } from 'vue-toastification';
import { UsersIcon, FireIcon, CheckCircleIcon } from '@heroicons/vue/24/solid';

const props = defineProps({
    workouts: Array,
    user: Object,
});

// State
const workoutsList = ref([]);
const studentsList = ref([]);
const isLoading = ref(false);
const toast = useToast();

// Check authentication and load workouts on mount
onMounted(() => {
    const token = localStorage.getItem('authToken');
    if (!token) {
        // No token, redirect to login
        window.location.href = '/admin/login';
        return;
    }

    // Load workouts and students from API
    loadWorkouts();
    loadStudents();
});

// Methods

const loadWorkouts = async () => {
    isLoading.value = true;
    try {
        const response = await fetch('/api/workouts', {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('authToken')}`,
                'Accept': 'application/json',
            },
        });

        if (response.ok) {
            workoutsList.value = await response.json();
        } else {
            showAlert('Failed to load workouts', 'error');
        }
    } catch (error) {
        showAlert('Connection error', 'error');
    } finally {
        isLoading.value = false;
    }
};

const loadStudents = async () => {
    try {
        const response = await fetch('/api/users?role=student', {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('authToken')}`,
                'Accept': 'application/json',
            },
        });

        if (response.ok) {
            studentsList.value = await response.json();
        }
    } catch (error) {
        console.error('Failed to load students:', error);
    }
};

const showAlert = (message, type = 'info') => {
    // Use toast library based on type
    switch (type) {
        case 'success':
            toast.success(message);
            break;
        case 'error':
            toast.error(message);
            break;
        case 'warning':
            toast.warning(message);
            break;
        default:
            toast.info(message);
    }
};

const logout = () => {
    localStorage.removeItem('authToken');
    window.location.href = '/admin';
};
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-purple-600 to-purple-800 p-3 sm:p-5">
        <div class="max-w-7xl mx-auto">
            <!-- Header -->
            <div class="bg-white rounded-2xl shadow-2xl p-4 sm:p-6 md:p-8 mb-6 sm:mb-8">
                <div class="flex flex-col gap-4 sm:flex-row sm:justify-between sm:items-center">
                    <div class="flex items-center gap-3 sm:gap-4">
                        <FireIcon class="w-10 h-10 sm:w-12 sm:h-12 text-purple-600 flex-shrink-0" />
                        <div>
                            <h1 class="text-2xl sm:text-3xl md:text-4xl font-bold text-gray-800">Fitness App Admin Panel</h1>
                            <p class="text-sm sm:text-base text-gray-600 hidden sm:block">Manage workouts, students, nutrition plans, and more</p>
                        </div>
                    </div>
                    <button
                        @click="logout"
                        class="self-start sm:self-auto px-4 sm:px-6 py-2 sm:py-3 bg-red-500 text-white rounded-lg text-sm sm:text-base font-semibold hover:bg-red-600 transition-all hover:-translate-y-0.5 shadow-lg"
                    >
                        Logout
                    </button>
                </div>
            </div>

            <!-- Navigation Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6 mb-6 sm:mb-8">
                <!-- Students Card -->
                <div class="bg-white rounded-xl shadow-lg hover:shadow-2xl transition-all hover:-translate-y-1 cursor-pointer overflow-hidden"
                     @click="router.visit('/admin/students')"
                >
                    <div class="bg-gradient-to-br from-blue-500 to-blue-600 p-6 sm:p-8 text-white">
                        <UsersIcon class="w-12 h-12 sm:w-16 sm:h-16 mb-3 sm:mb-4" />
                        <h3 class="text-xl sm:text-2xl font-bold">Students</h3>
                    </div>
                    <div class="p-5 sm:p-6">
                        <p class="text-gray-600 text-sm sm:text-base mb-4 leading-relaxed">View and manage student profiles and individual workouts</p>
                        <div class="flex items-center text-blue-600 font-semibold text-sm sm:text-base">
                            <span>Manage Students</span>
                            <span class="ml-2">→</span>
                        </div>
                    </div>
                </div>

                <!-- Workouts Card -->
                <div class="bg-white rounded-xl shadow-lg hover:shadow-2xl transition-all hover:-translate-y-1 cursor-pointer overflow-hidden"
                     @click="router.visit('/admin/workouts')"
                >
                    <div class="bg-gradient-to-br from-purple-500 to-purple-600 p-6 sm:p-8 text-white">
                        <FireIcon class="w-12 h-12 sm:w-16 sm:h-16 mb-3 sm:mb-4" />
                        <h3 class="text-xl sm:text-2xl font-bold">Workouts</h3>
                    </div>
                    <div class="p-5 sm:p-6">
                        <p class="text-gray-600 text-sm sm:text-base mb-4 leading-relaxed">Create and manage workout programs with exercises and videos</p>
                        <div class="flex items-center text-purple-600 font-semibold text-sm sm:text-base">
                            <span>Manage Workouts</span>
                            <span class="ml-2">→</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 sm:gap-6 mb-6 sm:mb-8">
                <div class="bg-white rounded-xl shadow-lg p-4 sm:p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-600 text-xs sm:text-sm mb-1">Total Workouts</p>
                            <p class="text-2xl sm:text-3xl font-bold text-purple-600">{{ workoutsList.length }}</p>
                        </div>
                        <FireIcon class="w-10 h-10 sm:w-12 sm:h-12 text-purple-600" />
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-lg p-4 sm:p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-600 text-xs sm:text-sm mb-1">Total Students</p>
                            <p class="text-2xl sm:text-3xl font-bold text-blue-600">{{ studentsList.length }}</p>
                        </div>
                        <UsersIcon class="w-10 h-10 sm:w-12 sm:h-12 text-blue-600" />
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-lg p-4 sm:p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-600 text-xs sm:text-sm mb-1">Active Plans</p>
                            <p class="text-2xl sm:text-3xl font-bold text-green-600">{{ workoutsList.filter(w => w.is_active).length }}</p>
                        </div>
                        <CheckCircleIcon class="w-10 h-10 sm:w-12 sm:h-12 text-green-600" />
                    </div>
                </div>
            </div>

        </div>
    </div>
</template>
