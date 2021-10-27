<?php

declare(strict_types=1);

namespace davekok\stream;

class SocketState
{
    public function __construct(
        public ReadyState|null $readyState = null,
        public bool|null $cryptoStateEnable = null,
        public int|null $cryptoStateType = null,
        public bool|null $running = null,
    ) {}

    public function diff(SocketState $other): SocketState
    {
        return new SocketState(
            readyState: $this->readyState !== $other->readyState ? $this->readyState : null,
            cryptoStateEnable: $this->cryptoStateEnable !== $other->cryptoStateEnable ? $this->cryptoStateEnable : null,
            cryptoStateType: $this->cryptoStateType !== $other->cryptoStateType ? $this->cryptoStateType : null,
            running: $this->running !== $other->running ? $this->running : null,
        );
    }

    public function apply(SocketState $patch): void
    {
        if ($other->readyState !== null) {
            $this->readyState = $other->readyState;
        }
        if ($other->cryptoStateEnable !== null) {
            $this->cryptoStateEnable = $other->cryptoStateEnable;
            $this->cryptoStateType = $other->cryptoStateType;
        }
        if ($other->running !== null) {
            $this->running = $other->running;
        }
    }
}
