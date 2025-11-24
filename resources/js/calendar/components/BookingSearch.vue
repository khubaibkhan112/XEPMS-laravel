<template>
    <Teleport to="body">
        <div
            v-if="show"
            class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/50 p-4 overflow-y-auto"
            @click.self="handleClose"
        >
            <div class="w-full max-w-3xl rounded-lg bg-white shadow-xl my-8 max-h-[90vh] overflow-y-auto">
                <div class="border-b border-slate-200 px-6 py-4">
                    <div class="flex items-center justify-between">
                        <h2 class="text-xl font-semibold text-slate-900">Search Reservations</h2>
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

                <div class="px-6 py-4">
                    <form @submit.prevent="handleSearch" class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700" for="searchGuestName">
                                    Guest Name
                                </label>
                                <input
                                    id="searchGuestName"
                                    v-model="form.guestName"
                                    class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                    placeholder="Search by guest name..."
                                    type="text"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700" for="searchRoom">
                                    Room
                                </label>
                                <select
                                    id="searchRoom"
                                    v-model="form.roomId"
                                    class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                >
                                    <option value="">All Rooms</option>
                                    <option v-for="room in rooms" :key="room.id" :value="room.id">
                                        {{ room.name }} - {{ room.type }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700" for="searchCheckIn">
                                    Check-in Date (From)
                                </label>
                                <input
                                    id="searchCheckIn"
                                    v-model="form.checkInFrom"
                                    class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                    type="date"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700" for="searchCheckInTo">
                                    Check-in Date (To)
                                </label>
                                <input
                                    id="searchCheckInTo"
                                    v-model="form.checkInTo"
                                    class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                    type="date"
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700" for="searchStatus">
                                    Status
                                </label>
                                <select
                                    id="searchStatus"
                                    v-model="form.status"
                                    class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                >
                                    <option value="">All Statuses</option>
                                    <option value="pending">Pending</option>
                                    <option value="confirmed">Confirmed</option>
                                    <option value="checked_in">Checked In</option>
                                    <option value="checked_out">Checked Out</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700" for="searchSource">
                                    Source
                                </label>
                                <select
                                    id="searchSource"
                                    v-model="form.source"
                                    class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                >
                                    <option value="">All Sources</option>
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

                        <div class="flex justify-end gap-3 border-t border-slate-200 pt-4">
                            <button
                                class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                                type="button"
                                @click="handleClear"
                            >
                                Clear
                            </button>
                            <button
                                class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                                type="submit"
                            >
                                Search
                            </button>
                        </div>
                    </form>

                    <div v-if="searchResults.length > 0" class="mt-6 border-t border-slate-200 pt-4">
                        <h3 class="mb-3 text-sm font-semibold text-slate-900">
                            Search Results ({{ searchResults.length }})
                        </h3>
                        <div class="space-y-2 max-h-96 overflow-y-auto">
                            <div
                                v-for="result in searchResults"
                                :key="result.id"
                                class="cursor-pointer rounded-lg border border-slate-200 bg-white p-3 transition hover:border-blue-300 hover:bg-blue-50"
                                @click="handleSelectResult(result)"
                            >
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2">
                                            <span class="font-medium text-slate-900">{{ result.guestName }}</span>
                                            <span
                                                class="rounded-full px-2 py-0.5 text-xs font-medium capitalize"
                                                :class="getStatusBadgeClass(result.status)"
                                            >
                                                {{ result.status }}
                                            </span>
                                        </div>
                                        <div class="mt-1 text-xs text-slate-600">
                                            <span>Room {{ result.roomNumber || result.roomId }}</span>
                                            <span class="mx-2">•</span>
                                            <span>{{ formatDate(result.checkIn) }} - {{ formatDate(result.checkOut) }}</span>
                                            <span class="mx-2">•</span>
                                            <span class="uppercase">{{ result.source }}</span>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-sm font-semibold text-slate-900">
                                            {{ formatCurrency(result.totalAmount) }} {{ result.currency || 'GBP' }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-else-if="hasSearched && searchResults.length === 0" class="mt-6 border-t border-slate-200 pt-4">
                        <div class="py-8 text-center">
                            <svg
                                class="mx-auto h-12 w-12 text-slate-400"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                />
                            </svg>
                            <p class="mt-2 text-sm text-slate-500">No reservations found matching your criteria.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup>
import { ref } from 'vue';
import { format } from 'date-fns';
import { STATUS_CONFIG } from '../constants/statuses';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    reservations: {
        type: Array,
        default: () => [],
    },
    rooms: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['close', 'select', 'filter']);

const form = ref({
    guestName: '',
    roomId: '',
    checkInFrom: '',
    checkInTo: '',
    status: '',
    source: '',
});

const searchResults = ref([]);
const hasSearched = ref(false);

function formatDate(date) {
    if (!date) return 'N/A';
    const d = date instanceof Date ? date : new Date(date);
    return format(d, 'MMM d, yyyy');
}

function formatCurrency(amount) {
    if (!amount) return '0.00';
    return new Intl.NumberFormat('en-GB', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(amount);
}

function getStatusBadgeClass(status) {
    const config = STATUS_CONFIG[status];
    return config?.badgeClass ?? 'bg-slate-600 text-white';
}

function handleSearch() {
    hasSearched.value = true;
    const results = props.reservations.filter((reservation) => {
        if (form.value.guestName) {
            const guestName = reservation.guestName?.toLowerCase() || '';
            if (!guestName.includes(form.value.guestName.toLowerCase())) {
                return false;
            }
        }

        if (form.value.roomId) {
            if (reservation.roomId?.toString() !== form.value.roomId.toString()) {
                return false;
            }
        }

        if (form.value.checkInFrom) {
            const checkIn = reservation.checkIn instanceof Date
                ? reservation.checkIn
                : new Date(reservation.checkIn);
            const fromDate = new Date(form.value.checkInFrom);
            if (checkIn < fromDate) {
                return false;
            }
        }

        if (form.value.checkInTo) {
            const checkIn = reservation.checkIn instanceof Date
                ? reservation.checkIn
                : new Date(reservation.checkIn);
            const toDate = new Date(form.value.checkInTo);
            if (checkIn > toDate) {
                return false;
            }
        }

        if (form.value.status) {
            if (reservation.status !== form.value.status) {
                return false;
            }
        }

        if (form.value.source) {
            if (reservation.source !== form.value.source) {
                return false;
            }
        }

        return true;
    });

    searchResults.value = results;
    emit('filter', form.value);
}

function handleClear() {
    form.value = {
        guestName: '',
        roomId: '',
        checkInFrom: '',
        checkInTo: '',
        status: '',
        source: '',
    };
    searchResults.value = [];
    hasSearched.value = false;
    emit('filter', form.value);
}

function handleSelectResult(result) {
    emit('select', result);
    emit('close');
}

function handleClose() {
    emit('close');
}
</script>


