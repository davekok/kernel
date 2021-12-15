<?php

declare(strict_types=1);

namespace davekok\kernel;

class SocketUrlFactory implements UrlFactory
{
    public function createUrl(string $url): Url
    {
        $parts = parse_url($url);

        isset($parts["scheme"])      === true
        && $parts["scheme"]          === "tcp"
        && isset($parts["host"])     === true
        && isset($parts["port"])     === true
        && isset($parts["fragment"]) === false
        ?: throw new KernelException("Not a socket url: $url");

        return new SocketUrl(
            scheme:   $parts["scheme"],
            username: $parts["user"],
            password: $parts["pass"],
            host:     $parts["host"],
            port:     $parts["port"],
            path:     $parts["path"],
            query:    $parts["query"],
        );
    }
}
