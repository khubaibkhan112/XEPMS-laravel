<?php

namespace App\Services\ChannelManager;

use App\Models\BookingDetail;
use App\Models\ChannelConnection;
use App\Models\ErrorLog;
use App\Models\OtaMapping;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\SyncLog;
use App\Services\ChannelManager\Contracts\ChannelClient;
use App\Services\ChannelManager\DTO\ChannelResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ReservationImportService
{
    public function __construct(
        protected readonly ChannelClientFactory $clientFactory
    ) {
    }

    /**
     * Import reservations from a channel connection.
     *
     * @param ChannelConnection $connection
     * @param Carbon|null $startDate
     * @param Carbon|null $endDate
     * @param array $options
     * @return array{success: int, failed: int, skipped: int, errors: array}
     */
    public function importReservations(
        ChannelConnection $connection,
        ?Carbon $startDate = null,
        ?Carbon $endDate = null,
        array $options = []
    ): array {
        $startDate ??= Carbon::today();
        $endDate ??= Carbon::today()->addMonths(3);

        $connection->update(['last_attempted_sync_at' => now()]);

        $stats = [
            'success' => 0,
            'failed' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        try {
            $client = $this->clientFactory->make($connection);
            $response = $client->pullReservations($startDate, $endDate, $options);

            if (!$response->success) {
                $this->logError(
                    $connection,
                    'Failed to pull reservations from channel',
                    [
                        'message' => $response->message,
                        'data' => $response->data,
                        'meta' => $response->meta,
                    ]
                );

                return $stats;
            }

            $reservations = $this->parseReservations($response->data, $connection->channel);

            foreach ($reservations as $reservationData) {
                try {
                    $result = $this->importReservation($connection, $reservationData);

                    if ($result['status'] === 'created') {
                        $stats['success']++;
                    } elseif ($result['status'] === 'updated') {
                        $stats['success']++;
                    } elseif ($result['status'] === 'skipped') {
                        $stats['skipped']++;
                    } else {
                        $stats['failed']++;
                        $stats['errors'][] = $result['error'] ?? 'Unknown error';
                    }
                } catch (Throwable $e) {
                    $stats['failed']++;
                    $stats['errors'][] = sprintf(
                        'Reservation %s: %s',
                        $reservationData['ota_reservation_code'] ?? 'unknown',
                        $e->getMessage()
                    );

                    $this->logError(
                        $connection,
                        'Failed to import reservation',
                        [
                            'reservation_data' => $reservationData,
                            'exception' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                        ]
                    );
                }
            }

            $connection->update(['last_successful_sync_at' => now()]);

            $this->logSync(
                $connection,
                'import_reservations',
                'inbound',
                'reservation',
                null,
                [
                    'start_date' => $startDate->toDateString(),
                    'end_date' => $endDate->toDateString(),
                    'stats' => $stats,
                ]
            );
        } catch (Throwable $e) {
            $this->logError(
                $connection,
                'Fatal error during reservation import',
                [
                    'exception' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]
            );

            $stats['errors'][] = $e->getMessage();
        }

        return $stats;
    }

    /**
     * Parse reservations from channel API response.
     *
     * @param array $data
     * @param string $channel
     * @return array
     */
    protected function parseReservations(array $data, string $channel): array
    {
        return match ($channel) {
            'booking_com' => $this->parseBookingComReservations($data),
            'expedia' => $this->parseExpediaReservations($data),
            'airbnb' => $this->parseAirbnbReservations($data),
            default => [],
        };
    }

    /**
     * Parse Booking.com reservation data.
     *
     * @param array $data
     * @return array
     */
    protected function parseBookingComReservations(array $data): array
    {
        $reservations = [];

        // Booking.com API typically returns reservations in a 'reservations' key
        $reservationList = $data['reservations'] ?? $data['result'] ?? $data;

        if (!is_array($reservationList)) {
            return [];
        }

        foreach ($reservationList as $item) {
            if (!is_array($item)) {
                continue;
            }

            $checkIn = $this->parseDate($item['check_in'] ?? $item['arrival_date'] ?? null);
            $checkOut = $this->parseDate($item['check_out'] ?? $item['departure_date'] ?? null);

            if (!$checkIn || !$checkOut) {
                continue;
            }

            $guest = $item['guest'] ?? $item['customer'] ?? [];
            $pricing = $item['pricing'] ?? $item['price'] ?? [];
            $room = $item['room'] ?? $item['accommodation'] ?? [];

            $reservations[] = [
                'ota_reservation_code' => $item['reservation_id'] ?? $item['confirmation_code'] ?? $item['booking_id'] ?? null,
                'channel_reference' => $item['reference'] ?? $item['booking_reference'] ?? null,
                'external_id' => $item['external_id'] ?? $item['id'] ?? null,
                'guest_first_name' => $guest['first_name'] ?? $item['guest_first_name'] ?? '',
                'guest_last_name' => $guest['last_name'] ?? $item['guest_last_name'] ?? '',
                'guest_email' => $guest['email'] ?? $item['guest_email'] ?? null,
                'guest_phone' => $guest['phone'] ?? $item['guest_phone'] ?? null,
                'guest_country_code' => $guest['country_code'] ?? $item['guest_country_code'] ?? null,
                'guest_city' => $guest['city'] ?? $item['guest_city'] ?? null,
                'guest_postal_code' => $guest['postal_code'] ?? $item['guest_postal_code'] ?? null,
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'adult_count' => (int) ($item['adults'] ?? $item['adult_count'] ?? $guest['adults'] ?? 1),
                'child_count' => (int) ($item['children'] ?? $item['child_count'] ?? $guest['children'] ?? 0),
                'total_amount' => $this->parseAmount($pricing['total'] ?? $pricing['amount'] ?? $item['total_price'] ?? 0),
                'paid_amount' => $this->parseAmount($pricing['paid'] ?? $pricing['paid_amount'] ?? $item['paid_amount'] ?? 0),
                'ota_commission_amount' => $this->parseAmount($pricing['commission'] ?? $item['commission'] ?? 0),
                'tax_amount' => $this->parseAmount($pricing['tax'] ?? $item['tax_amount'] ?? 0),
                'fee_amount' => $this->parseAmount($pricing['fees'] ?? $item['fee_amount'] ?? 0),
                'currency' => $pricing['currency'] ?? $item['currency'] ?? 'GBP',
                'exchange_rate' => $pricing['exchange_rate'] ?? $item['exchange_rate'] ?? null,
                'status' => $this->mapStatus($item['status'] ?? 'confirmed', 'booking_com'),
                'payment_status' => $this->mapPaymentStatus($item['payment_status'] ?? 'pending', 'booking_com'),
                'source' => 'booking_com',
                'locale' => $item['locale'] ?? 'en-GB',
                'market' => $item['market'] ?? 'United Kingdom',
                'rate_plan_code' => $room['rate_plan_code'] ?? $item['rate_plan_code'] ?? null,
                'board_basis' => $room['board_basis'] ?? $item['board_basis'] ?? null,
                'expected_arrival_time' => $this->parseTime($item['expected_arrival_time'] ?? null),
                'expected_departure_time' => $this->parseTime($item['expected_departure_time'] ?? null),
                'guest_details' => $guest,
                'extras' => $item['extras'] ?? $item['addons'] ?? [],
                'tax_breakdown' => $pricing['tax_breakdown'] ?? $item['tax_breakdown'] ?? [],
                'fee_breakdown' => $pricing['fee_breakdown'] ?? $item['fee_breakdown'] ?? [],
                'pricing_breakdown' => $pricing['breakdown'] ?? $item['pricing_breakdown'] ?? [],
                'notes' => $item['notes'] ?? $item['special_requests'] ?? null,
                'requires_channel_confirmation' => (bool) ($item['requires_confirmation'] ?? false),
                'channel_confirmed_at' => $this->parseDateTime($item['confirmed_at'] ?? null),
                'cancelled_at' => $this->parseDateTime($item['cancelled_at'] ?? null),
                'ota_room_id' => $room['room_id'] ?? $room['id'] ?? $item['room_id'] ?? null,
                'ota_room_type_id' => $room['room_type_id'] ?? $item['room_type_id'] ?? null,
                'ota_rate_plan_id' => $room['rate_plan_id'] ?? $item['rate_plan_id'] ?? null,
                'daily_rates' => $item['daily_rates'] ?? $pricing['daily_rates'] ?? [],
            ];
        }

        return $reservations;
    }

    /**
     * Parse Expedia reservation data.
     *
     * @param array $data
     * @return array
     */
    protected function parseExpediaReservations(array $data): array
    {
        $reservations = [];

        // Expedia API typically returns reservations in a 'reservations' or 'itineraries' key
        $reservationList = $data['reservations'] ?? $data['itineraries'] ?? $data['result'] ?? $data;

        if (!is_array($reservationList)) {
            return [];
        }

        foreach ($reservationList as $item) {
            if (!is_array($item)) {
                continue;
            }

            $checkIn = $this->parseDate($item['arrival_date'] ?? $item['check_in'] ?? $item['start_date'] ?? null);
            $checkOut = $this->parseDate($item['departure_date'] ?? $item['check_out'] ?? $item['end_date'] ?? null);

            if (!$checkIn || !$checkOut) {
                continue;
            }

            $guest = $item['guest'] ?? $item['customer'] ?? $item['primary_guest'] ?? [];
            $pricing = $item['pricing'] ?? $item['price'] ?? $item['charges'] ?? [];
            $room = $item['room'] ?? $item['accommodation'] ?? $item['room_type'] ?? [];
            $itinerary = $item['itinerary'] ?? [];

            // Expedia uses itinerary_id or confirmation_number as reservation identifier
            $reservationCode = $item['itinerary_id'] 
                ?? $item['confirmation_number'] 
                ?? $item['reservation_id'] 
                ?? $item['booking_id'] 
                ?? null;

            if (!$reservationCode) {
                continue; // Skip if no reservation identifier
            }

            $reservations[] = [
                'ota_reservation_code' => $reservationCode,
                'channel_reference' => $item['reference'] ?? $item['booking_reference'] ?? $item['itinerary_id'] ?? null,
                'external_id' => $item['external_id'] ?? $item['id'] ?? $item['itinerary_id'] ?? null,
                'guest_first_name' => $guest['first_name'] ?? $item['guest_first_name'] ?? $item['first_name'] ?? '',
                'guest_last_name' => $guest['last_name'] ?? $item['guest_last_name'] ?? $item['last_name'] ?? '',
                'guest_email' => $guest['email'] ?? $item['guest_email'] ?? $item['email'] ?? null,
                'guest_phone' => $guest['phone'] ?? $item['guest_phone'] ?? $item['phone'] ?? $item['phone_number'] ?? null,
                'guest_country_code' => $guest['country_code'] ?? $item['guest_country_code'] ?? $item['country'] ?? null,
                'guest_city' => $guest['city'] ?? $item['guest_city'] ?? $item['city'] ?? null,
                'guest_postal_code' => $guest['postal_code'] ?? $item['guest_postal_code'] ?? $item['postal_code'] ?? null,
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'adult_count' => (int) ($item['number_of_adults'] ?? $item['adults'] ?? $item['adult_count'] ?? $guest['adults'] ?? 1),
                'child_count' => (int) ($item['number_of_children'] ?? $item['children'] ?? $item['child_count'] ?? $guest['children'] ?? 0),
                'total_amount' => $this->parseAmount($pricing['total'] ?? $pricing['amount'] ?? $pricing['total_charges'] ?? $item['total_price'] ?? 0),
                'paid_amount' => $this->parseAmount($pricing['paid'] ?? $pricing['paid_amount'] ?? $item['paid_amount'] ?? $item['amount_paid'] ?? 0),
                'ota_commission_amount' => $this->parseAmount($pricing['commission'] ?? $item['commission'] ?? $item['expedia_commission'] ?? 0),
                'tax_amount' => $this->parseAmount($pricing['tax'] ?? $pricing['taxes'] ?? $item['tax_amount'] ?? $item['total_tax'] ?? 0),
                'fee_amount' => $this->parseAmount($pricing['fees'] ?? $item['fee_amount'] ?? $item['service_fee'] ?? 0),
                'currency' => $pricing['currency'] ?? $item['currency'] ?? 'GBP',
                'exchange_rate' => $pricing['exchange_rate'] ?? $item['exchange_rate'] ?? null,
                'status' => $this->mapStatus($item['status'] ?? $item['reservation_status'] ?? 'confirmed', 'expedia'),
                'payment_status' => $this->mapPaymentStatus($item['payment_status'] ?? $item['payment_status_code'] ?? 'pending', 'expedia'),
                'source' => 'expedia',
                'locale' => $item['locale'] ?? 'en-GB',
                'market' => $item['market'] ?? $item['country'] ?? 'United Kingdom',
                'rate_plan_code' => $room['rate_plan_code'] ?? $item['rate_plan_code'] ?? $item['rate_code'] ?? null,
                'board_basis' => $room['board_basis'] ?? $item['board_basis'] ?? $item['meal_plan'] ?? null,
                'expected_arrival_time' => $this->parseTime($item['expected_arrival_time'] ?? $item['arrival_time'] ?? null),
                'expected_departure_time' => $this->parseTime($item['expected_departure_time'] ?? $item['departure_time'] ?? null),
                'guest_details' => $guest,
                'extras' => $item['extras'] ?? $item['addons'] ?? $item['additional_services'] ?? [],
                'tax_breakdown' => $pricing['tax_breakdown'] ?? $item['tax_breakdown'] ?? $pricing['taxes'] ?? [],
                'fee_breakdown' => $pricing['fee_breakdown'] ?? $item['fee_breakdown'] ?? [],
                'pricing_breakdown' => $pricing['breakdown'] ?? $item['pricing_breakdown'] ?? $pricing['charge_breakdown'] ?? [],
                'notes' => $item['notes'] ?? $item['special_requests'] ?? $item['special_instructions'] ?? null,
                'requires_channel_confirmation' => (bool) ($item['requires_confirmation'] ?? $item['needs_confirmation'] ?? false),
                'channel_confirmed_at' => $this->parseDateTime($item['confirmed_at'] ?? $item['confirmation_date'] ?? null),
                'cancelled_at' => $this->parseDateTime($item['cancelled_at'] ?? $item['cancellation_date'] ?? null),
                'ota_room_id' => $room['room_id'] ?? $room['id'] ?? $item['room_id'] ?? $item['accommodation_id'] ?? null,
                'ota_room_type_id' => $room['room_type_id'] ?? $item['room_type_id'] ?? $item['accommodation_type_id'] ?? null,
                'ota_rate_plan_id' => $room['rate_plan_id'] ?? $item['rate_plan_id'] ?? $item['rate_code_id'] ?? null,
                'daily_rates' => $item['daily_rates'] ?? $pricing['daily_rates'] ?? $item['nightly_rates'] ?? [],
            ];
        }

        return $reservations;
    }

    /**
     * Parse Airbnb reservation data.
     *
     * @param array $data
     * @return array
     */
    protected function parseAirbnbReservations(array $data): array
    {
        // Placeholder for Airbnb parsing - will be implemented in task 24
        return [];
    }

    /**
     * Import a single reservation.
     *
     * @param ChannelConnection $connection
     * @param array $data
     * @return array{status: string, reservation?: Reservation, error?: string}
     */
    protected function importReservation(ChannelConnection $connection, array $data): array
    {
        if (empty($data['ota_reservation_code'])) {
            return [
                'status' => 'failed',
                'error' => 'Missing OTA reservation code',
            ];
        }

        // Check for duplicate
        $existing = Reservation::where('channel_connection_id', $connection->id)
            ->where('ota_reservation_code', $data['ota_reservation_code'])
            ->first();

        if ($existing) {
            // Update existing reservation
            return $this->updateReservation($existing, $data);
        }

        // Find room mapping
        $mapping = $this->findRoomMapping($connection, $data);

        if (!$mapping) {
            return [
                'status' => 'skipped',
                'error' => 'No room mapping found for OTA room identifier',
            ];
        }

        // Create new reservation
        return $this->createReservation($connection, $data, $mapping);
    }

    /**
     * Find room mapping for OTA reservation.
     *
     * @param ChannelConnection $connection
     * @param array $data
     * @return OtaMapping|null
     */
    protected function findRoomMapping(ChannelConnection $connection, array $data): ?OtaMapping
    {
        $otaRoomId = $data['ota_room_id'] ?? null;
        $otaRoomTypeId = $data['ota_room_type_id'] ?? null;
        $otaRatePlanId = $data['ota_rate_plan_id'] ?? null;

        if (!$otaRoomId && !$otaRoomTypeId && !$otaRatePlanId) {
            return null;
        }

        $query = OtaMapping::where('channel_connection_id', $connection->id)
            ->where('is_active', true);

        if ($otaRoomId) {
            $query->where(function ($q) use ($otaRoomId) {
                $q->where('ota_room_id', $otaRoomId)
                    ->orWhere('channel_identifier', $otaRoomId);
            });
        }

        if ($otaRoomTypeId) {
            $query->orWhere('ota_room_type_id', $otaRoomTypeId);
        }

        if ($otaRatePlanId) {
            $query->orWhere('ota_rate_plan_id', $otaRatePlanId);
        }

        return $query->first();
    }

    /**
     * Create a new reservation.
     *
     * @param ChannelConnection $connection
     * @param array $data
     * @param OtaMapping $mapping
     * @return array{status: string, reservation: Reservation}
     */
    protected function createReservation(
        ChannelConnection $connection,
        array $data,
        OtaMapping $mapping
    ): array {
        DB::beginTransaction();

        try {
            $reservation = Reservation::create([
                'property_id' => $connection->property_id,
                'room_type_id' => $mapping->room_type_id,
                'room_id' => $mapping->room_id,
                'channel_connection_id' => $connection->id,
                'channel_reference' => $data['channel_reference'] ?? null,
                'ota_reservation_code' => $data['ota_reservation_code'],
                'external_id' => $data['external_id'] ?? null,
                'guest_first_name' => $data['guest_first_name'] ?? '',
                'guest_last_name' => $data['guest_last_name'] ?? '',
                'guest_email' => $data['guest_email'] ?? null,
                'guest_phone' => $data['guest_phone'] ?? null,
                'guest_country_code' => $data['guest_country_code'] ?? null,
                'guest_city' => $data['guest_city'] ?? null,
                'guest_postal_code' => $data['guest_postal_code'] ?? null,
                'check_in' => $data['check_in'],
                'check_out' => $data['check_out'],
                'adult_count' => $data['adult_count'] ?? 1,
                'child_count' => $data['child_count'] ?? 0,
                'total_amount' => $data['total_amount'] ?? 0,
                'paid_amount' => $data['paid_amount'] ?? 0,
                'ota_commission_amount' => $data['ota_commission_amount'] ?? 0,
                'tax_amount' => $data['tax_amount'] ?? 0,
                'fee_amount' => $data['fee_amount'] ?? 0,
                'currency' => $data['currency'] ?? 'GBP',
                'exchange_rate' => $data['exchange_rate'] ?? null,
                'status' => $data['status'] ?? Reservation::STATUS_CONFIRMED,
                'payment_status' => $data['payment_status'] ?? Reservation::PAYMENT_PENDING,
                'source' => $data['source'] ?? 'booking_com',
                'locale' => $data['locale'] ?? 'en-GB',
                'market' => $data['market'] ?? 'United Kingdom',
                'rate_plan_code' => $data['rate_plan_code'] ?? null,
                'board_basis' => $data['board_basis'] ?? null,
                'expected_arrival_time' => $data['expected_arrival_time'] ?? null,
                'expected_departure_time' => $data['expected_departure_time'] ?? null,
                'guest_details' => $data['guest_details'] ?? [],
                'extras' => $data['extras'] ?? [],
                'tax_breakdown' => $data['tax_breakdown'] ?? [],
                'fee_breakdown' => $data['fee_breakdown'] ?? [],
                'pricing_breakdown' => $data['pricing_breakdown'] ?? [],
                'notes' => $data['notes'] ?? null,
                'requires_channel_confirmation' => $data['requires_channel_confirmation'] ?? false,
                'channel_confirmed_at' => $data['channel_confirmed_at'] ?? null,
                'cancelled_at' => $data['cancelled_at'] ?? null,
            ]);

            // Create booking details for daily rates
            if (!empty($data['daily_rates'])) {
                $this->createBookingDetails($reservation, $data['daily_rates'], $data);
            } else {
                // Create a single booking detail if no daily rates provided
                $this->createDefaultBookingDetail($reservation, $data);
            }

            $this->logSync(
                $connection,
                'import_reservation',
                'inbound',
                'reservation',
                $reservation->id,
                [
                    'ota_reservation_code' => $data['ota_reservation_code'],
                    'action' => 'created',
                ]
            );

            DB::commit();

            return [
                'status' => 'created',
                'reservation' => $reservation,
            ];
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update an existing reservation.
     *
     * @param Reservation $reservation
     * @param array $data
     * @return array{status: string, reservation: Reservation}
     */
    protected function updateReservation(Reservation $reservation, array $data): array
    {
        DB::beginTransaction();

        try {
            $reservation->update([
                'guest_first_name' => $data['guest_first_name'] ?? $reservation->guest_first_name,
                'guest_last_name' => $data['guest_last_name'] ?? $reservation->guest_last_name,
                'guest_email' => $data['guest_email'] ?? $reservation->guest_email,
                'guest_phone' => $data['guest_phone'] ?? $reservation->guest_phone,
                'check_in' => $data['check_in'] ?? $reservation->check_in,
                'check_out' => $data['check_out'] ?? $reservation->check_out,
                'adult_count' => $data['adult_count'] ?? $reservation->adult_count,
                'child_count' => $data['child_count'] ?? $reservation->child_count,
                'total_amount' => $data['total_amount'] ?? $reservation->total_amount,
                'paid_amount' => $data['paid_amount'] ?? $reservation->paid_amount,
                'status' => $data['status'] ?? $reservation->status,
                'payment_status' => $data['payment_status'] ?? $reservation->payment_status,
                'cancelled_at' => $data['cancelled_at'] ?? $reservation->cancelled_at,
                'notes' => $data['notes'] ?? $reservation->notes,
            ]);

            // Update booking details if daily rates provided
            if (!empty($data['daily_rates'])) {
                $reservation->bookingDetails()->delete();
                $this->createBookingDetails($reservation, $data['daily_rates'], $data);
            }

            $this->logSync(
                $reservation->channelConnection,
                'import_reservation',
                'inbound',
                'reservation',
                $reservation->id,
                [
                    'ota_reservation_code' => $data['ota_reservation_code'],
                    'action' => 'updated',
                ]
            );

            DB::commit();

            return [
                'status' => 'updated',
                'reservation' => $reservation,
            ];
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Create booking details from daily rates.
     *
     * @param Reservation $reservation
     * @param array $dailyRates
     * @param array $reservationData
     * @return void
     */
    protected function createBookingDetails(Reservation $reservation, array $dailyRates, array $reservationData): void
    {
        $checkIn = Carbon::parse($reservation->check_in);
        $checkOut = Carbon::parse($reservation->check_out);

        foreach ($dailyRates as $rateData) {
            $date = $this->parseDate($rateData['date'] ?? null);
            if (!$date || $date < $checkIn || $date >= $checkOut) {
                continue;
            }

            BookingDetail::create([
                'reservation_id' => $reservation->id,
                'date' => $date,
                'rate' => $this->parseAmount($rateData['rate'] ?? $rateData['amount'] ?? 0),
                'original_rate' => $this->parseAmount($rateData['original_rate'] ?? $rateData['rate'] ?? 0),
                'adult_count' => $rateData['adult_count'] ?? $reservationData['adult_count'] ?? 1,
                'child_count' => $rateData['child_count'] ?? $reservationData['child_count'] ?? 0,
                'pricing_breakdown' => $rateData['pricing_breakdown'] ?? [],
                'taxes' => $rateData['taxes'] ?? [],
                'fees' => $rateData['fees'] ?? [],
                'currency' => $rateData['currency'] ?? $reservationData['currency'] ?? 'GBP',
                'rate_plan_code' => $rateData['rate_plan_code'] ?? $reservationData['rate_plan_code'] ?? null,
                'board_basis' => $rateData['board_basis'] ?? $reservationData['board_basis'] ?? null,
                'channel_identifier' => $rateData['channel_identifier'] ?? null,
                'is_derived_rate' => (bool) ($rateData['is_derived'] ?? false),
            ]);
        }
    }

    /**
     * Create default booking detail when no daily rates provided.
     *
     * @param Reservation $reservation
     * @param array $reservationData
     * @return void
     */
    protected function createDefaultBookingDetail(Reservation $reservation, array $reservationData): void
    {
        $nights = $reservation->nights ?? 1;
        $ratePerNight = $nights > 0 ? ($reservation->total_amount / $nights) : $reservation->total_amount;

        $checkIn = Carbon::parse($reservation->check_in);
        $checkOut = Carbon::parse($reservation->check_out);

        $currentDate = $checkIn->copy();
        while ($currentDate < $checkOut) {
            BookingDetail::create([
                'reservation_id' => $reservation->id,
                'date' => $currentDate->toDateString(),
                'rate' => $ratePerNight,
                'original_rate' => $ratePerNight,
                'adult_count' => $reservation->adult_count,
                'child_count' => $reservation->child_count,
                'currency' => $reservation->currency,
                'rate_plan_code' => $reservation->rate_plan_code,
                'board_basis' => $reservation->board_basis,
                'is_derived_rate' => true,
            ]);

            $currentDate->addDay();
        }
    }

    /**
     * Map channel status to internal status.
     *
     * @param string $channelStatus
     * @param string $channel
     * @return string
     */
    protected function mapStatus(string $channelStatus, string $channel): string
    {
        $statusMap = [
            'booking_com' => [
                'confirmed' => Reservation::STATUS_CONFIRMED,
                'pending' => Reservation::STATUS_PENDING,
                'cancelled' => Reservation::STATUS_CANCELLED,
                'checked_in' => Reservation::STATUS_CHECKED_IN,
                'checked_out' => Reservation::STATUS_CHECKED_OUT,
            ],
            'expedia' => [
                'confirmed' => Reservation::STATUS_CONFIRMED,
                'pending' => Reservation::STATUS_PENDING,
                'cancelled' => Reservation::STATUS_CANCELLED,
                'canceled' => Reservation::STATUS_CANCELLED,
                'checked_in' => Reservation::STATUS_CHECKED_IN,
                'checked_out' => Reservation::STATUS_CHECKED_OUT,
                'checked-out' => Reservation::STATUS_CHECKED_OUT,
                'active' => Reservation::STATUS_CONFIRMED,
                'inactive' => Reservation::STATUS_CANCELLED,
                'no_show' => Reservation::STATUS_CANCELLED,
            ],
        ];

        $mapping = $statusMap[$channel] ?? [];
        $normalized = strtolower(trim($channelStatus));

        return $mapping[$normalized] ?? Reservation::STATUS_CONFIRMED;
    }

    /**
     * Map channel payment status to internal payment status.
     *
     * @param string $channelPaymentStatus
     * @param string $channel
     * @return string
     */
    protected function mapPaymentStatus(string $channelPaymentStatus, string $channel): string
    {
        $statusMap = [
            'booking_com' => [
                'paid' => Reservation::PAYMENT_PAID,
                'pending' => Reservation::PAYMENT_PENDING,
                'partial' => Reservation::PAYMENT_PARTIALLY_PAID,
                'refunded' => Reservation::PAYMENT_REFUNDED,
            ],
            'expedia' => [
                'paid' => Reservation::PAYMENT_PAID,
                'pending' => Reservation::PAYMENT_PENDING,
                'partial' => Reservation::PAYMENT_PARTIALLY_PAID,
                'partially_paid' => Reservation::PAYMENT_PARTIALLY_PAID,
                'refunded' => Reservation::PAYMENT_REFUNDED,
                'not_paid' => Reservation::PAYMENT_PENDING,
                'authorized' => Reservation::PAYMENT_PENDING,
                'captured' => Reservation::PAYMENT_PAID,
            ],
        ];

        $mapping = $statusMap[$channel] ?? [];
        $normalized = strtolower(trim($channelPaymentStatus));

        return $mapping[$normalized] ?? Reservation::PAYMENT_PENDING;
    }

    /**
     * Parse date from various formats.
     *
     * @param mixed $date
     * @return Carbon|null
     */
    protected function parseDate($date): ?Carbon
    {
        if (!$date) {
            return null;
        }

        if ($date instanceof Carbon) {
            return $date;
        }

        try {
            return Carbon::parse($date);
        } catch (Throwable $e) {
            return null;
        }
    }

    /**
     * Parse datetime from various formats.
     *
     * @param mixed $datetime
     * @return Carbon|null
     */
    protected function parseDateTime($datetime): ?Carbon
    {
        if (!$datetime) {
            return null;
        }

        if ($datetime instanceof Carbon) {
            return $datetime;
        }

        try {
            return Carbon::parse($datetime);
        } catch (Throwable $e) {
            return null;
        }
    }

    /**
     * Parse time from various formats.
     *
     * @param mixed $time
     * @return string|null
     */
    protected function parseTime($time): ?string
    {
        if (!$time) {
            return null;
        }

        if (is_string($time) && preg_match('/^\d{2}:\d{2}/', $time)) {
            return $time;
        }

        try {
            $carbon = Carbon::parse($time);
            return $carbon->format('H:i');
        } catch (Throwable $e) {
            return null;
        }
    }

    /**
     * Parse amount from various formats.
     *
     * @param mixed $amount
     * @return float
     */
    protected function parseAmount($amount): float
    {
        if (is_numeric($amount)) {
            return (float) $amount;
        }

        if (is_string($amount)) {
            return (float) preg_replace('/[^0-9.-]/', '', $amount);
        }

        return 0.0;
    }

    /**
     * Log sync operation.
     *
     * @param ChannelConnection $connection
     * @param string $operation
     * @param string $direction
     * @param string $entityType
     * @param int|null $entityId
     * @param array $metadata
     * @return void
     */
    protected function logSync(
        ChannelConnection $connection,
        string $operation,
        string $direction,
        string $entityType,
        ?int $entityId,
        array $metadata = []
    ): void {
        SyncLog::create([
            'channel_connection_id' => $connection->id,
            'reservation_id' => $entityType === 'reservation' ? $entityId : null,
            'channel' => $connection->channel,
            'environment' => $connection->uses_sandbox ? 'sandbox' : 'production',
            'operation' => $operation,
            'direction' => $direction,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'status' => 'success',
            'message' => sprintf('%s %s %s', $direction, $operation, $entityType),
            'metadata' => $metadata,
            'performed_at' => now(),
        ]);
    }

    /**
     * Log error.
     *
     * @param ChannelConnection $connection
     * @param string $message
     * @param array $context
     * @return void
     */
    protected function logError(ChannelConnection $connection, string $message, array $context = []): void
    {
        ErrorLog::create([
            'channel_connection_id' => $connection->id,
            'channel' => $connection->channel,
            'environment' => $connection->uses_sandbox ? 'sandbox' : 'production',
            'context' => 'reservation_import',
            'severity' => 'error',
            'message' => $message,
            'request_payload' => $context['request_payload'] ?? null,
            'response_payload' => $context['response_payload'] ?? null,
            'metadata' => $context,
            'stack_trace' => $context['trace'] ?? null,
            'occurred_at' => now(),
        ]);

        Log::error('Channel Manager Import Error', [
            'channel' => $connection->channel,
            'connection_id' => $connection->id,
            'message' => $message,
            'context' => $context,
        ]);
    }
}


