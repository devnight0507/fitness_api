<script setup>
import { ref, computed, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import { useToast } from 'vue-toastification';
import { UsersIcon, MagnifyingGlassIcon, CakeIcon, FlagIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    students: Array,
    user: Object,
});

const searchQuery = ref('');
const toast = useToast();
const studentsList = ref([]);
const isLoading = ref(true);

// Load students from API
onMounted(async () => {
    try {
        const response = await fetch('/api/users?role=student', {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('authToken')}`,
                'Accept': 'application/json',
            },
        });

        if (response.ok) {
            studentsList.value = await response.json();
        } else if (response.status === 401) {
            window.location.href = '/admin/login';
        }
    } catch (error) {
        console.error('Failed to load students:', error);
    } finally {
        isLoading.value = false;
    }
});

// Filter students based on search
const filteredStudents = computed(() => {
    if (!searchQuery.value) {
        return studentsList.value;
    }

    const query = searchQuery.value.toLowerCase();
    return studentsList.value.filter(student =>
        student.name.toLowerCase().includes(query) ||
        student.email.toLowerCase().includes(query) ||
        (student.goal && student.goal.toLowerCase().includes(query))
    );
});

const viewStudentProfile = (studentId) => {
    router.visit(`/admin/students/${studentId}`);
};

const logout = () => {
    localStorage.removeItem('authToken');
    window.location.href = '/admin/login';
};

const goToAdmin = () => {
    router.visit('/admin');
};

const getAvatarUrl = (student) => {
    if (!student.avatar_path) {
        return null; // No avatar, will show initial with CSS
    }
    if (student.avatar_path.startsWith('http')) {
        return student.avatar_path;
    }
    return `/storage/${student.avatar_path}`;
};

const getInitial = (name) => {
    if (!name) return 'S';

    const nameParts = name.trim().split(' ');
    if (nameParts.length === 1) {
        return nameParts[0].charAt(0).toUpperCase();
    }

    const firstInitial = nameParts[0].charAt(0).toUpperCase();
    const lastInitial = nameParts[nameParts.length - 1].charAt(0).toUpperCase();
    return firstInitial + lastInitial;
};

const formatDate = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
};
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-purple-600 to-purple-800 p-3 sm:p-5">
        <div class="max-w-7xl mx-auto">
            <!-- Header -->
            <div class="bg-white rounded-2xl shadow-2xl p-4 sm:p-6 md:p-8 mb-6 sm:mb-8">
                <div class="flex flex-col gap-4 sm:flex-row sm:justify-between sm:items-center">
                    <div class="flex items-center gap-3 sm:gap-4">
                        <UsersIcon class="w-8 h-8 sm:w-10 sm:h-10 text-purple-600 flex-shrink-0" />
                        <div>
                            <h1 class="text-2xl sm:text-3xl md:text-4xl font-bold text-gray-800">Students Management</h1>
                            <p class="text-sm sm:text-base text-gray-600 hidden sm:block">Manage student profiles and training plans</p>
                        </div>
                    </div>
                    <div class="flex gap-2 sm:gap-3">
                        <button
                            @click="goToAdmin"
                            class="flex-1 sm:flex-none px-3 sm:px-6 py-2 sm:py-3 bg-gray-600 text-white rounded-lg text-sm sm:text-base font-semibold hover:bg-gray-700 transition"
                        >
                            <span class="hidden sm:inline">← Back to Dashboard</span>
                            <span class="sm:hidden">← Back</span>
                        </button>
                        <button
                            @click="logout"
                            class="flex-1 sm:flex-none px-3 sm:px-6 py-2 sm:py-3 bg-red-600 text-white rounded-lg text-sm sm:text-base font-semibold hover:bg-red-700 transition"
                        >
                            Logout
                        </button>
                    </div>
                </div>
            </div>

            <!-- Search Bar -->
            <div class="bg-white rounded-2xl shadow-xl p-4 sm:p-6 mb-4 sm:mb-6">
                <div class="relative">
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Search students..."
                        class="w-full px-4 sm:px-6 py-3 sm:py-4 text-base sm:text-lg border-2 border-gray-300 rounded-xl focus:border-purple-600 focus:outline-none transition pr-12"
                    >
                    <MagnifyingGlassIcon class="absolute right-3 sm:right-4 top-1/2 transform -translate-y-1/2 w-5 h-5 sm:w-6 sm:h-6 text-gray-400" />
                </div>
            </div>

            <!-- Students Grid -->
            <div v-if="isLoading" class="bg-white rounded-2xl shadow-xl p-8 sm:p-12 text-center">
                <p class="text-gray-600 text-base sm:text-lg">Loading students...</p>
            </div>

            <div v-else-if="filteredStudents.length === 0" class="bg-white rounded-2xl shadow-xl p-8 sm:p-12 text-center">
                <p class="text-gray-600 text-base sm:text-lg">
                    {{ searchQuery ? 'No students found matching your search' : 'No students yet' }}
                </p>
            </div>

            <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
                <div
                    v-for="student in filteredStudents"
                    :key="student.id"
                    @click="viewStudentProfile(student.id)"
                    class="bg-white rounded-xl shadow-lg hover:shadow-2xl transition-all hover:-translate-y-1 cursor-pointer overflow-hidden"
                >
                    <!-- Student Avatar -->
                    <div class="h-40 sm:h-48 bg-gradient-to-br from-purple-400 to-purple-600 flex items-center justify-center">
                        <div v-if="!getAvatarUrl(student)" class="w-24 h-24 sm:w-32 sm:h-32 rounded-full border-4 border-white bg-teal-500 flex items-center justify-center">
                            <span class="text-white text-4xl sm:text-5xl font-bold">{{ getInitial(student.name) }}</span>
                        </div>
                        <img
                            v-else
                            :src="getAvatarUrl(student)"
                            :alt="student.name"
                            class="w-24 h-24 sm:w-32 sm:h-32 rounded-full border-4 border-white object-cover"
                        >
                    </div>

                    <!-- Student Info -->
                    <div class="p-4 sm:p-6">
                        <h3 class="text-lg sm:text-xl font-bold text-gray-800 mb-1 sm:mb-2">{{ student.name }}</h3>
                        <p class="text-gray-600 text-xs sm:text-sm mb-3 sm:mb-4 truncate">{{ student.email }}</p>

                        <div class="space-y-2 mb-3 sm:mb-4">
                            <div v-if="student.age" class="flex items-center gap-2 text-xs sm:text-sm text-gray-600">
                                <CakeIcon class="w-4 h-4 sm:w-5 sm:h-5" />
                                <span>{{ student.age }} years old</span>
                            </div>
                            <div v-if="student.goal" class="flex items-center gap-2 text-xs sm:text-sm text-gray-600">
                                <FlagIcon class="w-4 h-4 sm:w-5 sm:h-5 flex-shrink-0" />
                                <span class="line-clamp-2">{{ student.goal }}</span>
                            </div>
                        </div>

                        <div class="pt-3 sm:pt-4 border-t border-gray-200">
                            <p class="text-xs text-gray-500">
                                Member since {{ formatDate(student.created_at) }}
                            </p>
                        </div>

                        <button
                            class="w-full mt-3 sm:mt-4 px-3 sm:px-4 py-2 bg-purple-600 text-white rounded-lg text-sm sm:text-base font-semibold hover:bg-purple-700 transition"
                        >
                            View Profile →
                        </button>
                    </div>
                </div>
            </div>

            <!-- Stats Summary -->
            <div class="mt-6 sm:mt-8 bg-white rounded-2xl shadow-xl p-4 sm:p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-xs sm:text-sm">Total Students</p>
                        <p class="text-2xl sm:text-3xl font-bold text-purple-600">{{ filteredStudents.length }}</p>
                    </div>
                    <UsersIcon class="w-10 h-10 sm:w-14 sm:h-14 text-purple-600" />
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
