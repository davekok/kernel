<?php

declare(strict_types=1);

namespace DaveKok\Stream;

class IOCryptoStateChange extends IOStateChange
{
    public function __construct(
        public readonly bool $request,
        public readonly int|null $cryptoType = null,
        public readonly mixed $sessionStream = null
    ){}
}
