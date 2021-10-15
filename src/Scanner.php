<?php

declare(strict_types=1);

namespace davekok\stream;

interface Scanner
{
    /**
     * Reset scanner.
     */
    public function reset(): void;

    /**
     * Called when new input has arrived.
     */
    public function scan(string $input): void;

    /**
     * There is no more input.
     */
    public function endOfInput(): void;
}
