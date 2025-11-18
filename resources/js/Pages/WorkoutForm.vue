<script setup>
import { ref, computed, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import { useToast } from 'vue-toastification';
import {
    HomeIcon,
    BuildingOffice2Icon,
    FireIcon,
    PlusIcon,
    ArrowUpIcon,
    ArrowDownIcon,
    TrashIcon,
    ArrowUturnLeftIcon,
    PhotoIcon,
    UsersIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    workout: Object, // Will be null for create, populated for edit
});

const toast = useToast();

// Detect student_id from URL params for personal workouts
const urlParams = new URLSearchParams(window.location.search);
const studentId = ref(urlParams.get('student_id'));
const isPersonalWorkout = computed(() => !!studentId.value);

// Form state
const form = ref({
    id: null,
    title: '',
    category: '',
    location: 'gym',
    duration: '',
    level: '',
    description: '',
    thumbnail_path: '',
    youtube_url: '',
    video_file: null,
    thumbnail_file: null,
    exercises: [],
    is_personal: false,
    assigned_user_id: null,
});

const videoFileName = ref('Click to upload video (MP4, MOV, AVI, MKV, WEBM)');
const thumbnailFileName = ref('Click to upload image (JPG, PNG)');
const uploadProgress = ref(0);
const isUploading = ref(false);
const isSaving = ref(false);

// Exercise form state
const exerciseForm = ref({
    name: '',
    sets: 3,
    reps: '10',
    rest: 60,
});

// Computed property to display thumbnail URL properly
const displayThumbnailUrl = computed(() => {
    const path = form.value.thumbnail_path;
    if (!path) return '';

    // If it's already a full URL, return as is
    if (path.startsWith('http://') || path.startsWith('https://')) {
        return path;
    }

    // If it's a local storage path, convert to full URL with app URL
    return `${window.location.origin}/storage/${path}`;
});

onMounted(() => {
    // If editing, populate form
    if (props.workout) {
        form.value = {
            id: props.workout.id,
            title: props.workout.title,
            category: props.workout.category,
            location: props.workout.location || 'gym',
            duration: props.workout.duration,
            level: props.workout.level,
            description: props.workout.description || '',
            thumbnail_path: props.workout.thumbnail_path || '',
            youtube_url: props.workout.youtube_url || '',
            video_file: null,
            thumbnail_file: null,
            exercises: props.workout.exercises || [],
            is_personal: props.workout.is_personal || false,
            assigned_user_id: props.workout.assigned_user_id || null,
        };
    }
});

const pageTitle = computed(() => {
    if (form.value.id) {
        return form.value.is_personal ? 'Edit Personal Workout' : 'Edit Workout';
    }
    return studentId.value ? 'Create Personal Workout for Student' : 'Create New Workout';
});

const handleVideoChange = (event) => {
    const file = event.target.files[0];
    if (file) {
        form.value.video_file = file;
        videoFileName.value = `${file.name} (${(file.size / 1024 / 1024).toFixed(2)} MB)`;
    }
};

const handleThumbnailChange = (event) => {
    const file = event.target.files[0];
    if (file) {
        form.value.thumbnail_file = file;
        thumbnailFileName.value = `${file.name} (${(file.size / 1024 / 1024).toFixed(2)} MB)`;
    }
};

const addExercise = () => {

    if (!exerciseForm.value.name.trim()) {
        toast.error('Please enter exercise name');
        return;
    }

    const newExercise = {
        name: exerciseForm.value.name,
        sets: exerciseForm.value.sets,
        reps: exerciseForm.value.reps,
        rest: exerciseForm.value.rest,
        order_index: form.value.exercises.length,
    };

    form.value.exercises.push(newExercise);

    toast.success('Exercise added!');

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

const submitWorkout = async () => {
    isSaving.value = true;

    const url = form.value.id ? `/api/workouts/${form.value.id}` : '/api/workouts';
    const method = form.value.id ? 'PUT' : 'POST';

    // Prepare exercises data - keep sets and rest as numbers
    const exercises = form.value.exercises.map(ex => ({
        name: ex.name,
        sets: ex.sets || null,
        reps: ex.reps?.toString() || null, // reps can be string like "60 sec"
        rest: ex.rest || null,
        order_index: ex.order_index,
    }));

    const data = {
        title: form.value.title,
        category: form.value.category,
        location: form.value.location,
        duration: form.value.duration,
        level: form.value.level,
        description: form.value.description,
        thumbnail_path: form.value.thumbnail_path,
        youtube_url: form.value.youtube_url,
        exercises: exercises,
        is_personal: form.value.id ? form.value.is_personal : (studentId.value ? true : false),
        assigned_user_id: form.value.id ? form.value.assigned_user_id : (studentId.value ? parseInt(studentId.value) : null),
    };

    console.log('Submitting workout with data:', data);
    console.log('Exercises count:', exercises.length);
    console.log('Exercises array:', exercises);

    try {
        const response = await fetch(url, {
            method: method,
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('authToken')}`,
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify(data),
        });

        if (response.ok) {
            const savedWorkout = await response.json();
            console.log('Saved workout:', savedWorkout);
            let message = form.value.id ? 'Workout updated successfully!' : 'Workout created successfully!';

            // Upload thumbnail if selected
            if (form.value.thumbnail_file) {
                console.log('Uploading thumbnail for workout ID:', savedWorkout.id);
                await uploadThumbnail(savedWorkout.id);
                message += ' Thumbnail uploaded!';
            }

            // Upload video if selected
            if (form.value.video_file) {
                await uploadVideo(savedWorkout.id);
                message += ' Video uploaded!';
            }

            toast.success(message);

            // Redirect back to appropriate page
            setTimeout(() => {
                // Check if workout is personal (either from savedWorkout or form data)
                const isPersonal = savedWorkout.is_personal || form.value.is_personal;
                const assignedUserId = savedWorkout.assigned_user_id || form.value.assigned_user_id;

                if (isPersonal && assignedUserId) {
                    // If it's a personal workout, go back to student profile
                    router.visit(`/admin/students/${assignedUserId}`);
                } else {
                    // Otherwise go to workouts list
                    router.visit('/admin/workouts');
                }
            }, 1500);
        } else {
            const error = await response.json();
            toast.error(error.message || 'Failed to save workout');
        }
    } catch (error) {
        toast.error('Connection error: ' + error.message);
    } finally {
        isSaving.value = false;
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
                const errorData = JSON.parse(xhr.responseText);
                console.error('Video upload error response:', errorData);
                reject(new Error(errorData.message || 'Video upload failed'));
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

const uploadThumbnail = async (workoutId) => {
    const formData = new FormData();
    formData.append('thumbnail', form.value.thumbnail_file);
    formData.append('workout_id', workoutId);

    try {
        const response = await fetch('/api/thumbnails/upload', {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('authToken')}`,
            },
            body: formData,
        });

        if (!response.ok) {
            const errorData = await response.json();
            console.error('Thumbnail upload error response:', errorData);
            throw new Error(errorData.message || 'Thumbnail upload failed');
        }

        return await response.json();
    } catch (error) {
        console.error('Thumbnail upload error:', error);
        throw error;
    }
};

const goBack = () => {
    // If editing a personal workout or creating one via student ID, go back to student page
    if (form.value.is_personal && form.value.assigned_user_id) {
        router.visit(`/admin/students/${form.value.assigned_user_id}`);
    } else if (studentId.value) {
        router.visit(`/admin/students/${studentId.value}`);
    } else {
        router.visit('/admin/workouts');
    }
};
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-purple-600 to-purple-800 p-5">
        <div class="max-w-5xl mx-auto">
            <!-- Header -->
            <div class="bg-white rounded-2xl shadow-2xl p-8 mb-8">
                <div class="flex justify-between items-center">
                    <div>
                        <h1 class="text-4xl font-bold text-gray-800 mb-2">{{ pageTitle }}</h1>
                        <p class="text-gray-600">Fill in the details below to {{ form.id ? 'update' : 'create' }} a workout</p>
                        <div v-if="studentId || form.is_personal" class="mt-3 inline-block px-4 py-2 bg-blue-100 text-blue-800 rounded-lg font-semibold text-sm">
                            <UsersIcon class="w-4 h-4 inline-block mr-2" />
                            Personal Workout - Will be assigned only to this student
                        </div>
                    </div>
                    <button
                        @click="goBack"
                        class="px-6 py-3 bg-gray-500 text-white rounded-lg font-semibold hover:bg-gray-600 transition-all hover:-translate-y-0.5 shadow-lg"
                    >
                        <ArrowUturnLeftIcon class="w-5 h-5 inline-block mr-2" />
                        Back
                    </button>
                </div>
            </div>

            <!-- Workout Form -->
            <div class="bg-white rounded-2xl shadow-2xl p-8">
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

                    <!-- Location & Level -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Location *</label>
                            <select
                                v-model="form.location"
                                required
                                class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:border-purple-600 focus:outline-none transition"
                            >
                                <option value="gym">Gym</option>
                                <option value="home">Home</option>
                            </select>
                        </div>
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

                    <!-- Thumbnail Upload/URL -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Thumbnail Image</label>

                        <!-- Thumbnail Upload -->
                        <div
                            @click="$refs.thumbnailInput.click()"
                            class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center cursor-pointer hover:border-purple-600 hover:bg-purple-50 transition mb-3"
                        >
                            <input
                                ref="thumbnailInput"
                                type="file"
                                @change="handleThumbnailChange"
                                accept="image/jpeg,image/jpg,image/png,image/webp,.jpg,.jpeg,.png,.webp"
                                class="hidden"
                            >
                            <div class="flex items-center justify-center gap-2 text-gray-700">
                                <PhotoIcon class="w-5 h-5" />
                                <p>{{ thumbnailFileName }}</p>
                            </div>
                            <p class="text-sm text-gray-500 mt-2">Max size: 5MB</p>
                        </div>

                        <!-- OR Thumbnail URL -->
                        <p class="text-sm font-semibold text-gray-600 mb-2 text-center">OR use image URL</p>
                        <input
                            :value="displayThumbnailUrl"
                            @input="form.thumbnail_path = $event.target.value"
                            type="url"
                            placeholder="https://... or https://youtu.be/..."
                            class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:border-purple-600 focus:outline-none transition"
                        >
                    </div>

                    <!-- Exercise Builder Section -->
                    <div class="border-t-2 border-gray-200 pt-6">
                        <div class="flex items-center gap-3 mb-4">
                            <FireIcon class="w-8 h-8 text-purple-600" />
                            <h3 class="text-2xl font-bold text-gray-800">Workout Exercises</h3>
                        </div>
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
                                    <PlusIcon class="w-5 h-5 inline-block mr-2" />
                                    Add Exercise
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
                                        <ArrowUpIcon class="w-4 h-4" />
                                    </button>
                                    <button
                                        type="button"
                                        @click="moveExerciseDown(index)"
                                        :disabled="index === form.exercises.length - 1"
                                        class="px-3 py-1 bg-gray-200 text-gray-700 rounded hover:bg-gray-300 transition disabled:opacity-50"
                                        title="Move down"
                                    >
                                        <ArrowDownIcon class="w-4 h-4" />
                                    </button>
                                    <button
                                        type="button"
                                        @click="removeExercise(index)"
                                        class="px-3 py-1 bg-red-500 text-white rounded hover:bg-red-600 transition"
                                        title="Remove"
                                    >
                                        <TrashIcon class="w-4 h-4" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Buttons -->
                    <div class="flex gap-4 pt-6">
                        <button
                            type="submit"
                            :disabled="isSaving || isUploading"
                            class="flex-1 px-8 py-4 bg-purple-600 text-white rounded-lg font-semibold hover:bg-purple-700 transition-all hover:-translate-y-0.5 shadow-lg disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <span v-if="!isSaving">{{ form.id ? 'Update' : 'Create' }} Workout</span>
                            <span v-else>Saving...</span>
                        </button>
                        <button
                            type="button"
                            @click="goBack"
                            class="px-8 py-4 bg-gray-500 text-white rounded-lg font-semibold hover:bg-gray-600 transition-all"
                        >
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
