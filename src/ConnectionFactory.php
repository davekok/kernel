<?php

declare(strict_types=1);

namespace DaveKok\Stream;

interface ConnectionFactory
{
    public function createConnection(): Connection;
}
