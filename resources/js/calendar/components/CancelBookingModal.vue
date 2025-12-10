<template>
    <Teleport to="body">
        <div
            v-if="show"
            class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/50 p-4 overflow-y-auto"
        >
            <div class="w-full max-w-2xl rounded-lg bg-white shadow-xl my-8 max-h-[90vh] overflow-y-auto">
                <div class="border-b border-slate-200 px-6 py-4">
                    <div class="flex items-center justify-between">
                        <h2 class="text-xl font-semibold text-slate-900">Cancel Reservation</h2>
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

                <div v-if="reservation" class="px-6 py-4">
                    <div class="mb-6 rounded-lg border border-slate-200 bg-slate-50 p-4">
                        <h3 class="mb-3 text-sm font-semibold text-slate-900">Reservation Details</h3>
                        <div class="grid grid-cols-2 gap-3 text-sm">
                            <div>
                                <span class="text-slate-500">Guest:</span>
                                <span class="ml-2 font-medium text-slate-900">{{ reservation.guestName }}</span>
                            </div>
                            <div>
                                <span class="text-slate-500">Room:</span>
                                <span class="ml-2 font-medium text-slate-900">Room {{ reservation.roomNumber || reservation.roomId }}</span>
                            </div>
                            <div>
                                <span class="text-slate-500">Check-in:</span>
                                <span class="ml-2 font-medium text-slate-900">{{ formatDate(reservation.checkIn) }}</span>
                            </div>
                            <div>
                                <span class="text-slate-500">Check-out:</span>
                                <span class="ml-2 font-medium text-slate-900">{{ formatDate(reservation.checkOut) }}</span>
                            </div>
                            <div>
                                <span class="text-slate-500">Total Amount:</span>
                                <span class="ml-2 font-medium text-slate-900">{{ formatCurrency(reservation.totalAmount) }} {{ reservation.currency || 'GBP' }}</span>
                            </div>
                            <div>
                                <span class="text-slate-500">Status:</span>
                                <span class="ml-2 font-medium text-slate-900 capitalize">{{ reservation.status }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <div>
                            <label class="block text-sm font-medium text-slate-700" for="cancellationReason">
                                Cancellation Reason <span class="text-red-500">*</span>
                            </label>
                            <select
                                id="cancellationReason"
                                v-model="form.reason"
                                class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                required
                            >
                                <option value="">Select reason</option>
                                <option value="guest_request">Guest Request</option>
                                <option value="no_show">No Show</option>
                                <option value="double_booking">Double Booking</option>
                                <option value="maintenance">Maintenance Issue</option>
                                <option value="force_majeure">Force Majeure</option>
                                <option value="other">Other</option>
                            </select>
                        </div>

                        <div v-if="form.reason === 'other'">
                            <label class="block text-sm font-medium text-slate-700" for="customReason">
                                Additional Details
                            </label>
                            <textarea
                                id="customReason"
                                v-model="form.customReason"
                                class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                placeholder="Please provide additional details..."
                                rows="3"
                            ></textarea>
                        </div>

                        <div class="rounded-lg border border-amber-200 bg-amber-50 p-4">
                            <h4 class="mb-3 text-sm font-semibold text-amber-900">Cancellation Policy</h4>
                            <div class="space-y-2 text-sm text-amber-800">
                                <div class="flex items-start gap-2">
                                    <svg class="mt-0.5 h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path
                                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                        />
                                    </svg>
                                    <div>
                                        <p class="font-medium">{{ cancellationPolicy.title }}</p>
                                        <p class="text-xs text-amber-700">{{ cancellationPolicy.description }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                            <h4 class="mb-3 text-sm font-semibold text-slate-900">Refund Calculation</h4>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-slate-600">Original Amount:</span>
                                    <span class="font-medium text-slate-900">{{ formatCurrency(reservation.totalAmount) }} {{ reservation.currency || 'GBP' }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-slate-600">Cancellation Fee:</span>
                                    <span class="font-medium text-red-600">- {{ formatCurrency(cancellationFee) }} {{ reservation.currency || 'GBP' }}</span>
                                </div>
                                <div class="border-t border-slate-300 pt-2">
                                    <div class="flex justify-between">
                                        <span class="font-semibold text-slate-900">Refund Amount:</span>
                                        <span class="font-bold text-green-600">{{ formatCurrency(refundAmount) }} {{ reservation.currency || 'GBP' }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="flex items-center gap-2">
                                <input
                                    v-model="form.sendNotification"
                                    class="h-4 w-4 rounded border-slate-300"
                                    type="checkbox"
                                />
                                <span class="text-sm text-slate-700">Send cancellation email to guest</span>
                            </label>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700" for="adminNotes">
                                Admin Notes
                            </label>
                            <textarea
                                id="adminNotes"
                                v-model="form.adminNotes"
                                class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                placeholder="Internal notes about this cancellation..."
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
                            Keep Reservation
                        </button>
                        <button
                            :disabled="loading || !form.reason"
                            class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                            type="button"
                            @click="handleCancel"
                        >
                            <span v-if="!loading">Cancel Reservation</span>
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
                                Cancelling...
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { format, differenceInDays, differenceInHours } from 'date-fns';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    reservation: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(['close', 'submit']);

const form = ref({
    reason: '',
    customReason: '',
    sendNotification: true,
    adminNotes: '',
});

const loading = ref(false);
const error = ref('');

const cancellationPolicy = computed(() => {
    if (!props.reservation) {
        return { title: 'Standard Policy', description: 'Full refund if cancelled 48+ hours before check-in' };
    }

    const checkIn = props.reservation.checkIn instanceof Date
        ? props.reservation.checkIn
        : new Date(props.reservation.checkIn);
    const now = new Date();
    const hoursUntilCheckIn = differenceInHours(checkIn, now);

    if (hoursUntilCheckIn >= 48) {
        return {
            title: 'Full Refund',
            description: 'Cancelled 48+ hours before check-in. Full refund applies (minus processing fee).',
            refundPercentage: 100,
        };
    } else if (hoursUntilCheckIn >= 24) {
        return {
            title: 'Partial Refund (50%)',
            description: 'Cancelled 24-48 hours before check-in. 50% refund applies.',
            refundPercentage: 50,
        };
    } else if (hoursUntilCheckIn > 0) {
        return {
            title: 'Partial Refund (25%)',
            description: 'Cancelled less than 24 hours before check-in. 25% refund applies.',
            refundPercentage: 25,
        };
    } else {
        return {
            title: 'No Refund',
            description: 'Check-in date has passed. No refund available.',
            refundPercentage: 0,
        };
    }
});

const cancellationFee = computed(() => {
    if (!props.reservation) return 0;
    const total = props.reservation.totalAmount || 0;
    const processingFee = 10;
    return processingFee;
});

const refundAmount = computed(() => {
    if (!props.reservation) return 0;
    const total = props.reservation.totalAmount || 0;
    const fee = cancellationFee.value;
    const refundPercentage = cancellationPolicy.value.refundPercentage || 0;
    const refundableAmount = (total * refundPercentage) / 100;
    return Math.max(0, refundableAmount - fee);
});

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

watch(
    () => props.show,
    (isOpen) => {
        if (!isOpen) {
            form.value = {
                reason: '',
                customReason: '',
                sendNotification: true,
                adminNotes: '',
            };
            error.value = '';
        }
    }
);

function handleClose() {
    emit('close');
}

function handleCancel() {
    error.value = '';

    if (!form.value.reason) {
        error.value = 'Please select a cancellation reason.';
        return;
    }

    loading.value = true;

    setTimeout(() => {
        loading.value = false;
        emit('submit', {
            id: props.reservation?.id,
            reason: form.value.reason === 'other' ? form.value.customReason : form.value.reason,
            refundAmount: refundAmount.value,
            cancellationFee: cancellationFee.value,
            sendNotification: form.value.sendNotification,
            adminNotes: form.value.adminNotes,
            cancelledAt: new Date(),
        });
        handleClose();
    }, 500);
}
</script>




