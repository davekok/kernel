<?php

declare(strict_types=1);

namespace davekok\kernel;

use Throwable;

class Write implements WritableAction
{
    public function __construct(
        private readonly Actionable $actionable,
        private readonly Writer $writer,
        private readonly mixed $selector,
    ) {
        $this->actionable instanceof Writable ?: throw new KernelException("Actionable does not implement Writable interface.");
        is_resource($this->selector) ?: throw new KernelException("Expected selector property to be a resource.");
    }

    public function writableSelector(): mixed
    {
        return $this->selector;
    }

    public function execute(): void
    {
        try {
            $activity  = $this->actionable->activity();
            $buffer    = $this->actionable->writeBuffer();
            $lastChunk = $writer->write($buffer);
            $chunk     = $buffer->getChunk();
            $length    = strlen($chunk);

            if ($length === 0) {
                throw new KernelException("Output requested but no output.");
            }

            $written = $stream->writeChunk($chunk, $length);

            // if write is complete, move on to next action
            if ($buffer->written($written) === true && $lastChunk === true) {
                $activity->next();
            }
        } catch (Throwable $throwable) {
            $activity->throw($throwable);
        }
    }
}
