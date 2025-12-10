<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class Guest extends Model
{
    use HasFactory, SoftDeletes;

    public const TYPE_INDIVIDUAL = 'individual';
    public const TYPE_CORPORATE = 'corporate';
    public const TYPE_TRAVEL_AGENT = 'travel_agent';
    public const TYPE_GROUP = 'group';

    public const LOYALTY_NONE = 'none';
    public const LOYALTY_BRONZE = 'bronze';
    public const LOYALTY_SILVER = 'silver';
    public const LOYALTY_GOLD = 'gold';
    public const LOYALTY_PLATINUM = 'platinum';

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'country_code',
        'address_line1',
        'address_line2',
        'city',
        'state',
        'postal_code',
        'country',
        'date_of_birth',
        'gender',
        'nationality',
        'passport_number',
        'passport_expiry',
        'id_card_number',
        'id_card_type',
        'company_name',
        'company_address',
        'company_phone',
        'company_email',
        'guest_type',
        'loyalty_status',
        'loyalty_points',
        'email_verified',
        'phone_verified',
        'marketing_opt_in',
        'preferred_language',
        'preferred_currency',
        'notes',
        'tags',
        'custom_fields',
        'last_stay_at',
        'email_verified_at',
        'phone_verified_at',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'passport_expiry' => 'date',
        'loyalty_points' => 'integer',
        'email_verified' => 'boolean',
        'phone_verified' => 'boolean',
        'marketing_opt_in' => 'boolean',
        'tags' => 'array',
        'custom_fields' => 'array',
        'last_stay_at' => 'datetime',
        'email_verified_at' => 'datetime',
        'phone_verified_at' => 'datetime',
    ];

    protected $appends = ['full_name'];

    protected static function booted(): void
    {
        static::creating(function (Guest $guest) {
            // Auto-generate email if not provided (for phone-only registrations)
            if (!$guest->email && $guest->phone) {
                $guest->email = 'guest_' . time() . '@temp.local';
            }
        });

        static::updating(function (Guest $guest) {
            if ($guest->isDirty('email') && $guest->email && !str_contains($guest->email, '@temp.local')) {
                $guest->email_verified = false;
            }
            if ($guest->isDirty('phone') && $guest->phone) {
                $guest->phone_verified = false;
            }
        });
    }

    // Relationships
    public function reservations()
    {
        return $this->hasMany(Reservation::class, 'guest_id');
    }

    public function bookingHistory()
    {
        return $this->hasMany(GuestBookingHistory::class);
    }

    public function preferences()
    {
        return $this->hasMany(GuestPreference::class);
    }

    public function globalPreferences()
    {
        return $this->preferences()->whereNull('property_id');
    }

    public function propertyPreferences(int $propertyId)
    {
        return $this->preferences()->where('property_id', $propertyId);
    }

    // Accessors
    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    // Scopes
    public function scopeByEmail($query, string $email)
    {
        return $query->where('email', $email);
    }

    public function scopeByPhone($query, string $phone)
    {
        return $query->where('phone', $phone);
    }

    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%");
        });
    }

    public function scopeLoyaltyStatus($query, string $status)
    {
        return $query->where('loyalty_status', $status);
    }

    public function scopeGuestType($query, string $type)
    {
        return $query->where('guest_type', $type);
    }

    public function scopeVerified($query)
    {
        return $query->where('email_verified', true)->where('phone_verified', true);
    }

    // Helper methods
    public function getTotalBookings(): int
    {
        return $this->reservations()->count();
    }

    public function getTotalSpent(): float
    {
        return $this->reservations()->sum('total_amount');
    }

    public function getAverageRating(): ?float
    {
        return $this->bookingHistory()->whereNotNull('rating')->avg('rating');
    }

    public function hasStayedAt(int $propertyId): bool
    {
        return $this->reservations()
            ->where('property_id', $propertyId)
            ->whereIn('status', [Reservation::STATUS_CHECKED_OUT, Reservation::STATUS_CONFIRMED])
            ->exists();
    }

    public function updateLoyaltyStatus(): void
    {
        $totalSpent = $this->getTotalSpent();
        $bookings = $this->getTotalBookings();

        // Simple loyalty tier logic (can be customized)
        if ($totalSpent >= 10000 || $bookings >= 20) {
            $this->loyalty_status = self::LOYALTY_PLATINUM;
        } elseif ($totalSpent >= 5000 || $bookings >= 10) {
            $this->loyalty_status = self::LOYALTY_GOLD;
        } elseif ($totalSpent >= 2000 || $bookings >= 5) {
            $this->loyalty_status = self::LOYALTY_SILVER;
        } elseif ($totalSpent >= 500 || $bookings >= 2) {
            $this->loyalty_status = self::LOYALTY_BRONZE;
        }

        $this->save();
    }
}
