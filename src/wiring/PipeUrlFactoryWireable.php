<?php

declare(strict_types=1);

namespace davekok\kernel\wiring;

use davekok\kernel\PipeUrlFactory;
use davekok\kernel\UrlFactory;
use davekok\wiring\Wireable;

class PipeUrlFactoryWireable implements Wireable
{
    private readonly PipeUrlFactory $urlFactory;

    public function wire(): PipeUrlFactory
    {
        return $this->urlFactory ??= new PipeUrlFactory();
    }
}
