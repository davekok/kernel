<?php

declare(strict_types=1);

namespace DaveKok\Stream;

use Throwable;

class StreamServer
{
    private Map $activeStreams;
    private Map $passiveStreams;
    private Set $readyRead;
    private Set $readyWrite;

    public function __construct()
    {
        $this->activeStreams  = new Map();
        $this->passiveStreams = new Map();
        $this->readyRead      = new Set();
        $this->readyWrite     = new Set();
    }

    public function open(
        string $url,
        ConnectionFactory $connectionFactory,
        int $flags = STREAM_SERVER_BIND | STREAM_SERVER_LISTEN
    ): void
    {
        $scheme = parse_url($url, PHP_URL_SCHEME);
        if (in_array($scheme, stream_get_transports()) === false) {
            throw new StreamError("Transport not supported $scheme");
        }
        $passiveStream = stream_socket_server($url, $errno, $errstr, $flags);
        if ($passiveStream === false) {
            throw new StreamError("Unable to open passive stream {$url}: $errstr");
        }
        $this->passiveStreams->put($passiveStream, $connectionFactory);
        $readyRead->add($passiveStream);
    }

    public function run(): noreturn
    {
        for (;;) {
            try {
                $readStreams   = [...$readyRead->toArray()];
                $writeStreams  = [...$readyWrite->toArray()];
                $exceptStreams = [];

                $ret = stream_select($readStreams, $writeStreams, $exceptStreams, null);
                if ($ret === false || $ret === 0) {
                    continue;
                }

                foreach ($readStreams as $stream) {
                    if ($this->passiveStreams->contains($stream)) {
                        $connectionFactory = $this->passiveStreams->get($stream);
                        $stream = stream_socket_accept($stream);
                        $connection = $connectionFactory->createConnection();
                        $activeStreams->put($stream, $connection);
                    } else {
                        $connection = $activeStreams->get($stream);
                        $connection->pushInput(fread($stream, 8192));
                    }
                    $this->checkIOReady($connection, $stream);
                }

                foreach ($writeStreams as $stream) {
                    $connection = $activeStreams->get($stream);
                    $buffer = $connection->pullOutput();
                    $length = strlen($buffer);
                    if ($length > 0) {
                        fwrite($stream, $buffer, $length);
                        $this->checkIOReady($connection, $stream);
                    }
                }
            } catch (Throwable $e) {
                echo $e->getMessage(), "\n";
            }
        }
    }

    private function checkIOReady(Connection $connection, $stream): void
    {
        switch ($connection->checkIOReady()) {
            case IOReady::Read:
                $readyRead->add($stream);
                $readyWrite->remove($stream);
                return;

            case IOReady::Write:
                $readyRead->remove($stream);
                $readyWrite->add($stream);
                return;

            case IOReady::Both:
                $readyRead->add($stream);
                $readyWrite->add($stream);
                return;

            case IOReady::Closed:
                $readyRead->remove($stream);
                $readyWrite->remove($stream);
                $activeStreams->remove($stream);
                fclose($stream);
                return;
        }
    }
}
