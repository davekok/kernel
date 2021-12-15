<?php

declare(strict_types=1);

namespace davekok\kernel;

trait CryptobleTrait
{
    private bool $cryptoEnabled = false;

    public function enableCrypto(bool $enable, int|null $cryptoType = null): void
    {
        $this->activity->push(new Crypter($this, $enable, $cryptoType));
    }

    public function isCryptoEnabled(): bool
    {
        return $this->cryptoEnabled;
    }

    public function realEnableCrypto(bool $enable, int|null $cryptoType = null): void
    {
        stream_set_blocking($this->handle, true);
        $ret = match(true) {
            $this->cryptoType !== null => stream_socket_enable_crypto($this->handle, $enable, $cryptoType),
            default => stream_socket_enable_crypto($this->handle, $enable),
        };
        stream_set_blocking($this->handle, false);

        if ($ret === true) {
            $this->cryptoEnabled = $enable;
            return;
        }

        $id = get_resource_id($this->handle);

        if ($ret === false) {
            throw new KernelException("Negotiation failed for stream '{$id}'.");
        }

        throw new KernelException("Not enough data please try again for stream '{$id}'.");
    }
}
