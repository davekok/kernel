<?php

declare(strict_types=1);

namespace davekok\kernel;

use Throwable;

class Accept implements ReadableAction
{
    public function __construct(
        private readonly Actionable $actionable,
        private readonly Acceptor $acceptor,
        private readonly mixed $selector,
    ) {
        $this->actionable instanceof Passive ?: throw new KernelException("Actionable does not implement the Passive interface.");
    }

    public function readableSelector(): mixed
    {
        return $this->selector;
    }

    public function execute(): void
    {
        try {
            $this->acceptor->accept($this->actionable->accept());
        } catch (Throwable $throwable) {
            $this->actionable->activity()->throw($throwable);
        }
    }
}
