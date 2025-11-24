<template>
    <nav class="border-b border-slate-200 bg-white shadow-sm">
        <div class="mx-auto flex max-w-full items-center justify-between px-6 py-3">
            <div class="flex items-center gap-4">
                <h2 class="text-lg font-semibold text-slate-900">XEPMS</h2>
            </div>
            <div class="relative">
                <button
                    ref="profileButtonRef"
                    class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm transition hover:bg-slate-50"
                    type="button"
                    @click="toggleDropdown"
                >
                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-600 text-xs font-semibold text-white">
                        {{ userInitials }}
                    </div>
                    <span class="hidden font-medium text-slate-700 sm:block">{{ userName }}</span>
                    <svg
                        class="h-4 w-4 text-slate-500 transition"
                        :class="{ 'rotate-180': showDropdown }"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        viewBox="0 0 24 24"
                    >
                        <path d="M19 9l-7 7-7-7" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </button>
                <div
                    v-if="showDropdown"
                    v-click-outside="closeDropdown"
                    class="absolute right-0 top-full z-50 mt-2 w-56 rounded-lg border border-slate-200 bg-white shadow-lg"
                >
                    <div class="border-b border-slate-200 px-4 py-3">
                        <p class="text-sm font-medium text-slate-900">{{ userName }}</p>
                        <p class="text-xs text-slate-500">{{ userEmail }}</p>
                    </div>
                    <div class="py-1">
                        <a
                            class="flex items-center gap-3 px-4 py-2 text-sm text-slate-700 transition hover:bg-slate-50"
                            href="#"
                            @click.prevent="handleSettings"
                        >
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path
                                    d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                />
                                <path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            Settings
                        </a>
                        <a
                            class="flex items-center gap-3 px-4 py-2 text-sm text-slate-700 transition hover:bg-slate-50"
                            href="#"
                            @click.prevent="handleProfile"
                        >
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path
                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                />
                            </svg>
                            Profile
                        </a>
                        <a
                            class="flex items-center gap-3 px-4 py-2 text-sm text-slate-700 transition hover:bg-slate-50"
                            href="#"
                            @click.prevent="handleHelp"
                        >
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path
                                    d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                />
                            </svg>
                            Help & Support
                        </a>
                    </div>
                    <div class="border-t border-slate-200 py-1">
                        <a
                            class="flex items-center gap-3 px-4 py-2 text-sm text-red-600 transition hover:bg-red-50"
                            href="#"
                            @click.prevent="handleLogout"
                        >
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path
                                    d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                />
                            </svg>
                            Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

const showDropdown = ref(false);
const profileButtonRef = ref(null);

const userName = ref('Admin User');
const userEmail = ref('admin@example.com');

const userInitials = computed(() => {
    const parts = userName.value.split(' ');
    if (parts.length >= 2) {
        return (parts[0][0] + parts[1][0]).toUpperCase();
    }
    return userName.value.substring(0, 2).toUpperCase();
});

function toggleDropdown() {
    showDropdown.value = !showDropdown.value;
}

function closeDropdown() {
    showDropdown.value = false;
}

function handleSettings() {
    closeDropdown();
    console.log('Settings clicked');
}

function handleProfile() {
    closeDropdown();
    console.log('Profile clicked');
}

function handleHelp() {
    closeDropdown();
    console.log('Help clicked');
}

function handleLogout() {
    closeDropdown();
    window.location.href = '/login';
}

const vClickOutside = {
    mounted(el, binding) {
        el.clickOutsideEvent = (event) => {
            if (!(el === event.target || el.contains(event.target))) {
                binding.value();
            }
        };
        document.addEventListener('click', el.clickOutsideEvent);
    },
    unmounted(el) {
        document.removeEventListener('click', el.clickOutsideEvent);
    },
};

onMounted(() => {
    document.addEventListener('click', (e) => {
        if (profileButtonRef.value && !profileButtonRef.value.contains(e.target)) {
            closeDropdown();
        }
    });
});
</script>

