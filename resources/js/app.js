import './bootstrap';
import '../css/app.css';

import { createApp } from 'vue';
import CalendarApp from './calendar/App.vue';
import DashboardApp from './dashboard/App.vue';
import LoginApp from './auth/Login.vue';
import RoomsApp from './rooms/App.vue';
import PropertiesApp from './properties/App.vue';
import RoomTypesApp from './room-types/App.vue';
import PropertySearchApp from './customer/PropertySearch.vue';
import PropertyDetailApp from './customer/PropertyDetail.vue';
import BookingConfirmationApp from './customer/BookingConfirmation.vue';

const calendarElement = document.getElementById('calendar-app');
const dashboardElement = document.getElementById('dashboard-app');
const loginElement = document.getElementById('login-app');
const roomsElement = document.getElementById('rooms-app');
const propertiesElement = document.getElementById('properties-app');
const roomTypesElement = document.getElementById('room-types-app');
const propertySearchElement = document.getElementById('property-search-app');
const propertyDetailElement = document.getElementById('property-detail-app');
const bookingConfirmationElement = document.getElementById('booking-confirmation-app');

if (calendarElement) {
    createApp(CalendarApp).mount(calendarElement);
}

if (dashboardElement) {
    createApp(DashboardApp).mount(dashboardElement);
}

if (loginElement) {
    createApp(LoginApp).mount(loginElement);
}

if (roomsElement) {
    createApp(RoomsApp).mount(roomsElement);
}

if (propertiesElement) {
    createApp(PropertiesApp).mount(propertiesElement);
}

if (roomTypesElement) {
    createApp(RoomTypesApp).mount(roomTypesElement);
}

if (propertySearchElement) {
    createApp(PropertySearchApp).mount(propertySearchElement);
}

if (propertyDetailElement) {
    const propertyId = propertyDetailElement.getAttribute('data-property-id');
    createApp(PropertyDetailApp, { 
        propertyId: propertyId 
    }).mount(propertyDetailElement);
}

if (bookingConfirmationElement) {
    const bookingId = bookingConfirmationElement.getAttribute('data-booking-id');
    createApp(BookingConfirmationApp, { 
        bookingId: bookingId 
    }).mount(bookingConfirmationElement);
}
