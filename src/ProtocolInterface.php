<?php

declare(strict_types=1);

namespace DaveKok\Stream;

interface ProtocolInterface
{
    /**
     * Return the input/output state changes to apply.
     *
     * @return IOStateChange[]
     */
    public function getIOStateChanges(): array;

    /**
     * Push an stream error to the protocol.
     */
    public function pushError(StreamError $error): void;

    /**
     * Called when new input has arrived.
     */
    public function pushInput(string $buffer): void;

    /**
     * Called when there is no more input.
     */
    public function endOfInput(): void;

    /**
     * Called when indicated write ready and system is ready to write.
     */
    public function pullOuput(): string;
}
