<?php

declare(strict_types=1);

namespace davekok\kernel;

class ActiveSocket implements Actionable, Cryptoble, Readable, Writable
{
    use ActionableTrait;
    use CryptobleTrait;
    use ReadableTrait;
    use WritableTrait;

    public function __construct(
        public  readonly Activity    $activity,
        public  readonly Url         $url,
        public  readonly mixed       $handle,
        private readonly ReadBuffer  $readBuffer  = new ReadBuffer,
        private readonly WriteBuffer $writeBuffer = new WriteBuffer,
    ) {
        stream_set_blocking($this->handle, false);
        stream_set_chunk_size($this->handle, Kernel::CHUNK_SIZE);
        stream_set_read_buffer($this->handle, 0);
        stream_set_write_buffer($this->handle, 0);
    }

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
