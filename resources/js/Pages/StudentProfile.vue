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
const nutritionData = ref([]);
const subscriptionData = ref(null);

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
            nutritionData.value = data.nutrition_plans || [];
            subscriptionData.value = data.subscription || null;

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
        } else if (response.status === 401) {
            window.location.href = '/admin/login';
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

const createNutritionForStudent = () => {
    router.visit(`/admin/nutrition/create?student_id=${props.student.id}`);
};

const editNutrition = (nutritionId) => {
    router.visit(`/admin/nutrition/${nutritionId}/edit`);
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

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
};

const getPlanCategoryLabel = (category) => {
    if (category === 'StartClass') return 'START CLASS';
    if (category === 'UpLevel') return 'UP LEVEL';
    return category;
};

const getPlanTypeLabel = (type) => {
    if (type === 'monthly') return 'Monthly';
    if (type === 'quarterly') return 'Quarterly (3 months)';
    if (type === 'annual') return 'Annual (12 months)';
    return type;
};
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-purple-600 to-purple-800 p-3 sm:p-5">
        <div class="max-w-7xl mx-auto">
            <!-- Header -->
            <div class="bg-white rounded-2xl shadow-2xl p-4 sm:p-6 md:p-8 mb-6 sm:mb-8">
                <button
                    @click="goBack"
                    class="mb-3 sm:mb-4 px-3 sm:px-4 py-2 bg-gray-200 text-gray-700 rounded-lg text-sm sm:text-base hover:bg-gray-300 transition"
                >
                    <ArrowLeftIcon class="w-3 h-3 sm:w-4 sm:h-4 inline-block mr-2" />
                    Back to Students
                </button>

                <div class="flex flex-col sm:flex-row items-center gap-4 sm:gap-6">
                    <div v-if="studentData && !getAvatarUrl(studentData.avatar_path)" class="w-20 h-20 sm:w-24 sm:h-24 rounded-full border-4 border-purple-600 bg-teal-500 flex items-center justify-center flex-shrink-0">
                        <span class="text-white text-3xl sm:text-4xl font-bold">{{ getInitial(studentData.name) }}</span>
                    </div>
                    <img
                        v-else-if="studentData && getAvatarUrl(studentData.avatar_path)"
                        :src="getAvatarUrl(studentData.avatar_path)"
                        :alt="studentData.name"
                        class="w-20 h-20 sm:w-24 sm:h-24 rounded-full border-4 border-purple-600 object-cover flex-shrink-0"
                    >
                    <div class="flex-1 text-center sm:text-left">
                        <h1 class="text-2xl sm:text-3xl md:text-4xl font-bold text-gray-800">{{ studentData?.name || student.name }}</h1>
                        <p class="text-sm sm:text-base text-gray-600 mt-1">{{ studentData?.email || student.email }}</p>
                    </div>
                </div>
            </div>

            <!-- Tabs -->
            <div class="bg-white rounded-2xl shadow-xl mb-4 sm:mb-6">
                <div class="flex flex-col sm:flex-row gap-2 p-3 sm:p-4 border-b border-gray-200 overflow-x-auto">
                    <button
                        @click="activeTab = 'personal'"
                        :class="[
                            'px-4 sm:px-6 py-2 sm:py-3 rounded-lg font-semibold transition-all text-sm sm:text-base whitespace-nowrap',
                            activeTab === 'personal' ? 'bg-purple-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                        ]"
                    >
                        Personal Data
                    </button>
                    <button
                        @click="activeTab = 'workouts'"
                        :class="[
                            'px-4 sm:px-6 py-2 sm:py-3 rounded-lg font-semibold transition-all text-sm sm:text-base whitespace-nowrap',
                            activeTab === 'workouts' ? 'bg-purple-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                        ]"
                    >
                        Workouts ({{ workoutsData.length }})
                    </button>
                    <button
                        @click="activeTab = 'nutrition'"
                        :class="[
                            'px-4 sm:px-6 py-2 sm:py-3 rounded-lg font-semibold transition-all text-sm sm:text-base whitespace-nowrap',
                            activeTab === 'nutrition' ? 'bg-purple-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                        ]"
                    >
                        Nutrition ({{ nutritionData.length }})
                    </button>
                    <button
                        @click="activeTab = 'subscription'"
                        :class="[
                            'px-4 sm:px-6 py-2 sm:py-3 rounded-lg font-semibold transition-all text-sm sm:text-base whitespace-nowrap',
                            activeTab === 'subscription' ? 'bg-purple-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                        ]"
                    >
                        Subscription
                    </button>
                </div>

                <!-- Personal Data Tab -->
                <div v-show="activeTab === 'personal'" class="p-4 sm:p-6 md:p-8">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-4 sm:mb-6">
                        <h2 class="text-xl sm:text-2xl font-bold text-gray-800">Personal Information</h2>
                        <button
                            v-if="!isEditing"
                            @click="isEditing = true"
                            class="px-3 sm:px-4 py-2 bg-purple-600 text-white rounded-lg text-sm sm:text-base hover:bg-purple-700 transition"
                        >
                            <PencilIcon class="w-3 h-3 sm:w-4 sm:h-4 inline-block mr-1" />
                            Edit
                        </button>
                        <div v-else class="flex gap-2 w-full sm:w-auto">
                            <button
                                @click="savePersonalData"
                                :disabled="isSaving"
                                class="flex-1 sm:flex-none px-3 sm:px-4 py-2 bg-green-600 text-white rounded-lg text-sm sm:text-base hover:bg-green-700 transition disabled:opacity-50"
                            >
                                {{ isSaving ? 'Saving...' : '💾 Save' }}
                            </button>
                            <button
                                @click="cancelEdit"
                                :disabled="isSaving"
                                class="flex-1 sm:flex-none px-3 sm:px-4 py-2 bg-gray-500 text-white rounded-lg text-sm sm:text-base hover:bg-gray-600 transition disabled:opacity-50"
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
                <div v-show="activeTab === 'workouts'" class="p-4 sm:p-6 md:p-8">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-4 sm:mb-6">
                        <h2 class="text-xl sm:text-2xl font-bold text-gray-800">Student's Workouts</h2>
                        <button
                            @click="createWorkoutForStudent"
                            class="w-full sm:w-auto px-4 sm:px-6 py-2 sm:py-3 bg-purple-600 text-white rounded-lg text-sm sm:text-base font-semibold hover:bg-purple-700 transition shadow-lg"
                        >
                            <PlusIcon class="w-4 h-4 sm:w-5 sm:h-5 inline-block mr-2" />
                            Create New Workout
                        </button>
                    </div>

                    <div v-if="workoutsData.length === 0" class="bg-gray-50 rounded-xl p-8 sm:p-12 text-center">
                        <p class="text-base sm:text-lg text-gray-600 mb-3 sm:mb-4">No workouts assigned yet</p>
                        <button
                            @click="createWorkoutForStudent"
                            class="px-4 sm:px-6 py-2 sm:py-3 bg-purple-600 text-white rounded-lg text-sm sm:text-base font-semibold hover:bg-purple-700 transition"
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

                <!-- Nutrition Tab -->
                <div v-show="activeTab === 'nutrition'" class="p-4 sm:p-6 md:p-8">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-4 sm:mb-6">
                        <h2 class="text-xl sm:text-2xl font-bold text-gray-800">Student's Nutrition Plans</h2>
                        <button
                            @click="createNutritionForStudent"
                            class="w-full sm:w-auto px-4 sm:px-6 py-2 sm:py-3 bg-purple-600 text-white rounded-lg text-sm sm:text-base font-semibold hover:bg-purple-700 transition shadow-lg"
                        >
                            <PlusIcon class="w-4 h-4 sm:w-5 sm:h-5 inline-block mr-2" />
                            Create Nutrition Plan
                        </button>
                    </div>

                    <div v-if="nutritionData.length === 0" class="bg-gray-50 rounded-xl p-8 sm:p-12 text-center">
                        <p class="text-base sm:text-lg text-gray-600 mb-3 sm:mb-4">No nutrition plans assigned yet</p>
                        <button
                            @click="createNutritionForStudent"
                            class="px-4 sm:px-6 py-2 sm:py-3 bg-purple-600 text-white rounded-lg text-sm sm:text-base font-semibold hover:bg-purple-700 transition"
                        >
                            Create First Nutrition Plan
                        </button>
                    </div>

                    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div
                            v-for="nutrition in nutritionData"
                            :key="nutrition.id"
                            class="bg-white border-2 border-gray-200 rounded-xl overflow-hidden hover:border-purple-600 transition-all hover:shadow-xl"
                        >
                            <img
                                :src="getThumbnailUrl(nutrition.thumbnail_path)"
                                :alt="nutrition.title"
                                class="w-full h-40 object-cover"
                            >
                            <div class="p-4">
                                <h3 class="text-lg font-bold text-gray-800 mb-2">{{ nutrition.title }}</h3>

                                <p v-if="nutrition.description" class="text-sm text-gray-600 mb-3">
                                    {{ nutrition.description }}
                                </p>

                                <div v-if="nutrition.calories || nutrition.protein || nutrition.carbs || nutrition.fats" class="mb-3 p-3 bg-gray-50 rounded-lg">
                                    <div class="grid grid-cols-2 gap-2 text-xs">
                                        <div v-if="nutrition.calories">
                                            <span class="font-semibold">Calories:</span> {{ nutrition.calories }}
                                        </div>
                                        <div v-if="nutrition.protein">
                                            <span class="font-semibold">Protein:</span> {{ nutrition.protein }}g
                                        </div>
                                        <div v-if="nutrition.carbs">
                                            <span class="font-semibold">Carbs:</span> {{ nutrition.carbs }}g
                                        </div>
                                        <div v-if="nutrition.fats">
                                            <span class="font-semibold">Fats:</span> {{ nutrition.fats }}g
                                        </div>
                                    </div>
                                </div>

                                <p v-if="nutrition.meals" class="text-sm text-gray-600 mb-4">
                                    {{ nutrition.meals.length }} meal(s)
                                </p>

                                <button
                                    @click="editNutrition(nutrition.id)"
                                    class="w-full px-4 py-2 bg-purple-600 text-white rounded-lg font-semibold hover:bg-purple-700 transition"
                                >
                                    <PencilIcon class="w-4 h-4 inline-block mr-1" />
                                    Edit Nutrition Plan
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Subscription Tab -->
                <div v-show="activeTab === 'subscription'" class="p-4 sm:p-6 md:p-8">
                    <h2 class="text-xl sm:text-2xl font-bold text-gray-800 mb-4 sm:mb-6">Subscription Status</h2>

                    <!-- No Subscription -->
                    <div v-if="!subscriptionData" class="bg-gray-50 rounded-xl p-8 sm:p-12 text-center">
                        <div class="mb-4">
                            <svg class="w-16 h-16 sm:w-20 sm:h-20 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg sm:text-xl font-bold text-gray-700 mb-2">No Active Subscription</h3>
                        <p class="text-sm sm:text-base text-gray-600">This student does not have an active subscription.</p>
                    </div>

                    <!-- Active Subscription -->
                    <div v-else class="space-y-6">
                        <!-- Subscription Card -->
                        <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-xl p-6 border-2 border-purple-300">
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center gap-2">
                                    <div class="w-3 h-3 bg-green-500 rounded-full animate-pulse"></div>
                                    <span class="text-sm font-semibold text-green-700 uppercase tracking-wide">Active</span>
                                </div>
                                <span class="px-3 py-1 bg-purple-600 text-white text-xs font-bold rounded-full uppercase">
                                    {{ getPlanCategoryLabel(subscriptionData.plan_category) }}
                                </span>
                            </div>

                            <h3 class="text-2xl sm:text-3xl font-bold text-gray-800 mb-2">
                                {{ getPlanCategoryLabel(subscriptionData.plan_category) }}
                            </h3>
                            <p class="text-base sm:text-lg text-gray-600 mb-6">
                                {{ getPlanTypeLabel(subscriptionData.plan_type) }}
                            </p>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="bg-white rounded-lg p-4 shadow-sm">
                                    <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Period Start</p>
                                    <p class="text-base font-bold text-gray-800">{{ formatDate(subscriptionData.current_period_start) }}</p>
                                </div>
                                <div class="bg-white rounded-lg p-4 shadow-sm">
                                    <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Period End</p>
                                    <p class="text-base font-bold text-gray-800">{{ formatDate(subscriptionData.current_period_end) }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Subscription Details -->
                        <div class="bg-white rounded-xl p-6 border-2 border-gray-200">
                            <h4 class="text-lg font-bold text-gray-800 mb-4">Subscription Details</h4>

                            <div class="space-y-3">
                                <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                    <span class="text-sm font-semibold text-gray-600">Subscription ID</span>
                                    <span class="text-sm text-gray-800 font-mono">{{ subscriptionData.id }}</span>
                                </div>

                                <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                    <span class="text-sm font-semibold text-gray-600">Stripe Subscription</span>
                                    <span class="text-sm text-gray-800 font-mono text-right break-all">{{ subscriptionData.stripe_subscription_id }}</span>
                                </div>

                                <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                    <span class="text-sm font-semibold text-gray-600">Stripe Customer</span>
                                    <span class="text-sm text-gray-800 font-mono text-right break-all">{{ subscriptionData.stripe_customer_id }}</span>
                                </div>

                                <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                    <span class="text-sm font-semibold text-gray-600">Subscribed On</span>
                                    <span class="text-sm text-gray-800">{{ formatDate(subscriptionData.created_at) }}</span>
                                </div>

                                <div class="flex justify-between items-center py-2">
                                    <span class="text-sm font-semibold text-gray-600">Status</span>
                                    <span class="px-3 py-1 bg-green-100 text-green-800 text-xs font-bold rounded-full uppercase">
                                        {{ subscriptionData.status }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Plan Features -->
                        <div class="bg-white rounded-xl p-6 border-2 border-gray-200">
                            <h4 class="text-lg font-bold text-gray-800 mb-4">Plan Features</h4>

                            <div v-if="subscriptionData.plan_category === 'StartClass'" class="space-y-2">
                                <div class="flex items-center gap-2 text-sm text-gray-700">
                                    <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                    </svg>
                                    <span>Unlimited Workout Classes</span>
                                </div>
                                <div class="flex items-center gap-2 text-sm text-gray-700">
                                    <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                    </svg>
                                    <span>Access to All Video Content</span>
                                </div>
                                <div class="flex items-center gap-2 text-sm text-gray-700">
                                    <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                    </svg>
                                    <span>New Content Added Regularly</span>
                                </div>
                            </div>

                            <div v-else-if="subscriptionData.plan_category === 'UpLevel'" class="space-y-2">
                                <div class="flex items-center gap-2 text-sm text-gray-700">
                                    <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                    </svg>
                                    <span>Everything from START CLASS</span>
                                </div>
                                <div class="flex items-center gap-2 text-sm text-gray-700">
                                    <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                    </svg>
                                    <span>Custom Workout Plans</span>
                                </div>
                                <div class="flex items-center gap-2 text-sm text-gray-700">
                                    <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                    </svg>
                                    <span>Custom Nutrition Plans</span>
                                </div>
                                <div class="flex items-center gap-2 text-sm text-gray-700">
                                    <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                    </svg>
                                    <span>Direct Chat with Trainer</span>
                                </div>
                                <div class="flex items-center gap-2 text-sm text-gray-700">
                                    <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                    </svg>
                                    <span>Calendar & Event Scheduling</span>
                                </div>
                                <div class="flex items-center gap-2 text-sm text-gray-700">
                                    <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                    </svg>
                                    <span>Monthly Progress Assessments</span>
                                </div>
                                <div class="flex items-center gap-2 text-sm text-gray-700">
                                    <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                    </svg>
                                    <span>Individual Support & Guidance</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
