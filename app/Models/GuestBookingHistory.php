<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GuestBookingHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'guest_id',
        'reservation_id',
        'property_id',
        'check_in',
        'check_out',
        'nights',
        'total_amount',
        'paid_amount',
        'currency',
        'status',
        'payment_status',
        'rating',
        'review',
        'reviewed',
        'feedback',
    ];

    protected $casts = [
        'check_in' => 'date',
        'check_out' => 'date',
        'nights' => 'integer',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'rating' => 'decimal:2',
        'reviewed' => 'boolean',
        'feedback' => 'array',
    ];

    public function guest()
    {
        return $this->belongsTo(Guest::class);
    }

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }

    public function property()
    {
        return $this->belongsTo(Property::class);
    }
}
