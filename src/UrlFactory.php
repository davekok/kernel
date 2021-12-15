<?php

declare(strict_types=1);

namespace davekok\kernel;

interface UrlFactory
{
    public function createUrl(string $url): Url;
}
