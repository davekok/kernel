<?php

declare(strict_types=1);

namespace davekok\kernel;

class SchemesFactory
{
    public function __construct(private array $schemes) {}

    public function setScheme(string $scheme, UrlFactory $factory): self
    {
        $this->schemes[$scheme] = $factory;
        return $this;
    }

    public function createSchemes(): Schemes
    {
        return new Schemes($this->schemes);
    }
}
