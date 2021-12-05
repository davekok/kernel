<?php

declare(strict_types=1);

namespace davekok\kernel;

class ReadableWritableLocalFile implements Actionable, LocalFile, Readable, Writable
{
    use ActionableTrait;
    use LocalFileTrait;
    use ReadableTrait;
    use WritableTrait;

    public function __construct(
        public readonly Activity $activity,
        public readonly Url      $url,
        public readonly mixed    $handle,
    ) {
        stream_set_blocking($this->handle, false);
        stream_set_chunk_size($this->handle, Kernel::CHUNK_SIZE);
        stream_set_read_buffer($this->handle, 0);
        stream_set_write_buffer($this->handle, 0);
    }
}
