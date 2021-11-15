<?php

declare(strict_types=1);

namespace davekok\stream;

trait BlockableStreamTrait
{
    public function setBlocking(bool $blocking): void
    {
        if (stream_set_blocking($this->handle, $blocking) === false) {
            throw new StreamError("Setting blocking mode to ".($blocking?"on":"off")." failed for stream {$this->getId()}.");
        }
    }
}
