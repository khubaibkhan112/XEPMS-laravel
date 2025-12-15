<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class CheckIn extends Model
{
    use HasFactory;

    public const STATUS_COMPLETED = 'completed';
    public const STATUS_PENDING = 'pending';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'reservation_id',
        'property_id',
        'room_id',
        'checked_in_by',
        'checked_in_at',
        'expected_check_in_at',
        'actual_check_in_at',
        'early_check_in_minutes',
        'late_check_in_minutes',
        'guest_count',
        'adult_count',
        'child_count',
        'identification_type',
        'identification_number',
        'identification_issued_by',
        'identification_expiry_date',
        'vehicle_registration',
        'parking_space',
        'special_requests',
        'notes',
        'guest_signature',
        'documents',
        'status',
    ];

    protected $casts = [
        'checked_in_at' => 'datetime',
        'expected_check_in_at' => 'datetime',
        'actual_check_in_at' => 'datetime',
        'identification_expiry_date' => 'date',
        'early_check_in_minutes' => 'integer',
        'late_check_in_minutes' => 'integer',
        'guest_count' => 'integer',
        'adult_count' => 'integer',
        'child_count' => 'integer',
        'guest_signature' => 'array',
        'documents' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (CheckIn $checkIn) {
            if (!$checkIn->checked_in_at) {
                $checkIn->checked_in_at = now();
            }
            if (!$checkIn->actual_check_in_at) {
                $checkIn->actual_check_in_at = now();
            }

            // Calculate early/late check-in
            if ($checkIn->expected_check_in_at && $checkIn->actual_check_in_at) {
                $diffMinutes = $checkIn->expected_check_in_at->diffInMinutes($checkIn->actual_check_in_at, false);
                if ($diffMinutes < 0) {
                    $checkIn->early_check_in_minutes = abs($diffMinutes);
                } else {
                    $checkIn->late_check_in_minutes = $diffMinutes;
                }
            }
        });
    }

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function checkedInBy()
    {
        return $this->belongsTo(User::class, 'checked_in_by');
    }

    public function keys()
    {
        return $this->hasMany(RoomKey::class);
    }

    /**
     * Scope: Get check-ins for a property
     */
    public function scopeForProperty($query, int $propertyId)
    {
        return $query->where('property_id', $propertyId);
    }

    /**
     * Scope: Get check-ins for today
     */
    public function scopeToday($query)
    {
        return $query->whereDate('checked_in_at', Carbon::today());
    }

    /**
     * Scope: Get upcoming check-ins
     */
    public function scopeUpcoming($query, ?int $daysAhead = null)
    {
        $start = Carbon::today();
        $end = $daysAhead ? $start->copy()->addDays($daysAhead) : $start;

        return $query->whereHas('reservation', function ($q) use ($start, $end) {
            $q->whereDate('check_in', '>=', $start)
                ->whereDate('check_in', '<=', $end)
                ->where('status', '!=', Reservation::STATUS_CANCELLED)
                ->where('status', '!=', Reservation::STATUS_CHECKED_IN);
        });
    }
}






