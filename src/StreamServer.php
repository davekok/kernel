<?php

declare(strict_types=1);

namespace DaveKok\Stream;

use Throwable;

/**
 * Façade for this package.
 *
 * Implement the TimeOutInterface if you would like to add cron like abilities to your program.
 *
 * Please don't mix regular streams with the StreamServer. So no files, database connections, stdin, stdout, stderr, pipes or anything else.
 * Otherwise you will disrupt the flow of the program. Better option is to break your program into pieces. And have regular workers that
 * connect to the stream server, and let the stream server only pass data between the pieces.
 *
 * Example:
 *
 *     $server = new StreamServer();
 *     $server->listenOn("tcp://0.0.0.0:80", new \MyApp\HTTPProtocolFactory())
 *     $server->listenOn("tcp://0.0.0.0:443", new \MyApp\HTTPSProtocolFactory())
 *     $server->listenOn("tcp://0.0.0.0:25", new \MyApp\SMTPProtocolFactory())
 *     $server->listenOn("tcp://0.0.0.0:9000", new \MyApp\MyWorkerProtocolFactory())
 *     $server->listenOn("tcp://0.0.0.0:9001", new \MyApp\MyDebugProtocolFactory())
 *     $server->run();
 */
class StreamServer
{
    use ValidateTrait;

    private readonly StreamServerKernel $kernel;

    public function __construct(
        private readonly TimeOutInterface|null $timeOut = null
    )
    {
        $this->kernel = new StreamServerKernel($timeOut);
    }

    /**
     * Listen on the given url.
     */
    public function listenOn(
        string $url,
        ProtocolFactoryInterface $protocolFactory,
        int $flags = STREAM_SERVER_BIND | STREAM_SERVER_LISTEN
    ): void
    {
        $this->validateURL($url);
        $context = $protocolFactory->createContext();
        $this->validateStreamContext($context);
        $passiveStream = stream_socket_server($url, $errno, $errstr, $flags, $context);
        if ($passiveStream === false) {
            throw new StreamError("Unable to open passive stream '{$url}': $errstr");
        }
        $this->kernel->adoptPassiveStream($protocolFactory, $passiveStream);
    }

    public function run(): noreturn
    {
        $this->kernel->run();
    }
}
