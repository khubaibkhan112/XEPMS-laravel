<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Discount extends Model
{
    use HasFactory;

    public const TYPE_PERCENTAGE = 'percentage';
    public const TYPE_FIXED_AMOUNT = 'fixed_amount';
    public const TYPE_FREE_NIGHT = 'free_night';

    protected $fillable = [
        'property_id',
        'code',
        'name',
        'description',
        'type',
        'discount_value',
        'max_discount_amount',
        'min_purchase_amount',
        'currency',
        'room_type_id',
        'start_date',
        'end_date',
        'min_stay',
        'max_stay',
        'min_occupancy',
        'max_occupancy',
        'usage_limit',
        'usage_count',
        'usage_limit_per_user',
        'is_active',
        'is_public',
        'applicable_days',
        'excluded_dates',
        'included_dates',
        'loyalty_tier',
        'loyalty_points_required',
        'conditions',
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'max_discount_amount' => 'decimal:2',
        'min_purchase_amount' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'min_stay' => 'integer',
        'max_stay' => 'integer',
        'min_occupancy' => 'integer',
        'max_occupancy' => 'integer',
        'usage_limit' => 'integer',
        'usage_count' => 'integer',
        'usage_limit_per_user' => 'integer',
        'is_active' => 'boolean',
        'is_public' => 'boolean',
        'applicable_days' => 'array',
        'excluded_dates' => 'array',
        'included_dates' => 'array',
        'loyalty_points_required' => 'integer',
        'conditions' => 'array',
    ];

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function roomType()
    {
        return $this->belongsTo(RoomType::class);
    }

    /**
     * Check if discount applies to given parameters
     */
    public function appliesTo(
        ?int $roomTypeId = null,
        ?Carbon $checkIn = null,
        ?Carbon $checkOut = null,
        ?int $nights = null,
        ?int $adultCount = null,
        ?int $childCount = null,
        ?float $subtotal = null,
        ?int $userId = null
    ): bool {
        // Check if active
        if (!$this->is_active) {
            return false;
        }

        // Check date range
        $today = Carbon::today();
        if ($this->start_date && $today->lt($this->start_date)) {
            return false;
        }
        if ($this->end_date && $today->gt($this->end_date)) {
            return false;
        }

        // Check usage limit
        if ($this->usage_limit !== null && $this->usage_count >= $this->usage_limit) {
            return false;
        }

        // Check room type
        if ($this->room_type_id !== null && $roomTypeId !== $this->room_type_id) {
            return false;
        }

        // Check stay length
        if ($nights !== null) {
            if ($this->min_stay !== null && $nights < $this->min_stay) {
                return false;
            }
            if ($this->max_stay !== null && $nights > $this->max_stay) {
                return false;
            }
        }

        // Check occupancy
        if ($adultCount !== null || $childCount !== null) {
            $totalGuests = ($adultCount ?? 0) + ($childCount ?? 0);
            if ($this->min_occupancy !== null && $totalGuests < $this->min_occupancy) {
                return false;
            }
            if ($this->max_occupancy !== null && $totalGuests > $this->max_occupancy) {
                return false;
            }
        }

        // Check minimum purchase amount
        if ($subtotal !== null && $this->min_purchase_amount !== null && $subtotal < $this->min_purchase_amount) {
            return false;
        }

        // Check applicable dates
        if ($checkIn !== null) {
            if ($this->included_dates && !in_array($checkIn->toDateString(), $this->included_dates)) {
                return false;
            }
            if ($this->excluded_dates && in_array($checkIn->toDateString(), $this->excluded_dates)) {
                return false;
            }
        }

        // Check applicable days of week
        if ($checkIn !== null && $this->applicable_days) {
            $dayOfWeek = $checkIn->dayOfWeek;
            if (!in_array($dayOfWeek, $this->applicable_days)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Calculate discount amount
     */
    public function calculateDiscount(float $subtotal, int $nights = 1): float
    {
        $discount = 0;

        switch ($this->type) {
            case self::TYPE_PERCENTAGE:
                $discount = ($subtotal * $this->discount_value) / 100;
                if ($this->max_discount_amount !== null && $discount > $this->max_discount_amount) {
                    $discount = $this->max_discount_amount;
                }
                break;

            case self::TYPE_FIXED_AMOUNT:
                $discount = min($this->discount_value, $subtotal);
                break;

            case self::TYPE_FREE_NIGHT:
                // Free night discount - typically applies to the cheapest night
                // For simplicity, we'll calculate as average rate per night
                $averageRate = $nights > 0 ? ($subtotal / $nights) : 0;
                $freeNights = min((int) $this->discount_value, $nights);
                $discount = $averageRate * $freeNights;
                break;
        }

        return round($discount, 2);
    }

    /**
     * Increment usage count
     */
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }

    /**
     * Scope: Get active discounts
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('start_date')
                    ->orWhere('start_date', '<=', Carbon::today());
            })
            ->where(function ($q) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', Carbon::today());
            });
    }

    /**
     * Scope: Get public discounts
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope: Get discounts for property
     */
    public function scopeForProperty($query, int $propertyId)
    {
        return $query->where('property_id', $propertyId);
    }

    /**
     * Scope: Get discounts by code
     */
    public function scopeByCode($query, string $code)
    {
        return $query->where('code', strtoupper($code));
    }
}



