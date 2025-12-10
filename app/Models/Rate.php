<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Rate extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id',
        'room_type_id',
        'date',
        'rate',
        'base_rate',
        'currency',
        'rate_type',
        'rate_plan_code',
        'is_active',
        'valid_from',
        'valid_to',
        'description',
        'conditions',
    ];

    protected $casts = [
        'date' => 'date',
        'rate' => 'decimal:2',
        'base_rate' => 'decimal:2',
        'valid_from' => 'date',
        'valid_to' => 'date',
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

    public function scopeForDate($query, Carbon $date)
    {
        return $query->where('date', $date->toDateString());
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeBetweenDates($query, Carbon $start, Carbon $end)
    {
        return $query->whereBetween('date', [$start->toDateString(), $end->toDateString()]);
    }
}
