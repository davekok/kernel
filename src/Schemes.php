<?php

declare(strict_types=1);

namespace davekok\kernel;

class Schemes implements UrlFactory
{
    public function __construct(private readonly array $schemes) {}

    public function createUrl(string $url): Url
    {
        $scheme = $this->schemes[parse_url($url, PHP_URL_SCHEME)];
        return isset($this->schemes[$scheme])
            ? $this->schemes[$scheme]->createUrl($url)
            : throw new SchemeNotSupportedKernelException($url);
    }
}
