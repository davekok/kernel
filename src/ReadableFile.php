<?php

declare(strict_types=1);

namespace davekok\kernel;

class ReadableFile implements Actionable, File, Readable
{
    use ActionableTrait;
    use FileTrait;
    use ReadableTrait;

    public function __construct(
        public  readonly Url         $url,
        public  readonly mixed       $handle,
        public  readonly Activity    $activity,
        private readonly ReadBuffer  $readBuffer  = new ReadBuffer,
    ) {}
}
