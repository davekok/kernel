<?php

declare(strict_types=1);

namespace davekok\stream;

class Buffer
{
    public function __construct(
        private string $buffer = "",
        private int $mark = 0,
        private int $offset = 0
    ) {}

    /**
     * Reset buffer
     */
    public function reset(): void
    {
        $this->buffer = "";
        $this->offset = 0;
        $this->mark = 0;
    }

    /**
     * Add input
     */
    public function add(string $input): Buffer
    {
        // discard everthing before mark
        if ($this->mark > 0) {
            $this->buffer = substr($this->buffer, $this->mark);
            $this->offset -= $this->mark;
            $this->mark = 0;
        }
        // add input to buffer
        $this->buffer .= $input;
        return $this;
    }

    /**
     * Is buffer still valid
     */
    public function valid(): bool
    {
        return $this->offset < strlen($this->buffer);
    }

    /**
     * Peek at current byte in buffer.
     */
    public function peek(): int
    {
        return ord($this->buffer[$this->offset]);
    }

    /**
     * Mark current offset.
     */
    public function mark(): Buffer
    {
        $this->mark = $this->offset;
        return $this;
    }

    /**
     * Move offset to next byte in buffer.
     */
    public function next(): Buffer
    {
        ++$this->offset;
        return $this;
    }

    /**
     * Get all bytes from mark to current offset as string
     */
    public function getString(): string
    {
        return substr($this->buffer, $this->mark, $this->offset - $this->mark);
    }

    /**
     * Get all bytes from mark to current offset as int
     */
    public function getInt(): int
    {
        return (int)$this->getString();
    }

    /**
     * Get all bytes from mark to current offset as float
     */
    public function getFloat(): float
    {
        return (float)$this->getString();
    }
}
