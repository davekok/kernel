<?php

declare(strict_types=1);

namespace davekok\kernel;

trait WritableTrait
{
    public function writeBuffer(): WriteBuffer
    {
        return $this->writeBuffer;
    }

    public function write(Writer $writer): void
    {
        $this->activity->push(new Write($this, $writer));
    }

    public function writeChunk(string $buffer, int $length): int
    {
        return fwrite($this->handle, $buffer, $length) ?: throw new KernelException("Write error for stream {$this->getId()}.");
    }
}
