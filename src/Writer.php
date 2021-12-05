<?php

declare(strict_types=1);

namespace davekok\kernel;

interface Writer
{
    public function write(WriteBuffer $buffer): bool;
}
