<?php

declare(strict_types=1,ticks=1);

namespace davekok\stream;

use Psr\Log\LoggerInterface;
use Throwable;

class StreamKernel
{
    public const CHUCK_SIZE    = 1400;
    private bool  $running     = false;
    private array $streams     = [];
    private array $sockets     = [];
    private array $acceptors   = [];
    private array $readyRead   = [];
    private array $readyWrite  = [];

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly TimeOut|null $timeout = null
    ) {}

    /**
     * Add stream to stream kernel
     */
    public function addStream(Stream $stream, Acceptor $acceptor): self
    {
        if ($stream instanceof ActiveSocketStream === true) {
            try {
                $stream->setBlocking(false);
                $stream->setChunkSize(self::CHUCK_SIZE);

                $socket               = new StreamKernelSocket($stream->getLocalName(), $stream->getRemoteName());
                $id                   = $stream->getId();
                $this->streams[$id]   = $stream;
                $this->sockets[$id]   = $socket;
                $this->readyRead[$id] = $stream->handle;
                $acceptor->accept($socket);
                $this->updateState($stream);
            } catch (Throwable $e) {
                $this->logger->error(get_class($e).": ".$e->getMessage());
                $this->close($stream);
            }
        } else if ($stream instanceof PassiveSocketStream === true) {
            try {
                $stream->setBlocking(false);

                $id                   = $stream->getId();
                $this->streams[$id]   = $stream;
                $this->acceptors[$id] = $acceptor;
                $this->readyRead[$id] = $stream->handle;
            } catch (Throwable $e) {
                $this->logger->error(get_class($e).": ".$e->getMessage());
                $this->close($stream);
            }
        } else {
            throw new StreamError(get_class($stream) . " is currently not supported.");
        }

        return $this;
    }

    public function quit(): void
    {
        $this->running = false;
    }

    public function run(): noreturn
    {
        try {
            if ($this->running === true) {
                throw new StreamError("Already running.");
            }
            $this->running = true;
            pcntl_signal(SIGHUP , $this->quit(...));
            pcntl_signal(SIGINT , $this->quit(...));
            pcntl_signal(SIGQUIT, $this->quit(...));
            pcntl_signal(SIGTERM, $this->quit(...));
            while ($this->running) {
                try {
                    $streams = $this->select();
                    if ($streams === null) continue;
                    [$readStreams, $writeStreams] = $streams;
                    foreach ($readStreams as $stream) {
                        $stream = $this->streams[get_resource_id($stream)];
                        try {
                            $this->read($stream);
                        } catch (Throwable $e) {
                            $this->logger->error(get_class($e).": ".$e->getMessage());
                            $this->close($stream);
                        }
                    }
                    foreach ($writeStreams as $stream) {
                        $stream = $this->streams[get_resource_id($stream)];
                        try {
                            $this->write($stream);
                        } catch (Throwable $e) {
                            $this->logger->error(get_class($e).": ".$e->getMessage());
                            $this->close($stream);
                        }
                    }
                } catch (Throwable $e) {
                    $this->logger->error(get_class($e).": ".$e->getMessage());
                }
            }
        } catch (Throwable $e) {
            $this->logger->emergency(get_class($e).": ".$e->getMessage());
        }
        exit();
    }

    private function select(): array|null
    {
        $readStreams   = [...$this->readyRead];
        $writeStreams  = [...$this->readyWrite];
        $exceptStreams = [];
        $ret = stream_select($readStreams, $writeStreams, $exceptStreams, $this->timeOut());
        if ($ret === false) {
            return null;
        }
        if ($ret === 0) {
            if (isset($this->timeOut) === false) {
                return null;
            }
            $this->timeOut->timeOut();
            return null;
        }
        $this->logger->debug("streams ready: read(".count($readStreams).") write(".count($writeStreams).")");
        return [$readStreams, $writeStreams];
    }

    private function timeOut(): int|null
    {
        if (isset($this->timeOut) === false) {
            return null;
        }

        // restrict number of immediate time out to 10000
        for ($i = 0; $i < 10000; ++$i) {
            $timeOut = $this->timeOut->getNextTimeOut();

            // if timeout is in the future then return time out
            if ($timeOut > 0) {
                return $timeOut;
            }

            // if timeout is now or in the past call the time out function
            try {
                $this->timeOut->timeOut();
            } catch (Throwable $e) {
                $this->logger->error(get_class($e).": ".$e->getMessage());
            }
        }

        $this->logger->error("Too many immediate time outs.");
        return null;
    }

    private function updateState(ActiveSocketStream $stream): void
    {
        $id = $stream->getId();
        $statePatch = $this->sockets[$id]->getStateDiff();
        $this->updateReadyState($stream, $statePatch);
        $this->updateCryptoState($stream, $statePatch);
        $this->updateRunningState($statePatch);
        $this->sockets[$id]->applyState($statePatch);
    }

    private function updateReadyState(ActiveSocketStream $stream, SocketState $statePatch): void
    {
        if ($statePatch->readyState === null) {
            return;
        }

        $id = $stream->getId();
        switch ($statePatch->readyState) {
            case ReadyState::ReadReady:
                $this->readyRead[$id] = $stream->handle;
                unset($this->readyWrite[$id]);
                break;

            case ReadyState::WriteReady:
                unset($this->readyRead[$id]);
                $this->readyWrite[$id] = $stream->handle;
                break;

            case ReadyState::BothReady:
                $this->readyRead[$id]  = $stream->handle;
                $this->readyWrite[$id] = $stream->handle;
                break;

            case ReadyState::Close:
                $this->close($stream);
                break;
        }
    }

    private function updateCryptoState(Stream $stream, SocketState $statePatch): void
    {
        if ($statePatch->cryptoStateEnable === null) {
            return;
        }

        try {
            $stream->setBlocking($stream, true);
            $stream->enableCrypto($statePatch->cryptoStateEnable, $statePatch->cryptoStateType);
            $stream->setBlocking($stream, false);
        } catch (Throwable $e) {
            $statePatch->cryptoStateEnable = !$statePatch->cryptoStateEnable;
            throw $e;
        }
    }

    private function updateRunningState(SocketState $statePatch): void
    {
        // if no state change then update to current state
        if ($statePatch->running === null) {
            $statePatch->running = $statePatch->running;
            return;
        }
        // running state can only be set to false, once false it remains false
        $statePatch->running = $this->running = ($this->running && $statePatch->running);
    }

    private function read(StreamSocket $stream): void
    {
        if ($stream instanceof PassiveSocketStream) {
            $this->accept($stream);
            return;
        }

        $socket = $this->sockets[$stream->getId()];
        $reader = $socket->getReader();
        $buffer = $socket->getReaderBuffer();

        if ($stream->endOfStream() === true) {
            $reader->endOfInput($buffer);
            $this->updateState($stream);
            return;
        }

        $buffer->add($stream->read(self::CHUCK_SIZE));
        $reader->read($buffer);
        $this->updateState($stream);
    }

    private function accept(PassiveSocketStream $passiveStream): void
    {
        $this->logger->debug("accepting connnection from " . $activeStream->getRemoteName());
        try {
            $activeStream = $passiveStream->accept();
            $activeStream->setBlocking(false);
            $activeStream->setChunkSize(self::CHUCK_SIZE);

            $socket             = new StreamKernelSocket($activeStream->getLocalName(), $activeStream->getRemoteName());
            $id                 = $activeStream->getId();
            $this->streams[$id] = $activeStream;
            $this->sockets[$id] = $socket;
            $this->acceptors[$passiveStream->getId()]->accept($socket);
            $this->updateState($activeStream);
        } catch (Throwable $e) {
            $this->logger->error(get_class($e).": ".$e->getMessage());
            $this->close($activeStream);
        }
    }

    private function write(ActiveSocketStream $stream): void
    {
        $socket = $this->sockets[$stream->getId()];
        $writer = $socket->getWriter();
        $buffer = $socket->getWriterBuffer();

        // check
        if ($buffer->valid() === false) {
            $writer->write($buffer);
            if ($buffer->valid() === false) {
                $this->logger->debug("Output requested but no output.");
                $this->updateState($stream);
                return;
            }
        }

        $chunk  = $buffer->getChunk(self::CHUNK_SIZE);
        $length = strlen($chunk);
        if ($length === 0) {
            $this->logger->debug("Output requested but no output.");
            $this->updateState($stream);
            return;
        }

        $written = fwrite($stream, $buffer, $length);
        if ($written === false) {
            $this->logger->error("Write error");
            $this->close($stream);
            return;
        }

        $buffer->moveMarkBy($written);

        // if nothing left in buffer, update state
        if ($buffer->valid() === false) {
            $this->updateState($stream);
        }
    }

    private function close(Stream $stream): void
    {
        $id = $stream->getId();
        if (isset($this->sockets[$id])) {
            $this->sockets[$id]->close();
        }
        unset($this->sockets[$id]);
        unset($this->streams[$id]);
        unset($this->readyRead[$id]);
        unset($this->readyWrite[$id]);
        $stream->close();
    }
}
