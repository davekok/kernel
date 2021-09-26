<?php

declare(strict_types=1);

namespace DaveKok\Stream;

interface ProtocolFactoryInterface
{
    /**
     * Create a protocol.
     */
    public function createProtocol(): ProtocolInterface;
}
