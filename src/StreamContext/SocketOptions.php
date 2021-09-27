<?php

declare(strict_types=1);

namespace DaveKok\Stream\StreamContext;

class SocketOptions
{
    public const WRAPPER = "socket";
    public const INDEX_NAMES = [
        "bindTo" => "bindto",
        "backLog" => "backlog",
        "ipv6Only" => "ipv6_v6only",
        "reusePort" => "so_reuseport",
        "broadcast" => "so_broadcast",
        "tcpNoDelay" => "tcp_nodelay",
    ];

    public function __construct(
        public string|null $bindTo = null,
        public int|null $backLog = null,
        public bool|null $ipv6Only = null,
        public bool|null $reusePort = null,
        public bool|null $broadcast = null,
        public bool|null $tcpNoDelay = null,
    ) {}
}
