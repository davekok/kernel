<?php

declare(strict_types=1);

namespace davekok\kernel;

class SocketUrl extends Url
{
    public function connect(
        Activity $activity,
        float|null $timeout,
        int $flags = STREAM_CLIENT_CONNECT,
        Options|array|null $options = null,
    ): ActiveSocket
    {
        $handle = stream_socket_client(
            remote_socket: (string)$this,
            errno:         $errno,
            errstr:        $errstr,
            timeout:       $timeout ?? ini_get("default_socket_timeout"),
            flags:         $flags,
            context:       Options::createContext($options)
        ) ?: throw new KernelException($errstr, $errno);

        stream_set_blocking($handle, false);
        stream_set_chunk_size($handle, Kernel::CHUNK_SIZE);
        stream_set_read_buffer($handle, 0);
        stream_set_write_buffer($handle, 0);

        return new ActiveSocket($this, $activity, $handle);
    }

    public function bind(
        Activity $activity,
        int $flags = STREAM_SERVER_BIND | STREAM_SERVER_LISTEN,
        Options|array|null $options = null
    ): PassiveSocket
    {
        $handle = stream_socket_server(
            address:       (string)$this,
            error_code:    $errno,
            error_message: $errstr,
            flags:         $flags,
            context:       Options::createContext($options),
        ) ?: throw new KernelException($errstr, $errno);

        stream_set_blocking($handle, false);

        return new PassiveSocket($this, $activity->fork(), $handle);
    }
}
