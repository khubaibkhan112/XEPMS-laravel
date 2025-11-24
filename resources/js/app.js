import './bootstrap';
import '../css/app.css';

import { createApp } from 'vue';
import CalendarApp from './calendar/App.vue';
import DashboardApp from './dashboard/App.vue';
import LoginApp from './auth/Login.vue';
import RoomsApp from './rooms/App.vue';

const calendarElement = document.getElementById('calendar-app');
const dashboardElement = document.getElementById('dashboard-app');
const loginElement = document.getElementById('login-app');
const roomsElement = document.getElementById('rooms-app');

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
