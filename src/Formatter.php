<?php

declare(strict_types=1);

namespace DaveKok\Stream;

class Formatter
{
    /**
     * Called when ready to send output.
     */
    public function format(): string;
}
