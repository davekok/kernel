<?php

declare(strict_types=1);

namespace DaveKok\Stream;

use Throwable;

class StreamClientKernel implements StreamKernelInterface
{
    use UpdateCryptoStateTrait;
    use StreamMetaTrait;

    private readonly StreamState $state;
    private readonly StreamReadyState $readyState;
    private readonly ProtocolInterface $protocol;
    private readonly $stream;

    public function __construct(ProtocolFactoryInterface $protocolFactory, $stream): void
    {
        if (stream_set_chunk_size($stream, StreamKernelInterface::CHUCK_SIZE) === false) {
            fclose($stream);
            throw new StreamError("Failed to set chunk size to " . StreamKernelInterface::CHUCK_SIZE . ".");
        }
        try {
            $this->stream   = $stream;
            $this->state    = new StreamState($this, $stream);
            $this->protocol = $protocolFactory->createProtocol($state);
        } catch (Throwable $e) {
            $this->destroyState();
            throw $e;
        }
    }

    public function updateReadyState(mixed $stream, StreamReadyState $readyState): StreamReadyState
    {
        if (get_resource_id($stream) !== get_resource_id($this->stream)) {
            throw new StreamError("Wrong stream");
        }
        $this->readyState = $readyState;

        return $this->readyState;
    }

    public function quit(): void
    {
        $this->running = false;
    }

    public function run(): noreturn
    {
        if ($this->running === true) {
            throw new StreamError("Already running");
        }
        $this->running = true;
        try {
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
                        $this->quit();
                        break;
                }
            }
        } catch (Throwable $e) {
            echo get_class($e) . ": {$e->getMessage()}\n";
        }
        $this->destroyState();
        exit();
    }

    private function destroyState(): void
    {
        if (isset($this->state->protocol)) {
            $this->state->protocol->destroyProtocol();
        }
        fclose($this->stream);
    }

    private function read(): void
    {
        if (feof($this->stream) === true) {
            $this->state->protocol->endOfInput();
            $this->quit();
            return;
        }

        $buffer = fread($this->stream, StreamKernelInterface::CHUNK_SIZE);
        if ($buffer === false) {
            throw new StreamError("Read error");
        }

        $this->state->buffer = $buffer;
        $this->state->protocol->pushInput();
        $this->state->buffer = "";
    }

    private function write(): void
    {
        $this->state->protocol->pushOutput();
        if (strlen($this->state->buffer) === 0) {
            return;
        }

        $written = fwrite($this->stream, $this->state->buffer);
        if ($written === false || strlen($this->state->buffer) !== $written) {
            throw new StreamError("Write error");
        }

        $this->state->buffer = "";
    }
}
