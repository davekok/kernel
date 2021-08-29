<?php

declare(strict_types=1);

namespace DaveKok\Stream;

interface Connection
{
    public function checkIOReady(): IOReady;
    public function pushInput(string $buffer): void;
    public function pullOuput(): string;
}
