<?php

declare(strict_types=1);

namespace davekok\stream;

interface Reader
{
    public function read(ReaderBuffer $reader): void;
}
