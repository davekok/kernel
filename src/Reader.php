<?php

declare(strict_types=1);

namespace davekok\kernel;

interface Reader
{
    public function read(ReadBuffer $buffer): mixed;
}
