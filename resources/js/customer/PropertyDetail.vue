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
            <p class="text-gray-500">Loading property details...</p>
        </div>

        <div v-else-if="property" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Property Header -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h1 class="text-3xl font-bold text-gray-900 mb-2">{{ property.name }}</h1>
                <p class="text-gray-600 mb-4">{{ property.address }}</p>
                <div class="flex items-center gap-4 text-sm text-gray-500">
                    <span v-if="property.phone">{{ property.phone }}</span>
                    <span v-if="property.email">{{ property.email }}</span>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Room Types -->
                <div class="lg:col-span-2">
                    <h2 class="text-2xl font-semibold text-gray-900 mb-4">Available Room Types</h2>
                    <div class="space-y-4">
                        <div
                            v-for="roomType in property.room_types"
                            :key="roomType.id"
                            class="bg-white rounded-lg shadow-md p-6"
                        >
                            <h3 class="text-xl font-semibold text-gray-900 mb-2">{{ roomType.name }}</h3>
                            <p class="text-gray-600 mb-4">{{ roomType.description || 'No description available' }}</p>
                            <div class="flex items-center justify-between mb-4">
                                <div class="text-sm text-gray-500">
                                    <span>Max Occupancy: {{ roomType.max_occupancy }}</span>
                                </div>
                                <div class="text-lg font-bold text-blue-600">
                                    {{ formatCurrency(roomType.base_rate || 0, property.currency) }}/night
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Booking Form -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-lg shadow-md p-6 sticky top-4">
                        <h2 class="text-2xl font-semibold text-gray-900 mb-4">Book Now</h2>
                        <form @submit.prevent="submitBooking" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Room Type *</label>
                                <select
                                    v-model="bookingForm.room_type_id"
                                    required
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                >
                                    <option value="">Select Room Type</option>
                                    <option
                                        v-for="roomType in property.room_types"
                                        :key="roomType.id"
                                        :value="roomType.id"
                                    >
                                        {{ roomType.name }} - {{ formatCurrency(roomType.base_rate || 0, property.currency) }}/night
                                    </option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Check-in Date *</label>
                                <input
                                    v-model="bookingForm.check_in"
                                    type="date"
                                    :min="minDate"
                                    required
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    @change="checkAvailability"
                                />
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Check-out Date *</label>
                                <input
                                    v-model="bookingForm.check_out"
                                    type="date"
                                    :min="bookingForm.check_in || minDate"
                                    required
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    @change="checkAvailability"
                                />
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Adults *</label>
                                <input
                                    v-model.number="bookingForm.adult_count"
                                    type="number"
                                    min="1"
                                    required
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                />
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Children</label>
                                <input
                                    v-model.number="bookingForm.child_count"
                                    type="number"
                                    min="0"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                />
                            </div>

                            <div v-if="availabilityError" class="bg-red-50 border border-red-200 rounded-md p-3">
                                <p class="text-sm text-red-600">{{ availabilityError }}</p>
                            </div>

                            <div v-if="availabilityChecked && !availabilityError" class="bg-green-50 border border-green-200 rounded-md p-3">
                                <p class="text-sm text-green-600">Available for your dates!</p>
                            </div>

                            <div class="border-t pt-4">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">Guest Information</h3>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">First Name *</label>
                                    <input
                                        v-model="bookingForm.guest_first_name"
                                        type="text"
                                        required
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    />
                                </div>
                                <div class="mt-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Last Name *</label>
                                    <input
                                        v-model="bookingForm.guest_last_name"
                                        type="text"
                                        required
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    />
                                </div>
                                <div class="mt-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Email *</label>
                                    <input
                                        v-model="bookingForm.guest_email"
                                        type="email"
                                        required
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    />
                                </div>
                                <div class="mt-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Phone</label>
                                    <input
                                        v-model="bookingForm.guest_phone"
                                        type="tel"
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    />
                                </div>
                            </div>

                            <div class="border-t pt-4">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-gray-700">Total Amount:</span>
                                    <span class="text-xl font-bold text-blue-600">
                                        {{ calculateTotal() }}
                                    </span>
                                </div>
                            </div>

                            <button
                                type="submit"
                                :disabled="submitting || !availabilityChecked || availabilityError"
                                class="w-full bg-blue-600 text-white py-3 px-4 rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed font-semibold"
                            >
                                {{ submitting ? 'Processing...' : 'Complete Booking' }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';

const props = defineProps({
    propertyId: {
        type: String,
        required: true,
    },
});

const property = ref(null);
const loading = ref(true);
const submitting = ref(false);
const availabilityChecked = ref(false);
const availabilityError = ref('');
const minDate = new Date().toISOString().split('T')[0];

const bookingForm = ref({
    room_type_id: '',
    check_in: '',
    check_out: '',
    adult_count: 1,
    child_count: 0,
    guest_first_name: '',
    guest_last_name: '',
    guest_email: '',
    guest_phone: '',
});

const loadProperty = async () => {
    loading.value = true;
    try {
        const response = await fetch(`/public/api/properties/${props.propertyId}`);
        const data = await response.json();

        if (data.success) {
            property.value = data.data;
            // Select the first room type by default
            if (property.value.room_types && property.value.room_types.length > 0) {
                bookingForm.value.room_type_id = property.value.room_types[0].id;
            }
        }
    } catch (error) {
        console.error('Error loading property:', error);
    } finally {
        loading.value = false;
    }
};

const checkAvailability = async () => {
    if (!bookingForm.value.check_in || !bookingForm.value.check_out || !bookingForm.value.room_type_id) {
        availabilityChecked.value = false;
        availabilityError.value = '';
        return;
    }

    try {
        const params = new URLSearchParams({
            property_id: props.propertyId,
            check_in: bookingForm.value.check_in,
            check_out: bookingForm.value.check_out,
            room_type_id: bookingForm.value.room_type_id,
            adult_count: bookingForm.value.adult_count || 1,
            child_count: bookingForm.value.child_count || 0,
        });

        const response = await fetch(`/public/api/availability/check?${params.toString()}`);
        const data = await response.json();

        if (data.success && data.data.available) {
            availabilityChecked.value = true;
            availabilityError.value = '';
        } else {
            availabilityChecked.value = false;
            availabilityError.value = data.data.message || 'Not available for the selected dates';
        }
    } catch (error) {
        console.error('Error checking availability:', error);
        availabilityError.value = 'Error checking availability. Please try again.';
        availabilityChecked.value = false;
    }
};

const calculateTotal = () => {
    if (!property.value || !bookingForm.value.room_type_id || !bookingForm.value.check_in || !bookingForm.value.check_out) {
        return formatCurrency(0, property.value?.currency || 'GBP');
    }

    const roomType = property.value.room_types.find(rt => rt.id == bookingForm.value.room_type_id);
    if (!roomType) return formatCurrency(0, property.value.currency);

    const checkIn = new Date(bookingForm.value.check_in);
    const checkOut = new Date(bookingForm.value.check_out);
    const nights = Math.ceil((checkOut - checkIn) / (1000 * 60 * 60 * 24));

    const total = (roomType.base_rate || 0) * nights;
    return formatCurrency(total, property.value.currency);
};

const formatCurrency = (amount, currency = 'GBP') => {
    return new Intl.NumberFormat('en-GB', {
        style: 'currency',
        currency: currency || 'GBP',
    }).format(amount);
};

const submitBooking = async () => {
    if (!availabilityChecked.value || availabilityError.value) {
        return;
    }

    submitting.value = true;
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        const response = await fetch('/public/api/bookings', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({
                property_id: props.propertyId,
                ...bookingForm.value,
            }),
        });

        const data = await response.json();

        if (data.success) {
            window.location.href = `/booking/${data.data.id}`;
        } else {
            alert(data.message || 'Booking failed. Please try again.');
        }
    } catch (error) {
        console.error('Error submitting booking:', error);
        alert('An error occurred. Please try again.');
    } finally {
        submitting.value = false;
    }
};

onMounted(() => {
    loadProperty();
});
</script>

