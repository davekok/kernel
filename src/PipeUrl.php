<?php

declare(strict_types=1);

namespace davekok\kernel;

/**
 * Represents a named pipe. Use open to open it.
 */
class PipeUrl extends Url
{
    public function open(Activity $activity): ReadablePipe|WritablePipe|ReadableWritablePipe
    {
        return match ($this->host) {
            "stdin"  => new ReadablePipe($this, $activity, $this->openPipe(OpenMode::READ_ONLY)),
            "stdout" => new WritablePipe($this, $activity, $this->openPipe(OpenMode::APPEND_ONLY)),
            "stderr" => new WritablePipe($this, $activity, $this->openPipe(OpenMode::APPEND_ONLY)),
            "memory" => new ReadableWritablePipe($this, $activity, $this->openPipe(OpenMode::STRICT_READ_WRITE)),
            "temp"   => new ReadableWritablePipe($this, $activity, $this->openPipe(OpenMode::STRICT_READ_WRITE)),
            default  => throw new NotFoundException("Url not found: $this");
        };
    }

    private function openPipe(OpenMode $mode)
    {
        $handle = fopen("php://{$this->host}{$this->path}", $mode->value) ?: throw new KernelException("Failed to open: $this");
        stream_set_blocking($handle, false);
        stream_set_chunk_size($handle, Kernel::CHUNK_SIZE);
        return $handle;
    }
}
