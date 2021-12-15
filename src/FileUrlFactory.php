<?php

declare(strict_types=1);

namespace davekok\kernel;

class FileUrlFactory
{
    public function createUrl(string $url): Url
    {
        $parts = parse_url($url);

        isset($parts["scheme"])      === true
        && $parts["scheme"]          === "file"
        && isset($parts["user"])     === false
        && isset($parts["pass"])     === false
        && isset($parts["host"])     === false
        && isset($parts["port"])     === false
        && isset($parts["path"])     === true
        && $parts["path"]            !== ""
        && $parts["path"][0]         === "/"
        && isset($parts["query"])    === false
        && isset($parts["fragment"]) === false
        ?: throw new KernelException("Not a local file url: $url");

        return new FileUrl(scheme: $parts["scheme"], path: $parts["path"]);
    }
}
