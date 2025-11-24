<?php

namespace App\Services\ChannelManager\Airbnb;

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

class AirbnbClient implements ChannelClient
{
    public function __construct(
        protected readonly string $clientId,
        protected readonly string $clientSecret,
        protected readonly string $baseUrl,
        protected readonly array $config = [],
    ) {
    }

    public static function fromConnection(ChannelConnection $connection): self
    {
        $credentials = $connection->credentials ?? [];
        $settings = $connection->settings ?? [];

        $channelConfig = Config::get('channel_manager.channels.airbnb', []);

        $environment = $connection->uses_sandbox ? 'sandbox' : 'production';
        $baseUrl = $connection->api_base_url
            ?: Arr::get($channelConfig, "base_urls.{$environment}");

        if (!$baseUrl) {
            throw new ChannelException('Airbnb base URL is not configured.');
        }

        $clientId = Arr::get($credentials, 'client_id', config('services.airbnb.client_id'));
        $clientSecret = Arr::get($credentials, 'client_secret', config('services.airbnb.client_secret'));

        if (!$clientId || !$clientSecret) {
            throw new ChannelException('Airbnb credentials are missing.');
        }

        return new self(
            clientId: $clientId,
            clientSecret: $clientSecret,
            baseUrl: rtrim($baseUrl, '/'),
            config: [
                'environment' => $environment,
                'timeout' => Arr::get($settings, 'timeout', config('services.airbnb.timeout', 30)),
                'connect_timeout' => Arr::get($settings, 'connect_timeout', config('services.airbnb.connect_timeout', 10)),
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
                    'Airbnb connection succeeded.',
                    data: $response->json() ?? [],
                    meta: ['timestamp' => now()->toIso8601String()],
                );
            }

            return ChannelResponse::failure(
                sprintf('Airbnb connection failed with status %s.', $response->status()),
                data: $response->json() ?? [],
                meta: ['timestamp' => now()->toIso8601String()],
            );
        } catch (Throwable $exception) {
            report($exception);

            return ChannelResponse::failure(
                'Airbnb connection test encountered an error.',
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
            'start_date' => $start->toIso8601String(),
            'end_date' => $end->toIso8601String(),
            'currency' => $this->config['currency'],
            'locale' => $this->config['locale'],
        ], $options);

        try {
            $response = $this->http()->get($this->endpoint('reservations'), $payload);

            if ($response->successful()) {
                return ChannelResponse::success(
                    'Reservations retrieved from Airbnb.',
                    data: $response->json() ?? [],
                );
            }

            return ChannelResponse::failure(
                sprintf('Failed to retrieve Airbnb reservations. Status: %s', $response->status()),
                data: $response->json() ?? [],
            );
        } catch (Throwable $exception) {
            report($exception);

            return ChannelResponse::failure(
                'Unexpected error while retrieving Airbnb reservations.',
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
                    'Availability pushed to Airbnb.',
                    data: $response->json() ?? [],
                );
            }

            return ChannelResponse::failure(
                sprintf('Failed to push availability to Airbnb. Status: %s', $response->status()),
                data: $response->json() ?? [],
            );
        } catch (Throwable $exception) {
            report($exception);

            return ChannelResponse::failure(
                'Unexpected error while pushing availability to Airbnb.',
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
                    'Rates pushed to Airbnb.',
                    data: $response->json() ?? [],
                );
            }

            return ChannelResponse::failure(
                sprintf('Failed to push rates to Airbnb. Status: %s', $response->status()),
                data: $response->json() ?? [],
            );
        } catch (Throwable $exception) {
            report($exception);

            return ChannelResponse::failure(
                'Unexpected error while pushing rates to Airbnb.',
                meta: ['exception' => $exception->getMessage()],
            );
        }
    }

    protected function endpoint(string $name): string
    {
        $path = Arr::get($this->config(), "endpoints.{$name}");

        if (!$path) {
            throw new ChannelException(sprintf('Airbnb endpoint "%s" is not configured.', $name));
        }

        return $this->baseUrl . $path;
    }

    protected function http(): PendingRequest
    {
        $token = base64_encode($this->clientId . ':' . $this->clientSecret);

        return Http::withHeaders(array_merge([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => "Basic {$token}",
                'X-Channel-Environment' => $this->config['environment'],
                'X-Channel-Source' => 'xepms-channel-manager',
            ], $this->config['headers'] ?? []))
            ->timeout($this->config['timeout'])
            ->connectTimeout($this->config['connect_timeout']);
    }

    protected function config(?string $key = null, mixed $default = null): mixed
    {
        $channelConfig = Config::get('channel_manager.channels.airbnb', []);

        if ($key === null) {
            return $channelConfig;
        }

        return Arr::get($channelConfig, $key, $default);
    }
}





