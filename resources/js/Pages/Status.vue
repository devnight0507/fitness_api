<script setup>
defineProps({
    milestones: Array,
});
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-purple-600 to-purple-800 p-5">
        <div class="max-w-6xl mx-auto">
            <!-- Header -->
            <div class="bg-white rounded-2xl shadow-2xl p-10 mb-8 text-center">
                <h1 class="text-5xl font-bold text-gray-800 mb-3">Fitness App - Project Status</h1>
                <p class="text-xl text-gray-600">Current Development Progress</p>
            </div>

            <!-- Video Section -->
            <div class="bg-white rounded-2xl shadow-2xl p-8 mb-8">
                <h2 class="text-3xl font-bold text-gray-800 mb-6">Milestone 3 - Demo Video</h2>
                <div class="relative w-full pb-[56.25%] bg-black rounded-xl overflow-hidden">
                    <video
                        controls
                        class="absolute top-0 left-0 w-full h-full"
                    >
                        <source src="/assets/milestone_3.mp4" type="video/mp4">
                        Your browser does not support the video tag.
                    </video>
                </div>
            </div>

            <!-- Status Information -->
            <div class="bg-white rounded-2xl shadow-2xl p-8">
                <h3 class="text-2xl font-bold text-gray-800 mb-4">Development Status</h3>
                <span class="inline-block px-6 py-2 bg-green-500 text-white rounded-full font-semibold text-lg mb-6">
                    95% Complete
                </span>

                <p class="text-gray-700 text-lg mb-6">
                    The Fitness App development is nearly complete. Below are the completed milestones:
                </p>

                <ul class="space-y-3 mb-6">
                    <li v-for="(milestone, index) in milestones" :key="index" class="flex items-start pb-3 border-b border-gray-200 last:border-0">
                        <span class="text-green-500 font-bold mr-3 text-xl">✓</span>
                        <span class="text-gray-700">{{ milestone }}</span>
                    </li>
                </ul>

                <p class="text-gray-700 font-semibold">
                    <strong class="text-gray-900">Remaining Work:</strong> Payment integration (Stripe) for subscription management.
                </p>

                <a href="/admin" class="inline-block mt-6 text-purple-600 font-semibold hover:underline text-lg">
                    ← Back to Admin Panel
                </a>
            </div>
        </div>
    </div>
</template>
