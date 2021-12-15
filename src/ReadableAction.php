<?php

declare(strict_types=1);

namespace davekok\kernel;

interface ReadableAction extends Action
{
    public function readableSelector(): mixed /*resource of type stream*/;
}
