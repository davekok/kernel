<?php

declare(strict_types=1);

namespace DaveKok\Stream;

interface ProtocolFactoryInterface
{
    /**
     * Create a protocol by reflecting the given object. The reflection must reveal attributes.
     */
    public function createProtocol(): ProtocolInterface
}
