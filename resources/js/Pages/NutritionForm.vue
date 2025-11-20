<script setup>
import { ref, computed, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import { useToast } from 'vue-toastification';
import {
    ArrowLeftIcon,
    PlusIcon,
    TrashIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    nutrition: Object,
    student_id: [String, Number],
});

const toast = useToast();
const isLoading = ref(false);
const studentId = ref(null);

const form = ref({
    id: null,
    title: '',
    description: '',
    calories: 2000,
    protein: 150,
    carbs: 200,
    fats: 65,
    thumbnail_path: '',
    meals: [],
});

const mealForm = ref({
    type: 'Breakfast',
    time: '08:00',
    name: '',
    calories: 500,
    ingredients: '',
    instructions: '',
});

const thumbnailFile = ref(null);
const thumbnailPreview = ref(null);

onMounted(async () => {
    // Set student_id from props (passed from backend)
    if (props.student_id) {
        studentId.value = props.student_id;
        console.log('Student ID from props:', studentId.value);
    }

    // Check if editing existing nutrition plan
    if (props.nutrition) {
        form.value = {
            id: props.nutrition.id,
            title: props.nutrition.title,
            description: props.nutrition.description || '',
            calories: props.nutrition.calories,
            protein: props.nutrition.protein,
            carbs: props.nutrition.carbs,
            fats: props.nutrition.fats,
            thumbnail_path: props.nutrition.thumbnail_path || '',
            meals: props.nutrition.meals || [],
        };

        console.log('Editing nutrition plan:', props.nutrition);
        console.log('Meals loaded:', form.value.meals);
    }

    // Check for student_id in URL params (for create mode)
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('student_id')) {
        studentId.value = urlParams.get('student_id');
        console.log('Creating nutrition for student:', studentId.value);
    }
});

const goBack = () => {
    if (studentId.value) {
        router.visit(`/admin/students/${studentId.value}`);
    } else {
        router.visit('/admin');
    }
};

const handleThumbnailChange = (event) => {
    const file = event.target.files[0];
    if (file) {
        thumbnailFile.value = file;
        thumbnailPreview.value = URL.createObjectURL(file);
    }
};

const addMeal = () => {
    if (!mealForm.value.name.trim()) {
        toast.error('Please enter meal name');
        return;
    }

    // Convert ingredients string to array
    const ingredientsArray = mealForm.value.ingredients
        .split('\n')
        .map(i => i.trim())
        .filter(i => i.length > 0);

    const meal = {
        type: mealForm.value.type,
        time: mealForm.value.time,
        name: mealForm.value.name,
        calories: parseInt(mealForm.value.calories),
        ingredients: ingredientsArray,
        instructions: mealForm.value.instructions,
        order_index: form.value.meals.length,
    };

    form.value.meals.push(meal);

    // Reset form
    mealForm.value = {
        type: 'Breakfast',
        time: '08:00',
        name: '',
        calories: 500,
        ingredients: '',
        instructions: '',
    };

    toast.success('Meal added');
};

const removeMeal = (index) => {
    form.value.meals.splice(index, 1);
    // Update order indices
    form.value.meals.forEach((meal, idx) => {
        meal.order_index = idx;
    });
};

const uploadThumbnail = async () => {
    if (!thumbnailFile.value) {
        return null;
    }

    const formData = new FormData();
    formData.append('thumbnail', thumbnailFile.value);

    try {
        const response = await fetch('/api/thumbnails/upload', {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('authToken')}`,
                'Accept': 'application/json',
            },
            body: formData,
        });

        if (response.ok) {
            const data = await response.json();
            return data.thumbnail_path;
        } else {
            throw new Error('Thumbnail upload failed');
        }
    } catch (error) {
        console.error('Thumbnail upload error:', error);
        toast.error('Failed to upload thumbnail');
        return null;
    }
};

const savePlan = async () => {
    // Validation
    if (!form.value.title.trim()) {
        toast.error('Please enter a title');
        return;
    }

    if (form.value.meals.length === 0) {
        toast.error('Please add at least one meal');
        return;
    }

    isLoading.value = true;

    try {
        // Upload thumbnail if new file selected
        if (thumbnailFile.value) {
            const thumbnailPath = await uploadThumbnail();
            if (thumbnailPath) {
                form.value.thumbnail_path = thumbnailPath;
            }
        }

        // Prepare data
        const data = {
            title: form.value.title,
            description: form.value.description,
            calories: parseInt(form.value.calories),
            protein: parseInt(form.value.protein),
            carbs: parseInt(form.value.carbs),
            fats: parseInt(form.value.fats),
            thumbnail_path: form.value.thumbnail_path,
            meals: form.value.meals,
        };

        const url = form.value.id
            ? `/api/nutrition/${form.value.id}`
            : '/api/nutrition';
        const method = form.value.id ? 'PUT' : 'POST';

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
            const result = await response.json();
            const nutritionId = result.id || form.value.id;

            // If creating for a specific student, assign it
            if (studentId.value && !form.value.id) {
                await assignToStudent(nutritionId);
            }

            toast.success(form.value.id ? 'Nutrition plan updated!' : 'Nutrition plan created!');

            // Redirect back
            setTimeout(() => {
                goBack();
            }, 1000);
        } else {
            const error = await response.json();
            toast.error(error.message || 'Failed to save nutrition plan');
        }
    } catch (error) {
        console.error('Save error:', error);
        toast.error('Connection error');
    } finally {
        isLoading.value = false;
    }
};

const assignToStudent = async (nutritionId) => {
    try {
        const response = await fetch(`/api/nutrition/${nutritionId}/assign`, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('authToken')}`,
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                user_ids: [parseInt(studentId.value)],
            }),
        });

        if (!response.ok) {
            console.error('Failed to assign nutrition plan to student');
        }
    } catch (error) {
        console.error('Assignment error:', error);
    }
};

const displayThumbnailUrl = computed(() => {
    if (thumbnailPreview.value) {
        return thumbnailPreview.value;
    }
    if (form.value.thumbnail_path) {
        return `/storage/${form.value.thumbnail_path}`;
    }
    return null;
});
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-purple-600 to-purple-800 p-5">
        <div class="max-w-5xl mx-auto">
            <div class="bg-white rounded-2xl shadow-2xl p-8">
                <!-- Header -->
                <div class="mb-8">
                    <button
                        @click="goBack"
                        class="mb-4 px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition"
                    >
                        <ArrowLeftIcon class="w-4 h-4 inline-block mr-2" />
                        Back
                    </button>

                    <h1 class="text-4xl font-bold text-gray-800">
                        {{ form.id ? 'Edit Nutrition Plan' : 'Create Nutrition Plan' }}
                    </h1>
                    <p v-if="studentId" class="text-gray-600 mt-2">
                        Creating for specific student
                    </p>
                </div>

                <!-- Basic Information -->
                <div class="mb-8">
                    <h2 class="text-2xl font-bold text-gray-800 mb-4">Basic Information</h2>

                    <div class="grid grid-cols-1 gap-6">
                        <!-- Title -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Plan Title *
                            </label>
                            <input
                                v-model="form.title"
                                type="text"
                                placeholder="e.g., Weight Loss Plan, Muscle Gain Diet"
                                class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:border-purple-600 focus:outline-none"
                            >
                        </div>

                        <!-- Description -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Description / Guidance
                            </label>
                            <textarea
                                v-model="form.description"
                                rows="4"
                                placeholder="Add any guidance, instructions, or notes about this nutrition plan..."
                                class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:border-purple-600 focus:outline-none resize-none"
                            ></textarea>
                        </div>

                        <!-- Macros -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Daily Macros
                            </label>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">Calories</label>
                                    <input
                                        v-model.number="form.calories"
                                        type="number"
                                        class="w-full px-3 py-2 border-2 border-gray-300 rounded-lg focus:border-purple-600 focus:outline-none"
                                    >
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">Protein (g)</label>
                                    <input
                                        v-model.number="form.protein"
                                        type="number"
                                        class="w-full px-3 py-2 border-2 border-gray-300 rounded-lg focus:border-purple-600 focus:outline-none"
                                    >
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">Carbs (g)</label>
                                    <input
                                        v-model.number="form.carbs"
                                        type="number"
                                        class="w-full px-3 py-2 border-2 border-gray-300 rounded-lg focus:border-purple-600 focus:outline-none"
                                    >
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">Fats (g)</label>
                                    <input
                                        v-model.number="form.fats"
                                        type="number"
                                        class="w-full px-3 py-2 border-2 border-gray-300 rounded-lg focus:border-purple-600 focus:outline-none"
                                    >
                                </div>
                            </div>
                        </div>

                        <!-- Thumbnail -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Thumbnail Image
                            </label>

                            <!-- Current Thumbnail Preview -->
                            <div v-if="displayThumbnailUrl" class="mb-3 p-4 bg-green-50 border-2 border-green-200 rounded-lg">
                                <div class="flex items-start gap-4">
                                    <img :src="displayThumbnailUrl" alt="Thumbnail preview"
                                         class="w-32 h-32 object-cover rounded-lg shadow">
                                    <div class="flex-1">
                                        <span class="text-green-700 font-semibold">
                                            {{ thumbnailPreview ? '✅ New Thumbnail Selected' : '✅ Current Thumbnail' }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <input
                                type="file"
                                @change="handleThumbnailChange"
                                accept="image/*"
                                class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-purple-600 focus:outline-none"
                            >
                            <p class="text-xs text-gray-500 mt-1">JPG, PNG recommended</p>
                        </div>
                    </div>
                </div>

                <!-- Meals Section -->
                <div class="mb-8">
                    <h2 class="text-2xl font-bold text-gray-800 mb-4">Meals</h2>

                    <!-- Add Meal Form -->
                    <div class="bg-purple-50 rounded-xl p-6 mb-6">
                        <h3 class="text-lg font-bold text-gray-800 mb-4">Add Meal</h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Type</label>
                                <select
                                    v-model="mealForm.type"
                                    class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-purple-600 focus:outline-none"
                                >
                                    <option value="Breakfast">Breakfast</option>
                                    <option value="Lunch">Lunch</option>
                                    <option value="Dinner">Dinner</option>
                                    <option value="Snack">Snack</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Time</label>
                                <input
                                    v-model="mealForm.time"
                                    type="time"
                                    class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-purple-600 focus:outline-none"
                                >
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Meal Name *</label>
                                <input
                                    v-model="mealForm.name"
                                    type="text"
                                    placeholder="e.g., Chicken Salad"
                                    class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-purple-600 focus:outline-none"
                                >
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Calories</label>
                                <input
                                    v-model.number="mealForm.calories"
                                    type="number"
                                    class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-purple-600 focus:outline-none"
                                >
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Ingredients (one per line)
                            </label>
                            <textarea
                                v-model="mealForm.ingredients"
                                rows="4"
                                placeholder="200g chicken breast&#10;100g mixed greens&#10;2 tbsp olive oil&#10;1 tomato"
                                class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-purple-600 focus:outline-none resize-none"
                            ></textarea>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Instructions
                            </label>
                            <textarea
                                v-model="mealForm.instructions"
                                rows="3"
                                placeholder="Preparation instructions..."
                                class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-purple-600 focus:outline-none resize-none"
                            ></textarea>
                        </div>

                        <button
                            @click="addMeal"
                            class="w-full px-6 py-3 bg-purple-600 text-white rounded-lg font-semibold hover:bg-purple-700 transition"
                        >
                            <PlusIcon class="w-5 h-5 inline-block mr-2" />
                            Add Meal to Plan
                        </button>
                    </div>

                    <!-- Meals List -->
                    <div v-if="form.meals.length > 0" class="space-y-4">
                        <h3 class="text-lg font-bold text-gray-800">Meals in this Plan ({{ form.meals.length }})</h3>

                        <div
                            v-for="(meal, index) in form.meals"
                            :key="index"
                            class="bg-white border-2 border-gray-200 rounded-xl p-4"
                        >
                            <div class="flex justify-between items-start mb-2">
                                <div class="flex items-center gap-3">
                                    <span class="px-3 py-1 bg-purple-100 text-purple-700 font-semibold rounded-lg text-sm">
                                        {{ meal.type }}
                                    </span>
                                    <span class="text-sm text-gray-600">{{ meal.time }}</span>
                                </div>
                                <button
                                    @click="removeMeal(index)"
                                    class="text-red-600 hover:text-red-800 transition"
                                >
                                    <TrashIcon class="w-5 h-5" />
                                </button>
                            </div>

                            <h4 class="text-lg font-bold text-gray-800 mb-2">{{ meal.name }}</h4>
                            <p class="text-sm text-gray-600 mb-2">{{ meal.calories }} calories</p>

                            <div v-if="meal.ingredients && meal.ingredients.length > 0" class="mb-2">
                                <p class="text-sm font-semibold text-gray-700 mb-1">Ingredients:</p>
                                <ul class="list-disc list-inside text-sm text-gray-600">
                                    <li v-for="(ingredient, idx) in meal.ingredients" :key="idx">
                                        {{ ingredient }}
                                    </li>
                                </ul>
                            </div>

                            <div v-if="meal.instructions" class="mt-2 p-2 bg-blue-50 rounded text-sm text-gray-700">
                                <span class="font-semibold text-blue-700">Instructions:</span> {{ meal.instructions }}
                            </div>
                        </div>
                    </div>

                    <div v-else class="bg-gray-50 rounded-xl p-8 text-center">
                        <p class="text-gray-600">No meals added yet. Add your first meal above.</p>
                    </div>
                </div>

                <!-- Save Button -->
                <div class="flex gap-4">
                    <button
                        @click="goBack"
                        class="flex-1 px-6 py-4 bg-gray-300 text-gray-700 rounded-xl font-bold text-lg hover:bg-gray-400 transition"
                    >
                        Cancel
                    </button>
                    <button
                        @click="savePlan"
                        :disabled="isLoading"
                        class="flex-1 px-6 py-4 bg-purple-600 text-white rounded-xl font-bold text-lg hover:bg-purple-700 transition disabled:opacity-50"
                    >
                        {{ isLoading ? 'Saving...' : (form.id ? 'Update Plan' : 'Create Plan') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
