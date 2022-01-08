<?php

declare(strict_types=1);

namespace davekok\kernel\wiring;

use davekok\kernel\SocketUrlFactory;
use davekok\wiring\Wireable;

class SocketUrlFactoryWireable implements Wireable
{
    private readonly SocketUrlFactory $urlFactory;

    public function wire(): SocketUrlFactory
    {
        return $this->urlFactory ??= new SocketUrlFactory();
    }
}
