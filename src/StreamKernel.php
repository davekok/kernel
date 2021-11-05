<?php

declare(strict_types=1,ticks=1);

namespace davekok\stream;

use Psr\Log\LoggerInterface;
use Throwable;

class StreamKernel
{
    public const CHUNK_SIZE    = 1400;
    private bool  $running     = false;
    private array $streams     = [];
    private array $sockets     = [];
    private array $acceptors   = [];
    private array $readyRead   = [];
    private array $readyWrite  = [];
    private array $selectRead  = [];
    private array $selectWrite = [];
    private array $quitors     = [];

    public function __construct(
        private readonly LoggerInterface $log,
        private readonly TimeOut|null $timeout = null
    )
    {}

    /**
     * Add stream to stream kernel
     */
    public function addStream(Stream $stream, callback $acceptor): self
    {
        if ($stream instanceof ActiveSocketStream === true) {
            try {
                $stream->setBlocking(false);
                $stream->setChunkSize(self::CHUNK_SIZE);

                $socket               = new StreamKernelSocket($stream->getLocalName(), $stream->getRemoteName());
                $id                   = $stream->getId();
                $this->streams[$id]   = $stream;
                $this->sockets[$id]   = $socket;
                $this->readyRead[$id] = $stream->handle;
                $acceptor($socket);
                $this->updateState($stream);
            } catch (Throwable $e) {
                $this->log->error($e);
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
                $this->log->error($e);
                $this->close($stream);
            }
        } else {
            throw new StreamError(get_class($stream) . " is currently not supported.");
        }

        return $this;
    }

    /**
     * Remove stream to stream kernel
     */
    public function removeStream(Stream $stream): self
    {
        $this->close($stream);
    }

    /**
     * Add a quitor to the stream kernel, quitors get called when the kernel stops.
     */
    public function addQuitor(callback $quitor): self
    {
        $this->quitors[] = $quitor;
    }

    /**
     * Remove a quitor from the stream kernel.
     */
    public function removeQuitor(callback $quitor): self
    {
        $key = array_search($quitor, $this->quitors, true);
        if ($key === false) {
            return $this;
        }
        unset($this->quitors[$key]);
        return $this;
    }

    /**
     * Tell the kernel to stop quit on next pass.
     */
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

            // register signals
            pcntl_signal(SIGINT , $this->quit(...));
            pcntl_signal(SIGQUIT, $this->quit(...));
            pcntl_signal(SIGTERM, $this->quit(...));

            $this->log->info("running ...");
            while ($this->running) {
                try {
                    if ($this->select() === false) continue;
                    foreach ($this->selectRead as $stream) {
                        $stream = $this->streams[get_resource_id($stream)];
                        try {
                            $this->read($stream);
                        } catch (Throwable $e) {
                            $this->log->error($e);
                            $this->close($stream);
                        }
                    }
                    foreach ($this->selectWrite as $stream) {
                        $stream = $this->streams[get_resource_id($stream)];
                        try {
                            $this->write($stream);
                        } catch (Throwable $e) {
                            $this->log->error($e);
                            $this->close($stream);
                        }
                    }
                } catch (Throwable $e) {
                    $this->log->error($e);
                }
            }
        } catch (Throwable $e) {
            $this->log->emergency($e);
        }
        $this->log->info("quiting ...");
        foreach ($this->streams as $stream) {
            $this->close($stream);
        }
        foreach ($this->quitors as $quitor) {
            $quitor->quit();
        }
        exit();
    }

    private function select(): bool
    {
        $this->selectRead  = [...$this->readyRead];
        $this->selectWrite = [...$this->readyWrite];
        $exceptStreams = [];
        if (count($this->selectRead) === 0 && count($this->selectWrite) === 0) {
            $this->log->emergency("no streams to select");
            exit();
        }
        $ret = stream_select($this->selectRead, $this->selectWrite, $exceptStreams, $this->timeOut());
        if ($ret === false) {
            return false;
        }
        if ($ret === 0) {
            if (isset($this->timeOut) === false) {
                return false;
            }
            $this->timeOut->timeOut();
            return false;
        }
        return true;
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
                $this->log->error($e);
            }
        }

        $this->log->error("Too many immediate time outs.");
        return null;
    }

    private function updateState(ActiveSocketStream $stream): void
    {
        $id         = $stream->getId();
        $statePatch = $this->sockets[$id]->getStateDiff();

        if ($statePatch->readyState !== null) {
            switch ($statePatch->readyState) {
                case ReadyState::NotReady:
                    unset($this->readyRead[$id]);
                    unset($this->readyWrite[$id]);
                    break;

                case ReadyState::ReadReady:
                    $this->readyRead[$id] = $stream->handle;
                    unset($this->readyWrite[$id]);
                    break;

                case ReadyState::WriteReady:
                    unset($this->readyRead[$id]);
                    $this->readyWrite[$id] = $stream->handle;
                    break;

                case ReadyState::Close:
                    $this->close($stream);
                    return;
            }
        }

        if ($statePatch->cryptoStateEnable !== null) {
            try {
                $stream->setBlocking($stream, true);
                $stream->enableCrypto($statePatch->cryptoStateEnable, $statePatch->cryptoStateType);
                $stream->setBlocking($stream, false);
            } catch (Throwable $e) {
                $statePatch->cryptoStateEnable = !$statePatch->cryptoStateEnable;
                $this->log->error($e);
            }
        }

        $this->sockets[$id]->applyState($statePatch);
    }

    private function read(Stream $stream): void
    {
        if ($stream instanceof PassiveSocketStream) {
            $this->accept($stream);
            return;
        }

        $socket = $this->sockets[$stream->getId()];
        $reader = $socket->getReader();
        $buffer = $socket->getReaderBuffer();

        if ($stream->endOfStream() === true) {
            $reader($buffer->end());
            $this->updateState($stream);
            return;
        }

        $reader($buffer->add($stream->read(self::CHUNK_SIZE)));

        $this->updateState($stream);
    }

    private function accept(PassiveSocketStream $passiveStream): void
    {
        try {
            $activeStream = $passiveStream->accept();
            $activeStream->setBlocking(false);
            $activeStream->setChunkSize(self::CHUNK_SIZE);
            $this->log->info("accepting connnection from " . $activeStream->getRemoteName());

            $socket             = new StreamKernelSocket($activeStream->getLocalName(), $activeStream->getRemoteName());
            $id                 = $activeStream->getId();
            $this->streams[$id] = $activeStream;
            $this->sockets[$id] = $socket;
            $this->acceptors[$passiveStream->getId()]($socket);
            $this->updateState($activeStream);
        } catch (Throwable $e) {
            $this->log->error($e);
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
            $writer($buffer);
            if ($buffer->valid() === false) {
                $this->log->debug("Output requested but no output.");
                $this->updateState($stream);
                return;
            }
        }

        $chunk  = $buffer->getChunk(self::CHUNK_SIZE);
        $length = strlen($chunk);
        if ($length === 0) {
            $this->log->debug("Output requested but no output.");
            $this->updateState($stream);
            return;
        }

        $written = $stream->write($chunk, $length);
        if ($written === false) {
            $this->log->error("Write error");
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
        if ($stream instanceof ActiveSocketStream) {
            $this->log->info("closing connection to ".$stream->getRemoteName());
        }
        $id = $stream->getId();
        if (isset($this->sockets[$id])) {
            $this->sockets[$id]->close();
        }
        unset($this->sockets[$id]);
        unset($this->streams[$id]);
        unset($this->readyRead[$id]);
        unset($this->readyWrite[$id]);
    }
}
