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
        return $this->createActiveSocket(
            $activity,
            stream_socket_client(
                remote_socket: (string)$this,
                errno:         $errno,
                errstr:        $errstr,
                timeout:       $timeout ?? ini_get("default_socket_timeout"),
                flags:         $flags,
                context:       Options::createContext($options)
            ) ?: throw new KernelException($errstr, $errno)
        );
    }

    public function listen(
        Activity $activity,
        Acceptor $acceptor,
        int $flags = STREAM_SERVER_BIND | STREAM_SERVER_LISTEN,
        Options|array|null $options = null
    ): PassiveSocket
    {
        return $this->createPassiveSocket(
                $activity,
                stream_socket_server((string)$this, $errno, $errstr, $flags, Options::createContext($options))
                    ?: throw new KernelException($errstr, $errno)
            )
            ->listen($acceptor);
    }

    private function createActiveSocket(Activity $activity, mixed $handle): ActiveSocket
    {
        stream_set_blocking($handle, false);
        stream_set_chunk_size($handle, Kernel::CHUNK_SIZE);
        stream_set_read_buffer($handle, 0);
        stream_set_write_buffer($handle, 0);

        return new ActiveSocket($this, $activity, $handle);
    }

    private function createPassiveSocket(Activity $activity, mixed $handle): ActiveSocket
    {
        stream_set_blocking($handle, false);

        return new PassiveSocket($this, $activity->fork(), $handle);
    }
}
