<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\AsEncryptedArrayObject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChannelConnection extends Model
{
    use HasFactory;

    public const SUPPORTED_CHANNELS = [
        'booking_com' => 'Booking.com',
        'expedia' => 'Expedia',
        'airbnb' => 'Airbnb',
        'tripadvisor' => 'Tripadvisor',
        'viator' => 'Viator',
        'hotels_com' => 'Hotels.com',
        'laterooms' => 'LateRooms.com',
        'travel_republic' => 'Travel Republic',
    ];

    protected $fillable = [
        'property_id',
        'name',
        'channel',
        'connection_type',
        'uses_sandbox',
        'api_base_url',
        'is_active',
        'credentials',
        'settings',
        'last_successful_sync_at',
        'last_attempted_sync_at',
        'timezone',
        'region',
        'locale',
        'currency',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'uses_sandbox' => 'boolean',
        'credentials' => AsEncryptedArrayObject::class,
        'settings' => 'array',
        'last_successful_sync_at' => 'datetime',
        'last_attempted_sync_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (ChannelConnection $connection) {
            if (!in_array($connection->channel, self::supportedChannels(), true)) {
                throw new \InvalidArgumentException(sprintf(
                    'Unsupported channel "%s".',
                    $connection->channel
                ));
            }

            $connection->currency ??= config('channel_manager.default_currency');
            $connection->locale ??= config('channel_manager.default_locale');
            $connection->timezone ??= config('channel_manager.default_timezone');
            $connection->region ??= 'UK';
        });
    }

    public static function supportedChannels(): array
    {
        return array_keys(self::SUPPORTED_CHANNELS);
    }

    public static function supportedChannelOptions(): array
    {
        return self::SUPPORTED_CHANNELS;
    }

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function otaMappings()
    {
        return $this->hasMany(OtaMapping::class);
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }

    public function syncLogs()
    {
        return $this->hasMany(SyncLog::class);
    }

    public function errorLogs()
    {
        return $this->hasMany(ErrorLog::class);
    }

    public function rateSyncs()
    {
        return $this->hasMany(RateInventorySync::class);
    }
}
