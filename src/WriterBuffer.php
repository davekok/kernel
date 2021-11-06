<?php

declare(strict_types=1);

namespace davekok\stream;

interface WriterBuffer
{
    /**
     * Reset buffer
     */
    public function reset(): void;

    /**
     * Add output
     */
    public function add(string $output): self;
}
