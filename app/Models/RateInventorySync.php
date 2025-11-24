<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class RateInventorySync extends Model
{
    use HasFactory;

    protected $table = 'rate_inventory_sync';

    protected $fillable = [
        'channel_connection_id',
        'room_type_id',
        'date',
        'channel',
        'environment',
        'operation',
        'sync_type',
        'direction',
        'status',
        'currency',
        'rate_plan_code',
        'inventory_source',
        'payload_hash',
        'request_id',
        'priority',
        'retry_limit',
        'attempts',
        'response_time_ms',
        'payload',
        'response',
        'response_code',
        'response_message',
        'last_error_code',
        'last_error_message',
        'scheduled_at',
        'attempted_at',
        'completed_at',
        'last_synced_at',
    ];

    protected $casts = [
        'date' => 'date',
        'payload' => 'array',
        'response' => 'array',
        'scheduled_at' => 'datetime',
        'attempted_at' => 'datetime',
        'completed_at' => 'datetime',
        'attempts' => 'integer',
        'priority' => 'integer',
        'retry_limit' => 'integer',
        'response_time_ms' => 'integer',
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

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeScheduled(Builder $query): Builder
    {
        return $query
            ->whereNotNull('scheduled_at')
            ->where('status', 'scheduled')
            ->where('scheduled_at', '<=', Carbon::now());
    }

    public function scopeForChannel(Builder $query, string $channel): Builder
    {
        return $query->where('channel', $channel);
    }
}
