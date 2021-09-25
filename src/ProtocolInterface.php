<?php

declare(strict_types=1);

namespace DaveKok\Stream;

use Throwable;

interface ProtocolInterface
{
    /**
     * Called when new input has arrived, check buffer of stream state.
     */
    public function pushInput(): void;

    /**
     * Called when there is no more input.
     */
    public function endOfInput(): void;

    /**
     * Called when indicated write ready and system is ready to write, fill buffer of stream state.
     */
    public function pullOuput(): void;

    /**
     * Notify that an error has occurred.
     */
    public function notifyError(Throwable $throwable): void;

    /**
     * Destroy
     */
    public function destroyProtocol(): void;
}
