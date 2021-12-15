<?php

declare(strict_types=1);

namespace davekok\kernel;

interface Actionable
{
    public function activity(): Activity;
    public function url(): Url;
}
