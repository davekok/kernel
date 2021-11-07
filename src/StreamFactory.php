<?php

declare(strict_types=1);

namespace davekok\stream;

use davekok\stream\context\Options;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class StreamFactory
{
    private StreamKernel $kernel;

    /**
     * Implement the TimeOut if you would like to add cron like abilities to the stream kernel.
     */
    public function __construct(private TimeOut|null $timeOut = null, private LoggerInterface $log = new NullLogger()) {}

    public function createStreamKernel(): StreamKernel
    {
        return $this->kernel ?? new StreamKernel($this->log, $this->timeOut);
    }

    public function createStreamContext(StreamContext|Options|array|null $options = null): StreamContext
    {
        return new StreamContext($this->createContext($options));
    }

    public function createFileStream(
        Url $url,
        string $mode,
        bool $useIncludePath = false,
        StreamContext|Options|array|null $context = null
    ): FileStream
    {
        $handle = match (true) {
            $context !== null => fopen((string)$url, $mode, $useIncludePath, $this->createContext($context)),
            default => fopen((string)$url, $mode, $useIncludePath)
        };
        if ($handle === false) {
            throw new StreamError("Failed to create stream.");
        }
        return new FileStream($url, $handle);
    }

    public function createActiveSocketStream(
        Url $url,
        float|null $timeout = null,
        int $flags = STREAM_CLIENT_CONNECT,
        StreamContext|Options|array|null $context = null
    ): ActiveSocketStream
    {
        $this->validateUrl($url);
        $timeout ??= ini_get("default_socket_timeout");
        $handle = match (true) {
            $context !== null => stream_socket_client($url, $errno, $errstr, $timeout, $flags, $this->createContext($context)),
            default => stream_socket_client($url, $errno, $errstr, $timeout, $flags)
        };
        if ($handle === false) {
            throw new StreamError($errstr, $errno);
        }
        return new ActiveSocketStream($url, $handle);
    }

    public function createPassiveSocketStream(
        Url $url,
        int $flags = STREAM_SERVER_BIND | STREAM_SERVER_LISTEN,
        StreamContext|Options|array|null $context = null
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

    private function createContext(StreamContext|Options|array|null $context): mixed
    {
        return match (true) {
            $context instanceof StreamContext => $context->handle,
            $context instanceof Options => stream_context_create($context->toArray()),
            is_array($context) => stream_context_create($context),
            is_null($context) => stream_context_get_default(),
        };
    }

    private function validateUrl(Url $url): void
    {
        if (in_array($url->scheme, stream_get_transports()) === false) {
            throw new StreamError("Transport not supported '{$url->scheme}'.");
        }
    }
}
