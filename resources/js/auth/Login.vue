<template>
    <div class="flex min-h-screen items-center justify-center bg-gradient-to-br from-blue-50 to-indigo-100 px-4 py-12">
        <div class="w-full max-w-md">
            <div class="rounded-2xl bg-white p-8 shadow-xl">
                <div class="mb-8 text-center">
                    <h1 class="text-3xl font-bold text-slate-900">XEPMS</h1>
                    <p class="mt-2 text-sm text-slate-600">Sign in to your account</p>
                </div>

                <form @submit.prevent="handleLogin" class="space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-slate-700" for="email">
                            Email address
                        </label>
                        <input
                            id="email"
                            v-model="form.email"
                            class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                            placeholder="you@example.com"
                            required
                            type="email"
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700" for="password">
                            Password
                        </label>
                        <input
                            id="password"
                            v-model="form.password"
                            class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                            placeholder="••••••••"
                            required
                            type="password"
                        />
                    </div>

                    <div class="flex items-center justify-between">
                        <label class="flex items-center">
                            <input v-model="form.remember" class="h-4 w-4 rounded border-slate-300" type="checkbox" />
                            <span class="ml-2 text-sm text-slate-600">Remember me</span>
                        </label>
                        <a class="text-sm font-medium text-blue-600 hover:text-blue-500" href="#">
                            Forgot password?
                        </a>
                    </div>

                    <div v-if="error" class="rounded-lg bg-red-50 p-3 text-sm text-red-600">
                        {{ error }}
                    </div>

                    <button
                        :disabled="loading"
                        class="w-full rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                        type="submit"
                    >
                        <span v-if="!loading">Sign in</span>
                        <span v-else class="flex items-center justify-center gap-2">
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
                            Signing in...
                        </span>
                    </button>
                </form>

                <div class="mt-6 text-center text-sm text-slate-600">
                    Don't have an account?
                    <a class="font-medium text-blue-600 hover:text-blue-500" href="#">Contact administrator</a>
                </div>
            </div>

            <p class="mt-6 text-center text-xs text-slate-500">
                © 2025 Channel Manager. All rights reserved.
            </p>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';

const form = ref({
    email: '',
    password: '',
    remember: false,
});

const loading = ref(false);
const error = ref('');

async function handleLogin() {
    error.value = '';
    loading.value = true;

    try {
        // Get CSRF token with error checking
        const csrfTokenElement = document.querySelector('meta[name="csrf-token"]');
        if (!csrfTokenElement) {
            throw new Error('CSRF token meta tag not found. Please ensure the login page includes the CSRF token meta tag.');
        }

        const csrfToken = csrfTokenElement.content;
        if (!csrfToken) {
            throw new Error('CSRF token is empty. Please refresh the page and try again.');
        }

        const response = await fetch('/api/auth/login', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({
                email: form.value.email,
                password: form.value.password,
                remember: form.value.remember,
            }),
        });

        if (!response.ok) {
            // Handle HTTP errors
            let errorMessage = `HTTP ${response.status}: ${response.statusText}`;
            try {
                const errorData = await response.json();
                errorMessage = errorData.message || errorMessage;
                if (errorData.errors) {
                    const errorMessages = Object.values(errorData.errors).flat();
                    errorMessage = errorMessages.join(', ') || errorMessage;
                }
            } catch (e) {
                // If response is not JSON, use status text
                errorMessage = `Server error: ${response.status} ${response.statusText}`;
            }
            throw new Error(errorMessage);
        }

        const result = await response.json();

        if (result.success) {
            // Login successful, redirect to dashboard
            window.location.href = '/admin/dashboard';
        } else {
            // Show error message from server
            error.value = result.message || 'Login failed. Please check your credentials.';
            if (result.errors) {
                const errorMessages = Object.values(result.errors).flat();
                if (errorMessages.length > 0) {
                    error.value = errorMessages.join(', ');
                }
            }
            loading.value = false;
        }
    } catch (err) {
        // Enhanced error logging with line numbers and details
        const errorDetails = {
            message: err.message,
            stack: err.stack,
            name: err.name,
            line: err.line || 'Unknown',
            file: err.fileName || 'Login.vue',
        };
        
        console.error('Login error at Login.vue:', errorDetails);
        console.error('Error details:', {
            message: err.message,
            stack: err.stack,
            type: typeof err,
            constructor: err.constructor?.name,
        });
        
        error.value = err.message || 'An error occurred. Please try again.';
        loading.value = false;
    }
}
</script>

