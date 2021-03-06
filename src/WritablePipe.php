<?php

declare(strict_types=1);

namespace davekok\kernel;

class WritablePipe implements Actionable, Writable
{
    use ActionableTrait;
    use WritableTrait;

    public function __construct(
        public  readonly Url         $url,
        public  readonly Activity    $activity,
        public  readonly mixed       $handle,
        private readonly WriteBuffer $writeBuffer = new WriteBuffer,
    ) {}
}
