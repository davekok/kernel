<?php

declare(strict_types=1);

namespace davekok\stream;

interface IOStream
{
    public function endOfStream(): bool;
    public function read(int $size = 8192): string;
    public function write(string $buffer): int;
    public function setChunkSize(int $chuckSize): void;
}
