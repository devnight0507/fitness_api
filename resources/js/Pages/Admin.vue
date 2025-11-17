<script setup>
import { ref, computed, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    workouts: Array,
    user: Object,
});

// State
const activeTab = ref('list');
const workoutsList = ref([]);
const studentsList = ref([]);
const form = ref({
    id: null,
    title: '',
    category: '',
    duration: '',
    level: '',
    description: '',
    thumbnail_path: '',
    youtube_url: '',
    video_file: null,
    exercises: [], // Array of exercises for this workout
});
const videoFileName = ref('Click to upload video (MP4, MOV, AVI, MKV, WEBM)');
const uploadProgress = ref(0);
const isUploading = ref(false);
const alert = ref({ show: false, message: '', type: '' });
const isLoading = ref(false);

// Exercise form state
const exerciseForm = ref({
    name: '',
    sets: 3,
    reps: '10',
    rest: 60,
});

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
const switchTab = (tab) => {
    activeTab.value = tab;
    if (tab === 'list') {
        loadWorkouts();
    }
};

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

const handleVideoChange = (event) => {
    const file = event.target.files[0];
    if (file) {
        form.value.video_file = file;
        videoFileName.value = `${file.name} (${(file.size / 1024 / 1024).toFixed(2)} MB)`;
    }
};

const submitWorkout = async () => {
    const url = form.value.id ? `/api/workouts/${form.value.id}` : '/api/workouts';
    const method = form.value.id ? 'put' : 'post';

    const data = {
        title: form.value.title,
        category: form.value.category,
        duration: form.value.duration,
        level: form.value.level,
        description: form.value.description,
        thumbnail_path: form.value.thumbnail_path,
        youtube_url: form.value.youtube_url,
        exercises: form.value.exercises,
    };

    try {
        const response = await fetch(url, {
            method: method.toUpperCase(),
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('authToken')}`,
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify(data),
        });

        if (response.ok) {
            const savedWorkout = await response.json();
            let message = form.value.id ? 'Workout updated successfully!' : 'Workout created successfully!';

            // Upload video if selected
            if (form.value.video_file) {
                await uploadVideo(savedWorkout.id);
                message += ' Video uploaded successfully!';
            }

            showAlert(message, 'success');
            resetForm();
            switchTab('list');
        } else {
            const error = await response.json();
            showAlert(error.message || 'Failed to save workout', 'error');
        }
    } catch (error) {
        showAlert('Connection error: ' + error.message, 'error');
    }
};

const uploadVideo = async (workoutId) => {
    const formData = new FormData();
    formData.append('video', form.value.video_file);
    formData.append('workout_id', workoutId);

    isUploading.value = true;

    return new Promise((resolve, reject) => {
        const xhr = new XMLHttpRequest();

        xhr.upload.addEventListener('progress', (e) => {
            if (e.lengthComputable) {
                uploadProgress.value = Math.round((e.loaded / e.total) * 100);
            }
        });

        xhr.addEventListener('load', () => {
            isUploading.value = false;
            uploadProgress.value = 0;

            if (xhr.status >= 200 && xhr.status < 300) {
                resolve(JSON.parse(xhr.responseText));
            } else {
                reject(new Error('Video upload failed'));
            }
        });

        xhr.addEventListener('error', () => {
            isUploading.value = false;
            uploadProgress.value = 0;
            reject(new Error('Video upload error'));
        });

        xhr.open('POST', '/api/videos/upload');
        xhr.setRequestHeader('Authorization', `Bearer ${localStorage.getItem('authToken')}`);
        xhr.send(formData);
    });
};

const editWorkout = async (id) => {
    try {
        const response = await fetch(`/api/workouts/${id}`, {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('authToken')}`,
                'Accept': 'application/json',
            },
        });

        if (response.ok) {
            const workout = await response.json();
            form.value = {
                id: workout.id,
                title: workout.title,
                category: workout.category,
                duration: workout.duration,
                level: workout.level,
                description: workout.description || '',
                thumbnail_path: workout.thumbnail_path || '',
                youtube_url: workout.youtube_url || '',
                video_file: null,
                exercises: workout.exercises || [],
            };
            switchTab('create');
        }
    } catch (error) {
        showAlert('Failed to load workout', 'error');
    }
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
const addExercise = () => {
    if (!exerciseForm.value.name.trim()) {
        showAlert('Please enter exercise name', 'error');
        return;
    }

    form.value.exercises.push({
        name: exerciseForm.value.name,
        sets: exerciseForm.value.sets,
        reps: exerciseForm.value.reps,
        rest: exerciseForm.value.rest,
        order_index: form.value.exercises.length,
    });

    // Reset exercise form
    exerciseForm.value = {
        name: '',
        sets: 3,
        reps: '10',
        rest: 60,
    };
};

const removeExercise = (index) => {
    form.value.exercises.splice(index, 1);
    // Update order_index for remaining exercises
    form.value.exercises.forEach((ex, idx) => {
        ex.order_index = idx;
    });
};

const moveExerciseUp = (index) => {
    if (index === 0) return;
    const temp = form.value.exercises[index];
    form.value.exercises[index] = form.value.exercises[index - 1];
    form.value.exercises[index - 1] = temp;
    // Update order_index
    form.value.exercises.forEach((ex, idx) => {
        ex.order_index = idx;
    });
};

const moveExerciseDown = (index) => {
    if (index === form.value.exercises.length - 1) return;
    const temp = form.value.exercises[index];
    form.value.exercises[index] = form.value.exercises[index + 1];
    form.value.exercises[index + 1] = temp;
    // Update order_index
    form.value.exercises.forEach((ex, idx) => {
        ex.order_index = idx;
    });
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

const resetForm = () => {
    form.value = {
        id: null,
        title: '',
        category: '',
        duration: '',
        level: '',
        description: '',
        thumbnail_path: '',
        youtube_url: '',
        video_file: null,
        exercises: [],
    };
    videoFileName.value = 'Click to upload video (MP4, MOV, AVI, MKV, WEBM)';
};

const showAlert = (message, type) => {
    alert.value = { show: true, message, type };
    setTimeout(() => {
        alert.value.show = false;
    }, 5000);
};

const logout = () => {
    localStorage.removeItem('authToken');
    window.location.href = '/admin';
};

const formTitle = computed(() => {
    return form.value.id ? 'Edit Workout' : 'Create New Workout';
});
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

            <!-- Alert -->
            <div v-if="alert.show"
                 :class="[
                     'mb-6 p-4 rounded-lg',
                     alert.type === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                 ]"
            >
                {{ alert.message }}
            </div>

            <!-- Tabs -->
            <div class="flex gap-3 mb-6">
                <button
                    @click="switchTab('list')"
                    :class="[
                        'px-6 py-3 rounded-lg font-semibold transition-all',
                        activeTab === 'list' ? 'bg-purple-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'
                    ]"
                >
                    📋 Workout List
                </button>
                <button
                    @click="switchTab('create')"
                    :class="[
                        'px-6 py-3 rounded-lg font-semibold transition-all',
                        activeTab === 'create' ? 'bg-purple-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'
                    ]"
                >
                    ➕ Create Workout
                </button>
            </div>

            <!-- Workout List Tab -->
            <div v-show="activeTab === 'list'">
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
                            :src="workout.thumbnail_path || 'https://via.placeholder.com/400x200?text=No+Image'"
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

            <!-- Create/Edit Workout Tab -->
            <div v-show="activeTab === 'create'" class="bg-white rounded-2xl shadow-2xl p-8">
                <h2 class="text-3xl font-bold text-gray-800 mb-6">{{ formTitle }}</h2>
                <form @submit.prevent="submitWorkout" class="space-y-6">
                    <!-- Title -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Title *</label>
                        <input
                            v-model="form.title"
                            type="text"
                            required
                            placeholder="e.g., Full Body Strength Training"
                            class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:border-purple-600 focus:outline-none transition"
                        >
                    </div>

                    <!-- Category & Duration -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Category *</label>
                            <select
                                v-model="form.category"
                                required
                                class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:border-purple-600 focus:outline-none transition"
                            >
                                <option value="">Select category</option>
                                <option value="Gym">Gym</option>
                                <option value="Home">Home</option>
                                <option value="Outdoor">Outdoor</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Duration *</label>
                            <input
                                v-model="form.duration"
                                type="text"
                                required
                                placeholder="e.g., 45 min"
                                class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:border-purple-600 focus:outline-none transition"
                            >
                        </div>
                    </div>

                    <!-- Level -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Level *</label>
                        <select
                            v-model="form.level"
                            required
                            class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:border-purple-600 focus:outline-none transition"
                        >
                            <option value="">Select level</option>
                            <option value="Beginner">Beginner</option>
                            <option value="Intermediate">Intermediate</option>
                            <option value="Advanced">Advanced</option>
                        </select>
                    </div>

                    <!-- Description -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Description</label>
                        <textarea
                            v-model="form.description"
                            rows="4"
                            placeholder="Describe the workout..."
                            class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:border-purple-600 focus:outline-none transition resize-none"
                        ></textarea>
                    </div>

                    <!-- Video Upload -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Video Upload (Local Video)</label>
                        <div
                            @click="$refs.videoInput.click()"
                            class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center cursor-pointer hover:border-purple-600 hover:bg-purple-50 transition"
                        >
                            <input
                                ref="videoInput"
                                type="file"
                                @change="handleVideoChange"
                                accept="video/mp4,video/quicktime,video/x-msvideo,.mp4,.mov,.avi,.mkv,.webm"
                                class="hidden"
                            >
                            <p class="text-gray-700">📹 {{ videoFileName }}</p>
                            <p class="text-sm text-gray-500 mt-2">Max size: 100MB</p>
                            <div v-if="isUploading" class="w-full bg-gray-200 rounded-full h-2 mt-4">
                                <div class="bg-purple-600 h-2 rounded-full transition-all" :style="{ width: uploadProgress + '%' }"></div>
                            </div>
                        </div>
                    </div>

                    <!-- YouTube URL -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">OR YouTube Video URL</label>
                        <input
                            v-model="form.youtube_url"
                            type="url"
                            placeholder="https://www.youtube.com/watch?v=... or https://youtu.be/..."
                            class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:border-purple-600 focus:outline-none transition"
                        >
                        <p class="text-sm text-gray-500 mt-2">Use either local video upload OR YouTube URL (not both)</p>
                    </div>

                    <!-- Thumbnail URL -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Thumbnail URL</label>
                        <input
                            v-model="form.thumbnail_path"
                            type="url"
                            placeholder="https://..."
                            class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:border-purple-600 focus:outline-none transition"
                        >
                    </div>

                    <!-- Exercise Builder Section -->
                    <div class="border-t-2 border-gray-200 pt-6">
                        <h3 class="text-2xl font-bold text-gray-800 mb-4">💪 Workout Exercises</h3>
                        <p class="text-sm text-gray-600 mb-4">Build your personalized workout by adding exercises with sets, reps, and rest times.</p>

                        <!-- Add Exercise Form -->
                        <div class="bg-gray-50 p-6 rounded-lg mb-6">
                            <h4 class="font-semibold text-gray-700 mb-4">Add New Exercise</h4>
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Exercise Name *</label>
                                    <input
                                        v-model="exerciseForm.name"
                                        type="text"
                                        placeholder="e.g., Barbell Squat"
                                        class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-purple-600 focus:outline-none"
                                    >
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Sets *</label>
                                    <input
                                        v-model.number="exerciseForm.sets"
                                        type="number"
                                        min="1"
                                        class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-purple-600 focus:outline-none"
                                    >
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Reps *</label>
                                    <input
                                        v-model="exerciseForm.reps"
                                        type="text"
                                        placeholder="10 or 60s"
                                        class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-purple-600 focus:outline-none"
                                    >
                                </div>
                            </div>
                            <div class="flex gap-4 items-end">
                                <div class="flex-1">
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Rest (seconds) *</label>
                                    <input
                                        v-model.number="exerciseForm.rest"
                                        type="number"
                                        min="0"
                                        class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-purple-600 focus:outline-none"
                                    >
                                </div>
                                <button
                                    type="button"
                                    @click="addExercise"
                                    class="px-6 py-2 bg-green-500 text-white rounded-lg font-semibold hover:bg-green-600 transition"
                                >
                                    ➕ Add Exercise
                                </button>
                            </div>
                        </div>

                        <!-- Exercise List -->
                        <div v-if="form.exercises.length === 0" class="text-center text-gray-500 py-8 bg-gray-50 rounded-lg">
                            No exercises added yet. Add your first exercise above!
                        </div>
                        <div v-else class="space-y-3">
                            <div v-for="(exercise, index) in form.exercises" :key="index"
                                 class="bg-white border-2 border-gray-200 rounded-lg p-4 flex items-center gap-4"
                            >
                                <div class="flex-shrink-0 text-2xl font-bold text-purple-600">
                                    {{ index + 1 }}
                                </div>
                                <div class="flex-1 grid grid-cols-1 md:grid-cols-4 gap-4">
                                    <div class="md:col-span-2">
                                        <p class="font-semibold text-gray-800">{{ exercise.name }}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600">
                                            <span class="font-semibold">{{ exercise.sets }}</span> sets ×
                                            <span class="font-semibold">{{ exercise.reps }}</span> reps
                                        </p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600">
                                            Rest: <span class="font-semibold">{{ exercise.rest }}s</span>
                                        </p>
                                    </div>
                                </div>
                                <div class="flex-shrink-0 flex gap-2">
                                    <button
                                        type="button"
                                        @click="moveExerciseUp(index)"
                                        :disabled="index === 0"
                                        class="px-3 py-1 bg-gray-200 text-gray-700 rounded hover:bg-gray-300 transition disabled:opacity-50"
                                        title="Move up"
                                    >
                                        ⬆️
                                    </button>
                                    <button
                                        type="button"
                                        @click="moveExerciseDown(index)"
                                        :disabled="index === form.exercises.length - 1"
                                        class="px-3 py-1 bg-gray-200 text-gray-700 rounded hover:bg-gray-300 transition disabled:opacity-50"
                                        title="Move down"
                                    >
                                        ⬇️
                                    </button>
                                    <button
                                        type="button"
                                        @click="removeExercise(index)"
                                        class="px-3 py-1 bg-red-500 text-white rounded hover:bg-red-600 transition"
                                        title="Remove"
                                    >
                                        🗑️
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Buttons -->
                    <div class="flex gap-4">
                        <button
                            type="submit"
                            class="px-8 py-3 bg-purple-600 text-white rounded-lg font-semibold hover:bg-purple-700 transition-all hover:-translate-y-0.5 shadow-lg"
                        >
                            💾 Save Workout
                        </button>
                        <button
                            type="button"
                            @click="resetForm"
                            class="px-8 py-3 bg-gray-500 text-white rounded-lg font-semibold hover:bg-gray-600 transition-all"
                        >
                            🔄 Reset
                        </button>
                    </div>
                </form>
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
