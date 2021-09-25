<?php

declare(strict_types=1);

namespace DaveKok\Stream;

use Throwable;

interface StreamKernelInterface
{
    public const CHUCK_SIZE = 1400;
    public function updateReadyState(mxied $stream, StreamReadyState $readyState): StreamReadyState;
    public function updateCryptoState(mixed $stream, bool $enable, int|null $cryptoType = null, mixed $sessionStream = null): bool;
    public function run(): noreturn;
    public function quit(): void;
}
