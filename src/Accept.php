<?php

declare(strict_types=1);

namespace davekok\kernel;

use Throwable;

class Accept implements Action
{
    public function __construct(
        private readonly Actionable $actionable,
        private readonly Acceptor $acceptor,
    ) {
        if ($this->actionable instanceof Passive === false) {
            throw new KernelException("Not a passive actionable.");
        }
    }

    public function execute(): void
    {
        try {
            $this->acceptor->accept($this->actionable->accept());
        } catch (Throwable $throwable) {
            $this->actionable->activity()->logger->error($throwable);
        }
    }
}
