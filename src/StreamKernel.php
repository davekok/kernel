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
    private array $activities  = [];
    private array $factories   = [];
    private array $readyRead   = [];
    private array $readyWrite  = [];
    private array $selectRead  = [];
    private array $selectWrite = [];

    public function __construct(
        private readonly LoggerInterface $log,
        private readonly TimeOut|null $timeout = null
    )
    {}

    /**
     * Add a stream to stream kernel
     */
    public function addStream(Stream $stream, ControllerFactory $factory): self
    {
        if ($stream instanceof ActiveSocketStream === true) {
            try {
                $stream->setBlocking(false);
                $stream->setChunkSize(self::CHUNK_SIZE);

                $activity              = new StreamKernelActivity();
                $id                    = $stream->getId();
                $this->streams[$id]    = $stream;
                $this->activities[$id] = $activity;
                $this->readyRead[$id]  = $stream->handle;
                $activity->setController($factory->createController($activity));
                $this->next($stream);
            } catch (Throwable $e) {
                $this->log->error($e);
                $this->close($stream);
            }
        } else if ($stream instanceof PassiveSocketStream === true) {
            try {
                $stream->setBlocking(false);

                $id                   = $stream->getId();
                $this->streams[$id]   = $stream;
                $this->factories[$id] = $factory;
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
     * Tell the kernel to stop quit on next pass.
     */
    public function quit(): void
    {
        $this->running = false;
    }

    public function run(): void
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

    private function next(ActiveSocketStream $stream): void
    {
        $id       = $stream->getId();
        $activity = $this->activities[$id];

        $action = $activity->next();

        if ($action instanceof Reader) {
            $this->readyRead[$id] = $stream->handle;
            unset($this->readyWrite[$id]);
        } else if ($action instanceof Writer) {
            unset($this->readyRead[$id]);
            $this->readyWrite[$id] = $stream->handle;
        } else {
            unset($this->readyRead[$id]);
            unset($this->readyWrite[$id]);
        }

        if ($action instanceof StreamKernelCryptoAction) {
            try {
                $stream->setBlocking($stream, true);
                $stream->enableCrypto($action->cryptoStateEnable, $action->cryptoStateType);
                $stream->setBlocking($stream, false);
            } catch (Throwable $e) {
                $this->log->error($e);
            }
            $this->next($stream);
            return;
        }

        if ($action === null) {
            $this->close($stream);
        }

        if (is_callable($action)) {
            $this->log->error("Current action is an arbitrary action. Can't execute closing stream.");
            $this->close($stream);
        }
    }

    private function read(Stream $stream): void
    {
        if ($stream instanceof PassiveSocketStream) {
            $this->accept($stream);
            return;
        }

        $activity = $this->activities[$stream->getId()];
        $reader   = $activity->current();
        $buffer   = $activity->getReaderBuffer();

        if ($stream->endOfStream() === true) {
            $reader->read($buffer->end());
            $this->next($stream);
            return;
        }

        $reader->read($buffer->add($stream->read(self::CHUNK_SIZE)));

        $this->next($stream);
    }

    private function accept(PassiveSocketStream $passiveStream): void
    {
        try {
            $activeStream = $passiveStream->accept();
            $activeStream->setBlocking(false);
            $activeStream->setChunkSize(self::CHUNK_SIZE);
            $this->log->info("accepting connnection from " . $activeStream->getRemoteName());

            $activity              = new StreamKernelActivity();
            $id                    = $activeStream->getId();
            $this->streams[$id]    = $activeStream;
            $this->activities[$id] = $activity;
            $activity->setController($this->factories[$passiveStream->getId()]->createController($activity));
            $this->next($activeStream);
        } catch (Throwable $e) {
            $this->log->error($e);
            $this->close($activeStream);
        }
    }

    private function write(ActiveSocketStream $stream): void
    {
        $activity = $this->activities[$stream->getId()];
        $writer   = $activity->current();
        $buffer   = $activity->getWriterBuffer();

        // check
        if ($buffer->valid() === false) {
            $writer->write($buffer);
            if ($buffer->valid() === false) {
                $this->log->debug("Output requested but no output.");
                $this->next($stream);
                return;
            }
        }

        $chunk  = $buffer->getChunk(self::CHUNK_SIZE);
        $length = strlen($chunk);
        if ($length === 0) {
            $this->log->debug("Output requested but no output.");
            $this->next($stream);
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
            $this->next($stream);
        }
    }

    private function close(Stream $stream): void
    {
        if ($stream instanceof ActiveSocketStream) {
            $this->log->info("closing connection to ".$stream->getRemoteName());
        }
        $id = $stream->getId();
        if (isset($this->activities[$id])) {
            $this->activities[$id]->clear();
        }
        unset($this->activities[$id]);
        unset($this->streams[$id]);
        unset($this->readyRead[$id]);
        unset($this->readyWrite[$id]);
    }
}
