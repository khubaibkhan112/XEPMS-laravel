<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RefundPolicy extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id',
        'name',
        'description',
        'refund_type',
        'refund_percentage',
        'fixed_amount',
        'days_before_checkin',
        'days_after_booking',
        'minimum_nights',
        'requires_cancellation_reason',
        'allowed_cancellation_reasons',
        'is_default',
        'is_active',
        'priority',
    ];

    protected $casts = [
        'refund_percentage' => 'decimal:2',
        'fixed_amount' => 'decimal:2',
        'days_before_checkin' => 'integer',
        'days_after_booking' => 'integer',
        'minimum_nights' => 'integer',
        'requires_cancellation_reason' => 'boolean',
        'allowed_cancellation_reasons' => 'array',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'priority' => 'integer',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }
}
