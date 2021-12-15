<?php

declare(strict_types=1);

namespace davekok\kernel;

use Psr\Container\NotFoundExceptionInterface;

class NotFoundException extends KernelException implements NotFoundExceptionInterface {}
