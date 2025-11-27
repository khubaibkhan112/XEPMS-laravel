<?php

namespace App\Services\ChannelManager\DTO;

class ChannelResponse
{
    public function __construct(
        public readonly bool $success,
        public readonly string $message = '',
        public readonly array $data = [],
        public readonly array $meta = [],
    ) {
    }

    public static function success(string $message = '', array $data = [], array $meta = []): self
    {
        return new self(true, $message, $data, $meta);
    }

    public static function failure(string $message, array $data = [], array $meta = []): self
    {
        return new self(false, $message, $data, $meta);
    }
}






