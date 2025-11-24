import { addDays, formatISO, startOfWeek } from 'date-fns';

export const mockRooms = [
    { id: 1, name: 'Room 101', type: 'Deluxe Double', capacity: 2 },
    { id: 2, name: 'Room 102', type: 'Deluxe Double', capacity: 2 },
    { id: 3, name: 'Room 201', type: 'Family Suite', capacity: 4 },
    { id: 4, name: 'Room 202', type: 'King Suite', capacity: 3 },
    { id: 5, name: 'Room 301', type: 'Single', capacity: 1 },
    { id: 6, name: 'Room 302', type: 'Single', capacity: 1 },
];

export function mockReservations(baseDate = startOfWeek(new Date(), { weekStartsOn: 1 })) {
    return [
        {
            id: 'res-1',
            roomId: 1,
            guestName: 'Alice Thompson',
            checkIn: addDays(baseDate, 1),
            checkOut: addDays(baseDate, 4),
            status: 'confirmed',
            source: 'Booking.com',
            channelColor: '#f97316',
        },
        {
            id: 'res-2',
            roomId: 2,
            guestName: 'Martin Ross',
            checkIn: addDays(baseDate, 3),
            checkOut: addDays(baseDate, 6),
            status: 'checked_in',
            source: 'Expedia',
            channelColor: '#2563eb',
        },
        {
            id: 'res-3',
            roomId: 3,
            guestName: 'Luna Rivera',
            checkIn: addDays(baseDate, 5),
            checkOut: addDays(baseDate, 9),
            status: 'pending',
            source: 'Airbnb',
            channelColor: '#059669',
        },
        {
            id: 'res-4',
            roomId: 4,
            guestName: 'Corporate Hold',
            checkIn: addDays(baseDate, 2),
            checkOut: addDays(baseDate, 3),
            status: 'blocked',
            source: 'Maintenance',
            channelColor: '#475569',
        },
        {
            id: 'res-5',
            roomId: 5,
            guestName: 'James Carter',
            checkIn: addDays(baseDate, -1),
            checkOut: addDays(baseDate, 2),
            status: 'checked_in',
            source: 'Direct',
            channelColor: '#4f46e5',
        },
        {
            id: 'res-6',
            roomId: 6,
            guestName: 'Cancelled Stay',
            checkIn: addDays(baseDate, 4),
            checkOut: addDays(baseDate, 6),
            status: 'cancelled',
            source: 'Booking.com',
            channelColor: '#dc2626',
        },
    ].map((reservation) => ({
        ...reservation,
        checkInIso: formatISO(reservation.checkIn),
        checkOutIso: formatISO(reservation.checkOut),
    }));
}

