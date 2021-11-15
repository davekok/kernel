<?php

declare(strict_types=1,ticks=1);

namespace davekok\stream;

use davekok\stream\context\Options;
use Stringable;
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
    private array $logQueue    = [];

    public function __construct(
        private readonly TimeOut|null $timeOut = null,
        private readonly LogLevel $logFilterLevel = LogLevel::INFO,
        private readonly Pipe $logStream = new Pipe(new Url("php://stderr"), STDERR),
        private readonly StreamFactory $streamFactory = new StreamFactory(),
    ) {
        $this->streams[$this->logStream->getId()] = $this->logStream;
    }

    public function addActiveSocketStream(
        ControllerFactory $factory,
        Url $url,
        float|null $timeOut = null,
        int $flags = STREAM_CLIENT_CONNECT,
        Options|array|null $context = null
    ): self
    {
        try {
            $stream = $this->streamFactory->createActiveSocketStream($url, $timeOut, $flags, $context);
            $stream->setBlocking(false);
            $stream->setChunkSize(self::CHUNK_SIZE);

            $streamInfo            = new StreamInfo($stream->url, $stream->getLocalUrl(), $stream->getRemoteUrl());
            $activity              = new StreamKernelActivity($streamInfo, $this->logFilterLevel);
            $id                    = $stream->getId();
            $this->streams[$id]    = $stream;
            $this->activities[$id] = $activity;
            $this->readyRead[$id]  = $stream->handle;
            $activity->setController($factory->createController($activity));
            $this->next($stream);
        } catch (Throwable $e) {
            $this->close($stream);
            throw $e;
        }
        return $this;
    }

    public function addPassiveSocketStream(
        ControllerFactory $factory,
        Url $url,
        int $flags = STREAM_SERVER_BIND | STREAM_SERVER_LISTEN,
        Options|array|null $context = null
    ): self
    {
        try {
            $stream = $this->streamFactory->createPassiveSocketStream($url, $flags, $context);
            $stream->setBlocking(false);

            $id                   = $stream->getId();
            $this->streams[$id]   = $stream;
            $this->factories[$id] = $factory;
            $this->readyRead[$id] = $stream->handle;
        } catch (Throwable $e) {
            $this->close($stream);
            throw $e;
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

    public function run(): noreturn
    {
        if ($this->running === true) {
            throw new StreamError("Already running.");
        }
        $this->running = true;

        // report all errors that can't be handled through set_error_handler
        error_reporting(E_ERROR|E_PARSE|E_CORE_ERROR|E_CORE_WARNING|E_COMPILE_ERROR|E_COMPILE_WARNING|E_STRICT);
        // set an error handler for all errors that can be handled
        set_error_handler($this->errorHandler(...),
                E_WARNING|E_NOTICE|E_USER_ERROR|E_USER_WARNING|E_USER_NOTICE|E_RECOVERABLE_ERROR|E_DEPRECATED);

        // register signals
        pcntl_signal(SIGINT , $this->quit(...));
        pcntl_signal(SIGQUIT, $this->quit(...));
        pcntl_signal(SIGTERM, $this->quit(...));

        $this->info("running ...");
        while ($this->running) {
            if ($this->select() === false) continue;
            foreach ($this->selectRead as $stream) {
                try {
                    $stream = $this->streams[get_resource_id($stream)];
                    $this->read($stream);
                } catch (Throwable $e) {
                    $this->error($e);
                    $this->close($stream);
                }
            }
            foreach ($this->selectWrite as $stream) {
                try {
                    $stream = $this->streams[get_resource_id($stream)];
                    $this->write($stream);
                } catch (Throwable $e) {
                    $this->error($e);
                    $this->close($stream);
                }
            }
        }
        exit();
    }

    private function select(): bool
    {
        $this->selectRead  = [...$this->readyRead];
        $this->selectWrite = [...$this->readyWrite];
        $exceptStreams = [];
        if (count($this->selectRead) === 0 && count($this->selectWrite) === 0) {
            $this->emergency("no streams to select");
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
            try {
                $this->timeOut->timeOut();
            } catch (Throwable $e) {
                $this->error($e);
            }
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

            // if time out is in the future or no time out then return time out
            if ($timeOut > 0 || $timeOut === null) {
                return $timeOut;
            }

            // if time out is now or in the past call the time out function
            try {
                $this->timeOut->timeOut();
            } catch (Throwable $e) {
                $this->error($e);
            }
        }

        $this->error("Too many immediate time outs.");
        return null;
    }

    private function next(ActiveSocketStream $stream): void
    {
        $id       = $stream->getId();
        $activity = $this->activities[$id];
        $action   = $activity->next();

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
                $activity->getInfo()->cryptoEnabled = $action->cryptoStateEnable;
            } catch (Throwable $e) {
                $this->error($e);
            }
            $this->next($stream);
            return;
        }

        if ($action instanceof StreamKernelLogAction) {
            $this->readyWrite[$this->logStream->getId()] = $this->logStream->handle;
            $this->logQueue[] = $stream;
            return;
        }

        if ($action === null) {
            $this->close($stream);
        }

        if (is_callable($action)) {
            $this->error("Current action is an arbitrary action. Can't execute closing stream.");
            $this->close($stream);
        }
    }

    private function read(Stream $stream): bool
    {
        return match (true) {
            $stream instanceof ActiveSocketStream  => $this->readSocket($stream),
            $stream instanceof PassiveSocketStream => $this->acceptSocket($stream),
        };
    }

    private function readSocket(ActiveSocketStream $stream): bool
    {
        $activity = $this->activities[$stream->getId()];
        $reader   = $activity->current();
        $buffer   = $activity->getReaderBuffer();

        if ($stream->endOfStream() === true) {
            $reader->read($buffer->end());
            $this->next($stream);
            return true;
        }

        $reader->read($buffer->add($stream->read(self::CHUNK_SIZE)));

        $this->next($stream);
        return true;
    }

    private function acceptSocket(PassiveSocketStream $passiveStream): bool
    {
        try {
            $activeStream = $passiveStream->accept();
            $activeStream->setBlocking(false);
            $activeStream->setChunkSize(self::CHUNK_SIZE);
            $this->info("accepting connnection from " . $activeStream->getRemoteUrl());

            $streamInfo            = new StreamInfo(
                $passiveStream->url,
                $activeStream->getLocalUrl(),
                $activeStream->getRemoteUrl()
            );
            $activity              = new StreamKernelActivity($streamInfo, $this->logFilterLevel);
            $id                    = $activeStream->getId();
            $this->streams[$id]    = $activeStream;
            $this->activities[$id] = $activity;
            $activity->setController($this->factories[$passiveStream->getId()]->createController($activity));
            $this->next($activeStream);
        } catch (Throwable $e) {
            $this->error($e);
            $this->close($activeStream);
        }
        return true;
    }

    private function write(Stream $stream): bool
    {
        return match (true) {
            $stream instanceof ActiveSocketStream => $this->writeSocket($stream),
            $stream === $this->logStream          => $this->writeNextLog(),
        };
    }

    private function writeSocket(ActiveSocketStream $stream): bool
    {
        $activity = $this->activities[$stream->getId()];
        $writer   = $activity->current();
        $buffer   = $activity->getWriterBuffer();

        // check
        if ($buffer->valid() === false) {
            $writer->write($buffer);
            if ($buffer->valid() === false) {
                $this->debug("Output requested but no output.");
                $this->next($stream);
                return true;
            }
        }

        $chunk  = $buffer->getChunk(self::CHUNK_SIZE);
        $length = strlen($chunk);
        if ($length === 0) {
            $this->debug("Output requested but no output.");
            $this->next($stream);
            return true;
        }

        $written = $stream->write($chunk, $length);
        if ($written === false) {
            $this->error("Write error");
            $this->close($stream);
            return true;
        }

        $buffer->moveMarkBy($written);

        // if nothing left in buffer, update state
        if ($buffer->valid() === false) {
            $this->next($stream);
        }

        return true;
    }

    private function writeNextLog(): bool
    {
        $entry = array_shift($this->logQueue);
        if ($entry instanceof Stream) {
            // if it is a stream, retrieve the log action from the activity
            // and advance the advance the activity to the next action
            $this->logStream->write((string)$this->activities[$entry->getId()]->current());
            $this->next($entry);
        } else if ($entry instanceof StreamKernelLogAction) {
            // if it is a raw log action (should only be created within this class), write it
            $this->logStream->write((string)$entry);
        }
        if (count($this->logQueue) === 0) {
            unset($this->readyWrite[$this->logStream->getId()]);
        }
        return true;
    }

    private function close(Stream $stream): void
    {
        if ($stream instanceof ActiveSocketStream) {
            $this->info("closing connection to ".$stream->getRemoteUrl());
        }
        $id = $stream->getId();
        if (isset($this->activities[$id])) {
            $this->activities[$id]->clear()->setController(null);
        }
        unset($this->activities[$id]);
        unset($this->streams[$id]);
        unset($this->readyRead[$id]);
        unset($this->readyWrite[$id]);
    }

    private function emergency(string|Stringable $message): void
    {
        $this->log(LogLevel::EMERGENCY, $message);
    }

    private function alert(string|Stringable $message): void
    {
        $this->log(LogLevel::ALERT, $message);
    }

    private function critical(string|Stringable $message): void
    {
        $this->log(LogLevel::CRITICAL, $message);
    }

    private function error(string|Stringable $message): void
    {
        $this->log(LogLevel::ERROR, $message);
    }

    private function warning(string|Stringable $message): void
    {
        $this->log(LogLevel::WARNING, $message);
    }

    private function notice(string|Stringable $message): void
    {
        $this->log(LogLevel::NOTICE, $message);
    }

    private function info(string|Stringable $message): void
    {
        $this->log(LogLevel::INFO, $message);
    }

    private function debug(string|Stringable $message): void
    {
        $this->log(LogLevel::DEBUG, $message);
    }

    private function log(LogLevel $level, string|Stringable $message): void
    {
        if ($this->logFilterLevel->filter($level)) {
            $this->readyWrite[$this->logStream->getId()] = $this->logStream->handle;
            $this->logQueue[] = new StreamKernelLogAction($level, $message);
        }
    }

    public function errorHandler(int $errno, string $errstr, string $errfile, int $errline): bool
    {
        switch ($errno) {
            case E_USER_ERROR:
            case E_RECOVERABLE_ERROR:
                $this->error("$errfile($errline): $errstr");
                break;
            case E_USER_WARNING:
                $this->warning("$errfile($errline): $errstr");
                break;
            case E_USER_NOTICE:
            case E_NOTICE:
                $this->notice("$errfile($errline): $errstr");
                break;
            case E_USER_DEPRECATED:
            default:
                $this->debug("$errfile($errline): $errstr");
                break;
        }
        return true;
    }
}
