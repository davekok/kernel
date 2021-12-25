<?php

declare(strict_types=1);

namespace davekok\kernel;

class ReadableWritableFile implements Actionable, File, Readable, Writable, Closable
{
    use ActionableTrait;
    use FileTrait;
    use ReadableTrait;
    use WritableTrait;
    use ClosableTrait;

    public function __construct(
        public  readonly Url         $url,
        public  readonly mixed       $handle,
        public  readonly Activity    $activity,
        private readonly ReadBuffer  $readBuffer  = new ReadBuffer,
        private readonly WriteBuffer $writeBuffer = new WriteBuffer,
    ) {}
}
