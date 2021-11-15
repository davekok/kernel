<?php

declare(strict_types=1);

namespace davekok\stream;

/**
 * A passive socket or unconnected socket is a socket listening on a port for incoming connections.
 * Once a connection is made, accept is called to get an active socket for the connection.
 * Read and writing is not possible on passive sockets as they are unconnected.
 */
class PassiveSocketStream implements Stream, BlockableStream
{
    use StreamTrait;
    use BlockableStreamTrait;

    public function accept(): ActiveSocketStream
    {
        $handle = stream_socket_accept($this->handle);
        if ($handle === false) {
            throw new StreamError("Accept failed");
        }
        return new ActiveSocketStream($this->url, $handle);
    }
}
