<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoomFeature extends Model
{
    use HasFactory;

    public const TYPE_ADDON = 'addon';
    public const TYPE_EXTRA_BED = 'extra_bed';
    public const TYPE_AMENITY = 'amenity';
    public const TYPE_SERVICE = 'service';

    public const PRICING_PER_NIGHT = 'per_night';
    public const PRICING_PER_STAY = 'per_stay';
    public const PRICING_PER_PERSON = 'per_person';
    public const PRICING_PER_PERSON_PER_NIGHT = 'per_person_per_night';

    protected $fillable = [
        'property_id',
        'room_type_id',
        'name',
        'code',
        'type',
        'description',
        'price',
        'pricing_type',
        'currency',
        'max_quantity',
        'is_required',
        'is_active',
        'sort_order',
        'conditions',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'max_quantity' => 'integer',
        'is_required' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
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
     * Check if feature applies to given parameters
     */
    public function appliesTo(?int $roomTypeId = null, ?int $nights = null, ?\Illuminate\Support\Carbon $date = null): bool
    {
        // Check if feature is active
        if (!$this->is_active) {
            return false;
        }

        // Check room type match
        if ($this->room_type_id !== null && $roomTypeId !== $this->room_type_id) {
            return false;
        }

        // Check conditions
        $conditions = $this->conditions ?? [];
        
        if ($nights !== null) {
            if (isset($conditions['min_stay']) && $nights < $conditions['min_stay']) {
                return false;
            }
            if (isset($conditions['max_stay']) && $nights > $conditions['max_stay']) {
                return false;
            }
        }

        if ($date !== null) {
            if (isset($conditions['applicable_dates'])) {
                $dateStr = $date->toDateString();
                if (!in_array($dateStr, $conditions['applicable_dates'])) {
                    return false;
                }
            }
            if (isset($conditions['excluded_dates'])) {
                $dateStr = $date->toDateString();
                if (in_array($dateStr, $conditions['excluded_dates'])) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Calculate price for this feature
     */
    public function calculatePrice(int $quantity = 1, int $nights = 1, int $personCount = 1): float
    {
        $basePrice = (float) $this->price;

        switch ($this->pricing_type) {
            case self::PRICING_PER_STAY:
                return $basePrice * $quantity;

            case self::PRICING_PER_PERSON:
                return $basePrice * $quantity * $personCount;

            case self::PRICING_PER_PERSON_PER_NIGHT:
                return $basePrice * $quantity * $personCount * $nights;

            case self::PRICING_PER_NIGHT:
            default:
                return $basePrice * $quantity * $nights;
        }
    }

    /**
     * Scope: Get features for a property
     */
    public function scopeForProperty($query, int $propertyId)
    {
        return $query->where('property_id', $propertyId);
    }

    /**
     * Scope: Get features for a room type
     */
    public function scopeForRoomType($query, ?int $roomTypeId)
    {
        return $query->where(function ($q) use ($roomTypeId) {
            $q->whereNull('room_type_id')
                ->orWhere('room_type_id', $roomTypeId);
        });
    }

    /**
     * Scope: Get active features
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Get features by type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }
}






