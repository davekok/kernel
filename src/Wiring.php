<?php

declare(strict_types=1);

namespace davekok\kernel;

use davekok\system\NoSuchParameterWiringException;
use davekok\system\NoSuchServiceWiringException;
use davekok\system\NoSuchSetupServiceWiringException;
use davekok\system\Runnable;
use davekok\system\WiringInterface;
use davekok\system\Wirings;
use Psr\Logger\LogLevel;

class Wiring implements WiringInterface
{
    private readonly Schemes $schemes;
    private readonly Kernel $kernel;
    private readonly Logger $logger;
    private readonly UrlFactory $urlFactory;
    private string $logUrl;
    private string $logFilterLevel;

    public function infoParameters(string $key): array
    {
        return [
            "log-url"          => "The URL of the log stream, defaults to 'pipe://stderr'.",
            "log-filter-level" => "The log filter level, defaults to 'info'.",
        ];
    }

    public function setParameter(string $key, string|int|float|bool|null $value): void
    {
        match ($key) {
            "log-url"          => $this->logUrl         = $value,
            "log-filter-level" => $this->logFilterLevel = $value,
            default            => throw new NoSuchParameterWiringException($key),
        };
    }

    public function getParameter(string $key): string|int|float|bool|null
    {
        return match ($key) {
            "log-url"          => $this->logUrl,
            "log-filter-level" => $this->logFilterLevel,
            default            => throw new NoSuchParameterWiringException($key),
        };
    }

    public function setup(Wirings $wirings): void
    {
        $this->kernel         = new Kernel;
        $this->schemes        = new Schemes($this->kernel);
        $this->logUrl         = "pipe://stderr";
        $this->logFilterLevel = LogLevel::INFO;
    }

    public function setupService(string $key): object
    {
        return match ($key) {
            "schemes" => $this->schemes,
            default   => throw new NoSuchSetupServiceWiringException($key),
        };
    }

    public function wire(Wirings $wirings): Runnable|null
    {
        pcntl_signal(SIGINT , $this->kernel->quit(...));
        pcntl_signal(SIGQUIT, $this->kernel->quit(...));
        pcntl_signal(SIGTERM, $this->kernel->quit(...));

        $this->urlFactory = new MainUrlFactory($this->schemes->getSchemes());

        $this->logger = new Logger(
            actionable:  $this->urlFactory->createUrl($this->logUrl)->open($this->kernel->createActivity()),
            filterLevel: $this->logFilterLevel,
        );

        // report all errors that can't be handled through set_error_handler
        error_reporting(E_ERROR|E_PARSE|E_CORE_ERROR|E_CORE_WARNING|E_COMPILE_ERROR|E_COMPILE_WARNING|E_STRICT);
        // set an error handler for all errors that can be handled
        // TODO: tweak based on filter level.
        set_error_handler($this->logger->errorHandler(...),
                E_WARNING|E_NOTICE|E_USER_ERROR|E_USER_WARNING|E_USER_NOTICE|E_RECOVERABLE_ERROR|E_DEPRECATED);

        return $this->kernel;
    }

    public function service(string $key): object
    {
        return match ($key) {
            "kernel"      => $this->kernel,
            "logger"      => $this->logger,
            "url-factory" => $this->urlFactory,
            default       => throw new NoSuchServiceWiringException($key),
        };
    }
}
