<?php

declare(strict_types=1);

namespace DaveKok\Stream;

use Throwable;

trait UpdateCryptoStateTrait
{
    public function updateCryptoState(mixed $stream, bool $enable, int|null $cryptoType = null, mixed $sessionStream = null): bool
    {
        stream_set_blocking($stream, true);
        $ret = match(true) {
            $sessionStream !== null => stream_socket_enable_crypto($stream, $enable, $cryptoType, $sessionStream),
            $cryptoType !== null => stream_socket_enable_crypto($stream, $enable, $cryptoType),
            default => stream_socket_enable_crypto($stream, $enable)
        };
        stream_set_blocking($stream, false);

        if ($ret === false) {
            throw new StreamError("Negotiation failed.");
        }

        if ($ret === 0) {
            throw new StreamError("Not enough data please try again.");
        }

        return $enable;
    }
}
