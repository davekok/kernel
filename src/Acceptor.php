<?php

declare(strict_types=1);

namespace davekok\stream;

interface Acceptor
{
    /**
     * Accept a connection
     */
    public function accept(Connection $connection): void;
}
