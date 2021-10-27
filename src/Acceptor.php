<?php

declare(strict_types=1);

namespace davekok\stream;

interface Acceptor
{
    /**
     * Accept a socket.
     */
    public function accept(Socket $socket): void;
}
