<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';

const form = ref({
    email: '',
    password: '',
});

const errors = ref({});
const isLoading = ref(false);

const submit = async () => {
    errors.value = {};
    isLoading.value = true;

    try {
        const response = await fetch('/api/login', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify(form.value),
        });

        const data = await response.json();

        if (response.ok) {
            // Store token in localStorage
            localStorage.setItem('authToken', data.token);

            // Check if user is admin
            if (data.user.role === 'admin') {
                // Redirect to admin panel
                window.location.href = '/admin';
            } else {
                errors.value = { general: 'Only admins can access this panel' };
                isLoading.value = false;
            }
        } else {
            errors.value = { general: data.message || 'Invalid credentials' };
            isLoading.value = false;
        }
    } catch (error) {
        errors.value = { general: 'Connection error. Is the backend running?' };
        isLoading.value = false;
    }
};
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-purple-600 to-purple-800 flex items-center justify-center p-4 sm:p-5">
        <div class="bg-white rounded-2xl shadow-2xl p-6 sm:p-8 md:p-10 w-full max-w-md">
            <h2 class="text-2xl sm:text-3xl font-bold text-gray-800 mb-4 sm:mb-6 text-center">Admin Login</h2>

            <!-- Error Alert -->
            <div v-if="errors.general" class="mb-4 sm:mb-6 p-3 sm:p-4 bg-red-100 text-red-800 rounded-lg text-sm sm:text-base">
                {{ errors.general }}
            </div>

            <form @submit.prevent="submit" class="space-y-4 sm:space-y-6">
                <!-- Email -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Email</label>
                    <input
                        v-model="form.email"
                        type="email"
                        required
                        placeholder="admin@fitness.com"
                        class="w-full px-3 sm:px-4 py-2 sm:py-3 text-sm sm:text-base border-2 border-gray-300 rounded-lg focus:border-purple-600 focus:outline-none transition"
                        :disabled="isLoading"
                    >
                </div>

                <!-- Password -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Password</label>
                    <input
                        v-model="form.password"
                        type="password"
                        required
                        placeholder="••••••••"
                        class="w-full px-3 sm:px-4 py-2 sm:py-3 text-sm sm:text-base border-2 border-gray-300 rounded-lg focus:border-purple-600 focus:outline-none transition"
                        :disabled="isLoading"
                    >
                </div>

                <!-- Submit Button -->
                <button
                    type="submit"
                    :disabled="isLoading"
                    class="w-full px-4 sm:px-6 py-2 sm:py-3 text-sm sm:text-base bg-purple-600 text-white rounded-lg font-semibold hover:bg-purple-700 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <span v-if="!isLoading">Login</span>
                    <span v-else>Logging in...</span>
                </button>
            </form>

            <!-- Test Credentials -->
            <div class="mt-4 sm:mt-6 p-3 sm:p-4 bg-blue-50 border border-blue-200 rounded-lg">
                <p class="text-xs sm:text-sm font-semibold text-blue-800 mb-1 sm:mb-2">Test Credentials:</p>
                <p class="text-xs text-blue-700">
                    <strong>Email:</strong> admin@fitness.com<br>
                    <strong>Password:</strong> password123
                </p>
            </div>
        </div>
    </div>
</template>
