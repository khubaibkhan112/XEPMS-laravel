<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class CheckOut extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    public const PAYMENT_PENDING = 'pending';
    public const PAYMENT_PAID = 'paid';
    public const PAYMENT_PARTIAL = 'partial';

    protected $fillable = [
        'reservation_id',
        'property_id',
        'room_id',
        'invoice_id',
        'checked_out_by',
        'expected_check_out_at',
        'actual_check_out_at',
        'early_check_out_minutes',
        'late_check_out_minutes',
        'guest_count',
        'departure_notes',
        'room_condition',
        'key_return',
        'incidentals',
        'additional_charges',
        'damages',
        'final_amount',
        'payment_method',
        'payment_status',
        'status',
    ];

    protected $casts = [
        'expected_check_out_at' => 'datetime',
        'actual_check_out_at' => 'datetime',
        'early_check_out_minutes' => 'integer',
        'late_check_out_minutes' => 'integer',
        'guest_count' => 'integer',
        'room_condition' => 'array',
        'key_return' => 'array',
        'incidentals' => 'array',
        'additional_charges' => 'decimal:2',
        'damages' => 'decimal:2',
        'final_amount' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (CheckOut $checkOut) {
            if (!$checkOut->actual_check_out_at) {
                $checkOut->actual_check_out_at = now();
            }

            // Calculate early/late check-out
            if ($checkOut->expected_check_out_at && $checkOut->actual_check_out_at) {
                $diffMinutes = $checkOut->expected_check_out_at->diffInMinutes($checkOut->actual_check_out_at, false);
                if ($diffMinutes < 0) {
                    $checkOut->early_check_out_minutes = abs($diffMinutes);
                } else {
                    $checkOut->late_check_out_minutes = $diffMinutes;
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

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function checkedOutBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'checked_out_by');
    }

    public function scopeForProperty($query, int $propertyId)
    {
        return $query->where('property_id', $propertyId);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('actual_check_out_at', Carbon::today());
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }
}
