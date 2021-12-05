<?php

declare(strict_types=1);

namespace davekok\kernel;

use Psr\Logger\LoggerInterface;

class Activity implements Actionable
{
    private Action|null $loop    = null;
    private Action|null $current = null;
    private array       $actions = [];

    public function __construct(
        public readonly int $id,
        public readonly Kernel $kernel,
        public readonly LoggerInterface $logger,
    ) {}

    public function activity(): Activity
    {
        return $this;
    }

    public function logger(): LoggerInterface
    {
        return $this->logger;
    }

    public function url(): Url
    {
        return new Url("kernel:/activity/{$this->id}");
    }

    public function fork(): Activity
    {
        $id      = $this->id + 1;
        $actions = new Activity($id, $this->kernel, $this->logger);
        $this->kernel->start($id, $actions);
        return $actions;
    }

    public function suspend(): self
    {
        $this->kernel->suspend($this->id);

        return $this;
    }

    public function resume(): self
    {
        $this->kernel->resume($this->id);

        return $this;
    }

    public function stop(): void
    {
        $this->kernel->stop($this->id);
    }

    public function loop(): self
    {
        $this->loop = $this->current;

        return $this;
    }

    public function valid(): bool
    {
        return $this->current !== null;
    }

    public function current(): Action
    {
        return $this->current;
    }

    public function next(): self
    {
        if (count($this->actions) === 0) {
            $this->current = $this->loop;
            return;
        }

        $this->current = array_shift($this->actions);

        return $this;
    }

    public function push(Action $action): self
    {
        if ($this->current === null) {
            $this->current = $action;
            return;
        }

        $this->actions[] = $action;

        return $this;
    }

    public function clear(): self
    {
        $this->loop    = null;
        $this->current = null;
        $this->actions = [];

        return $this;
    }

    public function open(Url $url, OpenMode $openMode): LocalFile
    {
        $url->isLocalFileUrl() ?: throw new KernelException("Not a valid local file url: $url");
        return new (match ($openMode) {
            OpenMode::READ_ONLY => ReadableLocalFile::class
            OpenMode::READ_WRITE,
            OpenMode::STRICT_READ_WRITE,
            OpenMode::TRUNCATE_READ_WRITE,
            OpenMode::CREATE_READ_WRITE,
            OpenMode::READ_APPEND => ReadableWritableLocalFile::class,
            OpenMode::WRITE_ONLY,
            OpenMode::APPEND_ONLY,
            OpenMode::TRUNCATE_WRITE_ONLY,
            OpenMode::CREATE_WRITE_ONLY => WritableLocalFile::class,
            default => throw new KernelException("Invalid open mode."),
        })(
            $this,
            $url,
            fopen($url->path, $openMode->value) ?: throw new KernelException("Unable to open file: {$url->path}")
        );
    }

    public function connect(
        Url $url,
        float|null $timeout,
        int $flags = STREAM_CLIENT_CONNECT,
        Options|array|null $options = null,
    ): ActiveSocket
    {
        $url->isSocketUrl() ?: throw new KernelException("Not a valid socket url: $url")
        return new ActiveSocket(
            $this,
            $url,
            stream_socket_client(
                remote_socket: (string)$url,
                errno:         $errno,
                errstr:        $errstr,
                timeout:       $timeout ?? ini_get("default_socket_timeout"),
                flags:         $flags,
                context:       Options::createContext($options)
            ) ?: throw new KernelException($errstr, $errno),
        );
    }

    public function listen(
        Acceptor $acceptor,
        Url $url,
        int $flags = STREAM_SERVER_BIND | STREAM_SERVER_LISTEN,
        Options|array|null $options = null
    ): PassiveSocket
    {
        $url->isSocketUrl() ?: throw new KernelException("Not a valid socket url: $url");
        return (new PassiveSocket(
            $this->fork(),
            $url,
            stream_socket_server((string)$url, $errno, $errstr, $flags, Options::createContext($options))
                ?: throw new KernelException($errstr, $errno),
        ))->listen($acceptor);
    }
}
