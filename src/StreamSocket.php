<?php

declare(strict_types=1);

namespace DaveKok\Stream;

class StreamSocket extends Stream
{
    public static function connect(
        string $url,
        float|null $timeout = null,
        int $flags = STREAM_CLIENT_CONNECT,
        StreamContext|null $context = null
    ): StreamActiveSocket
    {
        $this->validateURL($url);
        $timeout ??= ini_get("default_socket_timeout");
        $handle = match (true) {
            $context !== null => stream_socket_client($url, $errno, $errstr, $timeout, $flags, $context->handle),
            default => stream_socket_client($url, $errno, $errstr, $timeout, $flags)
        }
        if ($handle === false) {
            throw new StreamError($errstr, $errno);
        }
        return new StreamActiveSocket($handle);
    }

    public static function listen(
        string $url,
        int $flags = STREAM_SERVER_BIND | STREAM_SERVER_LISTEN,
        StreamContext|null $context = null
    ): StreamPassiveSocket
    {
        $this->validateURL($url);
        $handle = match (true) {
            $context !== null => stream_socket_server($url, $errno, $errstr, $flags, $context->handle),
            default => stream_socket_server($url, $errno, $errstr, $flags)
        }
        if ($handle === false) {
            throw new StreamError($errstr, $errno);
        }
        return new StreamPassiveSocket($handle);
    }

    private function validateURL(string $url): void
    {
        $scheme = parse_url($url, PHP_URL_SCHEME);
        if (in_array($scheme, stream_get_transports()) === false) {
            throw new StreamError("Transport not supported '$scheme'.");
        }
    }

    public function getLocalName(): string
    {
        return stream_socket_get_name($this->handle, false);
    }
}
