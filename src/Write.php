<?php

declare(strict_types=1);

namespace davekok\kernel;

use Throwable;

class Write implements Action
{
    public function __construct(
        private readonly Actionable $actionable,
        private readonly Writer $writer,
    ) {
        if ($this->actionable instanceof Writable === false) {
            throw new KernelException("Not a writable actionable.");
        }
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
                $activity->logger->notice("Output requested but no output.");
                $activity->next();
                return;
            }

            $written = $stream->writeChunk($chunk, $length);

            // if write is complete, move on to next action
            if ($buffer->written($written) === true && $lastChunk === true) {
                $activity->next();
            }
        } catch (Throwable $throwable) {
            $activity->logger->error($throwable);
            $activity->clear();
        }
    }
}
