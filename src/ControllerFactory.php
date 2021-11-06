<?php

declare(strict_types=1);

namespace davekok\stream;

interface ControllerFactory
{
    /**
     * Create a new controller for given activity.
     */
    public function createController(Activity $activity): object;
}
