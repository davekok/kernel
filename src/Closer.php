<?php

declare(strict_types=1);

namespace DaveKok\Stream;

interface Closer
{
    /**
     * Called when stream is closed.
     */
    public function close(): void;
}
