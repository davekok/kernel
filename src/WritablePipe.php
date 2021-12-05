<?php

declare(strict_types=1);

namespace davekok\kernel;

class WritablePipe implements Actionable, Writable
{
    use ActionableTrait;
    use WritableTrait;
}
