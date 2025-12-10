<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Property extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'code',
        'timezone',
        'currency',
        'email',
        'phone',
        'address',
        'settings',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'settings' => 'array',
        'is_active' => 'boolean',
    ];

    public function roomTypes()
    {
        return $this->hasMany(RoomType::class);
    }

    public function rooms()
    {
        return $this->hasMany(Room::class);
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }

    public function channelConnections()
    {
        return $this->hasMany(ChannelConnection::class);
    }

    public function availability()
    {
        return $this->hasMany(RoomAvailability::class);
    }

    public function auditTrails()
    {
        return $this->hasMany(AuditTrail::class);
    }

    public function checkIns()
    {
        return $this->hasMany(CheckIn::class);
    }

    public function keys()
    {
        return $this->hasMany(RoomKey::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function rates()
    {
        return $this->hasMany(Rate::class);
    }

    public function rateRules()
    {
        return $this->hasMany(RateRule::class);
    }

    public function taxRates()
    {
        return $this->hasMany(TaxRate::class);
    }

    public function refundPolicies()
    {
        return $this->hasMany(RefundPolicy::class);
    }

    public function checkOuts()
    {
        return $this->hasMany(CheckOut::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }
}
