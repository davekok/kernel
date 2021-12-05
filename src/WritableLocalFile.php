<?php

declare(strict_types=1);

namespace davekok\kernel;

class WritableLocalFile implements Actionable, LocalFile, Writable
{
    use ActionableTrait;
    use LocalFileTrait;
    use WritableTrait;

    public function __construct(
        public readonly Activity $activity,
        public readonly Url      $url,
        public readonly mixed    $handle,
    ) {
        stream_set_blocking($this->handle, false);
        stream_set_chunk_size($this->handle, Kernel::CHUNK_SIZE);
        stream_set_write_buffer($this->handle, 0);
    }
}
