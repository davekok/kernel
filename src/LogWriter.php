<?php

declare(strict_types=1);

namespace davekok\kernel;

use Stringable;
use Throwable;

class LogWriter implements Writer
{
    private readonly string $message;
    private int $offset = 0;

    public function __construct(
        string $level,
        string|Stringable $message,
    ) {
        $this->message = date("[Y-m-d H-i-s] ") . "{$level}: " . match (true) {
            $message instanceof Throwable => "{$message->getMessage()}\n"
                . "## {$message->getFile()}({$message->getLine()}): " . get_class($message) . "\n"
                . $message->getTraceAsString(),
            default => $message,
        } . "\n";
    }

    public function write(WriteBuffer $buffer): bool
    {
        return $buffer->addChunk($this->offset, $this->message);
    }
}
