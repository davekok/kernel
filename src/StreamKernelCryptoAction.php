<?php

declare(strict_types=1);

namespace davekok\stream;

class StreamKernelCryptoAction
{
    public function __construct(
        public bool $cryptoStateEnable,
        public int $cryptoStateType,
    ) {}
}
