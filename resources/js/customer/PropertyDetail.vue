<template>
    <div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100">
        <!-- Header -->
        <header class="bg-white shadow-md sticky top-0 z-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                <div class="flex items-center justify-between">
                    <a href="/" class="flex items-center text-blue-600 hover:text-blue-800 transition-colors">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Back to Search
                    </a>
                    <a href="/login" class="text-gray-600 hover:text-gray-900 transition-colors">Admin Login</a>
                </div>
            </div>
        </header>

        <!-- Loading State -->
        <div v-if="loading" class="flex items-center justify-center min-h-screen">
            <div class="text-center">
                <div class="inline-block animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-blue-600 mb-4"></div>
                <p class="text-gray-600">Loading property details...</p>
            </div>
        </div>

        <div v-else-if="property" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Property Hero Section -->
            <div class="bg-gradient-to-r from-blue-600 to-blue-800 rounded-2xl shadow-xl p-8 mb-8 text-white relative overflow-hidden">
                <div class="absolute inset-0 bg-black opacity-10"></div>
                <div class="relative z-10">
                    <h1 class="text-4xl font-bold mb-3">{{ property.name }}</h1>
                    <div class="flex items-center mb-4 text-blue-100">
                        <svg v-if="property.address" class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <span>{{ property.address }}</span>
                    </div>
                    <div class="flex flex-wrap items-center gap-6 text-sm">
                        <div v-if="property.phone" class="flex items-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                            </svg>
                            {{ property.phone }}
                        </div>
                        <div v-if="property.email" class="flex items-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            {{ property.email }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Room Types -->
                <div class="lg:col-span-2 space-y-6">
                    <div class="bg-white rounded-xl shadow-lg p-6">
                        <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
                            <svg class="w-6 h-6 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                            Available Room Types
                        </h2>
                        <div class="space-y-4">
                            <div
                                v-for="roomType in property.room_types"
                                :key="roomType.id"
                                :class="[
                                    'rounded-xl border-2 p-6 transition-all duration-300 cursor-pointer',
                                    bookingForm.room_type_id == roomType.id 
                                        ? 'border-blue-600 bg-blue-50 shadow-md' 
                                        : 'border-gray-200 bg-white hover:border-blue-300 hover:shadow-md'
                                ]"
                                @click="selectRoomType(roomType.id)"
                            >
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center mb-2">
                                            <h3 class="text-xl font-bold text-gray-900 mr-3">{{ roomType.name }}</h3>
                                            <span v-if="bookingForm.room_type_id == roomType.id" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-600 text-white">
                                                Selected
                                            </span>
                                        </div>
                                        <p class="text-gray-600 mb-4">{{ roomType.description || 'Comfortable and well-appointed room for your stay.' }}</p>
                                        <div class="flex items-center gap-4 text-sm text-gray-500">
                                            <span class="flex items-center">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                                </svg>
                                                Max {{ roomType.max_occupancy }} guests
                                            </span>
                                        </div>
                                    </div>
                                    <div class="ml-4 text-right">
                                        <div class="text-2xl font-bold text-blue-600">
                                            {{ formatCurrency(roomType.base_rate || 0, property.currency) }}
                                        </div>
                                        <div class="text-sm text-gray-500">per night</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Booking Form -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-xl shadow-xl p-6 sticky top-24 border border-gray-100">
                        <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
                            <svg class="w-6 h-6 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Book Your Stay
                        </h2>
                        <form @submit.prevent="submitBooking" class="space-y-6">
                            <!-- Dates Section -->
                            <div class="space-y-4">
                                <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                                    <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    Select Dates
                                </h3>
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="relative">
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Check-in</label>
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                </svg>
                                            </div>
                                            <input
                                                v-model="bookingForm.check_in"
                                                type="date"
                                                :min="minDate"
                                                required
                                                class="block w-full pl-10 pr-3 py-3 border-2 border-gray-200 rounded-xl bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 shadow-sm hover:border-gray-300"
                                                @change="checkAvailability"
                                            />
                                        </div>
                                    </div>
                                    <div class="relative">
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Check-out</label>
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                </svg>
                                            </div>
                                            <input
                                                v-model="bookingForm.check_out"
                                                type="date"
                                                :min="bookingForm.check_in || minDate"
                                                required
                                                class="block w-full pl-10 pr-3 py-3 border-2 border-gray-200 rounded-xl bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 shadow-sm hover:border-gray-300"
                                                @change="checkAvailability"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Guests Section -->
                            <div class="space-y-4">
                                <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                                    <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                    Guests
                                </h3>
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="relative">
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Adults</label>
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                </svg>
                                            </div>
                                            <input
                                                v-model.number="bookingForm.adult_count"
                                                type="number"
                                                min="1"
                                                required
                                                class="block w-full pl-10 pr-3 py-3 border-2 border-gray-200 rounded-xl bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 shadow-sm hover:border-gray-300"
                                                @change="calculatePricing"
                                            />
                                        </div>
                                    </div>
                                    <div class="relative">
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Children</label>
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                                </svg>
                                            </div>
                                            <input
                                                v-model.number="bookingForm.child_count"
                                                type="number"
                                                min="0"
                                                class="block w-full pl-10 pr-3 py-3 border-2 border-gray-200 rounded-xl bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 shadow-sm hover:border-gray-300"
                                                @change="calculatePricing"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Availability Status -->
                            <div v-if="availabilityError" class="bg-red-50 border-l-4 border-red-500 rounded-lg p-4 flex items-start">
                                <svg class="w-5 h-5 text-red-500 mr-3 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <p class="text-sm text-red-700 font-medium">{{ availabilityError }}</p>
                            </div>

                            <div v-if="availabilityChecked && !availabilityError" class="bg-green-50 border-l-4 border-green-500 rounded-lg p-4 flex items-start">
                                <svg class="w-5 h-5 text-green-500 mr-3 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <div>
                                    <p class="text-sm text-green-700 font-medium">Available for your dates!</p>
                                    <p v-if="pricingDetails" class="text-xs text-green-600 mt-1">
                                        {{ pricingDetails.nights }} {{ pricingDetails.nights === 1 ? 'night' : 'nights' }} stay
                                    </p>
                                </div>
                            </div>

                            <!-- Guest Information -->
                            <div class="border-t border-gray-200 pt-6 space-y-4">
                                <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                                    <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    Guest Information
                                </h3>
                                <div class="relative">
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        First Name
                                        <span class="text-red-500 ml-1">*</span>
                                    </label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                            </svg>
                                        </div>
                                        <input
                                            v-model="bookingForm.guest_first_name"
                                            type="text"
                                            required
                                            class="block w-full pl-10 pr-3 py-3 border-2 border-gray-200 rounded-xl bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 shadow-sm hover:border-gray-300"
                                            placeholder="John"
                                        />
                                    </div>
                                </div>
                                <div class="relative">
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        Last Name
                                        <span class="text-red-500 ml-1">*</span>
                                    </label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                            </svg>
                                        </div>
                                        <input
                                            v-model="bookingForm.guest_last_name"
                                            type="text"
                                            required
                                            class="block w-full pl-10 pr-3 py-3 border-2 border-gray-200 rounded-xl bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 shadow-sm hover:border-gray-300"
                                            placeholder="Doe"
                                        />
                                    </div>
                                </div>
                                <div class="relative">
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        Email Address
                                        <span class="text-red-500 ml-1">*</span>
                                    </label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                            </svg>
                                        </div>
                                        <input
                                            v-model="bookingForm.guest_email"
                                            type="email"
                                            required
                                            class="block w-full pl-10 pr-3 py-3 border-2 border-gray-200 rounded-xl bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 shadow-sm hover:border-gray-300"
                                            placeholder="john@example.com"
                                        />
                                    </div>
                                </div>
                                <div class="relative">
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        Phone Number
                                        <span class="text-gray-400 text-xs font-normal ml-1">(Optional)</span>
                                    </label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                            </svg>
                                        </div>
                                        <input
                                            v-model="bookingForm.guest_phone"
                                            type="tel"
                                            class="block w-full pl-10 pr-3 py-3 border-2 border-gray-200 rounded-xl bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 shadow-sm hover:border-gray-300"
                                            placeholder="+1 (555) 123-4567"
                                        />
                                    </div>
                                </div>
                            </div>

                            <!-- Pricing Summary -->
                            <div class="border-t border-gray-200 pt-6 space-y-4 bg-gray-50 rounded-lg p-4 -mx-2">
                                <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                                    <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                    </svg>
                                    Price Summary
                                </h3>
                                <div v-if="pricingDetails" class="space-y-3">
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-gray-600">Room Subtotal</span>
                                        <span class="text-gray-900 font-medium">{{ formatCurrency(pricingDetails.room_subtotal || pricingDetails.subtotal || 0, property?.currency || 'GBP') }}</span>
                                    </div>
                                    <div v-if="pricingDetails.total_tax > 0" class="flex items-center justify-between text-sm">
                                        <span class="text-gray-600">Taxes & Fees</span>
                                        <span class="text-gray-900 font-medium">{{ formatCurrency(pricingDetails.total_tax || 0, property?.currency || 'GBP') }}</span>
                                    </div>
                                    <div v-if="pricingDetails.tax_breakdown && pricingDetails.tax_breakdown.length > 0" class="ml-4 space-y-1 text-xs text-gray-500 border-l-2 border-gray-200 pl-3">
                                        <div v-for="tax in pricingDetails.tax_breakdown" :key="tax.tax_rate_id" class="flex justify-between">
                                            <span>{{ tax.name }} ({{ tax.rate }}%):</span>
                                            <span>{{ formatCurrency(tax.amount || 0, property?.currency || 'GBP') }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center justify-between pt-4 border-t-2 border-gray-300">
                                    <span class="text-lg font-bold text-gray-900">Total</span>
                                    <span class="text-3xl font-bold text-blue-600">
                                        {{ displayTotal }}
                                    </span>
                                </div>
                            </div>

                            <button
                                type="submit"
                                :disabled="submitting || !availabilityChecked || availabilityError"
                                class="w-full bg-gradient-to-r from-blue-600 to-blue-700 text-white py-4 px-6 rounded-lg hover:from-blue-700 hover:to-blue-800 disabled:opacity-50 disabled:cursor-not-allowed font-semibold text-lg shadow-lg hover:shadow-xl transition-all duration-200 transform hover:scale-[1.02] disabled:transform-none flex items-center justify-center"
                            >
                                <span v-if="submitting" class="flex items-center">
                                    <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Processing...
                                </span>
                                <span v-else class="flex items-center">
                                    Complete Booking
                                    <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                    </svg>
                                </span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';

const props = defineProps({
    propertyId: {
        type: String,
        required: true,
    },
});

const property = ref(null);
const loading = ref(true);
const submitting = ref(false);
const availabilityChecked = ref(false);
const availabilityError = ref('');
const totalAmount = ref('');
const pricingDetails = ref(null);
const minDate = new Date().toISOString().split('T')[0];

const bookingForm = ref({
    room_type_id: '',
    check_in: '',
    check_out: '',
    adult_count: 1,
    child_count: 0,
    guest_first_name: '',
    guest_last_name: '',
    guest_email: '',
    guest_phone: '',
});

const loadProperty = async () => {
    loading.value = true;
    try {
        const response = await fetch(`/public/api/properties/${props.propertyId}`);
        const data = await response.json();

        if (data.success) {
            property.value = data.data;
            // Select the first room type by default
            if (property.value.room_types && property.value.room_types.length > 0) {
                bookingForm.value.room_type_id = property.value.room_types[0].id;
                // Calculate pricing if dates are already set
                if (bookingForm.value.check_in && bookingForm.value.check_out) {
                    calculatePricing();
                }
            }
        }
    } catch (error) {
        console.error('Error loading property:', error);
    } finally {
        loading.value = false;
    }
};

const checkAvailability = async () => {
    if (!bookingForm.value.check_in || !bookingForm.value.check_out || !bookingForm.value.room_type_id) {
        availabilityChecked.value = false;
        availabilityError.value = '';
        totalAmount.value = '';
        return;
    }

    try {
        // Check availability
        const params = new URLSearchParams({
            property_id: props.propertyId,
            check_in: bookingForm.value.check_in,
            check_out: bookingForm.value.check_out,
            room_type_id: bookingForm.value.room_type_id,
            adult_count: bookingForm.value.adult_count || 1,
            child_count: bookingForm.value.child_count || 0,
        });

        const response = await fetch(`/public/api/availability/check?${params.toString()}`);
        const data = await response.json();

        if (data.success && data.data.available) {
            availabilityChecked.value = true;
            availabilityError.value = '';
            
            // Calculate pricing
            await calculatePricing();
        } else {
            availabilityChecked.value = false;
            availabilityError.value = data.data.message || 'Not available for the selected dates';
            totalAmount.value = '';
        }
    } catch (error) {
        console.error('Error checking availability:', error);
        availabilityError.value = 'Error checking availability. Please try again.';
        availabilityChecked.value = false;
        totalAmount.value = '';
    }
};

const calculatePricing = async () => {
    if (!bookingForm.value.check_in || !bookingForm.value.check_out || !bookingForm.value.room_type_id) {
        pricingDetails.value = null;
        totalAmount.value = '';
        return;
    }

    try {
        const params = new URLSearchParams({
            property_id: props.propertyId,
            room_type_id: bookingForm.value.room_type_id,
            check_in: bookingForm.value.check_in,
            check_out: bookingForm.value.check_out,
            adult_count: bookingForm.value.adult_count || 1,
            child_count: bookingForm.value.child_count || 0,
        });

        const response = await fetch(`/public/api/pricing/calculate?${params.toString()}`);
        const data = await response.json();

        if (data.success) {
            pricingDetails.value = data.data;
            totalAmount.value = formatCurrency(data.data.total_amount, data.data.currency);
        }
    } catch (error) {
        console.error('Error calculating pricing:', error);
        pricingDetails.value = null;
        totalAmount.value = '';
    }
};

// Computed property for displaying total
const displayTotal = computed(() => {
    if (totalAmount.value) {
        return totalAmount.value;
    }
    
    if (pricingDetails.value && pricingDetails.value.total_amount) {
        return formatCurrency(pricingDetails.value.total_amount, pricingDetails.value.currency || property.value?.currency || 'GBP');
    }
    
    // Fallback calculation
    if (property.value && bookingForm.value.room_type_id && bookingForm.value.check_in && bookingForm.value.check_out) {
        const roomType = property.value.room_types?.find(rt => rt.id == bookingForm.value.room_type_id);
        if (roomType) {
            const checkIn = new Date(bookingForm.value.check_in);
            const checkOut = new Date(bookingForm.value.check_out);
            const nights = Math.ceil((checkOut - checkIn) / (1000 * 60 * 60 * 24));
            const total = (roomType.base_rate || 0) * nights;
            return formatCurrency(total, property.value.currency || 'GBP');
        }
    }
    
    return formatCurrency(0, property.value?.currency || 'GBP');
});

const selectRoomType = (roomTypeId) => {
    bookingForm.value.room_type_id = roomTypeId;
    if (bookingForm.value.check_in && bookingForm.value.check_out) {
        checkAvailability();
    }
};

const formatCurrency = (amount, currency = 'GBP') => {
    return new Intl.NumberFormat('en-GB', {
        style: 'currency',
        currency: currency || 'GBP',
    }).format(amount);
};

const submitBooking = async () => {
    // Validate form
    if (!bookingForm.value.guest_first_name || !bookingForm.value.guest_last_name || !bookingForm.value.guest_email) {
        window.toastr.error('Please fill in all required guest information fields.');
        return;
    }

    if (!bookingForm.value.check_in || !bookingForm.value.check_out) {
        window.toastr.error('Please select check-in and check-out dates.');
        return;
    }

    if (!bookingForm.value.room_type_id) {
        window.toastr.error('Please select a room type.');
        return;
    }

    if (!availabilityChecked.value || availabilityError.value) {
        window.toastr.error('Please ensure the room is available for your selected dates.');
        return;
    }

    submitting.value = true;
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        const response = await fetch('/public/api/bookings', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                property_id: parseInt(props.propertyId),
                room_type_id: parseInt(bookingForm.value.room_type_id),
                check_in: bookingForm.value.check_in,
                check_out: bookingForm.value.check_out,
                adult_count: parseInt(bookingForm.value.adult_count) || 1,
                child_count: parseInt(bookingForm.value.child_count) || 0,
                guest_first_name: bookingForm.value.guest_first_name,
                guest_last_name: bookingForm.value.guest_last_name,
                guest_email: bookingForm.value.guest_email,
                guest_phone: bookingForm.value.guest_phone || null,
            }),
        });

        if (!response.ok) {
            // Handle HTTP errors
            const errorText = await response.text();
            let errorData;
            try {
                errorData = JSON.parse(errorText);
            } catch {
                errorData = { message: 'Booking failed. Please try again.' };
            }
            throw new Error(errorData.message || `HTTP error! status: ${response.status}`);
        }

        const data = await response.json();

        if (data.success) {
            window.toastr.success('Booking created successfully!');
            setTimeout(() => {
                window.location.href = `/booking/${data.data.id}`;
            }, 1000);
        } else {
            const errorMessage = data.message || 'Booking failed. Please try again.';
            window.toastr.error(errorMessage);
            
            // Display validation errors if any
            if (data.errors) {
                const errorList = Object.values(data.errors).flat().join('<br>');
                window.toastr.error(errorList, 'Validation Errors', { timeOut: 8000 });
            }
        }
    } catch (error) {
        console.error('Error submitting booking:', error);
        window.toastr.error('An error occurred. Please try again.');
    } finally {
        submitting.value = false;
    }
};

onMounted(() => {
    loadProperty();
});
</script>

