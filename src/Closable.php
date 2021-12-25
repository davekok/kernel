<?php

declare(strict_types=1);

namespace davekok\kernel;

interface Closable
{
    public function close(): void;
}
