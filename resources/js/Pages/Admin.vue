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

// Assignment modal state
const assignmentModal = ref({
    show: false,
    workoutId: null,
    workoutTitle: '',
    selectedStudents: [],
    initiallyAssigned: [], // Track who was already assigned
});

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

// Exercise management functions
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
    window.location.href = '/admin';
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
    <div class="min-h-screen bg-gradient-to-br from-purple-600 to-purple-800 p-5">
        <div class="max-w-7xl mx-auto">
            <!-- Header -->
            <div class="bg-white rounded-2xl shadow-2xl p-8 mb-8">
                <div class="flex justify-between items-center">
                    <div>
                        <h1 class="text-4xl font-bold text-gray-800 mb-2">🏋️ Fitness App Admin Panel</h1>
                        <p class="text-gray-600">Manage workouts, upload videos, and assign to students</p>
                    </div>
                    <button
                        @click="logout"
                        class="px-6 py-3 bg-red-500 text-white rounded-lg font-semibold hover:bg-red-600 transition-all hover:-translate-y-0.5 shadow-lg"
                    >
                        Logout
                    </button>
                </div>
            </div>

            <!-- Header -->
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-gray-800">📋 Workout List</h2>
                <button
                    @click="createWorkout"
                    class="px-6 py-3 bg-purple-600 text-white rounded-lg font-semibold hover:bg-purple-700 transition-all shadow-lg hover:shadow-xl"
                >
                    ➕ Create New Workout
                </button>
            </div>

            <!-- Workout List -->
            <div>
                <div v-if="isLoading" class="bg-white rounded-2xl shadow-xl p-8">
                    <p class="text-center text-gray-600">Loading workouts...</p>
                </div>
                <div v-else-if="workoutsList.length === 0" class="bg-white rounded-2xl shadow-xl p-8">
                    <p class="text-center text-gray-600">No workouts yet. Create your first one!</p>
                </div>
                <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div v-for="workout in workoutsList" :key="workout.id"
                         class="bg-white rounded-xl overflow-hidden shadow-lg hover:shadow-2xl transition-all hover:-translate-y-1"
                    >
                        <img
                            :src="getThumbnailUrl(workout.thumbnail_path)"
                            :alt="workout.title"
                            class="w-full h-48 object-cover"
                        >
                        <div class="p-6">
                            <h3 class="text-xl font-bold text-gray-800 mb-3">{{ workout.title }}</h3>
                            <div class="flex gap-4 mb-4 text-sm text-gray-600">
                                <span>⏱️ {{ workout.duration }}</span>
                                <span>📊 {{ workout.level }}</span>
                                <span>📁 {{ workout.category }}</span>
                            </div>
                            <p class="text-gray-600 text-sm mb-4">{{ workout.description || 'No description' }}</p>
                            <p class="text-sm mb-2" :class="workout.video_path || workout.youtube_url ? 'text-green-600' : 'text-red-600'">
                                {{ workout.video_path ? '✅ Local video uploaded' : workout.youtube_url ? '✅ YouTube video linked' : '❌ No video' }}
                            </p>
                            <p class="text-sm mb-4 text-blue-600">
                                💪 {{ workout.exercise_count || 0 }} exercises
                            </p>
                            <div class="flex gap-2 mb-2">
                                <button
                                    @click="editWorkout(workout.id)"
                                    class="flex-1 px-4 py-2 bg-purple-600 text-white rounded-lg font-semibold hover:bg-purple-700 transition"
                                >
                                    ✏️ Edit
                                </button>
                                <button
                                    @click="deleteWorkout(workout.id)"
                                    class="flex-1 px-4 py-2 bg-red-500 text-white rounded-lg font-semibold hover:bg-red-600 transition"
                                >
                                    🗑️ Delete
                                </button>
                            </div>
                            <button
                                @click="openAssignmentModal(workout)"
                                class="w-full px-4 py-2 bg-green-500 text-white rounded-lg font-semibold hover:bg-green-600 transition"
                            >
                                👥 Assign to Students
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
                <div class="bg-white rounded-2xl shadow-2xl p-8 max-w-2xl w-full max-h-[80vh] overflow-y-auto">
                    <h3 class="text-2xl font-bold text-gray-800 mb-4">
                        👥 Assign Workout to Students
                    </h3>
                    <div class="mb-6">
                        <p class="text-gray-600">
                            Workout: <span class="font-semibold">{{ assignmentModal.workoutTitle }}</span>
                        </p>
                        <p v-if="assignmentModal.initiallyAssigned.length > 0" class="text-sm text-green-600 mt-1">
                            Currently assigned to {{ assignmentModal.initiallyAssigned.length }} student(s)
                        </p>
                        <p v-else class="text-sm text-gray-500 mt-1">
                            Not assigned to any students yet
                        </p>
                    </div>

                    <!-- Students List -->
                    <div class="space-y-3 mb-6">
                        <div v-for="student in studentsList" :key="student.id"
                             class="border-2 rounded-lg p-4 cursor-pointer transition"
                             :class="assignmentModal.selectedStudents.includes(student.id)
                                 ? 'border-purple-600 bg-purple-50'
                                 : 'border-gray-200 hover:border-purple-300'"
                             @click="toggleStudentSelection(student.id)"
                        >
                            <div class="flex items-center gap-3">
                                <div class="flex-shrink-0 w-6 h-6 border-2 rounded flex items-center justify-center"
                                     :class="assignmentModal.selectedStudents.includes(student.id)
                                         ? 'border-purple-600 bg-purple-600'
                                         : 'border-gray-300'"
                                >
                                    <span v-if="assignmentModal.selectedStudents.includes(student.id)" class="text-white text-sm">✓</span>
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-center gap-2">
                                        <p class="font-semibold text-gray-800">{{ student.name }}</p>
                                        <span v-if="assignmentModal.initiallyAssigned.includes(student.id)"
                                              class="px-2 py-0.5 bg-green-100 text-green-700 text-xs rounded-full font-semibold"
                                        >
                                            Already Assigned
                                        </span>
                                    </div>
                                    <p class="text-sm text-gray-500">{{ student.email }}</p>
                                </div>
                            </div>
                        </div>
                        <div v-if="studentsList.length === 0" class="text-center text-gray-500 py-8">
                            No students found. Please add students first.
                        </div>
                    </div>

                    <!-- Modal Actions -->
                    <div class="flex gap-4">
                        <button
                            @click="assignWorkoutToStudents"
                            class="flex-1 px-6 py-3 bg-purple-600 text-white rounded-lg font-semibold hover:bg-purple-700 transition"
                            :disabled="assignmentModal.selectedStudents.length === 0"
                        >
                            ✅ Assign to {{ assignmentModal.selectedStudents.length }} student(s)
                        </button>
                        <button
                            @click="closeAssignmentModal"
                            class="px-6 py-3 bg-gray-500 text-white rounded-lg font-semibold hover:bg-gray-600 transition"
                        >
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
