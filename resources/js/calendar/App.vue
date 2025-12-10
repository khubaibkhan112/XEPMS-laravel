<template>
    <div class="flex min-h-screen bg-slate-100">
        <SidebarNavigation />
        <div class="flex min-h-screen flex-1 flex-col">
            <TopNavbar />
            <header class="bg-white shadow-sm">
                <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-4 px-6 py-4">
                <div class="space-y-1">
                    <h1 class="text-xl font-semibold text-slate-900">
                        Reservation Calendar
                        <span v-if="selectedProperty" class="ml-2 text-base font-normal text-slate-500">
                            - {{ selectedProperty.name }}
                        </span>
                    </h1>
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
                        :disabled="!selectedPropertyId"
                        class="rounded-md bg-green-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-green-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600 disabled:cursor-not-allowed disabled:opacity-50"
                        type="button"
                        @click="showCreateModal = true"
                    >
                        + New Booking
                    </button>
                </div>
                </div>
            </header>

            <main class="mx-auto w-full max-w-7xl flex-1 px-6 py-6">
                <div v-if="!selectedPropertyId" class="rounded-lg bg-white p-12 text-center shadow-sm">
                    <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    <h3 class="mt-4 text-lg font-medium text-slate-900">No Property Selected</h3>
                    <p class="mt-2 text-sm text-slate-500">Please select a property from the sidebar to view its calendar.</p>
                    <p class="mt-1 text-xs text-slate-400">Click the edit icon next to "Property" in the sidebar to select a property.</p>
                </div>
                <div v-else-if="loading" class="flex items-center justify-center py-12">
                    <div class="text-center">
                        <svg class="mx-auto h-8 w-8 animate-spin text-blue-600" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" fill="currentColor"></path>
                        </svg>
                        <p class="mt-2 text-sm text-slate-600">Loading calendar...</p>
                    </div>
                </div>
                <CalendarLayout
                    v-else
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
            :property-id="selectedPropertyId"
            :show="showCreateModal"
            @close="showCreateModal = false"
            @submit="handleNewBooking"
        />
        <EditBookingModal
            :rooms="rooms"
            :reservation="selectedReservation"
            :property-id="selectedPropertyId"
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
            :property-id="selectedPropertyId"
            :show="showBlockModal"
            @close="showBlockModal = false"
            @submit="handleBlockDates"
        />
    </div>
</template>

<script setup>
import { addDays, startOfDay, subDays } from 'date-fns';
import { computed, onMounted, ref, watch } from 'vue';
import BlockDatesModal from './components/BlockDatesModal.vue';
import BookingSearch from './components/BookingSearch.vue';
import CalendarLayout from './components/CalendarLayout.vue';
import CancelBookingModal from './components/CancelBookingModal.vue';
import CreateBookingModal from './components/CreateBookingModal.vue';
import EditBookingModal from './components/EditBookingModal.vue';
import StatusLegend from './components/StatusLegend.vue';
import SidebarNavigation from '../shared/components/SidebarNavigation.vue';
import TopNavbar from '../shared/components/TopNavbar.vue';
import { usePropertySelection } from '../shared/composables/usePropertySelection';
import { calculateDailyAvailability } from './utils/calendar';

const viewOptions = [
    { label: 'Day', value: 'day' },
    { label: 'Week', value: 'week' },
    { label: 'Month', value: 'month' },
];

const { selectedPropertyId, selectedProperty } = usePropertySelection();
const rooms = ref([]);
const reservations = ref([]);
const loading = ref(false);
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

// Load rooms from API
async function loadRooms() {
    if (!selectedPropertyId.value) {
        rooms.value = [];
        return;
    }

    try {
        const response = await fetch(`/api/rooms?property_id=${selectedPropertyId.value}`);
        const result = await response.json();
        if (result.success) {
            rooms.value = result.data.map((room) => ({
                id: room.id,
                name: room.name || room.room_number,
                type: room.room_type?.name || 'Standard',
                capacity: room.max_occupancy || 2,
                room_number: room.room_number,
            }));
        }
    } catch (error) {
        console.error('Error loading rooms:', error);
    }
}

// Load reservations from API
async function loadReservations() {
    if (!selectedPropertyId.value) {
        reservations.value = [];
        return;
    }

    try {
        loading.value = true;
        const startDateStr = startDate.value.toISOString().split('T')[0];
        const endDate = addDays(startDate.value, visibleDays.value);
        const endDateStr = endDate.toISOString().split('T')[0];

        const response = await fetch(
            `/api/reservations?property_id=${selectedPropertyId.value}&start_date=${startDateStr}&end_date=${endDateStr}`
        );
        const result = await response.json();
        if (result.success) {
            reservations.value = result.data.map((res) => ({
                id: res.id,
                roomId: res.room_id,
                guestName: `${res.guest_first_name} ${res.guest_last_name}`,
                guestFirstName: res.guest_first_name,
                guestLastName: res.guest_last_name,
                guestEmail: res.guest_email,
                guestPhone: res.guest_phone,
                checkIn: new Date(res.check_in),
                checkOut: new Date(res.check_out),
                checkInIso: res.check_in,
                checkOutIso: res.check_out,
                status: res.status,
                source: res.source || 'direct',
                adultCount: res.adult_count || 1,
                childCount: res.child_count || 0,
                totalAmount: parseFloat(res.total_amount || 0),
                currency: res.currency || 'GBP',
                notes: res.notes,
                paymentStatus: res.payment_status,
                channelColor: getChannelColor(res.source),
            }));
        }
    } catch (error) {
        console.error('Error loading reservations:', error);
        window.toastr.error('Failed to load reservations. Please try again.');
    } finally {
        loading.value = false;
    }
}

function getChannelColor(source) {
    const colors = {
        'Booking.com': '#f97316',
        'Expedia': '#3b82f6',
        'Airbnb': '#ef4444',
        'direct': '#4f46e5',
    };
    return colors[source] || '#6b7280';
}

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

// Watch for property changes
watch(selectedPropertyId, async (newId) => {
    if (newId) {
        await loadRooms();
        await loadReservations();
    } else {
        rooms.value = [];
        reservations.value = [];
    }
});

// Watch for date range changes to reload reservations
watch([startDate, visibleDays], async () => {
    if (selectedPropertyId.value) {
        await loadReservations();
    }
});

// Load initial data
onMounted(async () => {
    if (selectedPropertyId.value) {
        await loadRooms();
        await loadReservations();
    }
});

// Listen for property changes from sidebar
window.addEventListener('property-changed', async () => {
    if (selectedPropertyId.value) {
        await loadRooms();
        await loadReservations();
    }
});

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

async function handleReservationUpdate(payload) {
    console.log('Calendar App: handleReservationUpdate called (drag & drop)', payload);
    try {
        const checkInDate = payload.checkIn instanceof Date 
            ? payload.checkIn 
            : new Date(payload.checkIn);
        const checkOutDate = payload.checkOut instanceof Date 
            ? payload.checkOut 
            : new Date(payload.checkOut);
            
        const response = await fetch(`/api/reservations/${payload.id}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({
                check_in: checkInDate.toISOString().split('T')[0],
                check_out: checkOutDate.toISOString().split('T')[0],
                room_id: payload.roomId,
            }),
        });

        const result = await response.json();
        console.log('Calendar App: Update response', result);
        
        if (result.success) {
            // Reload reservations to get updated data
            await loadReservations();
            window.toastr.success('Reservation moved successfully');
        } else {
            window.toastr.error(result.message || 'Failed to move reservation');
            if (result.errors) {
                console.error('Validation errors:', result.errors);
            }
        }
    } catch (error) {
        console.error('Error updating reservation:', error);
        window.toastr.error('Failed to move reservation. Please try again.');
    }
}

async function handleNewBooking(bookingData) {
    try {
        const response = await fetch('/api/reservations', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({
                property_id: selectedPropertyId.value,
                room_id: bookingData.roomId,
                room_type_id: bookingData.roomTypeId,
                guest_first_name: bookingData.guestFirstName,
                guest_last_name: bookingData.guestLastName,
                guest_email: bookingData.guestEmail,
                guest_phone: bookingData.guestPhone,
                check_in: bookingData.checkIn.toISOString().split('T')[0],
                check_out: bookingData.checkOut.toISOString().split('T')[0],
                adult_count: bookingData.adultCount || 1,
                child_count: bookingData.childCount || 0,
                total_amount: bookingData.totalAmount || 0,
                status: bookingData.status || 'confirmed',
                source: bookingData.source || 'direct',
                notes: bookingData.notes,
            }),
        });

        const result = await response.json();
        if (result.success) {
            // Reload reservations to show the new one
            await loadReservations();
            showCreateModal.value = false;
            window.toastr.success('Reservation created successfully');
        } else {
            window.toastr.error(result.message || 'Failed to create reservation');
            if (result.errors) {
                console.error('Validation errors:', result.errors);
            }
        }
    } catch (error) {
        console.error('Error creating reservation:', error);
        window.toastr.error('Failed to create reservation. Please try again.');
    }
}

function handleEditReservation(reservation) {
    console.log('Calendar App: handleEditReservation called', reservation);
    selectedReservation.value = reservation;
    showEditModal.value = true;
    console.log('Calendar App: Edit modal should be visible', showEditModal.value);
}

async function handleUpdateBooking(bookingData) {
    try {
        const response = await fetch(`/api/reservations/${bookingData.id}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({
                room_id: bookingData.roomId,
                room_type_id: bookingData.roomTypeId,
                guest_first_name: bookingData.guestFirstName,
                guest_last_name: bookingData.guestLastName,
                guest_email: bookingData.guestEmail,
                guest_phone: bookingData.guestPhone,
                check_in: bookingData.checkIn.toISOString().split('T')[0],
                check_out: bookingData.checkOut.toISOString().split('T')[0],
                adult_count: bookingData.adultCount || 1,
                child_count: bookingData.childCount || 0,
                total_amount: bookingData.totalAmount || 0,
                status: bookingData.status,
                source: bookingData.source,
                notes: bookingData.notes,
            }),
        });

        const result = await response.json();
        if (result.success) {
            // Reload reservations to get updated data
            await loadReservations();
            showEditModal.value = false;
            window.toastr.success('Reservation updated successfully');
        } else {
            window.toastr.error(result.message || 'Failed to update reservation');
            if (result.errors) {
                console.error('Validation errors:', result.errors);
            }
        }
    } catch (error) {
        console.error('Error updating reservation:', error);
        window.toastr.error('Failed to update reservation. Please try again.');
    }
}

function handleCancelReservation(reservation) {
    selectedReservation.value = reservation;
    showCancelModal.value = true;
}

async function handleCancelBooking(cancellationData) {
    try {
        const response = await fetch(`/api/reservations/${cancellationData.id}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
        });

        const result = await response.json();
        if (result.success) {
            // Reload reservations to reflect cancellation
            await loadReservations();
            showCancelModal.value = false;
            window.toastr.success('Reservation cancelled successfully');
        } else {
            window.toastr.error(result.message || 'Failed to cancel reservation');
        }
    } catch (error) {
        console.error('Error cancelling reservation:', error);
        window.toastr.error('Failed to cancel reservation. Please try again.');
    }
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
