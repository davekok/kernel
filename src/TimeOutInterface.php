<?php

declare(strict_types=1);

namespace DaveKok\Stream;

interface TimeOutInterface
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
