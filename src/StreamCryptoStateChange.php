<?php

declare(strict_types=1);

namespace DaveKok\Stream;

class StreamCryptoStateChange
{
    public function __construct(
        public bool $enable,
        public int|null $cryptoType = null,
        public mixed $sessionStream = null
    ){}
}
