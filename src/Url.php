<?php

declare(strict_types=1);

namespace davekok\stream;

class Url
{
    public const SCHEME_PORT = [
        "http"  => 80,
        "https" => 443,
        "smtp"  => 25,
        "ws"    => 80,
        "wss"   => 443,
    ];

    public static function createUrl(string $url): Url
    {
        $parts = parse_url($url);
        return new self(
            scheme:   $parts["scheme"]   ?? null,
            username: $parts["user"]     ?? null,
            password: $parts["pass"]     ?? null,
            host:     $parts["host"]     ?? null,
            port:     $parts["port"]     ?? null,
            path:     $parts["path"]     ?? null,
            query:    $parts["query"]    ?? null,
            fragment: $parts["fragment"] ?? null,
        );
    }

    public function __construct(
        public readonly string|null $scheme   = null,
        public readonly string|null $username = null,
        public readonly string|null $password = null,
        public readonly string|null $host     = null,
        public readonly int|null    $port     = null,
        public readonly string|null $path     = null,
        public readonly string|null $query    = null,
        public readonly string|null $fragment = null,
    ) {}

    public function __toString(): string
    {
        $url = "";
        if ($this->scheme !== null) {
            $url .= $this->scheme;
            $url .= "://";
        }
        if ($this->username !== null) {
            $url .= urlencode($this->username);
            if ($this->password !== null) {
                $url .= ":";
                $url .= urlencode($this->password);
            }
            $url .= "@";
        }
        if ($this->host !== null) {
            $url .= $this->host;
        }
        if ($this->port !== null && (isset(self::SCHEME_PORT[$this->scheme]) == false || self::SCHEME_PORT[$this->scheme] !== $this->port)) {
            $url .= ":";
            $url .= $this->port;
        }
        if ($this->path !== null) {
            $url .= $this->path;
        }
        if ($this->query !== null) {
            $url .= "?";
            $url .= $this->query;
        }
        if ($this->fragment !== null) {
            $url .= "#";
            $url .= $this->fragment;
        }
        return $url;
    }
}
