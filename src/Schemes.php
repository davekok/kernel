<?php

declare(strict_types=1);

namespace davekok\kernel;

class Schemes
{
    public function __construct(
        private array $schemes = [],
    ) {}

    public function setScheme(string $scheme, callable $wireable): self
    {
        $this->schemes[$scheme] = $wireable;
        return $this;
    }

    public function getScheme(string $scheme): callable
    {
        return match ($scheme) {
            "activity" => $this->schemes["activity"] ??= $this->wireActivityUrlFactory(...),
            "file"     => $this->schemes["file"]     ??= $this->wireFileUrlFactory(...),
            "pipe"     => $this->schemes["pipe"]     ??= $this->wirePipeUrlFactory(...),
            "tcp"      => $this->schemes["tcp"]      ??= $this->wireSocketUrlFactory(...),
            default    => $this->schemes[$scheme]    ?? throw new KernelException("Scheme not found: $scheme"),
        };
    }

    public function getSchemes(): array
    {
        $this->schemes["activity"] ??= $this->wireActivityUrlFactory(...);
        $this->schemes["file"]     ??= $this->wireFileUrlFactory(...);
        $this->schemes["pipe"]     ??= $this->wirePipeUrlFactory(...);
        $this->schemes["tcp"]      ??= $this->wireSocketUrlFactory(...);
        return $this->schemes;
    }

    public function wireActivityUrlFactory(Wirings $wirings): ActivityUrlFactory
    {
        return new ActivityUrlFactory($wirings->get("kernel")->service("kernel"));
    }

    public function wireFileUrlFactory(Wirings $wirings): FileUrlFactory
    {
        return new FileUrlFactory();
    }

    public function wirePipeUrlFactory(Wirings $wirings): PipeUrlFactory
    {
        return new PipeUrlFactory();
    }

    public function wireSocketUrlFactory(Wirings $wirings): SocketUrlFactory
    {
        return new SocketUrlFactory();
    }
}
