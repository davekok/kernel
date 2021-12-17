<?php

declare(strict_types=1);

namespace davekok\kernel;

trait ReadableTrait
{
    public function readBuffer(): ReadBuffer
    {
        return $this->readBuffer;
    }

    public function read(Reader $reader, callable $setter): void
    {
        $this->activity->push(new Read($this, $reader, $setter, $this->handle));
    }

    public function readChunk(): string
    {
        return fread($this->handle, Kernel::CHUNK_SIZE) ?: throw new KernelException("Read error for stream {$this->getId()}");
    }

    public function endOfInput(): bool
    {
        return feof($this->handle);
    }
}
