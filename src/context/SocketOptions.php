<?php

declare(strict_types=1);

namespace davekok\kernel\context;

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
        /**
         * Used to specify the IP address (either IPv4 or IPv6) and/or the port number that PHP will use to access the
         * network. The syntax is ip:port for IPv4 addresses, and [ip]:port for IPv6 addresses. Setting the IP or the
         * port to 0 will let the system choose the IP and/or port.
         */
        public string|null $bindTo = null,

        /**
         * Used to limit the number of outstanding connections in the socket's listen queue.
         * Only applies to StreamPassiveSocket.
         */
        public int|null $backLog = null,

        /**
         * Overrides the OS default regarding mapping IPv4 into IPv6.
         * Only applies to StreamPassiveSocket.
         */
        public bool|null $ipv6Only = null,

        /**
         * Allows multiple bindings to a same ip:port pair, even from separate processes.
         * Only applies to StreamPassiveSocket.
         */
        public bool|null $reusePort = null,

        /**
         * Enables sending and receiving data to/from broadcast addresses.
         * Only applies to StreamPassiveSocket.
         */
        public bool|null $broadcast = null,

        /**
         * Setting this option to true will set SOL_TCP,NO_DELAY=1 appropriately, thus disabling the TCP Nagle algorithm.
         */
        public bool|null $tcpNoDelay = null,
    ) {}
}
