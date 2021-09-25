<?php

declare(strict_types=1);

namespace DaveKok\Stream;

use Throwable;

class StreamClientKernel extends StreamKernel
{
    private readonly StreamState $state;
    private readonly StreamReadyState $readyState;
    private readonly ProtocolInterface $protocol;
    private readonly StreamActiveSocket $stream;

    public function setStream(StreamActiveSocket $stream): noreturn
    {
        try {
            $this->stream = $stream;
            $this->stream->setChunkSize(self::CHUCK_SIZE);

            $this->state    = new StreamState($this->stream->getLocalName(), $this->stream->getRemoteName());
            $this->protocol = $protocolFactory->createProtocol($this->state);
        } catch (Throwable $e) {
            $this->close();
            throw $e;
        }
    }

    public function run(): noreturn
    {
        try {
            if ($this->running === true) {
                throw new StreamError("Already running");
            }
            $this->running = true;
            while ($this->running) {
                switch ($this->readyState) {
                    case StreamReadyState::NotReady:
                        throw new StreamError("Stream not ready but nothing else to do.");

                    case StreamReadyState::ReadReady:
                        $this->read();
                        break;

                    case StreamReadyState::WriteReady:
                        $this->write();
                        break;

                    case StreamReadyState::Close:
                        $this->close();
                        break;
                }
            }
        } catch (Throwable $e) {
            $this->close();
            $this->logger->emergency(get_class($e).": ".$e->getMessage());
        }
        exit();
    }

    private function updateState(): void
    {
        $stateChanges = $this->state->getStateChanges();
        if (isset($stateChanges["readyState"]) === true) {
            $this->readyState = $stateChanges["readyState"];
        }
        $this->updateCryptoState($this->stream, $this->protocol, $stateChanges);
        if (isset($stateChanges["running"]) === true) {
            $this->running = $stateChanges["running"];
        }
        $this->state->updateState($stateChanges);
    }

    private function read(): void
    {
        if ($this->stream->endOfStream() === true) {
            $this->protocol->endOfInput();
            $this->protocol->updateState($this->state);
            return;
        }

        $this->protocol->pushInput($this->stream->read(self::CHUNK_SIZE));
        $this->protocol->updateState($this->state);
    }

    private function write(): void
    {
        $buffer = $this->protocol->pushOutput();
        $length = strlen($buffer);
        if ($length === 0) {
            $this->protocol->updateState($this->state);
            return;
        }

        $written = $this->stream->write($buffer);
        if ($written !== strlen($buffer)) {
            throw new StreamError("Write error");
        }

        $this->protocol->updateState($this->state);
    }

    protected function close(): void
    {
        if (isset($this->protocol)) {
            $this->protocol->close();
        }
        $this->stream->close();
        $this->running = false;
    }
}
