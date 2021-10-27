<?php

declare(strict_types=1);

namespace davekok\stream;

class ReaderBuffer
{
    public function __construct(
        private string $buffer = "",
        private int $mark      = 0,
        private int $offset    = 0
    ) {}

    /**
     * Reset buffer
     */
    public function reset(): void
    {
        $this->buffer = "";
        $this->offset = 0;
        $this->mark   = 0;
    }

    /**
     * Add input
     */
    public function add(string $input): self
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
     * Is buffer still valid.
     *
     * Optionally specify a lookahead to see if that much is left in the buffer.
     */
    public function valid(int $lookahead = 0): bool
    {
        return ($this->offset + $lookahead) < strlen($this->buffer);
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
    public function mark(): self
    {
        $this->mark = $this->offset;
        return $this;
    }

    /**
     * Move offset to next byte in buffer.
     */
    public function next(): self
    {
        ++$this->offset;
        return $this;
    }

    /**
     * Move offset to back one byte in buffer.
     */
    public function back(int $by = 1): self
    {
        $this->offset -= $by;
        if ($this->offset < $this->mark) {
            $this->offset = $this->mark;
            throw new ReaderException("Can not move back past mark.");
        }
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

    public function __toString(): string
    {
        return addcslashes(substr($this->buffer, $this->offset, 10), "\r\n\t\0");
    }
}
