<?php

declare(strict_types=1);

namespace davekok\kernel;

class KernelContainer
{
    public function __construct(
        public readonly Kernel  $kernel,
        public readonly Schemes $schemes,
        public readonly Logger  $logger,
    ) {}
}
