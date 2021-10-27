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
    public function unshiftReader(Reader $reader): void;
    public function shiftReader(): void;
    public function pushReader(Reader $reader): void;
    public function popReader(): void;
    public function setReader(Reader $reader): void;
    public function getReader(): Reader;
    public function unshiftWriter(Writer $writer): void;
    public function shiftWriter(): void;
    public function pushWriter(Writer $writer): void;
    public function popWriter(): void;
    public function setWriter(Writer $writer): void;
    public function getWriter(): Writer;
    public function addCloser(Closer $closer): void;
    public function removeCloser(Closer $closer): void;
    public function setReadyState(ReadyState $readyState): void;
    public function getReadyState(): ReadyState;
    public function setCryptoState(bool $enable, int|null $cryptoType = null): void;
    public function getCryptoState(): bool;
    public function setRunningState(bool $running): void;
    public function getRunningState(): bool;
}
