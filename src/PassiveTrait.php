<?php

declare(strict_types=1);

namespace davekok\kernel;

trait PassiveTrait
{
    public function listen(Acceptor $acceptor): self
    {
        $this->activity->push(new Accept($this, $factory, $acceptor, $this->handle));
        return $this;
    }

    public function accept(): Actionable
    {
        return new ActiveSocket(
            $this->activity->fork(),
            $this->url,
            stream_socket_accept($this->handle) ?: throw new KernelException("Accept failed"),
        );
    }
}
