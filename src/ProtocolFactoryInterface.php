<?php

declare(strict_types=1);

namespace DaveKok\Stream;

interface ProtocolFactoryInterface
{
    /**
     * Create a protocol, the given stream state object contains the state of the stream.
     *
     * Must not throw exceptions.
     *
     * If errors occur set $state->readyState to StreamReadyState::Close.
     */
    public function createProtocol(StreamState $state): ProtocolInterface

    /**
     * Should return a resource of type stream-context or null.
     *
     * @return resource|null
     */
    public function createContext();
}
