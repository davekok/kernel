<?php

declare(strict_types=1);

namespace DaveKok\Stream;

use Throwable;

class StreamServer
{
    private bool  $running    = false;
    private array $protocols  = [];
    private array $factories  = [];
    private array $readyRead  = [];
    private array $readyWrite = [];

    public function listenOn(
        string $url,
        ProtocolFactoryInterface $protocolFactory,
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
        stream_set_blocking($passiveStream, false);

        $id                   = get_resource_id($passiveStream);
        $this->factories[$id] = $protocolFactory;
        $this->readyRead[$id] = $passiveStream;
    }

    public function connectTo(
        string $url,
        ProtocolFactoryInterface $protocolFactory,
        int $flags = STREAM_CLIENT_CONNECT
    ): void
    {
        $scheme = parse_url($url, PHP_URL_SCHEME);
        if (in_array($scheme, stream_get_transports()) === false) {
            throw new StreamError("Transport not supported $scheme");
        }
        $activeStream = stream_socket_client($url, $errno, $errstr, $flags);
        if ($activeStream === false) {
            throw new StreamError("Unable to open active stream {$url}: $errstr");
        }
        stream_set_blocking($activeStream, false);

        $id                   = get_resource_id($activeStream);
        $this->protocols[$id] = $protocolFactory->createProtocol();
        $this->readyRead[$id] = $activeStream;
    }

    public function run(): noreturn
    {
        if ($this->running === true) {
            throw new StreamError("Already running");
        }
        $this->running = true;
        while ($this->running) {
            try {
                $readStreams   = [...$this->readyRead];
                $writeStreams  = [...$this->readyWrite];
                $exceptStreams = [];

                $ret = stream_select($readStreams, $writeStreams, $exceptStreams, null);
                if ($ret === false || $ret === 0) {
                    continue;
                }

                foreach ($readStreams as $stream) {
                    $id = get_resource_id($stream);

                    if (isset($this->factories[$id])) {
                        $protocolFactory = $this->factories[$id];
                        $activeStream    = stream_socket_accept($stream);
                        $protocol        = $protocolFactory->createProtocol();
                        stream_set_blocking($activeStream, false);
                        $this->protocols[get_resource_id($activeStream)] = $protocol;
                        $this->updateIOState($protocol, $activeStream);
                        continue;
                    }

                    if (isset($this->protocols[$id])) {
                        $protocol = $this->protocols[$id];
                        $protocol->pushInput(fread($stream, 8192));
                        $this->updateIOState($protocol, $stream);
                        continue;
                    }
                }

                foreach ($writeStreams as $stream) {
                    $protocol = $this->protocols->get($stream);
                    $buffer   = $protocol->pullOutput();
                    $length   = strlen($buffer);
                    if ($length > 0) {
                        fwrite($stream, $buffer, $length);
                    }
                    $this->updateIOState($protocol, $stream);
                }
            } catch (Throwable $e) {
                echo $e->getMessage(), "\n";
            }
        }
        exit();
    }

    private function updateIOState(ProtocolInterface $protocol, $stream): void
    {
        $id = get_resource_id($stream);
        foreach ($protocol->getIOStateChanges() as $stateChange) {
            if ($stateChange instanceof IOReadyStateChange === true) {
                switch ($stateChange->request) {
                    case IOReadyState::ReadReady:
                        $this->readyRead[$id] = $stream;
                        unset($this->readyWrite[$id]);
                        continue;

                    case IOReadyState::WriteReady:
                        unset($this->readyRead[$id]);
                        $this->readyWrite[$id] = $stream;
                        continue;

                    case IOReadyState::BothReady:
                        $this->readyRead[$id]  = $stream;
                        $this->readyWrite[$id] = $stream;
                        continue;

                    case IOReadyState::Close:
                        unset($this->readyRead[$id]);
                        unset($this->readyWrite[$id]);
                        unset($this->protocols[$id]);
                        fclose($stream);
                        continue;
                }
            }

            if ($stateChange instanceof IOCryptoStateChange === true) {
                $ret = match(true) {
                    $stateChange->sessionStream !== null => stream_socket_enable_crypto(
                            $stream,
                            $stateChange->request,
                            $stateChange->cryptoType,
                            $stateChange->sessionStream
                        ),
                    $stateChange->cryptoType !== null => stream_socket_enable_crypto(
                            $stream,
                            $stateChange->request,
                            $stateChange->cryptoType
                        ),
                    default => stream_socket_enable_crypto($stream, $stateChange->request)
                };

                if ($ret === false) {
                    $protocol->pushError(new StreamError("Negotiation failed."));
                    continue;
                }

                if ($ret === 0) {
                    $protocol->pushError(new StreamError("Not enough data please try again."));
                    continue;
                }

                continue;
            }
        }
    }
}
