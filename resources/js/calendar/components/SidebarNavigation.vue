<template>
    <aside class="flex w-64 flex-col border-r border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-6 py-5">
            <p class="text-2xl font-bold text-blue-600">XEPMS</p>
        </div>
        <nav class="flex-1 overflow-y-auto px-2 py-4 text-sm font-medium text-slate-600">
            <div class="px-2 text-xs uppercase tracking-wide text-slate-400">Main</div>
            <a
                class="mt-1 flex w-full items-center justify-between rounded-lg px-4 py-2 text-left transition hover:bg-slate-50"
                :class="isActive('/admin/dashboard') ? 'bg-blue-50 text-blue-700' : ''"
                href="/admin/dashboard"
            >
                <span>Dashboard</span>
            </a>
            <a
                class="mt-1 flex w-full items-center justify-between rounded-lg px-4 py-2 text-left transition hover:bg-slate-50"
                :class="isActive('/admin/calendar') ? 'bg-blue-50 text-blue-700' : ''"
                href="/admin/calendar"
            >
                <span>Calendar</span>
                <span class="rounded-full bg-blue-600 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-white">
                    Live
                </span>
            </a>
            <a
                class="mt-1 flex w-full items-center justify-between rounded-lg px-4 py-2 text-left transition hover:bg-slate-50"
                :class="isActive('/admin/reservations') ? 'bg-blue-50 text-blue-700' : ''"
                href="/admin/calendar"
            >
                <span>Reservations</span>
            </a>
            <a
                class="mt-1 flex w-full items-center justify-between rounded-lg px-4 py-2 text-left transition hover:bg-slate-50"
                :class="isActive('/admin/availability') ? 'bg-blue-50 text-blue-700' : ''"
                href="/admin/calendar"
            >
                <span>Availability</span>
            </a>
            <a
                class="mt-1 flex w-full items-center justify-between rounded-lg px-4 py-2 text-left transition hover:bg-slate-50"
                :class="isActive('/admin/channels') ? 'bg-blue-50 text-blue-700' : ''"
                href="/admin/calendar"
            >
                <span>Channel Manager</span>
            </a>

            <div class="px-2 pt-6 text-xs uppercase tracking-wide text-slate-400">Reports</div>
            <button
                class="mt-1 flex w-full items-center justify-between rounded-lg px-4 py-2 text-left transition hover:bg-slate-50"
                type="button"
            >
                <span>Occupancy</span>
            </button>
            <button
                class="mt-1 flex w-full items-center justify-between rounded-lg px-4 py-2 text-left transition hover:bg-slate-50"
                type="button"
            >
                <span>Revenue</span>
            </button>
        </nav>
        <div class="border-t border-slate-200 px-4 py-4 text-sm">
            <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-3">
                <p class="text-xs uppercase tracking-wide text-slate-400">Property</p>
                <p class="text-sm font-semibold text-slate-800">London Suites</p>
                <p class="text-xs text-slate-500">6 rooms online</p>
            </div>
        </div>
    </aside>
</template>

<script setup>
function isActive(path) {
    return window.location.pathname === path;
}
</script>

