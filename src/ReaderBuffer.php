<?php

declare(strict_types=1);

namespace davekok\stream;

interface ReaderBuffer
{
    /**
     * Reset buffer
     */
    public function reset(): void;

    /**
     * Tells whether this is the last chunk of the stream.
     */
    public function isLastChunk(): bool;

    /**
     * Is buffer still valid.
     *
     * Optionally specify a lookahead to see if that much is left in the buffer.
     */
    public function valid(int $lookahead = 0): bool;

    /**
     * Get current byte in buffer.
     */
    public function current(): int;

    /**
     * Mark current offset.
     */
    public function mark(): self;

    /**
     * Move offset to next byte in buffer.
     */
    public function next(): self;

    /**
     * Move offset back by X bytes in buffer, cannot move back past mark.
     */
    public function back(int $by = 1): self;

    /**
     * Equals buffer from mark to offset with value
     */
    public function equals(string $value): bool;

    /**
     * Get all bytes from mark to current offset as string
     */
    public function getString(): string;

    /**
     * Get all bytes from mark to current offset as int (not binary)
     */
    public function getInt(): int;

    /**
     * Get all bytes from mark to current offset as float (not binary)
     */
    public function getFloat(): float;
}
