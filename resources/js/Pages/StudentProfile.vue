<script setup>
import { ref, computed, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import { useToast } from 'vue-toastification';
import {
    FireIcon,
    PencilIcon,
    PlusIcon,
    ClockIcon,
    FolderIcon,
    HomeIcon,
    BuildingOffice2Icon,
    ArrowLeftIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    student: Object,
    workouts: Array,
    user: Object,
});

const activeTab = ref('personal');
const toast = useToast();
const isEditing = ref(false);
const isSaving = ref(false);
const isLoading = ref(true);
const studentData = ref(null);
const workoutsData = ref([]);

// Personal data form
const personalForm = ref({
    name: '',
    email: '',
    age: null,
    weight: null,
    height: null,
    goal: '',
    injuries: '',
    notes: '',
});

// Load student data from API
onMounted(async () => {
    try {
        const response = await fetch(`/api/students/${props.student.id}`, {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('authToken')}`,
                'Accept': 'application/json',
            },
        });

        if (response.ok) {
            const data = await response.json();
            studentData.value = data.student;
            workoutsData.value = data.workouts || [];

            // Populate form
            personalForm.value = {
                name: data.student.name,
                email: data.student.email,
                age: data.student.age,
                weight: data.student.weight,
                height: data.student.height,
                goal: data.student.goal,
                injuries: data.student.injuries,
                notes: data.student.notes,
            };
        } else if (response.status === 401) {
            window.location.href = '/admin/login';
        }
    } catch (error) {
        console.error('Failed to load student:', error);
    } finally {
        isLoading.value = false;
    }
});


const savePersonalData = async () => {
    isSaving.value = true;

    try {
        const response = await fetch(`/api/users/${props.student.id}`, {
            method: 'PUT',
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('authToken')}`,
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify(personalForm.value),
        });

        if (response.ok) {
            toast.success('Student profile updated successfully!');
            isEditing.value = false;
            // Reload page to get fresh data
            setTimeout(() => {
                router.reload();
            }, 1000);
        } else {
            const error = await response.json();
            toast.error(error.message || 'Failed to update profile');
        }
    } catch (error) {
        toast.error('Connection error');
    } finally {
        isSaving.value = false;
    }
};

const cancelEdit = () => {
    // Reset form to original values
    personalForm.value = {
        name: props.student.name,
        email: props.student.email,
        age: props.student.age,
        weight: props.student.weight,
        height: props.student.height,
        goal: props.student.goal,
        injuries: props.student.injuries,
        notes: props.student.notes,
    };
    isEditing.value = false;
};

const goBack = () => {
    router.visit('/admin/students');
};

const createWorkoutForStudent = () => {
    router.visit(`/admin/workouts/create?student_id=${props.student.id}`);
};

const editWorkout = (workoutId) => {
    router.visit(`/admin/workouts/${workoutId}/edit`);
};

const getAvatarUrl = (avatarPath) => {
    if (!avatarPath) {
        return null; // No avatar, will show initial with CSS
    }
    if (avatarPath.startsWith('http')) {
        return avatarPath;
    }
    return `/storage/${avatarPath}`;
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

const getThumbnailUrl = (thumbnailPath) => {
    if (!thumbnailPath) {
        return 'https://via.placeholder.com/400x200?text=No+Image';
    }
    if (thumbnailPath.startsWith('http')) {
        return thumbnailPath;
    }
    return `/storage/${thumbnailPath}`;
};

const getLocationIcon = (location) => {
    return location === 'home' ? HomeIcon : BuildingOffice2Icon;
};

const getLocationLabel = (location) => {
    return location === 'home' ? 'Home' : 'Gym';
};
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-purple-600 to-purple-800 p-5">
        <div class="max-w-7xl mx-auto">
            <!-- Header -->
            <div class="bg-white rounded-2xl shadow-2xl p-8 mb-8">
                <button
                    @click="goBack"
                    class="mb-4 px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition"
                >
                    <ArrowLeftIcon class="w-4 h-4 inline-block mr-2" />
                    Back to Students
                </button>

                <div class="flex items-center gap-6">
                    <div v-if="studentData && !getAvatarUrl(studentData.avatar_path)" class="w-24 h-24 rounded-full border-4 border-purple-600 bg-teal-500 flex items-center justify-center">
                        <span class="text-white text-4xl font-bold">{{ getInitial(studentData.name) }}</span>
                    </div>
                    <img
                        v-else-if="studentData && getAvatarUrl(studentData.avatar_path)"
                        :src="getAvatarUrl(studentData.avatar_path)"
                        :alt="studentData.name"
                        class="w-24 h-24 rounded-full border-4 border-purple-600 object-cover"
                    >
                    <div class="flex-1">
                        <h1 class="text-4xl font-bold text-gray-800">{{ studentData?.name || student.name }}</h1>
                        <p class="text-gray-600 mt-1">{{ studentData?.email || student.email }}</p>
                    </div>
                </div>
            </div>

            <!-- Tabs -->
            <div class="bg-white rounded-2xl shadow-xl mb-6">
                <div class="flex gap-2 p-4 border-b border-gray-200">
                    <button
                        @click="activeTab = 'personal'"
                        :class="[
                            'px-6 py-3 rounded-lg font-semibold transition-all',
                            activeTab === 'personal' ? 'bg-purple-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                        ]"
                    >
                        Personal Data
                    </button>
                    <button
                        @click="activeTab = 'workouts'"
                        :class="[
                            'px-6 py-3 rounded-lg font-semibold transition-all',
                            activeTab === 'workouts' ? 'bg-purple-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                        ]"
                    >
                        Workouts ({{ workoutsData.length }})
                    </button>
                </div>

                <!-- Personal Data Tab -->
                <div v-show="activeTab === 'personal'" class="p-8">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-2xl font-bold text-gray-800">Personal Information</h2>
                        <button
                            v-if="!isEditing"
                            @click="isEditing = true"
                            class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition"
                        >
                            <PencilIcon class="w-4 h-4 inline-block mr-1" />
                            Edit
                        </button>
                        <div v-else class="flex gap-2">
                            <button
                                @click="savePersonalData"
                                :disabled="isSaving"
                                class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition disabled:opacity-50"
                            >
                                {{ isSaving ? 'Saving...' : '💾 Save' }}
                            </button>
                            <button
                                @click="cancelEdit"
                                :disabled="isSaving"
                                class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition disabled:opacity-50"
                            >
                                Cancel
                            </button>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Name -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Name</label>
                            <input
                                v-model="personalForm.name"
                                :disabled="!isEditing"
                                type="text"
                                class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:border-purple-600 focus:outline-none disabled:bg-gray-100"
                            >
                        </div>

                        <!-- Email -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Email</label>
                            <input
                                v-model="personalForm.email"
                                :disabled="!isEditing"
                                type="email"
                                class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:border-purple-600 focus:outline-none disabled:bg-gray-100"
                            >
                        </div>

                        <!-- Age -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Age</label>
                            <input
                                v-model.number="personalForm.age"
                                :disabled="!isEditing"
                                type="number"
                                placeholder="e.g., 25"
                                class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:border-purple-600 focus:outline-none disabled:bg-gray-100"
                            >
                        </div>

                        <!-- Weight -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Weight (kg)</label>
                            <input
                                v-model.number="personalForm.weight"
                                :disabled="!isEditing"
                                type="number"
                                step="0.1"
                                placeholder="e.g., 70.5"
                                class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:border-purple-600 focus:outline-none disabled:bg-gray-100"
                            >
                        </div>

                        <!-- Height -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Height (m)</label>
                            <input
                                v-model.number="personalForm.height"
                                :disabled="!isEditing"
                                type="number"
                                step="0.01"
                                placeholder="e.g., 1.75"
                                class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:border-purple-600 focus:outline-none disabled:bg-gray-100"
                            >
                        </div>

                        <!-- Goal -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Goal / Objective</label>
                            <textarea
                                v-model="personalForm.goal"
                                :disabled="!isEditing"
                                rows="3"
                                placeholder="e.g., Lose weight, build muscle, improve endurance..."
                                class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:border-purple-600 focus:outline-none disabled:bg-gray-100"
                            ></textarea>
                        </div>

                        <!-- Injuries -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Injuries / Restrictions</label>
                            <textarea
                                v-model="personalForm.injuries"
                                :disabled="!isEditing"
                                rows="3"
                                placeholder="e.g., Knee pain, lower back issues..."
                                class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:border-purple-600 focus:outline-none disabled:bg-gray-100"
                            ></textarea>
                        </div>

                        <!-- Notes -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Trainer Notes / Observations</label>
                            <textarea
                                v-model="personalForm.notes"
                                :disabled="!isEditing"
                                rows="4"
                                placeholder="Private notes about this student..."
                                class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:border-purple-600 focus:outline-none disabled:bg-gray-100"
                            ></textarea>
                        </div>
                    </div>
                </div>

                <!-- Workouts Tab -->
                <div v-show="activeTab === 'workouts'" class="p-8">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-2xl font-bold text-gray-800">Student's Workouts</h2>
                        <button
                            @click="createWorkoutForStudent"
                            class="px-6 py-3 bg-purple-600 text-white rounded-lg font-semibold hover:bg-purple-700 transition shadow-lg"
                        >
                            <PlusIcon class="w-5 h-5 inline-block mr-2" />
                            Create New Workout
                        </button>
                    </div>

                    <div v-if="workoutsData.length === 0" class="bg-gray-50 rounded-xl p-12 text-center">
                        <p class="text-gray-600 text-lg mb-4">No workouts assigned yet</p>
                        <button
                            @click="createWorkoutForStudent"
                            class="px-6 py-3 bg-purple-600 text-white rounded-lg font-semibold hover:bg-purple-700 transition"
                        >
                            Create First Workout
                        </button>
                    </div>

                    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div
                            v-for="workout in workoutsData"
                            :key="workout.id"
                            class="bg-white border-2 border-gray-200 rounded-xl overflow-hidden hover:border-purple-600 transition-all hover:shadow-xl"
                        >
                            <img
                                :src="getThumbnailUrl(workout.thumbnail_path)"
                                :alt="workout.title"
                                class="w-full h-40 object-cover"
                            >
                            <div class="p-4">
                                <div class="flex items-center gap-2 mb-2">
                                    <component :is="getLocationIcon(workout.location)" class="w-6 h-6 text-gray-600" />
                                    <span class="px-2 py-1 bg-purple-100 text-purple-700 text-xs font-semibold rounded">
                                        {{ getLocationLabel(workout.location) }}
                                    </span>
                                    <span class="px-2 py-1 bg-blue-100 text-blue-700 text-xs font-semibold rounded">
                                        {{ workout.level }}
                                    </span>
                                </div>

                                <h3 class="text-lg font-bold text-gray-800 mb-2">{{ workout.title }}</h3>

                                <div class="flex gap-4 mb-3 text-sm text-gray-600">
                                    <span class="flex items-center gap-1">
                                        <ClockIcon class="w-4 h-4" />
                                        {{ workout.duration }}
                                    </span>
                                    <span class="flex items-center gap-1">
                                        <FolderIcon class="w-4 h-4" />
                                        {{ workout.category }}
                                    </span>
                                </div>

                                <p class="text-sm text-gray-600 mb-4">
                                    <FireIcon class="w-4 h-4 inline-block mr-1" />
                                    {{ workout.exercises?.length || 0 }} exercises
                                </p>

                                <button
                                    @click="editWorkout(workout.id)"
                                    class="w-full px-4 py-2 bg-purple-600 text-white rounded-lg font-semibold hover:bg-purple-700 transition"
                                >
                                    <PencilIcon class="w-4 h-4 inline-block mr-1" />
                                    Edit Workout
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Nutrition Tab (Placeholder) -->
                <div v-show="activeTab === 'nutrition'" class="p-8">
                    <div class="bg-gray-50 rounded-xl p-12 text-center">
                        <p class="text-gray-600 text-lg">Nutrition plans feature coming soon...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
