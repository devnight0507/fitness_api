<script setup>
import { ref, computed, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import { useToast } from 'vue-toastification';
import {
    CalendarIcon,
    ChevronLeftIcon,
    ChevronRightIcon,
    PlusIcon,
    XMarkIcon,
    FireIcon,
    ClockIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    user: Object,
});

const toast = useToast();
const currentDate = ref(new Date());
const events = ref([]);
const students = ref([]);
const isLoading = ref(true);
const showEventModal = ref(false);
const selectedDate = ref(null);
const selectedEvent = ref(null);
const currentUser = ref(null);
const filterType = ref('all');
const filterStudent = ref('all');
const searchQuery = ref('');

// Form data
const eventForm = ref({
    user_id: '',
    name: '',
    type: 'workout',
    date: '',
    time: '',
    description: '',
});

const eventTypes = [
    { value: 'workout', label: 'Workout', color: 'purple' },
    { value: 'nutrition', label: 'Nutrition', color: 'green' },
    { value: 'rest', label: 'Rest Day', color: 'blue' },
    { value: 'assessment', label: 'Assessment', color: 'orange' },
];

onMounted(async () => {
    // Get current authenticated user
    await loadCurrentUser();
    await loadStudents();
    await loadEvents();
    isLoading.value = false;
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
        const response = await fetch('/api/users?role=student', {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('authToken')}`,
                'Accept': 'application/json',
            },
        });

        if (response.ok) {
            const data = await response.json();
            students.value = Array.isArray(data) ? data : (data.data || []);
        }
    } catch (error) {
        console.error('Failed to load students:', error);
    }
};

const loadEvents = async () => {
    try {
        // Load all events without year/month filter to see all events
        const response = await fetch('/api/calendar', {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('authToken')}`,
                'Accept': 'application/json',
            },
        });

        if (response.ok) {
            const data = await response.json();
            events.value = Array.isArray(data) ? data : (data.data || []);
            console.log('Loaded events:', events.value); // Debug log
        }
    } catch (error) {
        console.error('Failed to load events:', error);
        toast.error('Failed to load calendar events');
    }
};

const createEvent = async () => {
    if (!eventForm.value.user_id || !eventForm.value.name || !eventForm.value.date) {
        toast.error('Please fill in all required fields');
        return;
    }

    try {
        const response = await fetch('/api/calendar', {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('authToken')}`,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(eventForm.value),
        });

        if (response.ok) {
            toast.success('Event created successfully');
            await loadEvents();
            closeEventModal();
        } else {
            const error = await response.json();
            toast.error(error.message || 'Failed to create event');
        }
    } catch (error) {
        console.error('Failed to create event:', error);
        toast.error('Failed to create event');
    }
};

const deleteEvent = async (eventId) => {
    if (!confirm('Are you sure you want to delete this event?')) {
        return;
    }

    try {
        const response = await fetch(`/api/calendar/${eventId}`, {
            method: 'DELETE',
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('authToken')}`,
                'Accept': 'application/json',
            },
        });

        if (response.ok) {
            toast.success('Event deleted successfully');
            await loadEvents();
            if (selectedEvent.value && selectedEvent.value.id === eventId) {
                selectedEvent.value = null;
            }
        } else {
            toast.error('Failed to delete event');
        }
    } catch (error) {
        console.error('Failed to delete event:', error);
        toast.error('Failed to delete event');
    }
};

const openEventModal = (date = null) => {
    if (date) {
        selectedDate.value = date;
        eventForm.value.date = formatDateForInput(date);
    } else {
        selectedDate.value = null;
        eventForm.value.date = '';
    }

    eventForm.value.user_id = '';
    eventForm.value.name = '';
    eventForm.value.type = 'workout';
    eventForm.value.time = '';
    eventForm.value.description = '';

    showEventModal.value = true;
};

const closeEventModal = () => {
    showEventModal.value = false;
    selectedDate.value = null;
};

const previousMonth = async () => {
    currentDate.value = new Date(currentDate.value.getFullYear(), currentDate.value.getMonth() - 1, 1);
    await loadEvents();
};

const nextMonth = async () => {
    currentDate.value = new Date(currentDate.value.getFullYear(), currentDate.value.getMonth() + 1, 1);
    await loadEvents();
};

const getMonthName = computed(() => {
    return currentDate.value.toLocaleString('en-US', { month: 'long', year: 'numeric' });
});

const getDaysInMonth = computed(() => {
    const year = currentDate.value.getFullYear();
    const month = currentDate.value.getMonth();
    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);
    const daysInMonth = lastDay.getDate();
    const startingDayOfWeek = firstDay.getDay();

    const days = [];

    // Add empty cells for days before the month starts
    for (let i = 0; i < startingDayOfWeek; i++) {
        days.push(null);
    }

    // Add actual days of the month
    for (let day = 1; day <= daysInMonth; day++) {
        days.push(new Date(year, month, day));
    }

    return days;
});

const getEventsForDate = (date) => {
    if (!date) return [];

    const dateString = formatDateForComparison(date);
    return events.value.filter(event => {
        const eventDate = formatDateForComparison(new Date(event.date));
        return eventDate === dateString;
    });
};

const formatDateForComparison = (date) => {
    return date.toISOString().split('T')[0];
};

const formatDateForInput = (date) => {
    return date.toISOString().split('T')[0];
};

const isToday = (date) => {
    if (!date) return false;
    const today = new Date();
    return date.getDate() === today.getDate() &&
           date.getMonth() === today.getMonth() &&
           date.getFullYear() === today.getFullYear();
};

const getEventTypeColor = (type) => {
    const eventType = eventTypes.find(t => t.value === type);
    return eventType ? eventType.color : 'gray';
};

const getEventTypeLabel = (type) => {
    const eventType = eventTypes.find(t => t.value === type);
    return eventType ? eventType.label : type;
};

const logout = () => {
    localStorage.removeItem('authToken');
    window.location.href = '/admin/login';
};

const goToAdmin = () => {
    router.visit('/admin');
};

const getStudentName = (userId) => {
    const student = students.value.find(s => s.id === userId);
    return student ? student.name : 'Unknown';
};
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-purple-600 to-purple-800 p-3 sm:p-5">
        <div class="max-w-7xl mx-auto">
            <!-- Header -->
            <div class="bg-white rounded-2xl shadow-2xl p-4 sm:p-6 md:p-8 mb-6 sm:mb-8">
                <div class="flex flex-col gap-4 sm:flex-row sm:justify-between sm:items-center">
                    <div class="flex items-center gap-3 sm:gap-4">
                        <CalendarIcon class="w-8 h-8 sm:w-10 sm:h-10 text-purple-600 flex-shrink-0" />
                        <div>
                            <h1 class="text-2xl sm:text-3xl md:text-4xl font-bold text-gray-800">Calendar</h1>
                            <p class="text-sm sm:text-base text-gray-600 hidden sm:block">Manage student schedules and events</p>
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

            <!-- Calendar Container -->
            <div class="bg-white rounded-2xl shadow-2xl p-4 sm:p-6">
                <!-- Calendar Header -->
                <div class="flex items-center justify-between mb-4 sm:mb-6">
                    <button
                        @click="previousMonth"
                        class="p-2 hover:bg-gray-100 rounded-lg transition"
                    >
                        <ChevronLeftIcon class="w-5 h-5 sm:w-6 sm:h-6 text-gray-600" />
                    </button>

                    <h2 class="text-lg sm:text-xl md:text-2xl font-bold text-gray-800">
                        {{ getMonthName }}
                    </h2>

                    <button
                        @click="nextMonth"
                        class="p-2 hover:bg-gray-100 rounded-lg transition"
                    >
                        <ChevronRightIcon class="w-5 h-5 sm:w-6 sm:h-6 text-gray-600" />
                    </button>
                </div>

                <!-- Add Event Button -->
                <div class="mb-4 sm:mb-6">
                    <button
                        @click="openEventModal()"
                        class="w-full sm:w-auto px-4 sm:px-6 py-2 sm:py-3 bg-purple-600 text-white rounded-lg text-sm sm:text-base font-semibold hover:bg-purple-700 transition flex items-center justify-center gap-2"
                    >
                        <PlusIcon class="w-4 h-4 sm:w-5 sm:h-5" />
                        <span>Add Event</span>
                    </button>
                </div>

                <!-- Loading State -->
                <div v-if="isLoading" class="text-center py-12 text-gray-600">
                    Loading calendar...
                </div>

                <!-- Calendar Grid -->
                <div v-else>
                    <!-- Weekday Headers -->
                    <div class="grid grid-cols-7 gap-1 sm:gap-2 mb-2">
                        <div v-for="day in ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']" :key="day"
                             class="text-center text-xs sm:text-sm font-semibold text-gray-600 py-2">
                            {{ day }}
                        </div>
                    </div>

                    <!-- Calendar Days -->
                    <div class="grid grid-cols-7 gap-1 sm:gap-2">
                        <div
                            v-for="(date, index) in getDaysInMonth"
                            :key="index"
                            class="min-h-[70px] sm:min-h-[100px] border-2 rounded-lg p-1 sm:p-2 cursor-pointer transition"
                            :class="{
                                'bg-gray-50 border-gray-200': !date,
                                'bg-white border-gray-200 hover:border-purple-400': date && !isToday(date),
                                'bg-purple-50 border-purple-500': date && isToday(date)
                            }"
                            @click="date && openEventModal(date)"
                        >
                            <div v-if="date" class="flex flex-col h-full">
                                <div class="text-xs sm:text-sm font-semibold text-gray-700 mb-1"
                                     :class="{ 'text-purple-600': isToday(date) }">
                                    {{ date.getDate() }}
                                </div>
                                <div class="flex-1 space-y-0.5 overflow-hidden">
                                    <div
                                        v-for="event in getEventsForDate(date).slice(0, 2)"
                                        :key="event.id"
                                        class="text-[10px] sm:text-xs px-1 py-0.5 rounded truncate"
                                        :class="{
                                            'bg-purple-200 text-purple-800': getEventTypeColor(event.type) === 'purple',
                                            'bg-green-200 text-green-800': getEventTypeColor(event.type) === 'green',
                                            'bg-blue-200 text-blue-800': getEventTypeColor(event.type) === 'blue',
                                            'bg-orange-200 text-orange-800': getEventTypeColor(event.type) === 'orange'
                                        }"
                                        @click.stop="selectedEvent = event"
                                    >
                                        {{ event.name }}
                                    </div>
                                    <div v-if="getEventsForDate(date).length > 2"
                                         class="text-[10px] text-gray-500 px-1">
                                        +{{ getEventsForDate(date).length - 2 }} more
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Upcoming Events List -->
            <div class="bg-white rounded-2xl shadow-2xl p-4 sm:p-6 mt-6 sm:mt-8">
                <h2 class="text-xl sm:text-2xl font-bold text-gray-800 mb-4 sm:mb-6">Upcoming Events</h2>

                <div v-if="events.length === 0" class="text-center py-8 text-gray-600">
                    <CalendarIcon class="w-16 h-16 mx-auto mb-4 text-gray-300" />
                    <p class="text-base sm:text-lg">No events scheduled yet</p>
                    <p class="text-sm text-gray-500 mt-2">Click "Add Event" to create your first event</p>
                </div>

                <div v-else class="space-y-3">
                    <div
                        v-for="event in events.slice(0, 10)"
                        :key="event.id"
                        class="flex items-center gap-3 sm:gap-4 p-3 sm:p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition cursor-pointer"
                        @click="selectedEvent = event"
                    >
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 sm:w-16 sm:h-16 rounded-lg flex flex-col items-center justify-center text-white font-bold"
                                 :class="{
                                     'bg-purple-500': getEventTypeColor(event.type) === 'purple',
                                     'bg-green-500': getEventTypeColor(event.type) === 'green',
                                     'bg-blue-500': getEventTypeColor(event.type) === 'blue',
                                     'bg-orange-500': getEventTypeColor(event.type) === 'orange'
                                 }">
                                <div class="text-xs sm:text-sm uppercase">{{ new Date(event.date).toLocaleDateString('en-US', { month: 'short' }) }}</div>
                                <div class="text-lg sm:text-2xl">{{ new Date(event.date).getDate() }}</div>
                            </div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="px-2 py-0.5 rounded text-xs font-semibold"
                                      :class="{
                                          'bg-purple-100 text-purple-800': getEventTypeColor(event.type) === 'purple',
                                          'bg-green-100 text-green-800': getEventTypeColor(event.type) === 'green',
                                          'bg-blue-100 text-blue-800': getEventTypeColor(event.type) === 'blue',
                                          'bg-orange-100 text-orange-800': getEventTypeColor(event.type) === 'orange'
                                      }">
                                    {{ getEventTypeLabel(event.type) }}
                                </span>
                            </div>
                            <h3 class="font-semibold text-sm sm:text-base text-gray-800 truncate">{{ event.name }}</h3>
                            <p class="text-xs sm:text-sm text-gray-600 truncate">{{ getStudentName(event.user_id) }}</p>
                            <p v-if="event.time" class="text-xs text-gray-500 mt-1">
                                <ClockIcon class="w-3 h-3 inline-block mr-1" />
                                {{ event.time }}
                            </p>
                        </div>
                    </div>
                </div>

                <div v-if="events.length > 10" class="mt-4 text-center text-sm text-gray-600">
                    Showing 10 of {{ events.length }} events
                </div>
            </div>

            <!-- Event Details Modal -->
            <div v-if="selectedEvent" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50" @click="selectedEvent = null">
                <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6" @click.stop>
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-xl font-bold text-gray-800">Event Details</h3>
                        <button @click="selectedEvent = null" class="p-1 hover:bg-gray-100 rounded-lg transition">
                            <XMarkIcon class="w-5 h-5 text-gray-600" />
                        </button>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <span class="inline-block px-3 py-1 rounded-full text-sm font-semibold"
                                  :class="{
                                      'bg-purple-200 text-purple-800': getEventTypeColor(selectedEvent.type) === 'purple',
                                      'bg-green-200 text-green-800': getEventTypeColor(selectedEvent.type) === 'green',
                                      'bg-blue-200 text-blue-800': getEventTypeColor(selectedEvent.type) === 'blue',
                                      'bg-orange-200 text-orange-800': getEventTypeColor(selectedEvent.type) === 'orange'
                                  }">
                                {{ getEventTypeLabel(selectedEvent.type) }}
                            </span>
                        </div>

                        <div>
                            <h4 class="text-2xl font-bold text-gray-800 mb-2">{{ selectedEvent.name }}</h4>
                            <p class="text-gray-600 mb-2">For: {{ getStudentName(selectedEvent.user_id) }}</p>
                            <p class="text-gray-600">{{ new Date(selectedEvent.date).toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }) }}</p>
                            <p v-if="selectedEvent.time" class="text-gray-600 flex items-center gap-1 mt-1">
                                <ClockIcon class="w-4 h-4" />
                                {{ selectedEvent.time }}
                            </p>
                        </div>

                        <div v-if="selectedEvent.description" class="pt-4 border-t border-gray-200">
                            <p class="text-gray-700">{{ selectedEvent.description }}</p>
                        </div>

                        <button
                            @click="deleteEvent(selectedEvent.id)"
                            class="w-full px-4 py-3 bg-red-600 text-white rounded-lg font-semibold hover:bg-red-700 transition"
                        >
                            Delete Event
                        </button>
                    </div>
                </div>
            </div>

            <!-- Create Event Modal -->
            <div v-if="showEventModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50" @click="closeEventModal">
                <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 max-h-[90vh] overflow-y-auto" @click.stop>
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-xl font-bold text-gray-800">Create Event</h3>
                        <button @click="closeEventModal" class="p-1 hover:bg-gray-100 rounded-lg transition">
                            <XMarkIcon class="w-5 h-5 text-gray-600" />
                        </button>
                    </div>

                    <form @submit.prevent="createEvent" class="space-y-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Student *</label>
                            <select
                                v-model="eventForm.user_id"
                                required
                                class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:border-purple-600 focus:outline-none"
                            >
                                <option value="">Select a student</option>
                                <option v-for="student in students" :key="student.id" :value="student.id">
                                    {{ student.name }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Event Type *</label>
                            <select
                                v-model="eventForm.type"
                                required
                                class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:border-purple-600 focus:outline-none"
                            >
                                <option v-for="type in eventTypes" :key="type.value" :value="type.value">
                                    {{ type.label }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Event Name *</label>
                            <input
                                v-model="eventForm.name"
                                type="text"
                                required
                                placeholder="e.g., Upper Body Workout"
                                class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:border-purple-600 focus:outline-none"
                            >
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Date *</label>
                            <input
                                v-model="eventForm.date"
                                type="date"
                                required
                                class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:border-purple-600 focus:outline-none"
                            >
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Time</label>
                            <input
                                v-model="eventForm.time"
                                type="time"
                                class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:border-purple-600 focus:outline-none"
                            >
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Description</label>
                            <textarea
                                v-model="eventForm.description"
                                rows="3"
                                placeholder="Add notes or instructions..."
                                class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:border-purple-600 focus:outline-none resize-none"
                            ></textarea>
                        </div>

                        <div class="flex gap-3 pt-2">
                            <button
                                type="button"
                                @click="closeEventModal"
                                class="flex-1 px-4 py-3 bg-gray-200 text-gray-700 rounded-lg font-semibold hover:bg-gray-300 transition"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                class="flex-1 px-4 py-3 bg-purple-600 text-white rounded-lg font-semibold hover:bg-purple-700 transition"
                            >
                                Create Event
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>
