<?php

declare(strict_types=1);

namespace davekok\stream;

use Stringable;

/**
 * Implements the activity interface
 */
class StreamKernelActivity implements Activity
{
    public function __construct(
        private StreamInfo $streamInfo,
        private LogLevel $logFilterLevel,
        private StreamKernelReaderBuffer $readerBuffer = new StreamKernelReaderBuffer(),
        private StreamKernelWriterBuffer $writerBuffer = new StreamKernelWriterBuffer(),
        private array $actions = [],
        private mixed $current = null,
        private object|null $controller = null,
    ) {}

    public function getStreamInfo(): StreamInfo
    {
        return $this->streamInfo;
    }

    public function add(callable $arbitraryAction): self
    {
        $this->actions[] = $arbitraryAction;
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

    public function addRead(Reader $reader): self
    {
        $this->actions[] = $reader;
        return $this;
    }

    public function addWrite(Writer $writer): self
    {
        $this->actions[] = $writer;
        return $this;
    }

    public function addClose(): self
    {
        $this->actions[] = null;
        return $this;
    }

    public function addEnableCrypto(bool $enable, int|null $cryptoType = null): self
    {
        $this->actions[] = new StreamKernelCryptoAction($enable, $cryptoType);
        return $this;
    }

    public function addEmergency(string|Stringable $message): self
    {
        return $this->addLog(LogLevel::EMERGENCY, $message);
    }

    public function addAlert(string|Stringable $message): self
    {
        return $this->addLog(LogLevel::ALERT, $message);
    }

    public function addCritical(string|Stringable $message): self
    {
        return $this->addLog(LogLevel::CRITICAL, $message);
    }

    public function addError(string|Stringable $message): self
    {
        return $this->addLog(LogLevel::ERROR, $message);
    }

    public function addWarning(string|Stringable $message): self
    {
        return $this->addLog(LogLevel::WARNING, $message);
    }

    public function addNotice(string|Stringable $message): self
    {
        return $this->addLog(LogLevel::NOTICE, $message);
    }

    public function addInfo(string|Stringable $message): self
    {
        return $this->addLog(LogLevel::INFO, $message);
    }

    public function addDebug(string|Stringable $message): self
    {
        return $this->addLog(LogLevel::DEBUG, $message);
    }

    public function addLog(LogLevel $level, string|Stringable $message): self
    {
        if ($this->logFilterLevel->filter($level)) {
            $this->actions[] = new StreamKernelLogAction($level, $message);
        }
        return $this;
    }

    public function setLogFilterLevel(LogLevel $logFilterLevel): self
    {
        $this->logFilterLevel = $logFilterLevel;
        return $this;
    }

    public function getLogFilterLevel(): LogLevel
    {
        return $this->logFilterLevel;
    }

    public function clear(): self
    {
        $this->current = null;
        $this->actions = [];
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

    /**
     * Saves a reference to controller. However, it is not actually used. Only to prevent garbage collection.
     */
    public function setController(object|null $controller): void
    {
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
