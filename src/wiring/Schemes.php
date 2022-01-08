<?php

declare(strict_types=1);

namespace davekok\kernel\wiring;

use davekok\kernel\MainUrlFactory;
use davekok\kernel\UrlFactory;
use davekok\wiring\Configurable;
use davekok\wiring\Wireable;
use davekok\wiring\WiringException;

class Schemes implements Configurable
{
    private readonly UrlFactory $urlFactory;

    public function __construct(
        private array $schemes = [],
    ) {}

    public function count(): int
    {
        return count($this->schemes);
    }

    public function set(string $scheme, Wireable $wireable): static
    {
        $this->schemes[$scheme] = $wireable;
        return $this;
    }

    public function get(string $scheme): Wireable
    {
        return $this->schemes[$scheme] ?? throw new WiringException("Scheme not found: $scheme");
    }

    public function all(): array
    {
        return $this->schemes;
    }

    public function wire(): UrlFactory
    {
        return $this->urlFactory ??= new MainUrlFactory($this->wireSchemes());
    }

    private function wireSchemes(): array
    {
        $schemes = [];
        foreach ($this->schemes as $scheme => $wireable) {
            $schemes[$scheme] = $wireable->wire();
        }
        return $schemes;
    }
}
