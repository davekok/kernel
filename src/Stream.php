<?php

declare(strict_types=1);

namespace davekok\stream;

abstract class Stream extends StreamContext
{
    public function __destruct()
    {
        fclose($this->handle);
    }

    public function getId(): int
    {
        return get_resource_id($this->handle);
    }

    public function setBlocking(bool $blocking): void
    {
        if (stream_set_blocking($this->handle, $blocking) === false) {
            throw new StreamError("Setting blocking mode to ".($blocking?"on":"off")." failed for stream {$this->getId()}.");
        }
    }
}
