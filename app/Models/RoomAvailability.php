<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class RoomAvailability extends Model
{
    use HasFactory;

    protected $table = 'room_availability';

    protected $fillable = [
        'property_id',
        'room_type_id',
        'room_id',
        'date',
        'available_count',
        'is_available',
        'closed_to_arrival',
        'closed_to_departure',
        'min_stay',
        'max_stay',
        'rate',
        'rate_plan_code',
        'currency',
        'board_basis',
        'inventory_source',
        'channel_identifier',
        'max_occupancy',
        'min_advance_booking_days',
        'max_advance_booking_days',
        'restrictions',
        'last_synced_at',
    ];

    protected $casts = [
        'date' => 'date',
        'is_available' => 'boolean',
        'closed_to_arrival' => 'boolean',
        'closed_to_departure' => 'boolean',
        'min_stay' => 'integer',
        'max_stay' => 'integer',
        'available_count' => 'integer',
        'rate' => 'decimal:2',
        'restrictions' => 'array',
        'max_occupancy' => 'integer',
        'min_advance_booking_days' => 'integer',
        'max_advance_booking_days' => 'integer',
        'last_synced_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (RoomAvailability $availability) {
            $availability->currency ??= 'GBP';
        });
    }

    public function scopeBetweenDates(Builder $query, Carbon $start, Carbon $end): Builder
    {
        return $query->whereBetween('date', [$start->toDateString(), $end->toDateString()]);
    }

    public function scopeForChannelIdentifier(Builder $query, string $channelIdentifier): Builder
    {
        return $query->where('channel_identifier', $channelIdentifier);
    }

    public function scopeOnlyAvailable(Builder $query): Builder
    {
        return $query->where('is_available', true)->where('available_count', '>', 0);
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
}
