<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id',
        'room_type_id',
        'name',
        'room_number',
        'status',
        'floor',
        'max_occupancy',
        'attributes',
        'is_active',
    ];

    protected $casts = [
        'attributes' => 'array',
        'is_active' => 'boolean',
        'max_occupancy' => 'integer',
    ];

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function roomType()
    {
        return $this->belongsTo(RoomType::class);
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }

    public function availability()
    {
        return $this->hasMany(RoomAvailability::class);
    }

    public function otaMappings()
    {
        return $this->hasMany(OtaMapping::class);
    }
}
