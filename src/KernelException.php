<?php

declare(strict_types=1);

namespace davekok\kernel;

use Psr\Container\ContainerExceptionInterface;
use Exception;

class KernelException extends Exception implements ContainerExceptionInterface {}
