<?php

declare(strict_types=1);

namespace DaveKok\Stream;

class StreamFactory
{
    public static function createStream(
        string $url,
        string $mode,
        bool $use_include_path = false,
        StreamContext|null $context = null
    ): self
    {
        $handle = match (true) {
            $context !== null => fopen($url, $mode, $use_include_path, $context->getResource()),
            default => fopen($url, $mode, $use_include_path)
        };
        return new Stream($handle);
    }

    public static function createSocketClient(
        string $url,
        float|null $timeout = null,
        int $flags = STREAM_CLIENT_CONNECT,
        StreamContext|null $context = null
    ): StreamActiveSocket
    {
        $this->validateURL($url);
        $timeout ??= ini_get("default_socket_timeout");
        $handle = match (true) {
            $context !== null => stream_socket_client($url, $errno, $errstr, $timeout, $flags, $context->getResource()),
            default => stream_socket_client($url, $errno, $errstr, $timeout, $flags)
        }
        if ($handle === false) {
            throw new StreamError($errstr, $errno);
        }
        return new StreamActiveSocket($handle);
    }

    public static function createSocketServer(
        string $url,
        int $flags = STREAM_SERVER_BIND | STREAM_SERVER_LISTEN,
        StreamContext|null $context = null
    ): StreamPassiveSocket
    {
        $this->validateURL($url);
        $handle = match (true) {
            $context !== null => stream_socket_server($url, $errno, $errstr, $flags, $context->getResource()),
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

    public function setBlocking(bool $blocking): void
    {
        if (stream_set_blocking($this->handle, $blocking) === false) {
            throw new StreamError("Setting blocking mode to ".($blocking?"on":"off")." failed."));
        }
    }

    public function setChunkSize(int $chuckSize): void
    {
        if (stream_set_chunk_size($this->handle, $chuckSize) === false) {
            throw new StreamError("Failed to set chunk size to $chuckSize.");
        }
    }

    public function endOfStream(): bool
    {
        return feof($this->handle);
    }

    public function read(int $size): string
    {
        $buffer = fread($this->handle, $size);
        if ($buffer === false) {
            throw new StreamError("Read error");
        }
        return $buffer;
    }

    public function write(string $buffer): int
    {
        $written = fwrite($this->handle, $buffer);
        if ($written === false) {
            throw new StreamError("Write error");
        }
        return $written;
    }

    public function close(): void
    {
        fclose($this->handle);
    }
}
