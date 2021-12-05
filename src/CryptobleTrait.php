<?php

declare(strict_types=1);

namespace davekok\kernel;

trait CryptobleTrait
{
    private bool $cryptoEnabled = false;

    public function enableCrypto(bool $enable, int|null $cryptoType = null): void
    {
        $this->activity->push(new Crypter($this->activity, $this->handle, $enable, $cryptoType, $this->setCryptoEnabled(...)));
    }

    public function isCryptoEnabled(): bool
    {
        return $this->cryptoEnabled;
    }

    private function setCryptoEnabled(bool $cryptoEnabled): void
    {
        $this->cryptoEnabled = $this->cryptoEnabled;
    }
}
