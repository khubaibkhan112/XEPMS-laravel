<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_SENT = 'sent';
    public const STATUS_PAID = 'paid';
    public const STATUS_OVERDUE = 'overdue';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'reservation_id',
        'property_id',
        'check_out_id',
        'invoice_number',
        'invoice_date',
        'due_date',
        'status',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'additional_charges',
        'total_amount',
        'paid_amount',
        'balance_due',
        'currency',
        'line_items',
        'tax_breakdown',
        'notes',
        'terms',
        'payment_terms',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'additional_charges' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'balance_due' => 'decimal:2',
        'line_items' => 'array',
        'tax_breakdown' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (Invoice $invoice) {
            if (!$invoice->invoice_number) {
                $invoice->invoice_number = static::generateInvoiceNumber($invoice->property_id);
            }
            if (!$invoice->invoice_date) {
                $invoice->invoice_date = now()->toDateString();
            }
            
            // Calculate balance due
            if ($invoice->total_amount !== null && $invoice->paid_amount !== null) {
                $invoice->balance_due = max(0, $invoice->total_amount - $invoice->paid_amount);
            }
        });

        static::updating(function (Invoice $invoice) {
            // Recalculate balance due
            if ($invoice->total_amount !== null && $invoice->paid_amount !== null) {
                $invoice->balance_due = max(0, $invoice->total_amount - $invoice->paid_amount);
            }
        });
    }

    protected static function generateInvoiceNumber(int $propertyId): string
    {
        $property = \App\Models\Property::find($propertyId);
        $prefix = $property ? strtoupper(substr($property->code ?? 'INV', 0, 3)) : 'INV';
        $year = now()->year;
        $month = now()->format('m');
        
        $lastInvoice = static::where('invoice_number', 'like', "{$prefix}-{$year}{$month}%")
            ->orderBy('id', 'desc')
            ->first();
        
        if ($lastInvoice) {
            $lastNumber = (int) substr($lastInvoice->invoice_number, -4);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }
        
        return sprintf('%s-%s%04d', $prefix, $year . $month, $nextNumber);
    }

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function checkOut()
    {
        return $this->belongsTo(CheckOut::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class, 'reservation_id', 'reservation_id')
            ->whereNotNull('invoice_id');
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID || $this->balance_due <= 0;
    }

    public function isOverdue(): bool
    {
        return $this->due_date && $this->due_date->isPast() && !$this->isPaid();
    }
}
