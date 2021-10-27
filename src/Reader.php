<?php

declare(strict_types=1);

namespace davekok\stream;

interface Reader
{
    /**
     * Called when new input has arrived.
     */
    public function read(ReaderBuffer $buffer): void;

    /**
     * There is no more input.
     * However, the input buffer might still contain something you may wish to read.
     */
    public function endOfInput(ReaderBuffer $buffer): void;
}
