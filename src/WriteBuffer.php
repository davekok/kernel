<?php

declare(strict_types=1);

namespace davekok\kernel;

class WriterBuffer
{
    public function __construct(
        private string $buffer = "",
        private int    $offset = 0,
    ) {}

    /**
     * Reset buffer
     */
    public function reset(): void
    {
        $this->buffer = "";
        $this->offset = 0;
    }

    /**
     * Add output
     */
    public function add(string $output): self
    {
        if (($this->offset + strlen($output)) >= Kernel::CHUNK_SIZE) {
            throw new WriterException("Too much output, check with valid or use addChunk");
        }
        // add output to buffer
        $this->buffer .= $output;
        $this->offset += strlen($output);
        return $this;
    }

    /**
     * Add a chunk of output and return if there are more chunks
     */
    public function addChunk(int &$offset, string $output): bool
    {
        $remaining = Kernel::CHUNK_SIZE - $this->offset;
        if ((strlen($output) - $offset) > $remaining) {
            $this->buffer .= substr($output, $offset, $remaining);
            $this->offset  = Kernel::CHUNK_SIZE;
            $offset       += $remaining;
            return true;
        } else {
            $this->buffer .= substr($output, $offset);
            $offset       += strlen($this->buffer) - $this->offset;
            $this->offset  = strlen($this->buffer);
            return false;
        }
    }

    public function valid(int $length): bool
    {
        return ($this->offset + $length) < Kernel::CHUNK_SIZE;
    }

    public function getChunk(): string
    {
        return substr($this->buffer, 0, Kernel::CHUNK_SIZE);
    }

    public function written(int $amount): bool
    {
        if ($amount < strlen($this->buffer)) {
            $this->buffer  = substr($this->buffer, $amount);
            $this->offset -= $amount;
            return false;
        } else {
            $this->buffer = "";
            $this->offset = 0;
            return true;
        }
    }
}
