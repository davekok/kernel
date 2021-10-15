<?php

declare(strict_types=1);

namespace davekok\stream;

/**
 * A passive socket or unconnected socket is a socket listening on a port for incoming connections.
 * Once a connection is made, call accept to get an active socket for the connection.
 * Read and writing is not possible on passive sockets as they are unconnected.
 */
class StreamPassiveSocket extends StreamSocket
{
    public function accept(): StreamActiveSocket
    {
        $handle = stream_socket_accept($this->handle);
        if ($handle === false) {
            throw new StreamError("Accept failed");
        }
        return new StreamActiveSocket($handle);
    }

    public function setChunkSize(int $chunkSize): void
    {
        throw new StreamError("Setting chunk size not supported on a passive socket.");
    }

    public function read(int $size = 8192): string
    {
        throw new StreamError("Reading not possible on a passive socket.");
    }

    public function write(string $buffer): int
    {
        throw new StreamError("Writing not possible on a passive socket.");
    }
}
