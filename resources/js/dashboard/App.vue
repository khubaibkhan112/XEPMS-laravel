<template>
    <div class="flex min-h-screen bg-slate-100">
        <SidebarNavigation />
        <div class="flex min-h-screen flex-1 flex-col">
            <TopNavbar />
            <main class="mx-auto w-full max-w-7xl flex-1 px-6 py-6">
                <!-- Page Header -->
                <div class="mb-6">
                    <h1 class="text-2xl font-bold text-slate-900">Dashboard</h1>
                    <p class="text-sm text-slate-600">Overview of your property performance and channel connections</p>
                </div>

                <!-- Stats Cards -->
                <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <StatCard
                        title="Occupancy Rate"
                        :value="`${stats.occupancyRate}%`"
                        :change="stats.occupancyChange"
                        icon="bed"
                        color="blue"
                    />
                    <StatCard
                        title="Total Revenue"
                        :value="formatCurrency(stats.totalRevenue)"
                        :change="stats.revenueChange"
                        icon="currency"
                        color="green"
                    />
                    <StatCard
                        title="Active Reservations"
                        :value="stats.activeReservations"
                        :change="stats.reservationsChange"
                        icon="calendar"
                        color="purple"
                    />
                    <StatCard
                        title="Connected Channels"
                        :value="stats.connectedChannels"
                        :change="null"
                        icon="link"
                        color="orange"
                    />
                </div>

                <!-- Main Content Grid -->
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <!-- Recent Reservations -->
                    <div class="lg:col-span-2">
                        <div class="rounded-lg bg-white shadow-sm ring-1 ring-slate-200">
                            <div class="border-b border-slate-200 px-6 py-4">
                                <div class="flex items-center justify-between">
                                    <h2 class="text-lg font-semibold text-slate-900">Recent Reservations</h2>
                                    <a
                                        class="text-sm font-medium text-blue-600 transition hover:text-blue-700"
                                        href="/admin/calendar"
                                    >
                                        View All
                                    </a>
                                </div>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full">
                                    <thead class="bg-slate-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">
                                                Guest
                                            </th>
                                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">
                                                Room
                                            </th>
                                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">
                                                Dates
                                            </th>
                                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">
                                                Source
                                            </th>
                                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">
                                                Status
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-200 bg-white">
                                        <tr
                                            v-for="reservation in recentReservations"
                                            :key="reservation.id"
                                            class="transition hover:bg-slate-50"
                                        >
                                            <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-slate-900">
                                                {{ reservation.guestName }}
                                            </td>
                                            <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-600">
                                                {{ reservation.room }}
                                            </td>
                                            <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-600">
                                                {{ reservation.dates }}
                                            </td>
                                            <td class="whitespace-nowrap px-6 py-4">
                                                <span
                                                    class="inline-flex rounded-full px-2 py-1 text-xs font-medium"
                                                    :class="getSourceClass(reservation.source)"
                                                >
                                                    {{ reservation.source }}
                                                </span>
                                            </td>
                                            <td class="whitespace-nowrap px-6 py-4">
                                                <span
                                                    class="inline-flex rounded-full px-2 py-1 text-xs font-medium capitalize"
                                                    :class="getStatusClass(reservation.status)"
                                                >
                                                    {{ reservation.status }}
                                                </span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Channel Connections & Quick Actions -->
                    <div class="space-y-6">
                        <!-- Channel Connections -->
                        <div class="rounded-lg bg-white shadow-sm ring-1 ring-slate-200">
                            <div class="border-b border-slate-200 px-6 py-4">
                                <h2 class="text-lg font-semibold text-slate-900">Channel Connections</h2>
                            </div>
                            <div class="px-6 py-4">
                                <div class="space-y-3">
                                    <ChannelStatus
                                        v-for="channel in channels"
                                        :key="channel.name"
                                        :name="channel.name"
                                        :status="channel.status"
                                        :last-sync="channel.lastSync"
                                        :reservations="channel.reservations"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Quick Actions -->
                        <div class="rounded-lg bg-white shadow-sm ring-1 ring-slate-200">
                            <div class="border-b border-slate-200 px-6 py-4">
                                <h2 class="text-lg font-semibold text-slate-900">Quick Actions</h2>
                            </div>
                            <div class="px-6 py-4">
                                <div class="space-y-2">
                                    <a
                                        class="flex w-full items-center justify-between rounded-lg border border-slate-200 bg-white px-4 py-3 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                                        href="/admin/calendar"
                                    >
                                        <span>New Reservation</span>
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path d="M12 4v16m8-8H4" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                    </a>
                                    <button
                                        class="flex w-full items-center justify-between rounded-lg border border-slate-200 bg-white px-4 py-3 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                                        type="button"
                                    >
                                        <span>Sync Channels</span>
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path
                                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                            />
                                        </svg>
                                    </button>
                                    <button
                                        class="flex w-full items-center justify-between rounded-lg border border-slate-200 bg-white px-4 py-3 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                                        type="button"
                                    >
                                        <span>Block Dates</span>
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path
                                                d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                            />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Occupancy Chart -->
                <div class="mt-6 rounded-lg bg-white shadow-sm ring-1 ring-slate-200">
                    <div class="border-b border-slate-200 px-6 py-4">
                        <h2 class="text-lg font-semibold text-slate-900">7-Day Occupancy Trend</h2>
                    </div>
                    <div class="px-6 py-6">
                        <OccupancyChart :data="occupancyData" />
                    </div>
                </div>
            </main>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import SidebarNavigation from '../shared/components/SidebarNavigation.vue';
import TopNavbar from '../shared/components/TopNavbar.vue';
import StatCard from './components/StatCard.vue';
import ChannelStatus from './components/ChannelStatus.vue';
import OccupancyChart from './components/OccupancyChart.vue';
import { mockDashboardData } from './data/dashboard-data';

const stats = ref(mockDashboardData.stats);
const recentReservations = ref(mockDashboardData.recentReservations);
const channels = ref(mockDashboardData.channels);
const occupancyData = ref(mockDashboardData.occupancyData);

function formatCurrency(amount) {
    return new Intl.NumberFormat('en-GB', {
        style: 'currency',
        currency: 'GBP',
    }).format(amount);
}

function getStatusClass(status) {
    const classes = {
        confirmed: 'bg-green-100 text-green-800',
        pending: 'bg-yellow-100 text-yellow-800',
        checked_in: 'bg-blue-100 text-blue-800',
        checked_out: 'bg-slate-100 text-slate-800',
        cancelled: 'bg-red-100 text-red-800',
    };
    return classes[status] || 'bg-slate-100 text-slate-800';
}

function getSourceClass(source) {
    const classes = {
        'Booking.com': 'bg-orange-100 text-orange-800',
        Expedia: 'bg-blue-100 text-blue-800',
        Airbnb: 'bg-pink-100 text-pink-800',
        Direct: 'bg-green-100 text-green-800',
        'Hotels.com': 'bg-purple-100 text-purple-800',
    };
    return classes[source] || 'bg-slate-100 text-slate-800';
}
</script>

