<?php

declare(strict_types=1);

namespace davekok\stream;

/**
 * This interface represents this side of a socket.
 *
 * When a socket is ready for reading the reader is called.
 * When a socket is ready for writing the writer is called.
 * When a socket is closed the closers are called.
 *
 * When using stack readers, readers can manage themself with the unshift/shift push/pop functions.
 * Same for writers.
 *
 * setReader or setWriter will clear the reader or writer stack.
 */
interface Socket
{
    public function setReader(callback $reader): void;
    public function getReader(): callback;
    public function setWriter(callback $writer): void;
    public function getWriter(): callback;
    public function setReadyState(ReadyState $readyState): void;
    public function getReadyState(): ReadyState;
    public function setCryptoState(bool $enable, int|null $cryptoType = null): void;
    public function getCryptoState(): bool;
}
