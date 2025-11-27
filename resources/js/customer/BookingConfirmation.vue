<template>
    <div class="min-h-screen bg-gray-50">
        <!-- Header -->
        <header class="bg-white shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                <div class="flex items-center justify-between">
                    <a href="/" class="text-blue-600 hover:text-blue-800">← Back to Search</a>
                    <a href="/login" class="text-blue-600 hover:text-blue-800">Admin Login</a>
                </div>
            </div>
        </header>

        <div v-if="loading" class="text-center py-12">
            <p class="text-gray-500">Loading booking details...</p>
        </div>

        <div v-else-if="booking" class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Success Message -->
            <div class="bg-green-50 border border-green-200 rounded-lg p-6 mb-6">
                <div class="flex items-center">
                    <svg class="h-8 w-8 text-green-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div>
                        <h2 class="text-2xl font-bold text-green-900">Booking Request Submitted!</h2>
                        <p class="text-green-700 mt-1">We'll confirm your booking shortly.</p>
                    </div>
                </div>
            </div>

            <!-- Booking Details -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h3 class="text-xl font-semibold text-gray-900 mb-4">Booking Details</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-500">Booking ID</p>
                        <p class="text-lg font-semibold text-gray-900">#{{ booking.id }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Status</p>
                        <span class="inline-block px-3 py-1 rounded-full text-sm font-medium" :class="statusClass">
                            {{ booking.status }}
                        </span>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Property</p>
                        <p class="text-lg font-semibold text-gray-900">{{ booking.property?.name }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Room Type</p>
                        <p class="text-lg font-semibold text-gray-900">{{ booking.room_type?.name }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Check-in</p>
                        <p class="text-lg font-semibold text-gray-900">{{ formatDate(booking.check_in) }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Check-out</p>
                        <p class="text-lg font-semibold text-gray-900">{{ formatDate(booking.check_out) }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Nights</p>
                        <p class="text-lg font-semibold text-gray-900">{{ booking.nights || calculateNights() }} night(s)</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Guests</p>
                        <p class="text-lg font-semibold text-gray-900">
                            {{ booking.adult_count }} Adult{{ booking.adult_count !== 1 ? 's' : '' }}
                            <span v-if="booking.child_count > 0">
                                , {{ booking.child_count }} Child{{ booking.child_count !== 1 ? 'ren' : '' }}
                            </span>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Guest Information -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h3 class="text-xl font-semibold text-gray-900 mb-4">Guest Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-500">Name</p>
                        <p class="text-lg font-semibold text-gray-900">
                            {{ booking.guest_first_name }} {{ booking.guest_last_name }}
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Email</p>
                        <p class="text-lg font-semibold text-gray-900">{{ booking.guest_email }}</p>
                    </div>
                    <div v-if="booking.guest_phone">
                        <p class="text-sm text-gray-500">Phone</p>
                        <p class="text-lg font-semibold text-gray-900">{{ booking.guest_phone }}</p>
                    </div>
                </div>
            </div>

            <!-- Pricing -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-xl font-semibold text-gray-900 mb-4">Pricing</h3>
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-700">Total Amount</span>
                        <span class="text-2xl font-bold text-blue-600">
                            {{ formatCurrency(booking.total_amount || 0, booking.currency) }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between text-sm text-gray-500">
                        <span>Payment Status</span>
                        <span class="capitalize">{{ booking.payment_status || 'Pending' }}</span>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="mt-6 text-center">
                <a
                    href="/"
                    class="inline-block bg-blue-600 text-white py-3 px-6 rounded-md hover:bg-blue-700 font-semibold"
                >
                    Search More Properties
                </a>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';

const props = defineProps({
    bookingId: {
        type: String,
        required: true,
    },
});

const booking = ref(null);
const loading = ref(true);

const statusClass = computed(() => {
    if (!booking.value) return '';
    const status = booking.value.status?.toLowerCase();
    if (status === 'confirmed') return 'bg-green-100 text-green-800';
    if (status === 'pending') return 'bg-yellow-100 text-yellow-800';
    if (status === 'cancelled') return 'bg-red-100 text-red-800';
    return 'bg-gray-100 text-gray-800';
});

const loadBooking = async () => {
    loading.value = true;
    try {
        const response = await fetch(`/public/api/bookings/${props.bookingId}`);
        const data = await response.json();

        if (data.success) {
            booking.value = data.data;
        }
    } catch (error) {
        console.error('Error loading booking:', error);
    } finally {
        loading.value = false;
    }
};

const formatDate = (date) => {
    if (!date) return '';
    return new Date(date).toLocaleDateString('en-GB', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};

const formatCurrency = (amount, currency = 'GBP') => {
    return new Intl.NumberFormat('en-GB', {
        style: 'currency',
        currency: currency || 'GBP',
    }).format(amount);
};

const calculateNights = () => {
    if (!booking.value?.check_in || !booking.value?.check_out) return 0;
    const checkIn = new Date(booking.value.check_in);
    const checkOut = new Date(booking.value.check_out);
    return Math.ceil((checkOut - checkIn) / (1000 * 60 * 60 * 24));
};

onMounted(() => {
    loadBooking();
});
</script>

