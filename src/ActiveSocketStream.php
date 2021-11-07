<?php

declare(strict_types=1);

namespace davekok\stream;

class ActiveSocketStream extends FileStream
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

    public function getLocalUrl(): Url
    {
        [$host, $port] = explode(":", stream_socket_get_name($this->handle, false));
        return new Url(scheme: $this->url->scheme, host: $host, port: (int)$port);
    }

    public function getRemoteUrl(): Url
    {
        [$host, $port] = explode(":", stream_socket_get_name($this->handle, true));
        return new Url(scheme: $this->url->scheme, host: $host, port: (int)$port);
    }
}
