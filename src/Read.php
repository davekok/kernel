<?php

declare(strict_types=1);

namespace davekok\kernel;

use Throwable;

class Read implements ReadableAction
{
    public function __construct(
        private readonly Actionable $actionable,
        private readonly Reader     $reader,
        private readonly mixed      $handler,
        private readonly mixed      $selector,
    ) {
        $this->actionable instanceof Readable ?: throw new KernelException("Not a readable actionable.");
        is_callable($this->handler) ?: throw new KernelException("Expected handler property to be callable.");
        is_resource($this->selector) ?: throw new KernelException("Expected selector property to be a resource.");
    }

    public function readableSelector(): mixed
    {
        return $this->selector;
    }

    public function execute(): void
    {
        try {
            $buffer = $this->actionable->readBuffer();

            $buffer->add($this->actionable->readChunk());
            if ($this->actionable->isEndOfInput()) {
                $buffer->markLastChunk();
            }

            $value = $reader->read($buffer);

            if ($value === null) {
                return;
            }

            $this->actionable->activity()->next();
            $this->handler($value);
        } catch (Throwable $throwable) {
            $this->actionable->activity()->throw($throwable);
        }
    }
}
