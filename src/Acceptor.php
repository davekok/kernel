<?php

declare(strict_types=1);

namespace davekok\kernel;

interface Acceptor
{
    public function accept(Actionable $actionable): void;
}
