<?php

declare(strict_types=1);

namespace davekok\stream;

interface Formatter
{
    /**
     * Called when ready to send output.
     */
    public function format(): string;
}
