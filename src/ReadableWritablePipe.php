<?php

declare(strict_types=1);

namespace davekok\kernel;

class ReadableWritablePipe implements Actionable, Readable, Writable
{
    use ActionableTrait;
    use ReadableTrait;
    use WritableTrait;

    public function __construct(
        public  readonly Url         $url,
        public  readonly mixed       $handle,
        public  readonly Activity    $activity,
        private readonly ReadBuffer  $readBuffer  = new ReadBuffer,
        private readonly WriteBuffer $writeBuffer = new WriteBuffer,
    ) {}
}
