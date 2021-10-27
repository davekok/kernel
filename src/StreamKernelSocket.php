<?php

declare(strict_types=1);

namespace davekok\stream;

/**
 * Implements the socket interface
 */
class StreamKernelSocket implements Socket
{
    private ReaderBuffer $readerBuffer;
    private array $readerStack = [];
    private Reader|null $currentReader = null;
    private WriterBuffer $writerBuffer;
    private array $writerStack = [];
    private Writer|null $currentWriter = null;
    private array $closers = [];
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

    public function unshiftReader(Reader $reader): void
    {
        if ($this->currentReader !== null) {
            array_unshift($this->readerStack, $this->currentReader);
        }
        $this->currentReader = $reader;
    }

    public function shiftReader(): void
    {
        $this->currentReader = array_shift($this->readerStack);
    }

    public function pushReader(Reader $reader): void
    {
        if ($this->currentReader !== null) {
            $this->readerStack[] = $this->currentReader;
        }
        $this->currentReader = $reader;
    }

    public function popReader(): void
    {
        $this->currentReader = array_pop($this->readerStack);
    }

    public function setReader(Reader $reader): void
    {
        $this->currentReader = $reader;
        $this->readerStack = [];
    }

    public function getReader(): Reader
    {
        return $this->currentReader;
    }

    public function unshiftWriter(Writer $writer): void
    {
        if ($this->currentWriter !== null) {
            array_unshift($this->writerStack, $this->currentWriter);
        }
        $this->currentWriter = $writer;
    }

    public function shiftWriter(): void
    {
        $this->currentWriter = array_shift($this->writerStack);
    }

    public function pushWriter(Writer $writer): void
    {
        if ($this->currentWriter !== null) {
            $this->writerStack[] = $this->currentWriter;
        }
        $this->currentWriter = $writer;
    }

    public function popWriter(): void
    {
        $this->currentWriter = array_pop($this->writerStack);
    }

    public function setWriter(Writer $writer): void
    {
        $this->currentWriter = $writer;
        $this->writerStack = [];
    }

    public function getWriter(): Writer
    {
        return $this->currentWriter;
    }

    public function addCloser(Closer $closer): void
    {
        $this->closers[] = $closer;
    }

    public function removeCloser(Closer $closer): void
    {
        $key = array_search($closer, $this->closers, true);
        if ($key === false) {
            return;
        }
        unset($this->closers[$key]);
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

    /**
     * Set whether the stream kernel should continue running.
     * Effects all streams.
     */
    public function setRunningState(bool $running): void
    {
        $this->nextSocketState->Running = $running;
    }

    public function getRunningState(): bool
    {
        return $this->currentSocketState->running;
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
        $this->readerStack = [];
        $this->writerStack = [];
        $this->currentReader = null;
        $this->currentWriter = null;
        foreach ($this->closers as $closer) {
            $closer->close();
        }
        $this->closers = [];
    }
}
