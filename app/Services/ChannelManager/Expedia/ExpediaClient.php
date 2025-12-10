<?php

namespace App\Services\ChannelManager\Expedia;

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

class ExpediaClient implements ChannelClient
{
    public function __construct(
        protected readonly string $apiKey,
        protected readonly string $apiSecret,
        protected readonly string $baseUrl,
        protected readonly array $config = [],
    ) {
    }

    public static function fromConnection(ChannelConnection $connection): self
    {
        $credentials = $connection->credentials ?? [];
        $settings = $connection->settings ?? [];

        $channelConfig = Config::get('channel_manager.channels.expedia', []);

        $environment = $connection->uses_sandbox ? 'sandbox' : 'production';
        $baseUrl = $connection->api_base_url
            ?: Arr::get($channelConfig, "base_urls.{$environment}");

        if (!$baseUrl) {
            throw new ChannelException('Expedia base URL is not configured.');
        }

        $apiKey = Arr::get($credentials, 'api_key', config('services.expedia.api_key'));
        $apiSecret = Arr::get($credentials, 'api_secret', config('services.expedia.api_secret'));

        if (!$apiKey || !$apiSecret) {
            throw new ChannelException('Expedia credentials are missing.');
        }

        return new self(
            apiKey: $apiKey,
            apiSecret: $apiSecret,
            baseUrl: rtrim($baseUrl, '/'),
            config: [
                'environment' => $environment,
                'timeout' => Arr::get($settings, 'timeout', config('services.expedia.timeout', 30)),
                'connect_timeout' => Arr::get($settings, 'connect_timeout', config('services.expedia.connect_timeout', 10)),
                'headers' => Arr::get($settings, 'headers', []),
                'channel' => $connection->channel,
                'locale' => $connection->locale ?? config('channel_manager.default_locale'),
                'currency' => $connection->currency ?? config('channel_manager.default_currency'),
                'timezone' => $connection->timezone ?? config('channel_manager.default_timezone'),
                'cid' => Arr::get($credentials, 'cid', config('services.expedia.cid')),
            ],
        );
    }

    public function testConnection(): ChannelResponse
    {
        try {
            $response = $this->http()->get($this->endpoint('test_connection'));

            if ($response->successful()) {
                return ChannelResponse::success(
                    'Expedia connection succeeded.',
                    data: $response->json() ?? [],
                    meta: ['timestamp' => now()->toIso8601String()],
                );
            }

            return ChannelResponse::failure(
                sprintf('Expedia connection failed with status %s.', $response->status()),
                data: $response->json() ?? [],
                meta: ['timestamp' => now()->toIso8601String()],
            );
        } catch (Throwable $exception) {
            report($exception);

            return ChannelResponse::failure(
                'Expedia connection test encountered an error.',
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
            'cid' => $this->config['cid'],
        ], $options);

        try {
            $response = $this->http()->get($this->endpoint('reservations'), $payload);

            if ($response->successful()) {
                return ChannelResponse::success(
                    'Reservations retrieved from Expedia.',
                    data: $response->json() ?? [],
                );
            }

            return ChannelResponse::failure(
                sprintf('Failed to retrieve Expedia reservations. Status: %s', $response->status()),
                data: $response->json() ?? [],
            );
        } catch (Throwable $exception) {
            report($exception);

            return ChannelResponse::failure(
                'Unexpected error while retrieving Expedia reservations.',
                meta: ['exception' => $exception->getMessage()],
            );
        }
    }

    public function pushAvailability(array $payload, array $options = []): ChannelResponse
    {
        $body = array_merge([
            'timezone' => $this->config['timezone'],
            'cid' => $this->config['cid'],
        ], $payload, $options);

        try {
            $response = $this->http()->post($this->endpoint('availability'), $body);

            if ($response->successful()) {
                return ChannelResponse::success(
                    'Availability pushed to Expedia.',
                    data: $response->json() ?? [],
                );
            }

            return ChannelResponse::failure(
                sprintf('Failed to push availability to Expedia. Status: %s', $response->status()),
                data: $response->json() ?? [],
            );
        } catch (Throwable $exception) {
            report($exception);

            return ChannelResponse::failure(
                'Unexpected error while pushing availability to Expedia.',
                meta: ['exception' => $exception->getMessage()],
            );
        }
    }

    public function pushRates(array $payload, array $options = []): ChannelResponse
    {
        $body = array_merge([
            'currency' => $this->config['currency'],
            'cid' => $this->config['cid'],
        ], $payload, $options);

        try {
            $response = $this->http()->post($this->endpoint('rates'), $body);

            if ($response->successful()) {
                return ChannelResponse::success(
                    'Rates pushed to Expedia.',
                    data: $response->json() ?? [],
                );
            }

            return ChannelResponse::failure(
                sprintf('Failed to push rates to Expedia. Status: %s', $response->status()),
                data: $response->json() ?? [],
            );
        } catch (Throwable $exception) {
            report($exception);

            return ChannelResponse::failure(
                'Unexpected error while pushing rates to Expedia.',
                meta: ['exception' => $exception->getMessage()],
            );
        }
    }

    protected function endpoint(string $name): string
    {
        $path = Arr::get($this->config(), "endpoints.{$name}");

        if (!$path) {
            throw new ChannelException(sprintf('Expedia endpoint "%s" is not configured.', $name));
        }

        return $this->baseUrl . $path;
    }

    protected function http(): PendingRequest
    {
        $basicToken = base64_encode($this->apiKey . ':' . $this->apiSecret);

        return Http::withHeaders(array_merge([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => "Basic {$basicToken}",
                'X-Channel-Environment' => $this->config['environment'],
                'X-Channel-Source' => 'xepms-channel-manager',
            ], $this->config['headers'] ?? []))
            ->timeout($this->config['timeout'])
            ->connectTimeout($this->config['connect_timeout']);
    }

    protected function config(?string $key = null, mixed $default = null): mixed
    {
        $channelConfig = Config::get('channel_manager.channels.expedia', []);

        if ($key === null) {
            return $channelConfig;
        }

        return Arr::get($channelConfig, $key, $default);
    }
}







