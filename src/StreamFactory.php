<?php

declare(strict_types=1);

namespace davekok\stream;

use davekok\stream\context\Options;

class StreamFactory
{
    public function createActiveSocketStream(
        Url $url,
        float|null $timeout = null,
        int $flags = STREAM_CLIENT_CONNECT,
        Options|array|null $context = null
    ): ActiveSocketStream
    {
        $this->validateUrl($url);
        $timeout ??= ini_get("default_socket_timeout");
        $handle = match (true) {
            $context !== null => stream_socket_client((string)$url, $errno, $errstr, $timeout, $flags, $this->createContext($context)),
            default => stream_socket_client((string)$url, $errno, $errstr, $timeout, $flags)
        };
        if ($handle === false) {
            throw new StreamError($errstr, $errno);
        }
        return new ActiveSocketStream($url, $handle);
    }

    public function createPassiveSocketStream(
        Url $url,
        int $flags = STREAM_SERVER_BIND | STREAM_SERVER_LISTEN,
        Options|array|null $context = null
    ): PassiveSocketStream
    {
        $this->validateUrl($url);
        $handle = match (true) {
            $context !== null => stream_socket_server((string)$url, $errno, $errstr, $flags, $this->createContext($context)),
            default => stream_socket_server((string)$url, $errno, $errstr, $flags)
        };
        if ($handle === false) {
            throw new StreamError($errstr, $errno);
        }
        return new PassiveSocketStream($url, $handle);
    }

    private function validateUrl(Url $url): void
    {
        if (in_array($url->scheme, stream_get_transports()) === false) {
            throw new StreamError("Transport not supported '{$url->scheme}'.");
        }
    }

    private function createContext(StreamContext|Options|array|null $context): mixed
    {
        return match (true) {
            $context instanceof Options => stream_context_create($context->toArray()),
            is_array($context) => stream_context_create($context),
            is_null($context) => stream_context_get_default(),
        };
    }
}
