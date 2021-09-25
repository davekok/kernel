<?php

declare(strict_types=1);

namespace DaveKok\Stream;

use Throwable;

trait StreamMetaTrait
{
    /**
     * Get the local name of the stream.
     */
    public function getLocalName($stream): string
    {
        return stream_socket_get_name($stream, false);
    }

    /**
     * Get the remote name of the stream.
     */
    public function getRemoteName($stream): string
    {
        return stream_socket_get_name($stream, true);
    }
}
