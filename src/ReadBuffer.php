<?php

declare(strict_types=1);

namespace davekok\kernel;

class ReadBuffer
{
    public function __construct(
        private string $buffer    = "",
        private int $mark         = 0,
        private int $offset       = 0,
        private bool $isLastChunk = true
    ) {}

    /**
     * Reset buffer
     */
    public function reset(): void
    {
        $this->buffer      = "";
        $this->offset      = 0;
        $this->mark        = 0;
        $this->isLastChunk = true;
    }

    /**
     * Add input
     */
    public function add(string $input): self
    {
        $this->isLastChunk = false;
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
     * Marks this chunk as last chunk.
     */
    public function markLastChunk(): self
    {
        $this->isLastChunk = true;
        return $this;
    }

    /**
     * Tells whether this is the last chunk of the stream.
     */
    public function isLastChunk(): bool
    {
        return $this->isLastChunk;
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
     * Get current byte in buffer.
     */
    public function current(): int
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
    public function next(int $by = 1): self
    {
        if ($by < 0 && ($this->offset + $by) < $this->mark) {
            throw new ReaderException("Cannot move back past mark.");
        }
        $this->offset += $by;
        return $this;
    }

    /**
     * Set offset X bytes from mark.
     */
    public function set(int $by = 1): self
    {
        if ($by < 0) {
            throw new ReaderException("Cannot move back past mark.");
        }
        $this->offset = $this->mark + $by;
        return $this;
    }

    /**
     * Move to end
     */
    public function end(): self
    {
        $this->offset = strlen($this->buffer);
        return $this;
    }

    /**
     * Move offset back by X bytes in buffer, cannot move back past mark.
     */
    public function back(int $by = 1): self
    {
        $this->offset -= $by;
        if ($this->offset < $this->mark) {
            $this->offset = $this->mark;
            throw new ReaderException("Cannot move back past mark.");
        }
        return $this;
    }

    /**
     * Equals buffer from mark to offset with value
     */
    public function equals(string $value, bool $case_insensitive = false): bool
    {
        return substr_compare($this->buffer, $value, $this->mark, $this->offset - $this->mark, $case_insensitive) === 0;
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

    /**
     * For logging purposes, to quickly dump some of the read buffer.
     */
    public function __toString(): string
    {
        return addcslashes(substr($this->buffer, $this->offset, 20), "\r\n\t\0");
    }
}
