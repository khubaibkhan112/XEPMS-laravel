<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class BookingDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'reservation_id',
        'date',
        'rate',
        'original_rate',
        'adult_count',
        'child_count',
        'pricing_breakdown',
        'taxes',
        'fees',
        'status',
        'currency',
        'rate_plan_code',
        'board_basis',
        'channel_identifier',
        'is_derived_rate',
    ];

    protected $casts = [
        'date' => 'date',
        'rate' => 'decimal:2',
        'original_rate' => 'decimal:2',
        'adult_count' => 'integer',
        'child_count' => 'integer',
        'pricing_breakdown' => 'array',
        'taxes' => 'array',
        'fees' => 'array',
        'is_derived_rate' => 'boolean',
    ];

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }

    public function scopeBetweenDates(Builder $query, Carbon $start, Carbon $end): Builder
    {
        return $query->whereBetween('date', [$start->toDateString(), $end->toDateString()]);
    }

    public function scopeForChannelIdentifier(Builder $query, string $channelIdentifier): Builder
    {
        return $query->where('channel_identifier', $channelIdentifier);
    }
}
