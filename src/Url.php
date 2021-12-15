<?php

declare(strict_types=1);

namespace davekok\kernel;

class Url
{
    public const SCHEME_PORT = [
        "http"  => 80,
        "https" => 443,
        "smtp"  => 25,
        "ws"    => 80,
        "wss"   => 443,
    ];

    public function __construct(
        public readonly string|null     $scheme   = null,
        public readonly string|null     $username = null,
        public readonly string|null     $password = null,
        public readonly string|null     $host     = null,
        public readonly int|null        $port     = null,
        public readonly string|null     $path     = null,
        public readonly string|null     $query    = null,
        public readonly string|int|null $fragment = null,
    ) {}

    public function __toString(): string
    {
        $url = "";
        if ($this->scheme !== null) {
            $url .= $this->scheme;
            $url .= ":";
        }
        if ($this->host !== null) {
            $url .= "//";
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
            if ($this->port !== null && (
                isset(self::SCHEME_PORT[$this->scheme]) === false
                || self::SCHEME_PORT[$this->scheme] !== $this->port
            )) {
                $url .= ":{$this->port}";
            }
        }
        if ($this->path !== null) {
            $url .= $this->path;
        }
        if ($this->query !== null) {
            $url .= "?{$this->query}";
        }
        if ($this->fragment !== null) {
            $url .= "#{$this->fragment}";
        }
        return $url;
    }
}
