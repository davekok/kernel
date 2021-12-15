<?php

declare(strict_types=1);

namespace davekok\kernel;

use Throwable;

class Read implements ReadableAction
{
    public function __construct(
        private readonly Actionable $actionable,
        private readonly Reader     $reader,
        private readonly callable   $setter,
        private readonly mixed      $selector,
    ) {
        if ($this->actionable instanceof Readable === false) {
            throw new KernelException("Not a readable actionable.");
        }
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
            $this->setter($value);
        } catch (Throwable $throwable) {
            $this->actionable->activity()->throw($throwable);
        }
    }
}
