<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class TaxRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id',
        'name',
        'code',
        'rate',
        'tax_type',
        'fixed_amount',
        'calculation_type',
        'is_compound',
        'priority',
        'is_active',
        'valid_from',
        'valid_to',
        'conditions',
        'description',
    ];

    protected $casts = [
        'rate' => 'decimal:2',
        'fixed_amount' => 'decimal:2',
        'priority' => 'integer',
        'is_active' => 'boolean',
        'is_compound' => 'boolean',
        'valid_from' => 'date',
        'valid_to' => 'date',
        'conditions' => 'array',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeApplicableForDate($query, Carbon $date)
    {
        return $query->where(function ($q) use ($date) {
            $q->whereNull('valid_from')
                ->orWhere('valid_from', '<=', $date->toDateString());
        })->where(function ($q) use ($date) {
            $q->whereNull('valid_to')
                ->orWhere('valid_to', '>=', $date->toDateString());
        });
    }

    public function scopeByPriority($query)
    {
        return $query->orderBy('priority', 'asc');
    }
}
