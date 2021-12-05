<?php

declare(strict_types=1);

namespace davekok\kernel;

interface Passive
{
    public function listen(ControllerFactory $factory): void;
    public function accept(): Actionable;
}
