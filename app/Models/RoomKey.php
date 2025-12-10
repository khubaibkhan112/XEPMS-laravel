<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoomKey extends Model
{
    use HasFactory;

    public const TYPE_PHYSICAL = 'physical';
    public const TYPE_ELECTRONIC = 'electronic';
    public const TYPE_CARD = 'card';
    public const TYPE_CODE = 'code';

    public const STATUS_AVAILABLE = 'available';
    public const STATUS_ISSUED = 'issued';
    public const STATUS_LOST = 'lost';
    public const STATUS_DAMAGED = 'damaged';
    public const STATUS_RETURNED = 'returned';

    protected $fillable = [
        'property_id',
        'room_id',
        'reservation_id',
        'check_in_id',
        'key_type',
        'key_identifier',
        'key_code',
        'issued_at',
        'returned_at',
        'issued_by',
        'returned_to',
        'status',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'issued_at' => 'datetime',
        'returned_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }

    public function checkIn()
    {
        return $this->belongsTo(CheckIn::class);
    }

    public function issuedBy()
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function returnedTo()
    {
        return $this->belongsTo(User::class, 'returned_to');
    }

    /**
     * Issue key to a reservation
     */
    public function issue(?int $userId = null): void
    {
        $this->update([
            'status' => self::STATUS_ISSUED,
            'issued_at' => now(),
            'issued_by' => $userId,
        ]);
    }

    /**
     * Return key
     */
    public function return(?int $userId = null): void
    {
        $this->update([
            'status' => self::STATUS_RETURNED,
            'returned_at' => now(),
            'returned_to' => $userId,
        ]);
    }

    /**
     * Mark key as lost
     */
    public function markAsLost(): void
    {
        $this->update([
            'status' => self::STATUS_LOST,
        ]);
    }

    /**
     * Mark key as damaged
     */
    public function markAsDamaged(): void
    {
        $this->update([
            'status' => self::STATUS_DAMAGED,
        ]);
    }

    /**
     * Scope: Get available keys
     */
    public function scopeAvailable($query)
    {
        return $query->where('status', self::STATUS_AVAILABLE);
    }

    /**
     * Scope: Get issued keys
     */
    public function scopeIssued($query)
    {
        return $query->where('status', self::STATUS_ISSUED);
    }

    /**
     * Scope: Get keys for a room
     */
    public function scopeForRoom($query, int $roomId)
    {
        return $query->where('room_id', $roomId);
    }
}




