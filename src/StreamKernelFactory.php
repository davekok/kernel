<?php

declare(strict_types=1);

namespace davekok\stream;

/**
 * Façade for this package.
 */
class StreamKernelFactory
{
    public function createClientKernel(): StreamClientKernel
    {
        return new StreamClientKernel();
    }

    /**
     * Implement the TimeOut if you would like to add cron like abilities to the stream server.
     *
     * Please don't mix regular streams with the StreamServerKernel. So no files, database connections, stdin, stdout, stderr, pipes or anything else.
     * Otherwise you will disrupt the flow of the program. Better option is to break your program into pieces. And have regular workers that
     * connect to the stream server, and let the stream server only pass data between the pieces.
     *
     * Example:
     *
     *     $container->get("stream-kernel-factory")
     *         ->createServerKernel()
     *         ->setLogger($container->get("my-logger"))
     *         ->listenOn("tcp://0.0.0.0:80", $container->get("http-protocol-factory"))
     *         ->listenOn("tcp://0.0.0.0:443", $container->get("https-protocol-factory"))
     *         ->listenOn("tcp://0.0.0.0:25", $container->get("smtp-protocol-factory"))
     *         ->listenOn("tcp://0.0.0.0:9000", $container->get("worker-protocol-factory"))
     *         ->listenOn("tcp://0.0.0.0:9001", $container->get("my-debug-protocol-factory"))
     *         ->run();
     */
    public function createServerKernel(TimeOut|null $timeOut = null): StreamServerKernel
    {
        return new StreamServerKernel($timeOut);
    }
}
