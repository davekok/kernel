<?php

declare(strict_types=1);

namespace davekok\kernel\wiring;

use davekok\kernel\FileUrlFactory;
use davekok\kernel\UrlFactory;
use davekok\wiring\Wireable;

class FileUrlFactoryWireable implements Wireable
{
    private readonly FileUrlFactory $urlFactory;

    public function wire(): FileUrlFactory
    {
        return $this->urlFactory ??= new FileUrlFactory();
    }
}
