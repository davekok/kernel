<?php

declare(strict_types=1);

namespace davekok\kernel;

class FileUrl extends Url
{
    public function open(Activity $activity, OpenMode $mode): File
    {
        return match ($mode) {
            OpenMode::READ_ONLY => new ReadableFile($this, $activity, $this->openFile($mode)),
            OpenMode::READ_WRITE,
            OpenMode::STRICT_READ_WRITE,
            OpenMode::TRUNCATE_READ_WRITE,
            OpenMode::CREATE_READ_WRITE,
            OpenMode::READ_APPEND => new ReadableWritableFile($this, $activity, $this->openFile($mode)),
            OpenMode::WRITE_ONLY,
            OpenMode::APPEND_ONLY,
            OpenMode::TRUNCATE_WRITE_ONLY,
            OpenMode::CREATE_WRITE_ONLY => new WritableFile($this, $activity, $this->openFile($mode)),
        };
    }

    private function openFile(OpenMode $mode)
    {
        $handle = fopen($this->path, $mode->value) ?: throw new KernelException("Failed to open: {$this}");
        stream_set_blocking($handle, false);
        stream_set_chunk_size($handle, Kernel::CHUNK_SIZE);
        return $handle;
    }
}
