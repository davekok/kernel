<?php

declare(strict_types=1);

namespace DaveKok\Stream;

interface Acceptor
{
    /**
     * Accept a connection
     */
    public function accept(Connection $connection): void;
}
