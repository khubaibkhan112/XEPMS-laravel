<template>
    <div class="flex min-h-screen bg-slate-100">
        <SidebarNavigation />
        <div class="flex min-h-screen flex-1 flex-col">
            <TopNavbar />
            <header class="bg-white shadow-sm">
                <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-4 px-6 py-4">
                <div class="space-y-1">
                    <h1 class="text-xl font-semibold text-slate-900">Reservation Calendar</h1>
                    <p class="text-sm text-slate-500">Visualise room occupancy, drag reservations, and track availability.</p>
                    <StatusLegend class="pt-2" />
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <div class="inline-flex rounded-md border border-slate-200 bg-white p-1 shadow-sm">
                        <button
                            v-for="option in viewOptions"
                            :key="option.value"
                            :class="[
                                'rounded-md px-3 py-1.5 text-sm font-medium transition',
                                viewMode === option.value
                                    ? 'bg-blue-600 text-white shadow'
                                    : 'text-slate-600 hover:bg-slate-50',
                            ]"
                            type="button"
                            @click="changeView(option.value)"
                        >
                            {{ option.label }}
                        </button>
                    </div>
                    <button
                        class="rounded-md border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600"
                        type="button"
                        @click="goToday"
                    >
                        Today
                    </button>
                    <button
                        class="rounded-md border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600"
                        type="button"
                        @click="goPrevious"
                    >
                        Prev
                    </button>
                    <button
                        class="rounded-md bg-blue-600 px-3 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-blue-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600"
                        type="button"
                        @click="goNext"
                    >
                        Next
                    </button>
                    <label class="flex items-center gap-2 text-sm text-slate-600">
                        <input v-model="showAvailability" class="h-4 w-4 rounded border-slate-300" type="checkbox" />
                        Show room availability
                    </label>
                    <button
                        class="rounded-md border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600"
                        type="button"
                        @click="showSearchModal = true"
                    >
                        <svg class="mr-1.5 inline h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        Search
                    </button>
                    <button
                        class="rounded-md border border-amber-300 bg-amber-50 px-3 py-2 text-sm font-medium text-amber-700 transition hover:bg-amber-100 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-600"
                        type="button"
                        @click="showBlockModal = true"
                    >
                        Block Dates
                    </button>
                    <button
                        class="rounded-md bg-green-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-green-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600"
                        type="button"
                        @click="showCreateModal = true"
                    >
                        + New Booking
                    </button>
                </div>
                </div>
            </header>

            <main class="mx-auto w-full max-w-7xl flex-1 px-6 py-6">
                <CalendarLayout
                    :rooms="rooms"
                    :reservations="filteredReservations"
                    :start-date="startDate"
                    :days="visibleDays"
                    :day-width="dayWidth"
                    :view-mode="viewMode"
                    :availability="availability"
                    :show-availability="showAvailability"
                    @update-reservation="handleReservationUpdate"
                    @edit-reservation="handleEditReservation"
                    @cancel-reservation="handleCancelReservation"
                />
            </main>
        </div>
        <CreateBookingModal
            :rooms="rooms"
            :property-id="1"
            :show="showCreateModal"
            @close="showCreateModal = false"
            @submit="handleNewBooking"
        />
        <EditBookingModal
            :rooms="rooms"
            :reservation="selectedReservation"
            :property-id="1"
            :show="showEditModal"
            @close="showEditModal = false"
            @submit="handleUpdateBooking"
        />
        <CancelBookingModal
            :reservation="selectedReservation"
            :show="showCancelModal"
            @close="showCancelModal = false"
            @submit="handleCancelBooking"
        />
        <BookingSearch
            :reservations="reservations"
            :rooms="rooms"
            :show="showSearchModal"
            @close="showSearchModal = false"
            @select="handleSearchSelect"
            @filter="handleSearchFilter"
        />
        <BlockDatesModal
            :rooms="rooms"
            :property-id="1"
            :show="showBlockModal"
            @close="showBlockModal = false"
            @submit="handleBlockDates"
        />
    </div>
</template>

<script setup>
import { addDays, startOfDay, subDays } from 'date-fns';
import { computed, ref, watch } from 'vue';
import BlockDatesModal from './components/BlockDatesModal.vue';
import BookingSearch from './components/BookingSearch.vue';
import CalendarLayout from './components/CalendarLayout.vue';
import CancelBookingModal from './components/CancelBookingModal.vue';
import CreateBookingModal from './components/CreateBookingModal.vue';
import EditBookingModal from './components/EditBookingModal.vue';
import StatusLegend from './components/StatusLegend.vue';
import SidebarNavigation from '../shared/components/SidebarNavigation.vue';
import TopNavbar from '../shared/components/TopNavbar.vue';
import { mockReservations, mockRooms } from './data/sample-data';
import { calculateDailyAvailability } from './utils/calendar';

const viewOptions = [
    { label: 'Day', value: 'day' },
    { label: 'Week', value: 'week' },
    { label: 'Month', value: 'month' },
];

const rooms = ref(mockRooms);
const reservations = ref(mockReservations());
const viewMode = ref('week');
const startDate = ref(startOfDay(new Date()));
const showAvailability = ref(true);
const showCreateModal = ref(false);
const showEditModal = ref(false);
const showCancelModal = ref(false);
const showSearchModal = ref(false);
const showBlockModal = ref(false);
const selectedReservation = ref(null);
const searchFilter = ref({});
const filteredReservations = computed(() => {
    if (Object.keys(searchFilter.value).length === 0) {
        return reservations.value;
    }

    return reservations.value.filter((reservation) => {
        if (searchFilter.value.guestName) {
            const guestName = reservation.guestName?.toLowerCase() || '';
            if (!guestName.includes(searchFilter.value.guestName.toLowerCase())) {
                return false;
            }
        }

        if (searchFilter.value.roomId) {
            if (reservation.roomId?.toString() !== searchFilter.value.roomId.toString()) {
                return false;
            }
        }

        if (searchFilter.value.checkInFrom) {
            const checkIn = reservation.checkIn instanceof Date
                ? reservation.checkIn
                : new Date(reservation.checkIn);
            const fromDate = new Date(searchFilter.value.checkInFrom);
            if (checkIn < fromDate) {
                return false;
            }
        }

        if (searchFilter.value.checkInTo) {
            const checkIn = reservation.checkIn instanceof Date
                ? reservation.checkIn
                : new Date(reservation.checkIn);
            const toDate = new Date(searchFilter.value.checkInTo);
            if (checkIn > toDate) {
                return false;
            }
        }

        if (searchFilter.value.status) {
            if (reservation.status !== searchFilter.value.status) {
                return false;
            }
        }

        if (searchFilter.value.source) {
            if (reservation.source !== searchFilter.value.source) {
                return false;
            }
        }

        return true;
    });
});

const visibleDays = computed(() => {
    switch (viewMode.value) {
        case 'day':
            return 1;
        case 'month':
            return 30;
        default:
            return 7;
    }
});

const dayWidth = computed(() => {
    switch (viewMode.value) {
        case 'day':
            return 320;
        case 'month':
            return 90;
        default:
            return 140;
    }
});

const availability = ref([]);

watch(
    [startDate, visibleDays, filteredReservations],
    () => {
        availability.value = calculateDailyAvailability(
            rooms.value,
            filteredReservations.value,
            startDate.value,
            visibleDays.value
        );
    },
    { immediate: true, deep: true }
);

function changeView(mode) {
    if (viewMode.value === mode) {
        return;
    }
    viewMode.value = mode;
}

function goToday() {
    startDate.value = startOfDay(new Date());
}

function goPrevious() {
    startDate.value = subDays(startDate.value, visibleDays.value);
}

function goNext() {
    startDate.value = addDays(startDate.value, visibleDays.value);
}

function handleReservationUpdate(payload) {
    reservations.value = reservations.value.map((reservation) =>
        reservation.id === payload.id
            ? {
                  ...reservation,
                  checkIn: payload.checkIn,
                  checkOut: payload.checkOut,
                  checkInIso: payload.checkIn.toISOString(),
                  checkOutIso: payload.checkOut.toISOString(),
                  roomId: payload.roomId || reservation.roomId,
              }
            : reservation
    );
}

function handleNewBooking(bookingData) {
    const newReservation = {
        id: `res-${Date.now()}`,
        roomId: parseInt(bookingData.roomId),
        guestName: `${bookingData.guestFirstName} ${bookingData.guestLastName}`,
        guestEmail: bookingData.guestEmail,
        guestPhone: bookingData.guestPhone,
        checkIn: bookingData.checkIn,
        checkOut: bookingData.checkOut,
        checkInIso: bookingData.checkIn.toISOString(),
        checkOutIso: bookingData.checkOut.toISOString(),
        status: bookingData.status,
        source: bookingData.source,
        adultCount: bookingData.adultCount,
        childCount: bookingData.childCount,
        totalAmount: bookingData.totalAmount || 0,
        currency: 'GBP',
        notes: bookingData.notes,
        channelColor: '#4f46e5',
    };
    reservations.value.push(newReservation);
}

function handleEditReservation(reservation) {
    selectedReservation.value = reservation;
    showEditModal.value = true;
}

function handleUpdateBooking(bookingData) {
    reservations.value = reservations.value.map((reservation) =>
        reservation.id === bookingData.id
            ? {
                  ...reservation,
                  roomId: parseInt(bookingData.roomId),
                  guestName: `${bookingData.guestFirstName} ${bookingData.guestLastName}`,
                  guestEmail: bookingData.guestEmail,
                  guestPhone: bookingData.guestPhone,
                  checkIn: bookingData.checkIn,
                  checkOut: bookingData.checkOut,
                  checkInIso: bookingData.checkIn.toISOString(),
                  checkOutIso: bookingData.checkOut.toISOString(),
                  status: bookingData.status,
                  source: bookingData.source,
                  adultCount: bookingData.adultCount,
                  childCount: bookingData.childCount,
                  totalAmount: bookingData.totalAmount || 0,
                  notes: bookingData.notes,
              }
            : reservation
    );
}

function handleCancelReservation(reservation) {
    selectedReservation.value = reservation;
    showCancelModal.value = true;
}

function handleCancelBooking(cancellationData) {
    reservations.value = reservations.value.map((reservation) =>
        reservation.id === cancellationData.id
            ? {
                  ...reservation,
                  status: 'cancelled',
                  cancelledAt: cancellationData.cancelledAt,
                  cancellationReason: cancellationData.reason,
                  refundAmount: cancellationData.refundAmount,
                  cancellationFee: cancellationData.cancellationFee,
                  notes: reservation.notes
                      ? `${reservation.notes}\n[Cancelled: ${cancellationData.reason}]`
                      : `[Cancelled: ${cancellationData.reason}]`,
              }
            : reservation
    );
}

function handleSearchSelect(reservation) {
    selectedReservation.value = reservation;
    showEditModal.value = true;
}

function handleSearchFilter(filter) {
    searchFilter.value = filter;
}

function handleBlockDates(result) {
    console.log('Dates blocked:', result);
    // Refresh availability or show success message
}
</script>
