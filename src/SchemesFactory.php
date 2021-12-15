<?php

declare(strict_types=1);

namespace davekok\kernel;

use davekok\container\ContainerFactory;
use Psr\Logger\LogLevel;

class SchemesFactory
{
    public function __construct(
        private readonly Kernel $kernel,
        private array $schemes = [],
    ) {}

    public function setScheme(string $scheme, UrlFactory $factory): self
    {
        $this->schemes[$scheme] = $factory;
        return $this;
    }

    public function getScheme(string $scheme): UrlFactory
    {
        return match ($scheme) {
            "activity" => $this->schemes["activity"] ??= new ActivityUrlFactory($this->kernel),
            "file"     => $this->schemes["file"]     ??= new FileUrlFactory(),
            "pipe"     => $this->schemes["pipe"]     ??= new PipeUrlFactory(),
            "tcp"      => $this->schemes["tcp"]      ??= new SocketUrlFactory(),
            default    => $this->schemes[$scheme]    ?? throw new NotFoundException($scheme),
        };
    }

    public function getSchemes(): array
    {
        $this->schemes["activity"] ??= new ActivityUrlFactory($this->kernel);
        $this->schemes["file"]     ??= new FileUrlFactory();
        $this->schemes["pipe"]     ??= new PipeUrlFactory();
        $this->schemes["tcp"]      ??= new SocketUrlFactory();
        return $this->schemes;
    }

    public function createSchemes(): Schemes
    {
        return new Schemes($this->schemes);
    }
}
