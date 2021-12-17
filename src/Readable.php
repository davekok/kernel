<?php

declare(strict_types=1);

namespace davekok\kernel;

interface Readable
{
    public function readBuffer(): ReadBuffer;
    public function read(Reader $reader, callable $setter): void;
    public function readChunk(): string;
    public function endOfInput(): bool;
}
