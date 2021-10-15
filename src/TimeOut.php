<?php

declare(strict_types=1);

namespace davekok\stream;

interface TimeOut
{
    /**
     * Return number of seconds from now to next timeout.
     */
    public function getNextTimeOut(): int;

    /**
     * Called when timeout is reached.
     */
    public function timeOut(): void;
}
