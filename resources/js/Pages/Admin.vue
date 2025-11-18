<script setup>
import { ref, computed, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import { useToast } from 'vue-toastification';

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
    <div class="min-h-screen bg-gradient-to-br from-purple-600 to-purple-800 p-5">
        <div class="max-w-7xl mx-auto">
            <!-- Header -->
            <div class="bg-white rounded-2xl shadow-2xl p-8 mb-8">
                <div class="flex justify-between items-center">
                    <div>
                        <h1 class="text-4xl font-bold text-gray-800 mb-2">🏋️ Fitness App Admin Panel</h1>
                        <p class="text-gray-600">Manage workouts, students, nutrition plans, and more</p>
                    </div>
                    <button
                        @click="logout"
                        class="px-6 py-3 bg-red-500 text-white rounded-lg font-semibold hover:bg-red-600 transition-all hover:-translate-y-0.5 shadow-lg"
                    >
                        Logout
                    </button>
                </div>
            </div>

            <!-- Navigation Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <!-- Students Card -->
                <div class="bg-white rounded-xl shadow-lg hover:shadow-2xl transition-all hover:-translate-y-1 cursor-pointer overflow-hidden"
                     @click="router.visit('/admin/students')"
                >
                    <div class="bg-gradient-to-br from-blue-500 to-blue-600 p-6 text-white">
                        <div class="text-5xl mb-3">👥</div>
                        <h3 class="text-xl font-bold">Students</h3>
                    </div>
                    <div class="p-6">
                        <p class="text-gray-600 text-sm mb-4">View and manage student profiles and individual workouts</p>
                        <div class="flex items-center text-blue-600 font-semibold text-sm">
                            <span>Manage Students</span>
                            <span class="ml-2">→</span>
                        </div>
                    </div>
                </div>

                <!-- Workouts Card -->
                <div class="bg-white rounded-xl shadow-lg hover:shadow-2xl transition-all hover:-translate-y-1 cursor-pointer overflow-hidden"
                     @click="router.visit('/admin/workouts')"
                >
                    <div class="bg-gradient-to-br from-purple-500 to-purple-600 p-6 text-white">
                        <div class="text-5xl mb-3">💪</div>
                        <h3 class="text-xl font-bold">Workouts</h3>
                    </div>
                    <div class="p-6">
                        <p class="text-gray-600 text-sm mb-4">Create and manage workout programs with exercises and videos</p>
                        <div class="flex items-center text-purple-600 font-semibold text-sm">
                            <span>Manage Workouts</span>
                            <span class="ml-2">→</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-600 text-sm mb-1">Total Workouts</p>
                            <p class="text-3xl font-bold text-purple-600">{{ workoutsList.length }}</p>
                        </div>
                        <div class="text-4xl">💪</div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-lg p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-600 text-sm mb-1">Total Students</p>
                            <p class="text-3xl font-bold text-blue-600">{{ studentsList.length }}</p>
                        </div>
                        <div class="text-4xl">👥</div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-lg p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-600 text-sm mb-1">Active Plans</p>
                            <p class="text-3xl font-bold text-green-600">{{ workoutsList.filter(w => w.is_active).length }}</p>
                        </div>
                        <div class="text-4xl">✅</div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</template>
