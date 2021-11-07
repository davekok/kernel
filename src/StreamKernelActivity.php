<?php

declare(strict_types=1);

namespace davekok\stream;

/**
 * Implements the activity interface
 */
class StreamKernelActivity implements Activity
{
    /**
     * Saves reference to controller. However, it is not actually used. Only to prevent garbage collection.
     */
    private object|null $controller = null;

    /**
     * The current action.
     */
    private mixed $current = null;

    public function __construct(
        private StreamInfo $streamInfo,
        private StreamKernelReaderBuffer $readerBuffer = new StreamKernelReaderBuffer(),
        private StreamKernelWriterBuffer $writerBuffer = new StreamKernelWriterBuffer(),
        private array $actions = [],
    ) {}

    public function getStreamInfo(): StreamInfo
    {
        return $this->streamInfo;
    }

    public function read(Reader $reader): self
    {
        $this->actions = [$reader];
        return $this;
    }

    public function write(Writer $writer): self
    {
        $this->actions = [$writer];
        return $this;
    }

    public function close(): self
    {
        $this->actions = [null];
        return $this;
    }

    public function enableCrypto(bool $enable, int|null $cryptoType = null): self
    {
        $this->actions = [new StreamKernelCryptoAction($enable, $cryptoType)];
        return $this;
    }

    public function andThen(callable $arbitraryAction): self
    {
        $this->actions[] = $arbitraryAction;
        return $this;
    }

    public function andThenRead(Reader $reader): self
    {
        $this->actions[] = $reader;
        return $this;
    }

    public function andThenWrite(Writer $writer): self
    {
        $this->actions[] = $writer;
        return $this;
    }

    public function andThenClose(): self
    {
        $this->actions[] = null;
        return $this;
    }

    public function andThenEnableCrypto(bool $enable, int|null $cryptoType = null): self
    {
        $this->actions[] = new StreamKernelCryptoAction($enable, $cryptoType);
        return $this;
    }

    public function push(mixed ...$args): self
    {
        $next = array_shift($this->actions);
        if (is_callable($next) === false) {
            throw new StreamError("Current action is a not an arbitrary action. Fix your activity.");
        }
        $next(...$args);
        return $this;
    }

    public function repeat(): self
    {
        array_unshift($this->actions, $this->current);
        return $this;
    }

// internal package functions

    public function next(): mixed
    {
        return $this->current = array_shift($this->actions);
    }

    public function current(): mixed
    {
        return $this->current;
    }

    public function clear(): void
    {
        $this->controller = null;
        $this->current    = null;
        $this->actions    = [];
    }

    public function setController(object $controller): void
    {
        if ($this->controller !== null) {
            throw new StreamError("Controller is already set.");
        }
        $this->controller = $controller;
    }

    public function getReaderBuffer(): StreamKernelReaderBuffer
    {
        return $this->readerBuffer;
    }

    public function getWriterBuffer(): StreamKernelWriterBuffer
    {
        return $this->writerBuffer;
    }
}
