<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SyncLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'channel_connection_id',
        'reservation_id',
        'channel_connection_id',
        'reservation_id',
        'channel',
        'environment',
        'operation',
        'direction',
        'entity_type',
        'entity_id',
        'status',
        'request_id',
        'external_reference',
        'http_method',
        'endpoint',
        'response_code',
        'response_time_ms',
        'message',
        'request_payload',
        'response_payload',
        'metadata',
        'retry_count',
        'performed_at',
        'next_retry_at',
    ];

    protected $casts = [
        'request_payload' => 'array',
        'response_payload' => 'array',
        'metadata' => 'array',
        'response_code' => 'integer',
        'response_time_ms' => 'integer',
        'retry_count' => 'integer',
        'performed_at' => 'datetime',
        'next_retry_at' => 'datetime',
    ];

    public function channelConnection()
    {
        return $this->belongsTo(ChannelConnection::class);
    }

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }
}
