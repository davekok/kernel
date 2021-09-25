<?php

declare(strict_types=1);

namespace DaveKok\Stream;

use Throwable;

class StreamServerKernel implements StreamKernelInterface
{
    use UpdateCryptoStateTrait;
    use StreamMetaTrait;

    private bool  $running    = false;
    private array $states     = [];
    private array $protocols  = [];
    private array $factories  = [];
    private array $readyRead  = [];
    private array $readyWrite = [];

    public function __construct(
        private readonly TimeOutInterface|null $timeOut = null
    ) {}

    public function quit(): void
    {
        $this->running = false;
    }

    /**
     * Adopt a passive network stream that can receive connections with the accept system call.
     */
    public function adoptPassiveStream(ProtocolFactoryInterface $protocolFactory, $passiveStream): void
    {
        if (stream_set_blocking($passiveStream, false) === false) {
            throw new StreamError("Unable to enable asynchronous IO for passive stream.");
        }

        $id                   = get_resource_id($passiveStream);
        $this->factories[$id] = $protocolFactory;
        $this->readyRead[$id] = $passiveStream;
    }

    public function updateReadyState(mixed $stream, StreamReadyState $readyState): StreamReadyState
    {
        $id = get_resource_id($stream);
        switch ($readyState) {
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
                $this->destroyState($stream);
                break;
        }

        return $readyState;
    }

    public function run(): noreturn
    {
        if ($this->running === true) {
            throw new StreamError("Already running");
        }
        $this->running = true;
        while ($this->running) {
            $readStreams   = [...$this->readyRead];
            $writeStreams  = [...$this->readyWrite];
            $exceptStreams = [];
            $ret = stream_select($readStreams, $writeStreams, $exceptStreams, $this->timeOut());
            if ($ret === false) {
                continue;
            }
            if ($ret === 0) {
                if (isset($this->timeOut) === false) {
                    continue;
                }

                try {
                    $this->timeOut->timeOut();
                } catch (Throwable $e) {}
                continue;
            }

            foreach ($readStreams as $stream) {
                $this->read($stream);
            }

            foreach ($writeStreams as $stream) {
                $this->write($stream);
            }
        }
        exit();
    }

    private function destroyState($stream): void
    {
        $id = get_resource_id($stream);
        if (isset($this->protocols[$id])) {
            $this->protocols[$id]->destroyProtocol();
        }
        unset($this->states[$id]);
        unset($this->readyRead[$id]);
        unset($this->readyWrite[$id]);
        fclose($stream);
    }

    private function timeOut(): int|null
    {
        if (isset($this->timeOut) === false) {
            return null;
        }

        // restrict number of immediate time out to 10000
        foreach ($i = 0; $i < 10000; ++$i) {
            $timeOut = $this->timeOut->getNextTimeOut();

            // if timeout is in the future then return time out
            if ($timeOut > 0) {
                return $timeOut;
            }

            // if timeout is now or in the past call the time out function
            $this->timeOut->timeOut();
        }

        throw new StreamError("Too many immediate time outs.");
    }

    private function read($stream): void
    {
        $id = get_resource_id($stream);

        // Is new connection on a passive stream.
        if (isset($this->factories[$id])) {
            $this->accept($this->factories[$id], $stream);

            return;
        }

        try {
            // If no associated state
            if (isset($this->states[$id]) === false) {
                throw new StreamError("No state for stream {$id}, yet stream is still active.");
            }

            if (feof($stream) === true) {
                $this->state[$id]->buffer = "";
                $this->protocols[$id]->endOfInput();

                return;
            }

            $buffer = fread($stream, StreamKernelInterface::CHUCK_SIZE);
            if ($buffer === false) {
                throw new StreamError("Failed to read from stream {$id}.");
            }

            $this->state[$id]->buffer = $buffer;
            $this->protocols[$id]->pushInput();
        } catch (Throwable $e) {
            echo get_class($e) . ": {$e->getMessage()}\n";
            $this->destroyState($stream);
        }
    }

    private function accept(ProtocolFactoryInterface $protocolFactory, $passiveStream): void
    {
        $activeStream = stream_socket_accept($passiveStream);
        if (stream_set_blocking($activeStream, false) === false) {
            fclose($activeStream);
            return;
        }
        if (stream_set_chunk_size($activeStream, StreamKernelInterface::CHUCK_SIZE) === false) {
            fclose($activeStream);
            return;
        }
        try {
            $state                = new StreamState($this, $stream);
            $id                   = get_resource_id($stream);
            $this->states[$id]    = $state;
            $this->protocols[$id] = $protocolFactory->createProtocol($state);
        } catch (Throwable $e) {
            $this->destroyState($stream);
        }
    }

    private function write($stream): void
    {
        $id = get_resource_id($stream);
        if (isset($this->protocols[$id]) === false) {
            continue;
        }

        try {
            $this->protocols[$id]->pullOutput($state);
        } catch (Throwable $e) {
            $this->protocols[$id]->notifyError($e);
        }

        $buffer = $this->states[$id]->buffer;
        $length = strlen($buffer);
        if ($length === 0) {
            continue;
        }

        $written = fwrite($stream, $buffer, $length);
        if ($written === false) {
            $this->protocols[$id]->notifyError(new StreamError("Write error"));
            return;
        }

        if ($written < strlen($buffer)) {
            $buffer = substr($buffer, $written);
            // queue state
            // create temp state, to handle multiple writes
            return;
        }
    }
}
