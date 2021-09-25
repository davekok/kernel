<?php

declare(strict_types=1);

namespace DaveKok\Stream;

interface TimeOutInterface
{
    public function getNextTimeOut(): int;
    public function timeOut(): void;
}
