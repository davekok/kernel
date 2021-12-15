<?php

declare(strict_types=1);

namespace davekok\kernel;

/**
 * Create a url for a named pipe.
 *
 * Supported url's:
 * - pipe://stdin
 * - pipe://stdout
 * - pipe://stderr
 * - pipe://memory
 * - pipe://temp
 * - pipe://temp/maxmemory:{maxmemory}
 */
class PipeUrlFactory implements UrlFactory
{
    public function createUrl(string $url): Url
    {
        $parts = parse_url($url);

        isset($parts["scheme"])      === true
        && $parts["scheme"]          === "pipe"
        && isset($parts["user"])     === false
        && isset($parts["pass"])     === false
        && isset($parts["host"])     === true
        && in_array($parts["host"], ["stdin", "stdout", "stderr", "memory", "temp"])
        && isset($parts["port"])     === false
        && isset($parts["query"])    === false
        && isset($parts["fragment"]) === false
        ?: throw new KernelException("Not a pipe url: $url");

        return new PipeUrl(scheme: $parts["scheme"], host: $parts["host"], path: $parts["path"] ?? null);
    }
}
