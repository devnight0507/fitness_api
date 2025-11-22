<script setup>
import { ref, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import { useToast } from 'vue-toastification';
import {
    FireIcon,
    PlusIcon,
    PencilIcon,
    TrashIcon,
    HomeIcon,
    BuildingOffice2Icon,
    ClockIcon,
    FolderIcon,
    ArrowLeftIcon,
    UsersIcon,
    CheckCircleIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    workouts: Array,
    user: Object,
});

// State
const workoutsList = ref([]);
const studentsList = ref([]);
const isLoading = ref(false);
const toast = useToast();

// Assignment modal state
const assignmentModal = ref({
    show: false,
    workoutId: null,
    workoutTitle: '',
    selectedStudents: [],
    initiallyAssigned: [], // Track who was already assigned
});

// Load workouts and students on mount
onMounted(() => {
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
        } else if (response.status === 401) {
            window.location.href = '/admin/login';
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
        } else if (response.status === 401) {
            window.location.href = '/admin/login';
        }
    } catch (error) {
        console.error('Failed to load students:', error);
    }
};

const editWorkout = (id) => {
    // Navigate to the edit page
    router.visit(`/admin/workouts/${id}/edit`);
};

const createWorkout = () => {
    // Navigate to the create page
    router.visit('/admin/workouts/create');
};

const deleteWorkout = async (id) => {
    if (!confirm('Are you sure you want to delete this workout?')) return;

    try {
        const response = await fetch(`/api/workouts/${id}`, {
            method: 'DELETE',
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('authToken')}`,
                'Accept': 'application/json',
            },
        });

        if (response.ok) {
            showAlert('Workout deleted successfully', 'success');
            loadWorkouts();
        } else {
            showAlert('Failed to delete workout', 'error');
        }
    } catch (error) {
        showAlert('Connection error', 'error');
    }
};

// Student assignment functions
const openAssignmentModal = async (workout) => {
    assignmentModal.value = {
        show: true,
        workoutId: workout.id,
        workoutTitle: workout.title,
        selectedStudents: [],
        initiallyAssigned: [],
    };

    // Fetch current assignments for this workout
    try {
        const response = await fetch(`/api/workouts/${workout.id}/assignments`, {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('authToken')}`,
                'Accept': 'application/json',
            },
        });

        if (response.ok) {
            const assignments = await response.json();
            // Pre-select students who already have this workout assigned
            const assignedStudentIds = assignments.map(a => a.user_id);
            assignmentModal.value.selectedStudents = [...assignedStudentIds];
            assignmentModal.value.initiallyAssigned = [...assignedStudentIds];
        }
    } catch (error) {
        console.error('Failed to load assignments:', error);
    }
};

const closeAssignmentModal = () => {
    assignmentModal.value = {
        show: false,
        workoutId: null,
        workoutTitle: '',
        selectedStudents: [],
    };
};

const toggleStudentSelection = (studentId) => {
    const index = assignmentModal.value.selectedStudents.indexOf(studentId);
    if (index > -1) {
        assignmentModal.value.selectedStudents.splice(index, 1);
    } else {
        assignmentModal.value.selectedStudents.push(studentId);
    }
};

const assignWorkoutToStudents = async () => {
    if (assignmentModal.value.selectedStudents.length === 0) {
        showAlert('Please select at least one student', 'error');
        return;
    }

    try {
        const response = await fetch(`/api/workouts/${assignmentModal.value.workoutId}/assign`, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('authToken')}`,
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                user_ids: assignmentModal.value.selectedStudents,
            }),
        });

        if (response.ok) {
            showAlert('Workout assigned successfully!', 'success');
            closeAssignmentModal();
        } else {
            showAlert('Failed to assign workout', 'error');
        }
    } catch (error) {
        showAlert('Connection error', 'error');
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
    window.location.href = '/admin/login';
};

const goToAdmin = () => {
    router.visit('/admin');
};

// Helper to get thumbnail URL
const getThumbnailUrl = (thumbnailPath) => {
    if (!thumbnailPath) {
        return 'https://via.placeholder.com/400x200?text=No+Image';
    }

    // If it's already a full URL, return as is
    if (thumbnailPath.startsWith('http://') || thumbnailPath.startsWith('https://')) {
        return thumbnailPath;
    }

    // Convert storage path to public URL
    return `/storage/${thumbnailPath}`;
};
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-purple-600 to-purple-800 p-3 sm:p-5">
        <div class="max-w-7xl mx-auto">
            <!-- Header -->
            <div class="bg-white rounded-2xl shadow-2xl p-4 sm:p-6 md:p-8 mb-6 sm:mb-8">
                <div class="flex flex-col gap-4 md:flex-row md:justify-between md:items-center">
                    <div class="flex items-center gap-3 sm:gap-4">
                        <FireIcon class="w-8 h-8 sm:w-10 sm:h-10 text-purple-600 flex-shrink-0" />
                        <div>
                            <h1 class="text-2xl sm:text-3xl md:text-4xl font-bold text-gray-800">Workouts Management</h1>
                            <p class="text-sm sm:text-base text-gray-600 hidden sm:block">Create and manage workout classes for all students</p>
                        </div>
                    </div>
                    <div class="flex gap-2 sm:gap-3">
                        <button
                            @click="goToAdmin"
                            class="flex-1 sm:flex-none px-3 sm:px-6 py-2 sm:py-3 bg-gray-600 text-white rounded-lg text-sm sm:text-base font-semibold hover:bg-gray-700 transition"
                        >
                            <ArrowLeftIcon class="w-4 h-4 sm:w-5 sm:h-5 inline-block sm:mr-2" />
                            <span class="hidden sm:inline">Back to Dashboard</span>
                            <span class="sm:hidden">Back</span>
                        </button>
                        <button
                            @click="logout"
                            class="flex-1 sm:flex-none px-3 sm:px-6 py-2 sm:py-3 bg-red-500 text-white rounded-lg text-sm sm:text-base font-semibold hover:bg-red-600 transition-all hover:-translate-y-0.5 shadow-lg"
                        >
                            Logout
                        </button>
                    </div>
                </div>
            </div>

            <!-- Workouts Section Header -->
            <div class="flex flex-col sm:flex-row justify-between items-stretch sm:items-center gap-3 mb-4 sm:mb-6">
                <h2 class="text-xl sm:text-2xl font-bold text-white">All Workouts (Classes)</h2>
                <button
                    @click="createWorkout"
                    class="px-4 sm:px-6 py-2 sm:py-3 bg-white text-purple-600 rounded-lg text-sm sm:text-base font-semibold hover:bg-gray-100 transition-all shadow-lg hover:shadow-xl"
                >
                    <PlusIcon class="w-4 h-4 sm:w-5 sm:h-5 inline-block mr-2" />
                    Create New Workout
                </button>
            </div>

            <!-- Workout List -->
            <div>
                <div v-if="isLoading" class="bg-white rounded-2xl shadow-xl p-6 sm:p-8">
                    <p class="text-center text-gray-600">Loading workouts...</p>
                </div>
                <div v-else-if="workoutsList.length === 0" class="bg-white rounded-2xl shadow-xl p-6 sm:p-8">
                    <p class="text-center text-gray-600">No workouts yet. Create your first one!</p>
                </div>
                <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
                    <div v-for="workout in workoutsList" :key="workout.id"
                         class="bg-white rounded-xl overflow-hidden shadow-lg hover:shadow-2xl transition-all hover:-translate-y-1"
                    >
                        <img
                            :src="getThumbnailUrl(workout.thumbnail_path)"
                            :alt="workout.title"
                            class="w-full h-40 sm:h-48 object-cover"
                        >
                        <div class="p-4 sm:p-6">
                            <h3 class="text-lg sm:text-xl font-bold text-gray-800 mb-2 sm:mb-3">{{ workout.title }}</h3>
                            <div class="flex flex-wrap gap-2 sm:gap-4 mb-3 sm:mb-4 text-xs sm:text-sm text-gray-600">
                                <span class="flex items-center gap-1">
                                    <component :is="workout.location === 'home' ? HomeIcon : BuildingOffice2Icon" class="w-3 h-3 sm:w-4 sm:h-4" />
                                    {{ workout.location === 'home' ? 'Home' : 'Gym' }}
                                </span>
                                <span class="flex items-center gap-1">
                                    <ClockIcon class="w-3 h-3 sm:w-4 sm:h-4" />
                                    {{ workout.duration }}
                                </span>
                                <span>{{ workout.level }}</span>
                                <span class="flex items-center gap-1">
                                    <FolderIcon class="w-3 h-3 sm:w-4 sm:h-4" />
                                    {{ workout.category }}
                                </span>
                            </div>
                            <p class="text-gray-600 text-xs sm:text-sm mb-3 sm:mb-4 line-clamp-2">{{ workout.description || 'No description' }}</p>
                            <p class="text-xs sm:text-sm mb-3 sm:mb-4 text-blue-600">
                                <FireIcon class="w-3 h-3 sm:w-4 sm:h-4 inline-block mr-1" />
                                {{ workout.exercise_count || 0 }} exercises
                            </p>
                            <div class="flex gap-2 mb-2">
                                <button
                                    @click="editWorkout(workout.id)"
                                    class="flex-1 px-3 sm:px-4 py-2 bg-purple-600 text-white rounded-lg text-xs sm:text-sm font-semibold hover:bg-purple-700 transition"
                                >
                                    <PencilIcon class="w-3 h-3 sm:w-4 sm:h-4 inline-block mr-1" />
                                    Edit
                                </button>
                                <button
                                    @click="deleteWorkout(workout.id)"
                                    class="flex-1 px-3 sm:px-4 py-2 bg-red-500 text-white rounded-lg text-xs sm:text-sm font-semibold hover:bg-red-600 transition"
                                >
                                    <TrashIcon class="w-3 h-3 sm:w-4 sm:h-4 inline-block mr-1" />
                                    Delete
                                </button>
                            </div>
                            <button
                                @click="openAssignmentModal(workout)"
                                class="w-full px-3 sm:px-4 py-2 bg-green-500 text-white rounded-lg text-xs sm:text-sm font-semibold hover:bg-green-600 transition"
                            >
                                <UsersIcon class="w-3 h-3 sm:w-4 sm:h-4 inline-block mr-1" />
                                Assign to Students
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Assignment Modal -->
            <div v-if="assignmentModal.show"
                 class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50"
                 @click.self="closeAssignmentModal"
            >
                <div class="bg-white rounded-2xl shadow-2xl p-4 sm:p-6 md:p-8 max-w-2xl w-full max-h-[85vh] sm:max-h-[80vh] overflow-y-auto">
                    <h3 class="text-xl sm:text-2xl font-bold text-gray-800 mb-3 sm:mb-4 flex items-center gap-2">
                        <UsersIcon class="w-5 h-5 sm:w-6 sm:h-6" />
                        Assign Workout to Students
                    </h3>
                    <div class="mb-4 sm:mb-6">
                        <p class="text-sm sm:text-base text-gray-600">
                            Workout: <span class="font-semibold">{{ assignmentModal.workoutTitle }}</span>
                        </p>
                        <p v-if="assignmentModal.initiallyAssigned.length > 0" class="text-xs sm:text-sm text-green-600 mt-1">
                            Currently assigned to {{ assignmentModal.initiallyAssigned.length }} student(s)
                        </p>
                        <p v-else class="text-xs sm:text-sm text-gray-500 mt-1">
                            Not assigned to any students yet
                        </p>
                    </div>

                    <!-- Students List -->
                    <div class="space-y-2 sm:space-y-3 mb-4 sm:mb-6">
                        <div v-for="student in studentsList" :key="student.id"
                             class="border-2 rounded-lg p-3 sm:p-4 cursor-pointer transition"
                             :class="assignmentModal.selectedStudents.includes(student.id)
                                 ? 'border-purple-600 bg-purple-50'
                                 : 'border-gray-200 hover:border-purple-300'"
                             @click="toggleStudentSelection(student.id)"
                        >
                            <div class="flex items-center gap-2 sm:gap-3">
                                <div class="flex-shrink-0 w-5 h-5 sm:w-6 sm:h-6 border-2 rounded flex items-center justify-center"
                                     :class="assignmentModal.selectedStudents.includes(student.id)
                                         ? 'border-purple-600 bg-purple-600'
                                         : 'border-gray-300'"
                                >
                                    <CheckCircleIcon v-if="assignmentModal.selectedStudents.includes(student.id)" class="w-3 h-3 sm:w-4 sm:h-4 text-white" />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-2">
                                        <p class="font-semibold text-sm sm:text-base text-gray-800 truncate">{{ student.name }}</p>
                                        <span v-if="assignmentModal.initiallyAssigned.includes(student.id)"
                                              class="px-2 py-0.5 bg-green-100 text-green-700 text-xs rounded-full font-semibold self-start sm:self-auto whitespace-nowrap"
                                        >
                                            Already Assigned
                                        </span>
                                    </div>
                                    <p class="text-xs sm:text-sm text-gray-500 truncate">{{ student.email }}</p>
                                </div>
                            </div>
                        </div>
                        <div v-if="studentsList.length === 0" class="text-center text-gray-500 py-6 sm:py-8 text-sm sm:text-base">
                            No students found. Please add students first.
                        </div>
                    </div>

                    <!-- Modal Actions -->
                    <div class="flex flex-col sm:flex-row gap-2 sm:gap-4">
                        <button
                            @click="assignWorkoutToStudents"
                            class="flex-1 px-4 sm:px-6 py-2 sm:py-3 bg-purple-600 text-white rounded-lg text-sm sm:text-base font-semibold hover:bg-purple-700 transition disabled:opacity-50"
                            :disabled="assignmentModal.selectedStudents.length === 0"
                        >
                            <CheckCircleIcon class="w-4 h-4 sm:w-5 sm:h-5 inline-block mr-2" />
                            Assign to {{ assignmentModal.selectedStudents.length }} student(s)
                        </button>
                        <button
                            @click="closeAssignmentModal"
                            class="px-4 sm:px-6 py-2 sm:py-3 bg-gray-500 text-white rounded-lg text-sm sm:text-base font-semibold hover:bg-gray-600 transition"
                        >
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
