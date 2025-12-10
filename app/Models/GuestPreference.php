<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GuestPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'guest_id',
        'property_id',
        'preference_type',
        'preference_key',
        'preference_value',
        'notes',
        'priority',
        'is_active',
    ];

    protected $casts = [
        'priority' => 'integer',
        'is_active' => 'boolean',
    ];

    public function guest()
    {
        return $this->belongsTo(Guest::class);
    }

    public function property()
    {
        return $this->belongsTo(Property::class);
    }
}
