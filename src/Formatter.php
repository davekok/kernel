<?php

declare(strict_types=1);

namespace davekok\stream;

class Formatter
{
    /**
     * Called when ready to send output.
     */
    public function format(): string;
}
