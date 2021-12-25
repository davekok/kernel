<?php

declare(strict_types=1);

namespace davekok\kernel;

class ActiveSocket implements Actionable, Cryptoble, Readable, Writable, Closable
{
    use ActionableTrait;
    use CryptobleTrait;
    use ReadableTrait;
    use WritableTrait;
    use ClosableTrait;

    public function __construct(
        public  readonly Url         $url,
        public  readonly Activity    $activity,
        public  readonly mixed       $handle,
        private readonly ReadBuffer  $readBuffer  = new ReadBuffer,
        private readonly WriteBuffer $writeBuffer = new WriteBuffer,
    ) {}

    public function localUrl(): Url
    {
        [$host, $port] = explode(":", stream_socket_get_name($this->handle, false));
        return new Url(scheme: $this->url->scheme, host: $host, port: (int)$port);
    }

    public function remoteUrl(): Url
    {
        [$host, $port] = explode(":", stream_socket_get_name($this->handle, true));
        return new Url(scheme: $this->url->scheme, host: $host, port: (int)$port);
    }
}
