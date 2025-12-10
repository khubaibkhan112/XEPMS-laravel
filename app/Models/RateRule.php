<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class RateRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id',
        'room_type_id',
        'name',
        'rule_type',
        'rate_adjustment',
        'rate_multiplier',
        'fixed_rate',
        'adjustment_type',
        'valid_from',
        'valid_to',
        'conditions',
        'priority',
        'is_active',
        'description',
    ];

    protected $casts = [
        'rate_adjustment' => 'decimal:2',
        'rate_multiplier' => 'decimal:2',
        'fixed_rate' => 'decimal:2',
        'valid_from' => 'date',
        'valid_to' => 'date',
        'priority' => 'integer',
        'is_active' => 'boolean',
        'conditions' => 'array',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeApplicableForDate($query, Carbon $date)
    {
        return $query->where(function ($q) use ($date) {
            $q->whereNull('valid_from')
                ->orWhere('valid_from', '<=', $date->toDateString());
        })->where(function ($q) use ($date) {
            $q->whereNull('valid_to')
                ->orWhere('valid_to', '>=', $date->toDateString());
        });
    }

    public function scopeByPriority($query)
    {
        return $query->orderBy('priority', 'desc');
    }
}
