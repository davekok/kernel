<?php

declare(strict_types=1);

namespace davekok\kernel;

interface Writable
{
    public function writeBuffer(): WriteBuffer;
    public function write(Writer $writer): void;
    public function writeChunk(string $buffer, int $length): int;
}
