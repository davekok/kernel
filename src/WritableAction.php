<?php

declare(strict_types=1);

namespace davekok\kernel;

interface WritableAction extends Action
{
    public function writableSelector(): mixed /*resource of type stream*/;
}
