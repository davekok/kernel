<?php

declare(strict_types=1);

namespace davekok\kernel;

class ReadableLocalFile implements Actionable, LocalFile, Readable
{
    use ActionableTrait;
    use LocalFileTrait;
    use ReadableTrait;

    public function __construct(
        public  readonly Activity    $activity,
        public  readonly Url         $url,
        public  readonly mixed       $handle,
        private readonly ReadBuffer  $readBuffer  = new ReadBuffer,
    ) {
        stream_set_blocking($this->handle, false);
        stream_set_chunk_size($this->handle, Kernel::CHUNK_SIZE);
        stream_set_read_buffer($this->handle, 0);
    }
}
