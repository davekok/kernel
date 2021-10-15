<?php

declare(strict_types=1);

namespace davekok\stream;

interface Closer
{
    /**
     * Called when stream is closed.
     */
    public function close(): void;
}
