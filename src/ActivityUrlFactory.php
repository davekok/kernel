<?php

declare(strict_types=1);

namespace davekok\kernel;

class ActivityUrlFactory implements UrlFactory
{
    public function __construct(private readonly Kernel $kernel) {}

    public function createUrl(string $url): Activity
    {
        $parts = parse_url($url);

        isset($parts["scheme"])   === true
        && $parts["scheme"]       === "activity"
        && isset($parts["user"])  === false
        && isset($parts["pass"])  === false
        && isset($parts["host"])  === false
        && isset($parts["port"])  === false
        && isset($parts["path"])  === false
        && isset($parts["query"]) === false
        ?: throw new KernelException("Not a activity url: $url");

        if (isset($parts["fragment"])) {
            if (filter_var($parts["fragment"], FILTER_VALIDATE_INT) === false) {
                throw new KernelException("Not a activity url: $url");
            }
            return $this->kernel->getActivity((int)$parts["fragment"]);
        }

        return $this->kernel->createActivity();
    }
}
