<?php

declare(strict_types=1);

namespace davekok\stream;

class FileStream extends Stream
{
    public function endOfStream(): bool
    {
        return feof($this->handle);
    }

    public function read(int $size = 8192): string
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

    public function setChunkSize(int $chuckSize): void
    {
        if (stream_set_chunk_size($this->handle, $chuckSize) === false) {
            throw new StreamError("Failed to set chunk size to '$chuckSize' for stream {$this->getId()}.");
        }
    }
}
