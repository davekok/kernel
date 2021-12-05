<?php

declare(strict_types=1);

namespace davekok\kernel;

use Psr\Logger\LoggerInterface;
use Psr\Logger\LogLevel;
use Stringable;

class Logger implements LoggerInterface
{
    private const LEVELS = [
        LogLevel::EMERGENCY => 0,
        LogLevel::ALERT     => 1,
        LogLevel::CRITICAL  => 2,
        LogLevel::ERROR     => 3,
        LogLevel::WARNING   => 4,
        LogLevel::NOTICE    => 5,
        LogLevel::INFO      => 6,
        LogLevel::DEBUG     => 7,
    ];

    private Actionable $actionable;

    public function __construct(
        private readonly string $filterLevel = LogLevel::INFO,
    ) {
        // TODO: optimize with filter level

        // report all errors that can't be handled through set_error_handler
        error_reporting(E_ERROR|E_PARSE|E_CORE_ERROR|E_CORE_WARNING|E_COMPILE_ERROR|E_COMPILE_WARNING|E_STRICT);
        // set an error handler for all errors that can be handled
        set_error_handler($this->errorHandler(...),
                E_WARNING|E_NOTICE|E_USER_ERROR|E_USER_WARNING|E_USER_NOTICE|E_RECOVERABLE_ERROR|E_DEPRECATED);
    }

    public function setActionable(Actionable $actionable): void
    {
        if ($actionable instanceof Writable === false) {
            throw new KernelException("Writable actionable expected.");
        }
        $this->actionable = $actionable;
    }

    public function log($level, string|Stringable $message, array $context = []): void
    {
        if (self::LEVELS[$this->filterLevel] <= self::LEVELS[$other]) {
            $this->actionable->write(new LogWriter($level, $message));
        }
    }

    public function emergency(string|Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::EMERGENCY, $message);
    }

    public function alert(string|Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::ALERT, $message);
    }

    public function critical(string|Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::CRITICAL, $message);
    }

    public function error(string|Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::ERROR, $message);
    }

    public function warning(string|Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::WARNING, $message);
    }

    public function notice(string|Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::NOTICE, $message);
    }

    public function info(string|Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::INFO, $message);
    }

    public function debug(string|Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::DEBUG, $message);
    }

    public function errorHandler(int $errno, string $errstr, string $errfile, int $errline): bool
    {
        switch ($errno) {
            case E_USER_ERROR:
            case E_RECOVERABLE_ERROR:
                $this->error("$errfile($errline): $errstr");
                break;
            case E_USER_WARNING:
                $this->warning("$errfile($errline): $errstr");
                break;
            case E_USER_NOTICE:
            case E_NOTICE:
                $this->notice("$errfile($errline): $errstr");
                break;
            case E_USER_DEPRECATED:
            default:
                $this->debug("$errfile($errline): $errstr");
                break;
        }
        return true;
    }
}
