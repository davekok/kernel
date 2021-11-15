<?php

declare(strict_types=1);

namespace davekok\stream;

use Stringable;
use Throwable;

class StreamKernelLogAction
{
    public function __construct(
        public readonly LogLevel $level,
        public readonly string|Stringable $message,
    ) {}

    public function __toString(): string
    {
        return date("[Y-m-d H-i-s] ") . "{$this->level->label()}: " . match (true) {
            $this->message instanceof Throwable => "{$this->message->getMessage()}\n"
                . "## {$this->message->getFile()}({$this->message->getLine()}): " . get_class($this->message) . "\n"
                . $this->message->getTraceAsString(),
            default => $this->message,
        } . "\n";
    }
}
