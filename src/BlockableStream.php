<?php

declare(strict_types=1);

namespace davekok\stream;

interface BlockableStream
{
    public function setBlocking(bool $blocking): void;
}
