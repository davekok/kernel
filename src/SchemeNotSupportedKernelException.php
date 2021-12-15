<?php

declare(strict_types=1);

namespace davekok\kernel;

use Psr\Container\NotFoundExceptionInterface;

class SchemeNotSupportedKernelException extends KernelException implements NotFoundExceptionInterface {}
