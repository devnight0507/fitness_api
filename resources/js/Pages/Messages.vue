<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue';
import { router } from '@inertiajs/vue3';
import { useToast } from 'vue-toastification';
import {
    ChatBubbleLeftRightIcon,
    PaperAirplaneIcon,
    MagnifyingGlassIcon,
    ArrowLeftIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    user: Object,
});

const toast = useToast();
const students = ref([]);
const selectedStudent = ref(null);
const messages = ref([]);
const messageText = ref('');
const searchQuery = ref('');
const isLoading = ref(true);
const isSending = ref(false);
const messagesContainer = ref(null);
const pollingInterval = ref(null);
const currentUser = ref(null);

// Load students on mount
onMounted(async () => {
    // Get current authenticated user
    await loadCurrentUser();
    await loadStudents();
    isLoading.value = false;

    // Start polling for new messages every 5 seconds
    pollingInterval.value = setInterval(() => {
        if (selectedStudent.value) {
            loadMessages(selectedStudent.value.id, false);
        }
    }, 5000);
});

onUnmounted(() => {
    if (pollingInterval.value) {
        clearInterval(pollingInterval.value);
    }
});

const loadCurrentUser = async () => {
    try {
        const response = await fetch('/api/user', {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('authToken')}`,
                'Accept': 'application/json',
            },
        });

        if (response.ok) {
            currentUser.value = await response.json();
        } else if (response.status === 401) {
            window.location.href = '/admin/login';
        }
    } catch (error) {
        console.error('Failed to load current user:', error);
    }
};

const loadStudents = async () => {
    try {
        const response = await fetch('/api/users/chat', {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('authToken')}`,
                'Accept': 'application/json',
            },
        });

        if (response.ok) {
            const data = await response.json();
            // Handle both array and object with data property
            students.value = Array.isArray(data) ? data : (data.data || []);
        } else if (response.status === 401) {
            window.location.href = '/admin/login';
        }
    } catch (error) {
        console.error('Failed to load students:', error);
        toast.error('Failed to load students');
    }
};

const loadMessages = async (studentId, showLoading = true) => {
    if (showLoading) {
        isLoading.value = true;
    }

    try {
        const response = await fetch(`/api/messages?user_id=${studentId}`, {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('authToken')}`,
                'Accept': 'application/json',
            },
        });

        if (response.ok) {
            const data = await response.json();
            // Handle both array and object with data property
            let messagesData = Array.isArray(data) ? data : (data.data || []);

            // Sort messages by created_at in ascending order (oldest first)
            messagesData.sort((a, b) => new Date(a.created_at) - new Date(b.created_at));

            messages.value = messagesData;

            // Mark unread messages as read if we have current user
            if (currentUser.value && Array.isArray(messages.value)) {
                const unreadMessages = messages.value.filter(m =>
                    !m.is_read && m.receiver_id === currentUser.value.id
                );

                for (const message of unreadMessages) {
                    await markAsRead(message.id);
                }
            }

            // Update unread count for this student
            const student = students.value.find(s => s.id === studentId);
            if (student) {
                student.unread_count = 0;
            }

            // Scroll to bottom
            await nextTick();
            scrollToBottom();
        }
    } catch (error) {
        console.error('Failed to load messages:', error);
    } finally {
        if (showLoading) {
            isLoading.value = false;
        }
    }
};

const markAsRead = async (messageId) => {
    try {
        await fetch(`/api/messages/${messageId}/read`, {
            method: 'PATCH',
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('authToken')}`,
                'Accept': 'application/json',
            },
        });
    } catch (error) {
        console.error('Failed to mark message as read:', error);
    }
};

const sendMessage = async () => {
    if (!messageText.value.trim() || !selectedStudent.value || isSending.value) {
        return;
    }

    isSending.value = true;

    try {
        const response = await fetch('/api/messages', {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('authToken')}`,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                receiver_id: selectedStudent.value.id,
                message: messageText.value.trim(),
            }),
        });

        if (response.ok) {
            const data = await response.json();
            const newMessage = data.data || data;
            messages.value.push(newMessage);
            messageText.value = '';

            await nextTick();
            scrollToBottom();
        } else {
            toast.error('Failed to send message');
        }
    } catch (error) {
        console.error('Failed to send message:', error);
        toast.error('Failed to send message');
    } finally {
        isSending.value = false;
    }
};

const selectStudent = async (student) => {
    selectedStudent.value = student;
    await loadMessages(student.id);
};

const scrollToBottom = () => {
    if (messagesContainer.value) {
        messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
    }
};

const filteredStudents = computed(() => {
    if (!searchQuery.value) {
        return students.value;
    }

    const query = searchQuery.value.toLowerCase();
    return students.value.filter(student =>
        student.name.toLowerCase().includes(query) ||
        student.email.toLowerCase().includes(query)
    );
});

const getAvatarUrl = (student) => {
    if (!student.avatar_path) {
        return null;
    }
    if (student.avatar_path.startsWith('http')) {
        return student.avatar_path;
    }
    return `/storage/${student.avatar_path}`;
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

const formatTime = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
};

const formatDate = (dateString) => {
    const date = new Date(dateString);
    const today = new Date();
    const yesterday = new Date(today);
    yesterday.setDate(yesterday.getDate() - 1);

    if (date.toDateString() === today.toDateString()) {
        return 'Today';
    } else if (date.toDateString() === yesterday.toDateString()) {
        return 'Yesterday';
    } else {
        return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    }
};

const logout = () => {
    localStorage.removeItem('authToken');
    window.location.href = '/admin/login';
};

const goToAdmin = () => {
    router.visit('/admin');
};
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-purple-600 to-purple-800 p-3 sm:p-5">
        <div class="max-w-7xl mx-auto">
            <!-- Header -->
            <div class="bg-white rounded-2xl shadow-2xl p-4 sm:p-6 md:p-8 mb-6 sm:mb-8">
                <div class="flex flex-col gap-4 sm:flex-row sm:justify-between sm:items-center">
                    <div class="flex items-center gap-3 sm:gap-4">
                        <ChatBubbleLeftRightIcon class="w-8 h-8 sm:w-10 sm:h-10 text-purple-600 flex-shrink-0" />
                        <div>
                            <h1 class="text-2xl sm:text-3xl md:text-4xl font-bold text-gray-800">Messages</h1>
                            <p class="text-sm sm:text-base text-gray-600 hidden sm:block">Chat with your students</p>
                        </div>
                    </div>
                    <div class="flex gap-2 sm:gap-3">
                        <button
                            @click="goToAdmin"
                            class="flex-1 sm:flex-none px-3 sm:px-6 py-2 sm:py-3 bg-gray-600 text-white rounded-lg text-sm sm:text-base font-semibold hover:bg-gray-700 transition"
                        >
                            <span class="hidden sm:inline">← Back to Dashboard</span>
                            <span class="sm:hidden">← Back</span>
                        </button>
                        <button
                            @click="logout"
                            class="flex-1 sm:flex-none px-3 sm:px-6 py-2 sm:py-3 bg-red-600 text-white rounded-lg text-sm sm:text-base font-semibold hover:bg-red-700 transition"
                        >
                            Logout
                        </button>
                    </div>
                </div>
            </div>

            <!-- Messages Container -->
            <div class="bg-white rounded-2xl shadow-2xl overflow-hidden" style="height: calc(100vh - 250px); min-height: 500px;">
                <div class="flex h-full">
                    <!-- Students Sidebar -->
                    <div class="w-full sm:w-80 border-r border-gray-200 flex flex-col" :class="{ 'hidden sm:flex': selectedStudent }">
                        <!-- Search -->
                        <div class="p-3 sm:p-4 border-b border-gray-200">
                            <div class="relative">
                                <input
                                    v-model="searchQuery"
                                    type="text"
                                    placeholder="Search students..."
                                    class="w-full px-3 sm:px-4 py-2 text-sm sm:text-base border-2 border-gray-300 rounded-lg focus:border-purple-600 focus:outline-none pr-10"
                                >
                                <MagnifyingGlassIcon class="absolute right-3 top-1/2 transform -translate-y-1/2 w-4 h-4 sm:w-5 sm:h-5 text-gray-400" />
                            </div>
                        </div>

                        <!-- Students List -->
                        <div class="flex-1 overflow-y-auto">
                            <div v-if="isLoading" class="p-8 text-center text-gray-600">
                                Loading students...
                            </div>
                            <div v-else-if="filteredStudents.length === 0" class="p-8 text-center text-gray-600">
                                No students found
                            </div>
                            <div v-else>
                                <div
                                    v-for="student in filteredStudents"
                                    :key="student.id"
                                    @click="selectStudent(student)"
                                    class="p-3 sm:p-4 border-b border-gray-100 hover:bg-gray-50 cursor-pointer transition"
                                    :class="{ 'bg-purple-50': selectedStudent && selectedStudent.id === student.id }"
                                >
                                    <div class="flex items-center gap-3">
                                        <div class="relative flex-shrink-0">
                                            <div v-if="!getAvatarUrl(student)" class="w-10 h-10 sm:w-12 sm:h-12 rounded-full bg-teal-500 flex items-center justify-center">
                                                <span class="text-white text-sm sm:text-base font-bold">{{ getInitial(student.name) }}</span>
                                            </div>
                                            <img
                                                v-else
                                                :src="getAvatarUrl(student)"
                                                :alt="student.name"
                                                class="w-10 h-10 sm:w-12 sm:h-12 rounded-full object-cover"
                                            >
                                            <div v-if="student.unread_count > 0" class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 rounded-full flex items-center justify-center">
                                                <span class="text-white text-xs font-bold">{{ student.unread_count }}</span>
                                            </div>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="font-semibold text-sm sm:text-base text-gray-800 truncate">{{ student.name }}</p>
                                            <p class="text-xs sm:text-sm text-gray-600 truncate">{{ student.email }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Messages Area -->
                    <div class="flex-1 flex flex-col" :class="{ 'hidden sm:flex': !selectedStudent }">
                        <div v-if="!selectedStudent" class="flex-1 flex items-center justify-center text-gray-500">
                            <div class="text-center">
                                <ChatBubbleLeftRightIcon class="w-16 h-16 sm:w-20 sm:h-20 mx-auto mb-4 text-gray-300" />
                                <p class="text-base sm:text-lg">Select a student to start chatting</p>
                            </div>
                        </div>

                        <template v-else>
                            <!-- Chat Header -->
                            <div class="p-3 sm:p-4 border-b border-gray-200 bg-gray-50">
                                <div class="flex items-center gap-3">
                                    <button
                                        @click="selectedStudent = null"
                                        class="sm:hidden p-2 hover:bg-gray-200 rounded-lg transition"
                                    >
                                        <ArrowLeftIcon class="w-5 h-5 text-gray-600" />
                                    </button>
                                    <div v-if="!getAvatarUrl(selectedStudent)" class="w-10 h-10 rounded-full bg-teal-500 flex items-center justify-center flex-shrink-0">
                                        <span class="text-white text-base font-bold">{{ getInitial(selectedStudent.name) }}</span>
                                    </div>
                                    <img
                                        v-else
                                        :src="getAvatarUrl(selectedStudent)"
                                        :alt="selectedStudent.name"
                                        class="w-10 h-10 rounded-full object-cover flex-shrink-0"
                                    >
                                    <div class="flex-1 min-w-0">
                                        <p class="font-semibold text-sm sm:text-base text-gray-800 truncate">{{ selectedStudent.name }}</p>
                                        <p class="text-xs sm:text-sm text-gray-600 truncate">{{ selectedStudent.email }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Messages -->
                            <div ref="messagesContainer" class="flex-1 overflow-y-auto p-3 sm:p-4 space-y-3 sm:space-y-4 bg-gray-50">
                                <div v-if="messages.length === 0" class="text-center text-gray-500 mt-8">
                                    No messages yet. Start the conversation!
                                </div>

                                <div v-for="message in messages" :key="message.id">
                                    <div
                                        class="flex"
                                        :class="currentUser && message.sender_id === currentUser.id ? 'justify-end' : 'justify-start'"
                                    >
                                        <div
                                            class="max-w-xs sm:max-w-md lg:max-w-lg px-3 sm:px-4 py-2 sm:py-3 rounded-2xl text-sm sm:text-base"
                                            :class="currentUser && message.sender_id === currentUser.id
                                                ? 'bg-purple-600 text-white rounded-br-none'
                                                : 'bg-white text-gray-800 rounded-bl-none shadow-sm'"
                                        >
                                            <p class="break-words">{{ message.message }}</p>
                                            <p class="text-xs mt-1 opacity-70">
                                                {{ formatTime(message.created_at) }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Message Input -->
                            <div class="p-3 sm:p-4 border-t border-gray-200 bg-white">
                                <form @submit.prevent="sendMessage" class="flex gap-2 sm:gap-3">
                                    <input
                                        v-model="messageText"
                                        type="text"
                                        placeholder="Type a message..."
                                        class="flex-1 px-3 sm:px-4 py-2 sm:py-3 text-sm sm:text-base border-2 border-gray-300 rounded-lg focus:border-purple-600 focus:outline-none"
                                        :disabled="isSending"
                                    >
                                    <button
                                        type="submit"
                                        :disabled="!messageText.trim() || isSending"
                                        class="px-4 sm:px-6 py-2 sm:py-3 bg-purple-600 text-white rounded-lg font-semibold hover:bg-purple-700 transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
                                    >
                                        <PaperAirplaneIcon class="w-4 h-4 sm:w-5 sm:h-5" />
                                        <span class="hidden sm:inline">Send</span>
                                    </button>
                                </form>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
