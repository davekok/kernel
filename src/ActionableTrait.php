<?php

declare(strict_types=1);

namespace davekok\kernel;

use Psr\Logger\LoggerInterface;

trait ActionableTrait
{
    public function activity(): Activity
    {
        return $this->activity;
    }

    public function url(): Url
    {
        return $this->url;
    }
}
