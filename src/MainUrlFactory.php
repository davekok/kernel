<?php

declare(strict_types=1);

namespace davekok\kernel;

class MainUrlFactory implements UrlFactory
{
    public function __construct(private readonly array $schemes) {}

    public function createUrl(string $url): Url
    {
        return $this->schemes[parse_url($url, PHP_URL_SCHEME)]->createUrl($url)
            ?? throw new KernelException("Scheme not supported: $url");
    }
}
