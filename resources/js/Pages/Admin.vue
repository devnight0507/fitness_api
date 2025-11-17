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
});
const videoFileName = ref('Click to upload video (MP4, MOV, AVI, MKV, WEBM)');
const uploadProgress = ref(0);
const isUploading = ref(false);
const alert = ref({ show: false, message: '', type: '' });
const isLoading = ref(false);

// Check authentication and load workouts on mount
onMounted(() => {
    const token = localStorage.getItem('authToken');
    if (!token) {
        // No token, redirect to login
        window.location.href = '/admin/login';
        return;
    }

    // Load workouts from API
    loadWorkouts();
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
                            <p class="text-sm mb-4" :class="workout.video_path || workout.youtube_url ? 'text-green-600' : 'text-red-600'">
                                {{ workout.video_path ? '✅ Local video uploaded' : workout.youtube_url ? '✅ YouTube video linked' : '❌ No video' }}
                            </p>
                            <div class="flex gap-2">
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
        </div>
    </div>
</template>
