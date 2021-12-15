<?php

declare(strict_types=1);

namespace davekok\kernel;

class ReadablePipe implements Actionable, Readable
{
    use ActionableTrait;
    use ReadableTrait;

    public function __construct(
        public  readonly Url        $url,
        public  readonly Activity   $activity,
        public  readonly mixed      $handle,
        private readonly ReadBuffer $readBuffer = new ReadBuffer,
    ) {}
}
