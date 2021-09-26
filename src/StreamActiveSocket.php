<?php

declare(strict_types=1);

namespace DaveKok\Stream;

class StreamActiveSocket extends StreamSocket
{
    public function enableCrypto(bool $enable, int|null $cryptoType = null): void
    {
        match (match(true) {
            $cryptoType !== null => stream_socket_enable_crypto($this->handle, $enable, $cryptoType),
            default => stream_socket_enable_crypto($this->handle, $enable),
        }) {
            false => new StreamError("Negotiation failed for stream '{$this->getId()}'."),
            0 => throw new StreamError("Not enough data please try again '{$this->getId()}'."),
        };
    }

    public function getRemoteName(): string
    {
        return stream_socket_get_name($this->handle, true);
    }
}
