<?php

declare(strict_types=1);

namespace davekok\stream;

/**
 * Implements the socket interface
 */
class StreamKernelSocket implements Socket
{
    private ReaderBuffer $readerBuffer;
    private mixed $reader = null;
    private WriterBuffer $writerBuffer;
    private mixed $writer = null;
    private SocketState $currentSocketState;
    private SocketState $nextSocketState;

    public function __construct(
        public readonly string $localName,
        public readonly string $remoteName
    ) {
        $this->readerBuffer = new ReaderBuffer();
        $this->writerBuffer = new WriterBuffer();
        $this->currentSocketState = new SocketState(ReadyState::NotReady, false, null, true);
        $this->nextSocketState = new SocketState(ReadyState::NotReady, false, null, true);
    }

    public function setReader(callback $reader): void
    {
        $this->reader = $reader;
    }

    public function getReader(): callback
    {
        return $this->reader;
    }

    public function setWriter(callback $writer): void
    {
        $this->writer = $writer;
    }

    public function getWriter(): callback
    {
        return $this->writer;
    }

    public function setReadyState(ReadyState $readyState): void
    {
        $this->nextSocketState->readyState = $readyState;
    }

    public function getReadyState(): ReadyState
    {
        return $this->currentSocketState->readyState;
    }

    public function setCryptoState(bool $enable, int|null $cryptoType = null): void
    {
        $this->nextSocketState->cryptoStateEnable = $enable;
        $this->nextSocketState->cryptoStateType = $cryptoType;
    }

    public function getCryptoState(): bool
    {
        return $this->currentSocketState->cryptoStateEnable;
    }

// internal package functions

    /**
     * Called by the stream kernel to get the state.
     */
    public function getStateDiff(): SocketState
    {
        return $this->nextSocketState->diff($this->currentSocketState);
    }

    /**
     * Called by the stream kernel to commit the new state.
     */
    public function applyState(SocketState $patch): void
    {
        $this->currentSocketState->apply($patch);
    }

    public function getReaderBuffer(): ReaderBuffer
    {
        return $this->readerBuffer;
    }

    public function getWriterBuffer(): WriterBuffer
    {
        return $this->writerBuffer;
    }

    public function close(): void
    {
        $this->nextReadyState = ReadyState::Close;
    }
}
