<?php

declare(strict_types=1);

namespace DaveKok\Stream;

use Throwable;

interface ProtocolInterface
{
    /**
     * Update the stream state.
     */
    public function updateState(StreamState $state): void;

    /**
     * New input has arrived.
     */
    public function pushInput(string $buffer): void;

    /**
     * There is no more input.
     */
    public function endOfInput(): void;

    /**
     * Return output to write
     */
    public function pullOuput(): string;

    /**
     * Close the protocol
     */
    public function close(): void;
}
