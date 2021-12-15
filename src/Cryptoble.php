<?php

declare(strict_types=1);

namespace davekok\kernel;

interface Cryptoble
{
    public function enableCrypto(bool $enable, int|null $cryptoType = null): void;
    public function isCryptoEnabled(): bool;
    public function realEnableCrypto(bool $enable, int|null $cryptoType = null): bool;
}
