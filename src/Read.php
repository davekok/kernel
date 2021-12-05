<?php

declare(strict_types=1);

namespace davekok\kernel;

use Throwable;

class Read implements Action
{
    public function __construct(
        private readonly Actionable $actionable,
        private readonly Reader     $reader,
        private readonly callable   $andThen,
    ) {
        if ($this->actionable instanceof Readable === false) {
            throw new KernelException("Not a readable actionable.");
        }
    }

    public function execute(): void
    {
        try {
            $buffer = $this->actionable->readBuffer();
            $value  = $reader->read(match ($this->actionable->isEndOfInput()) {
                true  => $buffer->end(),
                false => $buffer->add($this->actionable->readChunk()),
            });

            if ($value === null) {
                return;
            }

            $this->actionable->activity()->next();
            $this->andThen($value);
        } catch (Throwable $throwable) {
            $this->actionable->activity()->next();
            $this->actionable->activity()->logger()->error($throwable);
            $this->andThen($throwable);
        }
    }
}
