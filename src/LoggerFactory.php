<?php

declare(strict_types=1);

namespace davekok\kernel;

use Psr\Logger\LoggerInterface;
use Psr\Logger\LogLevel;
use Stringable;

class LoggerFactory
{
    public function __construct(
        private readonly string $url = "pipe://stderr",
        private readonly string $filterLevel = LogLevel::INFO,
    ) {}

    public function createLogger(UrlFactory $urlFactory, Kernel $kernel): Logger
    {
        $logger = new Logger(
            actionable:  $urlFactory->createUrl($this->url)->open($kernel->createActivity()),
            filterLevel: $this->filterLevel
        );
        // report all errors that can't be handled through set_error_handler
        error_reporting(E_ERROR|E_PARSE|E_CORE_ERROR|E_CORE_WARNING|E_COMPILE_ERROR|E_COMPILE_WARNING|E_STRICT);
        // set an error handler for all errors that can be handled
        set_error_handler($logger->errorHandler(...),
                E_WARNING|E_NOTICE|E_USER_ERROR|E_USER_WARNING|E_USER_NOTICE|E_RECOVERABLE_ERROR|E_DEPRECATED);

        return $logger;
    }
}
