<?php

declare(strict_types=1);

namespace davekok\stream;

use Throwable;

class StreamServerKernel extends StreamKernel
{
    private array $streams     = [];
    private array $connections = [];
    private array $acceptors   = [];
    private array $readyRead   = [];
    private array $readyWrite  = [];
    private array $buffers     = [];

    public function __construct(
        private readonly TimeOut|null $timeout = null
    ) {}

    /**
     * Add stream to stream kernel
     */
    public function addStream(StreamPassiveSocket $stream, Acceptor $acceptor): void
    {
        $stream->setBlocking(false);

        $id                   = $stream->getId();
        $this->streams[$id]   = $stream;
        $this->acceptors[$id] = $acceptor;
        $this->readyRead[$id] = $stream;
    }

    public function run(): noreturn
    {
        try {
            if ($this->running === true) {
                throw new StreamError("Already running.");
            }
            $this->running = true;
            while ($this->running) {
                try {
                    $streams = $this->select();
                    if ($streams === null) continue;
                    [$readStreams, $writeStreams] = $streams;
                    foreach ($readStreams as $stream) {
                        try {
                            $this->read($stream);
                        } catch (Throwable $e) {
                            $this->logger->error(get_class($e).": ".$e->getMessage());
                            $this->close($stream);
                        }
                    }
                    foreach ($writeStreams as $stream) {
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

    private function updateState(StreamActiveSocket $stream): void
    {
        $id = $stream->getId();
        $stateChanges = $this->connections[$id]->getStateChanges();
        $this->updateReadyState($stream, $stateChanges);
        $this->updateCryptoState($stream, $stateChanges);
        $this->updateRunningState($stateChanges);
        $this->connections[$id]->updateState($stateChanges);
    }

    private function updateReadyState(StreamActiveSocket $stream, array $stateChanges): void
    {
        if (isset($stateChanges["readyState"]) === false) {
            return;
        }

        $id = $stream->getId();
        switch ($stateChanges["readyState"]) {
            case StreamReadyState::ReadReady:
                $this->readyRead[$id] = $stream;
                unset($this->readyWrite[$id]);
                break;

            case StreamReadyState::WriteReady:
                unset($this->readyRead[$id]);
                $this->readyWrite[$id] = $stream;
                break;

            case StreamReadyState::BothReady:
                $this->readyRead[$id]  = $stream;
                $this->readyWrite[$id] = $stream;
                break;

            case StreamReadyState::Close:
                $this->close($stream);
                break;
        }
    }

    private function read(StreamSocket $stream): void
    {
        if ($stream instanceof StreamPassiveSocket) {
            $this->accept($stream);
            return;
        }

        if ($stream->endOfStream() === true) {
            $this->connections[$id]->endOfInput();
            $this->updateState($stream);
            return;
        }

        $this->connections[$id]->pushInput($stream->read(self::CHUCK_SIZE));
        $this->updateState($stream);
    }

    private function accept(StreamPassiveSocket $passiveStream): void
    {
        try {
            $activeStream = $passiveStream->accept();
            $activeStream->setBlocking(false);
            $activeStream->setChunkSize(self::CHUCK_SIZE);

            $connection             = new Connection($activeStream->getLocalName(), $activeStream->getRemoteName());
            $id                     = $activeStream->getId();
            $this->streams[$id]     = $activeStream;
            $this->connections[$id] = $connection;
            $this->acceptors[$passiveStream->getId()]->accept($connection);
            $this->updateState($activeStream);
        } catch (Throwable $e) {
            $this->logger->error(get_class($e).": ".$e->getMessage());
            $this->close($activeStream);
        }
    }

    private function write(StreamActiveSocket $stream): void
    {
        $id = $stream->getId();

        // get output
        if (isset($this->buffers[$id]) === true) {
            $buffer = $this->buffers[$id];
            unset($this->buffers[$id]);
        } else {
            $buffer = $this->connections[$id]->pullOutput();
        }

        $length = strlen($buffer);
        if ($length === 0) {
            $this->logger->notify("Output requested but no output.");
            $this->updateState($stream);
            return;
        }

        // write in chunks
        if ($length > self::CHUCK_SIZE) {
            // safe remaining in buffer
            $this->buffers[$id] = substr($buffer, self::CHUCK_SIZE);
            // truncate buffer to chunk size
            $buffer = substr($buffer, 0, self::CHUCK_SIZE);
            $length = self::CHUCK_SIZE;
        }

        // write buffer
        $written = fwrite($stream, $buffer, $length);
        if ($written === false) {
            $this->logger->error("Write error");
            $this->close($stream);
            return;
        }

        // check if entire buffer is written
        if ($written < strlen($buffer)) {
            // prepend remaining to buffer
            $this->buffers[$id] = substr($buffer, $written) . ($this->buffers[$id] ?? "");
            return;
        }

        // if write is completed update state
        if (isset($this->buffers[$id]) === false) {
            $this->updateState($stream);
        }
    }

    private function close(StreamSocket $stream): void
    {
        $id = $stream->getId();
        if (isset($this->connections[$id])) {
            $this->connections[$id]->close();
        }
        unset($this->connections[$id]);
        unset($this->streams[$id]);
        unset($this->readyRead[$id]);
        unset($this->readyWrite[$id]);
        unset($this->buffers[$id]);
        $stream->close();
    }
}
