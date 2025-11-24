<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoomType extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id',
        'name',
        'code',
        'base_occupancy',
        'max_occupancy',
        'base_rate',
        'currency',
        'description',
        'amenities',
        'is_active',
    ];

    protected $casts = [
        'amenities' => 'array',
        'is_active' => 'boolean',
        'base_occupancy' => 'integer',
        'max_occupancy' => 'integer',
        'base_rate' => 'decimal:2',
    ];

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function rooms()
    {
        return $this->hasMany(Room::class);
    }

    public function reservations()
    {
        return $this->hasManyThrough(Reservation::class, Room::class);
    }

    public function availability()
    {
        return $this->hasMany(RoomAvailability::class);
    }

    public function otaMappings()
    {
        return $this->hasMany(OtaMapping::class);
    }

    public function rateSyncs()
    {
        return $this->hasMany(RateInventorySync::class);
    }
}
