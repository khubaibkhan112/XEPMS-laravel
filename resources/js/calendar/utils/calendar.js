import {
    addDays,
    differenceInCalendarDays,
    format,
    startOfDay,
    isToday,
} from 'date-fns';

export function buildDateRange(startDate, days) {
    const base = startOfDay(startDate);

    return Array.from({ length: days }).map((_, index) => {
        const date = addDays(base, index);
        return {
            date,
            iso: date.toISOString(),
            formatted: format(date, 'dd MMM'),
            weekday: format(date, 'EEE'),
            isToday: isToday(date),
        };
    });
}

export function daysBetween(startDate, endDate) {
    const msPerDay = 1000 * 60 * 60 * 24;
    const start = startOfDay(startDate);
    const end = startOfDay(endDate);
    return Math.max(1, Math.round((end - start) / msPerDay));
}

export function calculateOffset(startDate, targetDate) {
    const base = startOfDay(startDate);
    const target = startOfDay(targetDate);
    const msPerDay = 1000 * 60 * 60 * 24;
    return Math.round((target - base) / msPerDay);
}

export function calculateDailyAvailability(rooms, reservations, startDate, days) {
    const totalRooms = rooms.length;
    const range = buildDateRange(startDate, days);

    const availability = range.map((day) => ({
        iso: day.iso,
        date: day.date,
        total: totalRooms,
        booked: 0,
        available: totalRooms,
        occupancy: 0,
    }));

    reservations.forEach((reservation) => {
        const stayLength = Math.max(1, differenceInCalendarDays(reservation.checkOut, reservation.checkIn));

        for (let i = 0; i < stayLength; i += 1) {
            const currentDate = addDays(reservation.checkIn, i);
            const index = calculateOffset(startDate, currentDate);

            if (index < 0 || index >= availability.length) {
                continue;
            }

            // cancelled stays do not affect availability
            if (reservation.status === 'cancelled') {
                continue;
            }

            availability[index].booked += 1;
            availability[index].available = Math.max(0, availability[index].available - 1);
        }
    });

    availability.forEach((day) => {
        day.occupancy = day.total === 0 ? 0 : Math.round(((day.total - day.available) / day.total) * 100);
    });

    return availability;
}

