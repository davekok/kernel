<?php

declare(strict_types=1);

namespace davekok\kernel;

trait ClosableTrait
{
    public function close(): void
    {
        fclose($this->handle);
    }
}
