<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class Reservation extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const STATUS_PENDING = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_CHECKED_IN = 'checked_in';
    public const STATUS_CHECKED_OUT = 'checked_out';

    public const PAYMENT_PENDING = 'pending';
    public const PAYMENT_PAID = 'paid';
    public const PAYMENT_PARTIALLY_PAID = 'partial';
    public const PAYMENT_REFUNDED = 'refunded';

    protected $touches = ['room', 'property'];

    protected $fillable = [
        'property_id',
        'room_type_id',
        'room_id',
        'channel_connection_id',
        'channel_reference',
        'ota_reservation_code',
        'external_id',
        'guest_first_name',
        'guest_last_name',
        'guest_email',
        'guest_phone',
        'guest_country_code',
        'guest_city',
        'guest_postal_code',
        'check_in',
        'check_out',
        'nights',
        'adult_count',
        'child_count',
        'total_amount',
        'paid_amount',
        'balance_due',
        'ota_commission_amount',
        'tax_amount',
        'fee_amount',
        'exchange_rate',
        'currency',
        'status',
        'payment_status',
        'source',
        'is_primary_guest',
        'guest_details',
        'extras',
        'tax_breakdown',
        'fee_breakdown',
        'pricing_breakdown',
        'notes',
        'locale',
        'market',
        'rate_plan_code',
        'board_basis',
        'expected_arrival_time',
        'expected_departure_time',
        'requires_channel_confirmation',
        'channel_confirmed_at',
        'cancelled_at',
        'checked_in_at',
        'checked_out_at',
    ];

    protected $casts = [
        'check_in' => 'date',
        'check_out' => 'date',
        'cancelled_at' => 'datetime',
        'checked_in_at' => 'datetime',
        'checked_out_at' => 'datetime',
        'channel_confirmed_at' => 'datetime',
        'expected_arrival_time' => 'datetime:H:i',
        'expected_departure_time' => 'datetime:H:i',
        'guest_details' => 'array',
        'extras' => 'array',
        'tax_breakdown' => 'array',
        'fee_breakdown' => 'array',
        'pricing_breakdown' => 'array',
        'is_primary_guest' => 'boolean',
        'requires_channel_confirmation' => 'boolean',
        'adult_count' => 'integer',
        'child_count' => 'integer',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'balance_due' => 'decimal:2',
        'ota_commission_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'fee_amount' => 'decimal:2',
        'exchange_rate' => 'decimal:6',
    ];

    protected $appends = ['guest_name', 'stay_length'];

    protected static function booted(): void
    {
        static::saving(function (Reservation $reservation) {
            if ($reservation->check_in && $reservation->check_out) {
                $checkIn = $reservation->asDateTime($reservation->check_in)->startOfDay();
                $checkOut = $reservation->asDateTime($reservation->check_out)->startOfDay();
                $reservation->nights = max(1, $checkIn->diffInDays($checkOut));
            }

            $reservation->currency ??= 'GBP';
            $reservation->locale ??= 'en-GB';
            $reservation->market ??= 'United Kingdom';

            if ($reservation->total_amount !== null && $reservation->paid_amount !== null) {
                $reservation->balance_due = max(
                    0,
                    round((float) $reservation->total_amount - (float) $reservation->paid_amount, 2)
                );
            }
        });
    }

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function roomType()
    {
        return $this->belongsTo(RoomType::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function channelConnection()
    {
        return $this->belongsTo(ChannelConnection::class);
    }

    public function bookingDetails()
    {
        return $this->hasMany(BookingDetail::class);
    }

    public function syncLogs()
    {
        return $this->hasMany(SyncLog::class);
    }

    public function errorLogs()
    {
        return $this->hasMany(ErrorLog::class);
    }

    public function getGuestNameAttribute(): string
    {
        return trim($this->guest_first_name . ' ' . $this->guest_last_name);
    }

    public function getStayLengthAttribute(): string
    {
        if (!$this->check_in || !$this->check_out) {
            return '';
        }

        $checkIn = $this->check_in instanceof Carbon ? $this->check_in : Carbon::parse($this->check_in);
        $checkOut = $this->check_out instanceof Carbon ? $this->check_out : Carbon::parse($this->check_out);
        $nights = $this->nights ?: $checkIn->diffInDays($checkOut);

        return sprintf(
            '%s → %s (%d night%s)',
            $checkIn->format('d M Y'),
            $checkOut->format('d M Y'),
            $nights,
            $nights === 1 ? '' : 's'
        );
    }

    public function scopeForChannel(Builder $query, int $channelConnectionId): Builder
    {
        return $query->where('channel_connection_id', $channelConnectionId);
    }

    public function scopeBetweenDates(Builder $query, Carbon $start, Carbon $end): Builder
    {
        return $query
            ->whereDate('check_in', '>=', $start->toDateString())
            ->whereDate('check_out', '<=', $end->toDateString());
    }

    public function scopeUpcomingCheckIns(Builder $query, ?int $daysAhead = null): Builder
    {
        $start = Carbon::today();
        $end = $daysAhead ? $start->copy()->addDays($daysAhead) : $start;

        return $query
            ->whereDate('check_in', '>=', $start)
            ->whereDate('check_in', '<=', $end);
    }
}
