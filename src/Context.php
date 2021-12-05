<?php

declare(strict_types=1);

namespace davekok\kernel;

use davekok\kernel\context\Options;

interface Context
{
    public function getOptions(): Options;
    public function setOptions(Options $options): void;
}
