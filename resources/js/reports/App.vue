<template>
    <div class="flex min-h-screen bg-slate-100">
        <SidebarNavigation />
        <div class="flex min-h-screen flex-1 flex-col">
            <TopNavbar />
            <header class="bg-white shadow-sm">
                <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-4 px-6 py-4">
                    <div class="space-y-1">
                        <h1 class="text-xl font-semibold text-slate-900">Reports & Analytics</h1>
                        <p class="text-sm text-slate-500">View comprehensive reports and analytics for your properties.</p>
                    </div>
                </div>
            </header>

            <main class="mx-auto w-full max-w-7xl flex-1 px-6 py-6">
                <!-- Report Type Tabs -->
                <div class="mb-6 border-b border-slate-200">
                    <nav class="-mb-px flex space-x-8">
                        <button
                            v-for="tab in tabs"
                            :key="tab.id"
                            :class="[
                                'whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium transition',
                                activeTab === tab.id
                                    ? 'border-blue-500 text-blue-600'
                                    : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700',
                            ]"
                            type="button"
                            @click="activeTab = tab.id; loadReport()"
                        >
                            {{ tab.name }}
                        </button>
                    </nav>
                </div>

                <!-- Filters -->
                <div class="mb-6 rounded-lg bg-white p-4 shadow-sm">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Property</label>
                            <select
                                v-model="filters.property_id"
                                class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                @change="loadReport()"
                            >
                                <option :value="null">All Properties</option>
                                <option
                                    v-for="property in properties"
                                    :key="property.id"
                                    :value="property.id"
                                >
                                    {{ property.name }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Start Date</label>
                            <input
                                v-model="filters.start_date"
                                class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                type="date"
                                @change="loadReport()"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">End Date</label>
                            <input
                                v-model="filters.end_date"
                                class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                type="date"
                                @change="loadReport()"
                            />
                        </div>
                        <div class="flex items-end">
                            <button
                                class="w-full rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-blue-700"
                                type="button"
                                @click="loadReport()"
                            >
                                Generate Report
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Loading State -->
                <div v-if="loading" class="flex items-center justify-center py-12">
                    <div class="text-center">
                        <svg class="mx-auto h-8 w-8 animate-spin text-blue-600" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" fill="currentColor"></path>
                        </svg>
                        <p class="mt-2 text-sm text-slate-600">Loading report...</p>
                    </div>
                </div>

                <!-- Report Content -->
                <div v-else-if="reportData" class="space-y-6">
                    <!-- Reservations Report -->
                    <div v-if="activeTab === 'reservations'" class="rounded-lg bg-white p-6 shadow-sm">
                        <h2 class="mb-4 text-lg font-semibold text-slate-900">Reservation Statistics</h2>
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                            <div class="rounded-lg bg-blue-50 p-4">
                                <div class="text-sm text-blue-600">Total Reservations</div>
                                <div class="mt-1 text-2xl font-bold text-blue-900">{{ reportData.statistics?.total || 0 }}</div>
                            </div>
                            <div class="rounded-lg bg-green-50 p-4">
                                <div class="text-sm text-green-600">Confirmed</div>
                                <div class="mt-1 text-2xl font-bold text-green-900">{{ reportData.statistics?.confirmed || 0 }}</div>
                            </div>
                            <div class="rounded-lg bg-yellow-50 p-4">
                                <div class="text-sm text-yellow-600">Pending</div>
                                <div class="mt-1 text-2xl font-bold text-yellow-900">{{ reportData.statistics?.pending || 0 }}</div>
                            </div>
                            <div class="rounded-lg bg-red-50 p-4">
                                <div class="text-sm text-red-600">Cancelled</div>
                                <div class="mt-1 text-2xl font-bold text-red-900">{{ reportData.statistics?.cancelled || 0 }}</div>
                            </div>
                        </div>
                        <div class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-3">
                            <div class="rounded-lg bg-slate-50 p-4">
                                <div class="text-sm text-slate-600">Total Revenue</div>
                                <div class="mt-1 text-xl font-bold text-slate-900">£{{ formatCurrency(reportData.statistics?.total_revenue || 0) }}</div>
                            </div>
                            <div class="rounded-lg bg-slate-50 p-4">
                                <div class="text-sm text-slate-600">Average Stay Length</div>
                                <div class="mt-1 text-xl font-bold text-slate-900">{{ reportData.statistics?.average_stay_length || 0 }} nights</div>
                            </div>
                            <div class="rounded-lg bg-slate-50 p-4">
                                <div class="text-sm text-slate-600">Average Daily Rate</div>
                                <div class="mt-1 text-xl font-bold text-slate-900">£{{ formatCurrency(reportData.statistics?.average_daily_rate || 0) }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Revenue Report -->
                    <div v-if="activeTab === 'revenue'" class="rounded-lg bg-white p-6 shadow-sm">
                        <h2 class="mb-4 text-lg font-semibold text-slate-900">Revenue Statistics</h2>
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                            <div class="rounded-lg bg-green-50 p-4">
                                <div class="text-sm text-green-600">Total Revenue</div>
                                <div class="mt-1 text-2xl font-bold text-green-900">£{{ formatCurrency(reportData.summary?.total_revenue || 0) }}</div>
                            </div>
                            <div class="rounded-lg bg-blue-50 p-4">
                                <div class="text-sm text-blue-600">Total Paid</div>
                                <div class="mt-1 text-2xl font-bold text-blue-900">£{{ formatCurrency(reportData.summary?.total_paid || 0) }}</div>
                            </div>
                            <div class="rounded-lg bg-yellow-50 p-4">
                                <div class="text-sm text-yellow-600">Balance Due</div>
                                <div class="mt-1 text-2xl font-bold text-yellow-900">£{{ formatCurrency(reportData.summary?.total_balance || 0) }}</div>
                            </div>
                            <div class="rounded-lg bg-purple-50 p-4">
                                <div class="text-sm text-purple-600">Avg Transaction</div>
                                <div class="mt-1 text-2xl font-bold text-purple-900">£{{ formatCurrency(reportData.summary?.average_transaction || 0) }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Occupancy Report -->
                    <div v-if="activeTab === 'occupancy'" class="rounded-lg bg-white p-6 shadow-sm">
                        <h2 class="mb-4 text-lg font-semibold text-slate-900">Occupancy Statistics</h2>
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                            <div class="rounded-lg bg-blue-50 p-4">
                                <div class="text-sm text-blue-600">Total Rooms</div>
                                <div class="mt-1 text-2xl font-bold text-blue-900">{{ reportData.total_rooms || 0 }}</div>
                            </div>
                            <div class="rounded-lg bg-green-50 p-4">
                                <div class="text-sm text-green-600">Average Occupancy Rate</div>
                                <div class="mt-1 text-2xl font-bold text-green-900">{{ reportData.average_occupancy_rate || 0 }}%</div>
                            </div>
                        </div>
                        <div v-if="reportData.by_room_type && reportData.by_room_type.length > 0" class="mt-6">
                            <h3 class="mb-3 text-md font-semibold text-slate-700">Occupancy by Room Type</h3>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-slate-200">
                                    <thead class="bg-slate-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Room Type</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Total Rooms</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Occupied Nights</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Occupancy Rate</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-200 bg-white">
                                        <tr v-for="item in reportData.by_room_type" :key="item.room_type_id">
                                            <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-900">{{ item.room_type_name }}</td>
                                            <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-500">{{ item.total_rooms }}</td>
                                            <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-500">{{ item.occupied_nights }}</td>
                                            <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-500">{{ item.occupancy_rate }}%</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Guests Report -->
                    <div v-if="activeTab === 'guests'" class="rounded-lg bg-white p-6 shadow-sm">
                        <h2 class="mb-4 text-lg font-semibold text-slate-900">Guest Statistics</h2>
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                            <div class="rounded-lg bg-blue-50 p-4">
                                <div class="text-sm text-blue-600">Total Guests</div>
                                <div class="mt-1 text-2xl font-bold text-blue-900">{{ reportData.statistics?.total_guests || 0 }}</div>
                            </div>
                            <div class="rounded-lg bg-green-50 p-4">
                                <div class="text-sm text-green-600">Active Guests</div>
                                <div class="mt-1 text-2xl font-bold text-green-900">{{ reportData.statistics?.active_guests || 0 }}</div>
                            </div>
                            <div class="rounded-lg bg-yellow-50 p-4">
                                <div class="text-sm text-yellow-600">New Guests</div>
                                <div class="mt-1 text-2xl font-bold text-yellow-900">{{ reportData.statistics?.new_guests || 0 }}</div>
                            </div>
                            <div class="rounded-lg bg-purple-50 p-4">
                                <div class="text-sm text-purple-600">Repeat Guests</div>
                                <div class="mt-1 text-2xl font-bold text-purple-900">{{ reportData.statistics?.repeat_guests || 0 }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Dashboard Report -->
                    <div v-if="activeTab === 'dashboard'" class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
                        <div class="rounded-lg bg-white p-6 shadow-sm">
                            <div class="text-sm text-slate-600">Today's Check-ins</div>
                            <div class="mt-2 text-3xl font-bold text-slate-900">{{ reportData.today_check_ins || 0 }}</div>
                        </div>
                        <div class="rounded-lg bg-white p-6 shadow-sm">
                            <div class="text-sm text-slate-600">Today's Check-outs</div>
                            <div class="mt-2 text-3xl font-bold text-slate-900">{{ reportData.today_check_outs || 0 }}</div>
                        </div>
                        <div class="rounded-lg bg-white p-6 shadow-sm">
                            <div class="text-sm text-slate-600">Current Occupancy</div>
                            <div class="mt-2 text-3xl font-bold text-slate-900">{{ reportData.current_occupancy || 0 }}</div>
                        </div>
                        <div class="rounded-lg bg-white p-6 shadow-sm">
                            <div class="text-sm text-slate-600">This Month Revenue</div>
                            <div class="mt-2 text-3xl font-bold text-slate-900">£{{ formatCurrency(reportData.this_month_revenue || 0) }}</div>
                            <div :class="[
                                'mt-1 text-sm',
                                (reportData.revenue_change_percent || 0) >= 0 ? 'text-green-600' : 'text-red-600'
                            ]">
                                {{ reportData.revenue_change_percent >= 0 ? '+' : '' }}{{ reportData.revenue_change_percent || 0 }}% vs last month
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else-if="!loading" class="rounded-lg bg-white p-12 text-center shadow-sm">
                    <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    <h3 class="mt-4 text-lg font-medium text-slate-900">No data available</h3>
                    <p class="mt-2 text-sm text-slate-500">Select filters and generate a report to view data.</p>
                </div>
            </main>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import SidebarNavigation from '../shared/components/SidebarNavigation.vue';
import TopNavbar from '../shared/components/TopNavbar.vue';

const activeTab = ref('dashboard');
const loading = ref(false);
const reportData = ref(null);
const properties = ref([]);

const tabs = [
    { id: 'dashboard', name: 'Dashboard' },
    { id: 'reservations', name: 'Reservations' },
    { id: 'revenue', name: 'Revenue' },
    { id: 'occupancy', name: 'Occupancy' },
    { id: 'guests', name: 'Guests' },
];

const filters = ref({
    property_id: null,
    start_date: new Date().toISOString().split('T')[0].substring(0, 7) + '-01',
    end_date: new Date().toISOString().split('T')[0],
});

async function loadProperties() {
    try {
        const response = await fetch('/api/properties');
        const result = await response.json();
        if (result.success) {
            properties.value = result.data.data || result.data;
        }
    } catch (error) {
        console.error('Error loading properties:', error);
    }
}

async function loadReport() {
    try {
        loading.value = true;
        const queryParams = new URLSearchParams();

        if (filters.value.property_id) {
            queryParams.append('property_id', filters.value.property_id);
        }
        if (filters.value.start_date) {
            queryParams.append('start_date', filters.value.start_date);
        }
        if (filters.value.end_date) {
            queryParams.append('end_date', filters.value.end_date);
        }

        const endpoint = `/api/reports/${activeTab.value}`;
        const url = queryParams.toString() ? `${endpoint}?${queryParams.toString()}` : endpoint;

        const response = await fetch(url);
        const result = await response.json();

        if (result.success) {
            reportData.value = result.data;
        } else {
            window.toastr?.error(result.message || 'Failed to load report');
        }
    } catch (error) {
        console.error('Error loading report:', error);
        window.toastr?.error('Failed to load report. Please try again.');
    } finally {
        loading.value = false;
    }
}

function formatCurrency(value) {
    return Number(value).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}

onMounted(() => {
    loadProperties();
    loadReport();
});
</script>

