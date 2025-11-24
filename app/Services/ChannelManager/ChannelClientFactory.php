<?php

namespace App\Services\ChannelManager;

use App\Models\ChannelConnection;
use App\Services\ChannelManager\BookingCom\BookingComClient;
use App\Services\ChannelManager\Airbnb\AirbnbClient;
use App\Services\ChannelManager\Contracts\ChannelClient;
use App\Services\ChannelManager\Exceptions\ChannelException;
use App\Services\ChannelManager\Expedia\ExpediaClient;

class ChannelClientFactory
{
    public static function make(ChannelConnection $connection): ChannelClient
    {
        return match ($connection->channel) {
            'booking_com' => BookingComClient::fromConnection($connection),
            'expedia' => ExpediaClient::fromConnection($connection),
            'airbnb' => AirbnbClient::fromConnection($connection),
            default => throw new ChannelException(sprintf(
                'Channel client for "%s" is not yet implemented.',
                $connection->channel
            )),
        };
    }
}

