<?php

declare(strict_types=1);

namespace davekok\kernel;

class Crypter implements Action
{
    public function __construct(
        private readonly Actionable $actionable,
        private readonly bool $cryptoEnable,
        private readonly int $cryptoType,
    ) {}

    public function execute(): void
    {
        try {
            $this->realEnableCrypto($this->cryptoEnable, $this->cryptoType);
            $this->actionable->activity()->next();
        } catch (Throwable $throwable) {
            $this->actionable->activity()->throw($throwable);
        }
    }
}
