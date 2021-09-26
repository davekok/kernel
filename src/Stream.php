<?php

declare(strict_types=1);

namespace DaveKok\Stream;

/**
 * A wrapper for PHP stream functions.
 */
class Stream extends StreamContext
{
    public static function createStream(
        string $url,
        string $mode,
        bool $use_include_path = false,
        StreamContext|null $context = null
    ): self
    {
        $handle = match (true) {
            $context !== null => fopen($url, $mode, $use_include_path, $context->handle),
            default => fopen($url, $mode, $use_include_path)
        };
        return new Stream($handle);
    }

    public function getId(): int
    {
        return get_resource_id($this->handle);
    }

    public function endOfStream(): bool
    {
        return feof($this->handle);
    }

    public function read(int $size): string
    {
        $buffer = fread($this->handle, $size);
        if ($buffer === false) {
            throw new StreamError("Read error for stream {$this->getId()}.");
        }
        return $buffer;
    }

    public function write(string $buffer): int
    {
        $written = fwrite($this->handle, $buffer);
        if ($written === false) {
            throw new StreamError("Write error for stream {$this->getId()}.");
        }
        return $written;
    }

    public function close(): void
    {
        fclose($this->handle);
    }

    public function setBlocking(bool $blocking): void
    {
        if (stream_set_blocking($this->handle, $blocking) === false) {
            throw new StreamError("Setting blocking mode to ".($blocking?"on":"off")." failed for stream {$this->getId()}.");
        }
    }

    public function setChunkSize(int $chuckSize): void
    {
        if (stream_set_chunk_size($this->handle, $chuckSize) === false) {
            throw new StreamError("Failed to set chunk size to '$chuckSize' for stream {$this->getId()}.");
        }
    }
}
