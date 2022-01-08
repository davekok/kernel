<?php

declare(strict_types=1);

namespace davekok\kernel\wiring;

use davekok\wiring\NoSuchParameterWiringException;
use davekok\wiring\NoSuchServiceWiringException;
use davekok\wiring\NoSuchSetupServiceWiringException;
use davekok\wiring\Runnable;
use davekok\wiring\Wireable;
use davekok\wiring\Wiring;
use davekok\wiring\Wirings;
use Psr\Log\LogLevel;

class KernelWiring implements Wiring
{
    private readonly Kernel $kernel;
    private readonly Logger $logger;
    private readonly Schemes $schemes;
    private readonly UrlFactory $urlFactory;
    private readonly Wirings $wirings;
    private string $logUrl;
    private string $logFilterLevel;

    public function setWirings(Wirings $wirings): void
    {
        $this->wirings = $wirings;
    }

    public function infoParameters(): array
    {
        return [
            "log-url"          => "The URL of the log stream.",
            "log-filter-level" => "The log filter level.",
        ];
    }

    public function setParameter(string $key, array|string|int|float|bool|null $value): void
    {
        match ($key) {
            "log-url"          => $this->logUrl         = $value,
            "log-filter-level" => $this->logFilterLevel = $value,
            default            => throw new NoSuchParameterWiringException($key),
        };
    }

    public function getParameter(string $key): array|string|int|float|bool|null
    {
        return match ($key) {
            "log-url"          => $this->logUrl         ??= "pipe://stderr",
            "log-filter-level" => $this->logFilterLevel ??= LogLevel::INFO,
            default            => throw new NoSuchParameterWiringException($key),
        };
    }

    public function prewire(): void
    {
    }

    public function wireable(string $wireable): Wireable
    {
        return match ($wireable) {
            "schemes" => $this->schemes ??= new Schemes([
                "activity" => new ActivityUrlFactoryWireable($this),
                "file"     => new FileUrlFactoryWireable(),
                "pipe"     => new PipeUrlFactoryWireable(),
                "tcp"      => new SocketUrlFactoryWireable(),
            ]),
            default => throw new NoSuchWireableWiringException($key),
        };
    }

    public function wire(): void
    {
    }

    public function service(string $key): object
    {
        return match ($key) {
            "logger"      => $this->logger     ??= $this->createLogger(),
            "url-factory" => $this->urlFactory ??= $this->wireable("schemes")->wire(),
            default       => throw new NoSuchServiceWiringException($key),
        };
    }

    public function listRunnables(): array
    {
        return ["kernel"];
    }

    public function helpRunnable(string $runnable): string
    {
        return match($runnable) {
            "kernel" => "Runnable for managing activities.",
            default  => throw new NoSuchRunnableWiringException($runnable),
        };
    }

    public function runnable(string $runnable, array $args): Runnable
    {
        return match ($runnable) {
            "kernel" => $this->kernel ??= $this->createKernel(),
            default  => throw new NoSuchRunnableWiringException($runnable),
        };
    }

    private function createKernel(): Kernel
    {
        $kernel = new Kernel;
        pcntl_signal(SIGINT , $kernel->quit(...));
        pcntl_signal(SIGQUIT, $kernel->quit(...));
        pcntl_signal(SIGTERM, $kernel->quit(...));
        return $kernel;
    }

    private function createLogger(): Logger
    {
        $logger = new Logger(
            actionable:  $this->wireable("schemes")->wire()->createUrl($this->logUrl)->open($this->runnable("kernel")->createActivity()),
            filterLevel: $this->getParameter("log-filter-level"),
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
