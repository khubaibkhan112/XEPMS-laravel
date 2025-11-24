<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ErrorLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'channel_connection_id',
        'reservation_id',
        'channel',
        'environment',
        'context',
        'severity',
        'error_code',
        'request_id',
        'external_reference',
        'http_status',
        'message',
        'request_payload',
        'response_payload',
        'metadata',
        'stack_trace',
        'is_resolved',
        'occurred_at',
        'resolved_at',
        'resolved_by',
    ];

    protected $casts = [
        'request_payload' => 'array',
        'response_payload' => 'array',
        'metadata' => 'array',
        'http_status' => 'integer',
        'is_resolved' => 'boolean',
        'occurred_at' => 'datetime',
        'resolved_at' => 'datetime',
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
