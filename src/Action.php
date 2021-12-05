<?php

declare(strict_types=1);

namespace davekok\kernel;

interface Action
{
    public function execute(): void;
}
