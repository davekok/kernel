<?php

declare(strict_types=1);

namespace davekok\stream;

class SocketState
{
    public function __construct(
        public ReadyState|null $readyState = null,
        public bool|null $cryptoStateEnable = null,
        public int|null $cryptoStateType = null,
    ) {}

    public function diff(SocketState $with): SocketState
    {
        return new SocketState(
            readyState: $this->readyState !== $with->readyState ? $this->readyState : null,
            cryptoStateEnable: $this->cryptoStateEnable !== $with->cryptoStateEnable ? $this->cryptoStateEnable : null,
            cryptoStateType: $this->cryptoStateType !== $with->cryptoStateType ? $this->cryptoStateType : null,
        );
    }

    public function apply(SocketState $patch): void
    {
        if ($patch->readyState !== null) {
            $this->readyState = $patch->readyState;
        }
        if ($patch->cryptoStateEnable !== null) {
            $this->cryptoStateEnable = $patch->cryptoStateEnable;
            $this->cryptoStateType = $patch->cryptoStateType;
        }
    }
}
