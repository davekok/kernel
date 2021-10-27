<?php

declare(strict_types=1);

namespace davekok\stream;

interface Writer
{
    /**
     * Called when ready to write.
     */
    public function write(WriterBuffer $buffer): void;
}
