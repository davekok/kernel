<?php

declare(strict_types=1);

namespace DaveKok\Stream;

class Scanner
{
    /**
     * Called when new input has arrived.
     */
    public function scan(string $input): void;

    /**
     * There is no more input.
     */
    public function endOfInput(): void;
}
