<?php

namespace App\Services\ChannelManager\Contracts;

use App\Services\ChannelManager\DTO\ChannelResponse;
use Illuminate\Support\Carbon;

interface ChannelClient
{
    public function testConnection(): ChannelResponse;

    public function pullReservations(Carbon $start, Carbon $end, array $options = []): ChannelResponse;

    public function pushAvailability(array $payload, array $options = []): ChannelResponse;

    public function pushRates(array $payload, array $options = []): ChannelResponse;
}







