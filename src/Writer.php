<?php

declare(strict_types=1);

namespace davekok\stream;

interface Writer
{
    public function write(WriterBuffer $buffer): void;
}
