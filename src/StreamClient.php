<?php

declare(strict_types=1);

namespace DaveKok\Stream;

/**
 * Façade for this package.
 */
class StreamClient
{
    use ValidateTrait;

    /**
     * Connect to an URL.
     */
    public function connectTo(
        string $url,
        ProtocolFactoryInterface $protocolFactory,
        int $flags = STREAM_CLIENT_CONNECT
    ): noreturn
    {
        $this->validateURL($url);
        $context = $protocolFactory->createContext();
        $this->validateStreamContext($context);
        $stream = stream_socket_client($url, $errno, $errstr, $flags, $context)
        if ($stream === false) {
            throw new StreamError("Unable to open stream '{$url}': $errstr");
        }
        $kernel = new StreamClientKernel($protocolFactory, $stream);
        $kernel->run();
    }
}
