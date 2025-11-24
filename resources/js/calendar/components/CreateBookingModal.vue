<template>
    <Teleport to="body">
        <div
            v-if="show"
            class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/50 p-4 overflow-y-auto"
            @click.self="handleClose"
        >
            <div class="w-full max-w-2xl rounded-lg bg-white shadow-xl my-8 max-h-[90vh] overflow-y-auto">
            <div class="border-b border-slate-200 px-6 py-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-semibold text-slate-900">Create New Reservation</h2>
                    <button
                        class="rounded-lg p-1 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600"
                        type="button"
                        @click="handleClose"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M6 18L18 6M6 6l12 12" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </button>
                </div>
            </div>

            <form @submit.prevent="handleSubmit" class="px-6 py-4">
                <div class="space-y-6">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700" for="guestFirstName">
                                First Name <span class="text-red-500">*</span>
                            </label>
                            <input
                                id="guestFirstName"
                                v-model="form.guestFirstName"
                                class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                placeholder="John"
                                required
                                type="text"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700" for="guestLastName">
                                Last Name <span class="text-red-500">*</span>
                            </label>
                            <input
                                id="guestLastName"
                                v-model="form.guestLastName"
                                class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                placeholder="Doe"
                                required
                                type="text"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700" for="guestEmail">
                                Email
                            </label>
                            <input
                                id="guestEmail"
                                v-model="form.guestEmail"
                                class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                placeholder="john@example.com"
                                type="email"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700" for="guestPhone">
                                Phone
                            </label>
                            <input
                                id="guestPhone"
                                v-model="form.guestPhone"
                                class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                placeholder="+44 20 1234 5678"
                                type="tel"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700" for="checkIn">
                                Check-in Date <span class="text-red-500">*</span>
                            </label>
                            <input
                                id="checkIn"
                                v-model="form.checkIn"
                                class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                required
                                type="date"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700" for="checkOut">
                                Check-out Date <span class="text-red-500">*</span>
                            </label>
                            <input
                                id="checkOut"
                                v-model="form.checkOut"
                                class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                required
                                type="date"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700" for="roomId">
                                Room <span class="text-red-500">*</span>
                            </label>
                            <select
                                id="roomId"
                                v-model="form.roomId"
                                class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                required
                            >
                                <option value="">Select room</option>
                                <option v-for="room in rooms" :key="room.id" :value="room.id">
                                    {{ room.name }} - {{ room.type }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700" for="adultCount">
                                Adults <span class="text-red-500">*</span>
                            </label>
                            <input
                                id="adultCount"
                                v-model.number="form.adultCount"
                                class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                min="1"
                                required
                                type="number"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700" for="childCount">
                                Children
                            </label>
                            <input
                                id="childCount"
                                v-model.number="form.childCount"
                                class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                min="0"
                                type="number"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700" for="totalAmount">
                                Total Amount (GBP)
                            </label>
                            <input
                                id="totalAmount"
                                v-model.number="form.totalAmount"
                                class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                min="0"
                                step="0.01"
                                type="number"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700" for="source">
                                Source
                            </label>
                            <select
                                id="source"
                                v-model="form.source"
                                class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                            >
                                <option value="direct">Direct</option>
                                <option value="booking_com">Booking.com</option>
                                <option value="expedia">Expedia</option>
                                <option value="airbnb">Airbnb</option>
                                <option value="tripadvisor">Tripadvisor</option>
                                <option value="viator">Viator</option>
                                <option value="hotels_com">Hotels.com</option>
                                <option value="laterooms">LateRooms.com</option>
                                <option value="travel_republic">Travel Republic</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700" for="status">
                            Status
                        </label>
                        <select
                            id="status"
                            v-model="form.status"
                            class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                        >
                            <option value="pending">Pending</option>
                            <option value="confirmed">Confirmed</option>
                            <option value="checked_in">Checked In</option>
                            <option value="checked_out">Checked Out</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700" for="notes">
                            Notes
                        </label>
                        <textarea
                            id="notes"
                            v-model="form.notes"
                            class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                            placeholder="Additional notes or special requests..."
                            rows="3"
                        ></textarea>
                    </div>

                    <div v-if="error" class="rounded-lg bg-red-50 p-3 text-sm text-red-600">
                        {{ error }}
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3 border-t border-slate-200 pt-4">
                    <button
                        class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                        type="button"
                        @click="handleClose"
                    >
                        Cancel
                    </button>
                    <button
                        :disabled="loading"
                        class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                        type="submit"
                    >
                        <span v-if="!loading">Create Reservation</span>
                        <span v-else class="flex items-center gap-2">
                            <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle
                                    class="opacity-25"
                                    cx="12"
                                    cy="12"
                                    r="10"
                                    stroke="currentColor"
                                    stroke-width="4"
                                ></circle>
                                <path
                                    class="opacity-75"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                    fill="currentColor"
                                ></path>
                            </svg>
                            Creating...
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    </Teleport>
</template>

<script setup>
import { format } from 'date-fns';
import { ref, watch } from 'vue';
import { availabilityService } from '../services/availabilityService';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    rooms: {
        type: Array,
        default: () => [],
    },
    initialDate: {
        type: Date,
        default: null,
    },
    propertyId: {
        type: Number,
        default: 1,
    },
});

const emit = defineEmits(['close', 'submit']);

const form = ref({
    guestFirstName: '',
    guestLastName: '',
    guestEmail: '',
    guestPhone: '',
    checkIn: '',
    checkOut: '',
    roomId: '',
    adultCount: 1,
    childCount: 0,
    totalAmount: 0,
    source: 'direct',
    status: 'pending',
    notes: '',
});

const loading = ref(false);
const error = ref('');

watch(
    () => props.initialDate,
    (newDate) => {
        if (newDate) {
            form.value.checkIn = format(newDate, 'yyyy-MM-dd');
            const nextDay = new Date(newDate);
            nextDay.setDate(nextDay.getDate() + 1);
            form.value.checkOut = format(nextDay, 'yyyy-MM-dd');
        }
    },
    { immediate: true }
);

watch(
    () => props.show,
    (isOpen) => {
        if (!isOpen) {
            form.value = {
                guestFirstName: '',
                guestLastName: '',
                guestEmail: '',
                guestPhone: '',
                checkIn: props.initialDate ? format(props.initialDate, 'yyyy-MM-dd') : '',
                checkOut: props.initialDate
                    ? format(new Date(props.initialDate.getTime() + 86400000), 'yyyy-MM-dd')
                    : '',
                roomId: '',
                adultCount: 1,
                childCount: 0,
                totalAmount: 0,
                source: 'direct',
                status: 'pending',
                notes: '',
            };
            error.value = '';
        }
    }
);

function handleClose() {
    emit('close');
}

async function handleSubmit() {
    error.value = '';
    
    if (new Date(form.value.checkIn) >= new Date(form.value.checkOut)) {
        error.value = 'Check-out date must be after check-in date.';
        return;
    }

    if (!form.value.roomId) {
        error.value = 'Please select a room.';
        return;
    }

    loading.value = true;

    try {
        // Check availability before creating
        const availabilityCheck = await availabilityService.checkAvailability({
            property_id: props.propertyId,
            check_in: form.value.checkIn,
            check_out: form.value.checkOut,
            room_id: form.value.roomId,
            adult_count: form.value.adultCount,
            child_count: form.value.childCount,
        });

        const selectedRoom = availabilityCheck.data?.rooms?.find(
            room => room.room_id === parseInt(form.value.roomId)
        );

        if (!selectedRoom || !selectedRoom.is_available) {
            error.value = selectedRoom?.conflicts?.length > 0
                ? `Room is not available. Conflicts: ${selectedRoom.conflicts.map(c => c.guest_name).join(', ')}`
                : 'Selected room is not available for the chosen dates.';
            loading.value = false;
            return;
        }

        emit('submit', {
            ...form.value,
            checkIn: new Date(form.value.checkIn),
            checkOut: new Date(form.value.checkOut),
        });
        handleClose();
    } catch (err) {
        error.value = 'Failed to check availability. Please try again.';
        console.error('Availability check error:', err);
    } finally {
        loading.value = false;
    }
}
</script>


