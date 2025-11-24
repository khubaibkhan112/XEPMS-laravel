<?php

namespace App\Services\ChannelManager\BookingCom;

use App\Models\ChannelConnection;
use App\Services\ChannelManager\Contracts\ChannelClient;
use App\Services\ChannelManager\DTO\ChannelResponse;
use App\Services\ChannelManager\Exceptions\ChannelException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Throwable;

class BookingComClient implements ChannelClient
{
    public function __construct(
        protected readonly string $username,
        protected readonly string $password,
        protected readonly string $baseUrl,
        protected readonly array $config = [],
    ) {
    }

    public static function fromConnection(ChannelConnection $connection): self
    {
        $credentials = $connection->credentials ?? [];
        $settings = $connection->settings ?? [];

        $channelConfig = Config::get('channel_manager.channels.booking_com', []);

        $environment = $connection->uses_sandbox ? 'sandbox' : 'production';
        $baseUrl = $connection->api_base_url
            ?: Arr::get($channelConfig, "base_urls.{$environment}");

        if (!$baseUrl) {
            throw new ChannelException('Booking.com base URL is not configured.');
        }

        $username = Arr::get($credentials, 'username', config('services.booking_com.username'));
        $password = Arr::get($credentials, 'password', config('services.booking_com.password'));

        if (!$username || !$password) {
            throw new ChannelException('Booking.com credentials are missing.');
        }

        return new self(
            username: $username,
            password: $password,
            baseUrl: rtrim($baseUrl, '/'),
            config: [
                'environment' => $environment,
                'timeout' => Arr::get($settings, 'timeout', config('services.booking_com.timeout', 30)),
                'connect_timeout' => Arr::get($settings, 'connect_timeout', config('services.booking_com.connect_timeout', 10)),
                'headers' => Arr::get($settings, 'headers', []),
                'channel' => $connection->channel,
                'locale' => $connection->locale ?? config('channel_manager.default_locale'),
                'currency' => $connection->currency ?? config('channel_manager.default_currency'),
                'timezone' => $connection->timezone ?? config('channel_manager.default_timezone'),
            ],
        );
    }

    public function testConnection(): ChannelResponse
    {
        try {
            $response = $this->http()->get($this->endpoint('test_connection'));

            if ($response->successful()) {
                return ChannelResponse::success(
                    'Booking.com connection succeeded.',
                    data: $response->json() ?? [],
                    meta: ['timestamp' => now()->toIso8601String()],
                );
            }

            return ChannelResponse::failure(
                sprintf('Booking.com connection failed with status %s.', $response->status()),
                data: $response->json() ?? [],
                meta: ['timestamp' => now()->toIso8601String()],
            );
        } catch (Throwable $exception) {
            report($exception);

            return ChannelResponse::failure(
                'Booking.com connection test encountered an error.',
                meta: [
                    'timestamp' => now()->toIso8601String(),
                    'exception' => $exception->getMessage(),
                ],
            );
        }
    }

    public function pullReservations(Carbon $start, Carbon $end, array $options = []): ChannelResponse
    {
        $payload = array_merge([
            'from' => $start->toIso8601String(),
            'to' => $end->toIso8601String(),
            'currency' => $this->config['currency'],
            'locale' => $this->config['locale'],
        ], $options);

        try {
            $response = $this->http()->get($this->endpoint('reservations'), $payload);

            if ($response->successful()) {
                return ChannelResponse::success(
                    'Reservations retrieved from Booking.com.',
                    data: $response->json() ?? [],
                );
            }

            return ChannelResponse::failure(
                sprintf('Failed to retrieve Booking.com reservations. Status: %s', $response->status()),
                data: $response->json() ?? [],
            );
        } catch (Throwable $exception) {
            report($exception);

            return ChannelResponse::failure(
                'Unexpected error while retrieving Booking.com reservations.',
                meta: ['exception' => $exception->getMessage()],
            );
        }
    }

    public function pushAvailability(array $payload, array $options = []): ChannelResponse
    {
        $body = array_merge([
            'timezone' => $this->config['timezone'],
            'currency' => $this->config['currency'],
        ], $payload, $options);

        try {
            $response = $this->http()->post($this->endpoint('availability'), $body);

            if ($response->successful()) {
                return ChannelResponse::success(
                    'Availability pushed to Booking.com.',
                    data: $response->json() ?? [],
                );
            }

            return ChannelResponse::failure(
                sprintf('Failed to push availability to Booking.com. Status: %s', $response->status()),
                data: $response->json() ?? [],
            );
        } catch (Throwable $exception) {
            report($exception);

            return ChannelResponse::failure(
                'Unexpected error while pushing availability to Booking.com.',
                meta: ['exception' => $exception->getMessage()],
            );
        }
    }

    public function pushRates(array $payload, array $options = []): ChannelResponse
    {
        $body = array_merge([
            'currency' => $this->config['currency'],
        ], $payload, $options);

        try {
            $response = $this->http()->post($this->endpoint('rates'), $body);

            if ($response->successful()) {
                return ChannelResponse::success(
                    'Rates pushed to Booking.com.',
                    data: $response->json() ?? [],
                );
            }

            return ChannelResponse::failure(
                sprintf('Failed to push rates to Booking.com. Status: %s', $response->status()),
                data: $response->json() ?? [],
            );
        } catch (Throwable $exception) {
            report($exception);

            return ChannelResponse::failure(
                'Unexpected error while pushing rates to Booking.com.',
                meta: ['exception' => $exception->getMessage()],
            );
        }
    }

    protected function endpoint(string $name): string
    {
        $path = Arr::get($this->config('endpoints', []), $name);

        if (!$path) {
            throw new ChannelException(sprintf('Booking.com endpoint "%s" is not configured.', $name));
        }

        return $this->baseUrl . $path;
    }

    protected function http(): PendingRequest
    {
        return Http::withBasicAuth($this->username, $this->password)
            ->timeout($this->config['timeout'])
            ->connectTimeout($this->config['connect_timeout'])
            ->withHeaders(array_merge([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'X-Channel-Environment' => $this->config['environment'],
                'X-Channel-Source' => 'xepms-channel-manager',
            ], $this->config['headers']));
    }

    protected function config(?string $key = null, mixed $default = null): mixed
    {
        $channelConfig = Config::get('channel_manager.channels.booking_com', []);

        if ($key === null) {
            return $channelConfig;
        }

        return Arr::get($channelConfig, $key, $default);
    }
}





