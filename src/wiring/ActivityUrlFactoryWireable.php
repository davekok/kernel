<?php

declare(strict_types=1);

namespace davekok\kernel\wiring;

use davekok\kernel\ActivityUrlFactory;
use davekok\wiring\Wireable;

class ActivityUrlFactoryWireable implements Wireable
{
    private readonly ActivityUrlFactory $urlFactory;

    public function __construct(private readonly KernelWiring $wiring) {}

    public function wire(): ActivityUrlFactory
    {
        return $this->urlFactory ??= new ActivityUrlFactory($this->wiring->service("kernel"));
    }
}
