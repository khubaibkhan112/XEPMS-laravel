<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OtaMapping extends Model
{
    use HasFactory;

    protected $fillable = [
        'channel_connection_id',
        'room_type_id',
        'room_id',
        'channel_identifier',
        'ota_room_id',
        'ota_rate_plan_id',
        'ota_listing_id',
        'ota_product_code',
        'market',
        'locale',
        'currency',
        'sync_direction',
        'pricing_model',
        'rate_multiplier',
        'tax_inclusive',
        'lead_time_days',
        'restrictions',
        'meta',
        'is_active',
        'last_synced_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'meta' => 'array',
        'rate_multiplier' => 'decimal:4',
        'tax_inclusive' => 'boolean',
        'lead_time_days' => 'integer',
        'restrictions' => 'array',
        'last_synced_at' => 'datetime',
    ];

    public function channelConnection()
    {
        return $this->belongsTo(ChannelConnection::class);
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
