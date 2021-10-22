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
    public function scan(ScanBuffer $buffer): void;

    /**
     * There is no more input.
     * However, the scan buffer might still contain something you may wish to parse.
     */
    public function endOfInput(ScanBuffer $buffer): void;
}
