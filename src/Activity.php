<?php

declare(strict_types=1);

namespace davekok\kernel;

use Psr\Logger\LoggerInterface;
use Throwable;

class Activity extends Url implements Actionable
{
    private array         $actions = [];
    private Action|null   $loop    = null;
    private Action|null   $current = null;
    private mixed         $catcher = null;

    public function __construct(
        private readonly Kernel $kernel,
        int $id,
    ) {
        parent::__construct(scheme: "activity", fragment: $id);
    }

    public function activity(): Activity
    {
        return $this;
    }

    public function url(): Url
    {
        return $this;
    }

    public function fork(): Activity
    {
        return $this->kernel->createActivity();
    }

    public function suspend(): self
    {
        $this->kernel->suspend($this);

        return $this;
    }

    public function resume(): self
    {
        $this->kernel->resume($this);

        return $this;
    }

    public function stop(): void
    {
        $this->kernel->stop($this);
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
            return $this;
        }

        $this->current = array_shift($this->actions);

        return $this;
    }

    public function push(Action $action): self
    {
        if ($this->current === null) {
            $this->current = $action;
            $this->kernel->resume($this);
            return $this;
        }

        $this->actions[] = $action;

        return $this;
    }

    public function clear(): self
    {
        $this->loop    = null;
        $this->current = null;
        $this->catcher = null;
        $this->actions = [];

        return $this;
    }

    public function catch(callable $catcher): self
    {
        $this->catcher = $catcher;
    }

    public function throw(Throwable $throwable): void
    {
        if (isset($this->catcher) === false) {
            return;
        }

        $this->catcher($throwable);
    }
}
