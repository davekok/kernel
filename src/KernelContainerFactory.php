<?php

declare(strict_types=1);

namespace davekok\kernel;

use davekok\container\ContainerFactory;
use Psr\Logger\LogLevel;

class KernelContainerFactory implements ContainerFactory
{
    private readonly SchemesFactory $schemesFactory;

    public function __construct(
        private readonly Kernel $kernel = new Kernel,
        private string $logUrl = "pipe://stderr",
        private string $logFilterLevel = LogLevel::INFO,
    ) {}

    /**
     * Config setter
     */
    public function set(string $key, string|int|float|bool|null $value): static
    {
        match ($key) {
            "log-url"          => $this->logUrl         = $value,
            "log-filter-level" => $this->logFilterLevel = $value,
            default            => throw new NotFoundException($key),
        };
        return $this;
    }

    /**
     * Config getter
     */
    public function get(string $key): string|int|float|bool|null
    {
        return match ($key) {
            "log-url"          => $this->logUrl,
            "log-filter-level" => $this->logFilterLevel,
            default            => throw new NotFoundException($key),
        };
    }

    /**
     * Config info
     */
    public function info(string $key): array
    {
        return [
            "log-url"          => "The URL of the log stream, defaults to 'pipe://stderr'.",
            "log-filter-level" => "The log filter level, defaults to 'info'.",
        ];
    }

    /**
     * Create the container with the current configuration.
     */
    public function createContainer(): KernelContainer
    {
        $this->wireKernel();
        $schemes = $this->getSchemesFactory()->createSchemes();
        $logger  = $this->createLogger($schemes);
        return new KernelContainer($this->kernel, $schemes, $logger);
    }

    public function getSchemesFactory(): SchemesFactory
    {
        return $this->schemesFactory ??= new SchemesFactory($this->kernel);
    }

    private function wireKernel()
    {
        pcntl_signal(SIGINT , $this->kernel->quit(...));
        pcntl_signal(SIGQUIT, $this->kernel->quit(...));
        pcntl_signal(SIGTERM, $this->kernel->quit(...));
    }

    private function createLogger(Schemes $schemes): Logger
    {
        $logger = new Logger(
            actionable:  $schemes->createUrl($this->logUrl)->open($this->kernel->createActivity()),
            filterLevel: $this->logFilterLevel,
        );

        // report all errors that can't be handled through set_error_handler
        error_reporting(E_ERROR|E_PARSE|E_CORE_ERROR|E_CORE_WARNING|E_COMPILE_ERROR|E_COMPILE_WARNING|E_STRICT);
        // set an error handler for all errors that can be handled
        // TODO: tweak based on filter level.
        set_error_handler($logger->errorHandler(...),
                E_WARNING|E_NOTICE|E_USER_ERROR|E_USER_WARNING|E_USER_NOTICE|E_RECOVERABLE_ERROR|E_DEPRECATED);

        return $logger;
    }
}
