<?php

declare(strict_types=1);

namespace DaveKok\Stream;

class IOReadyStateChange extends IOStateChange
{
    public function __construct(
        public readyonly IOReadyState $request
    ) {}
}
