<?php

declare(strict_types=1);

namespace davekok\kernel;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
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

    public function __construct(
        private readonly Actionable $actionable,
        private readonly string $filterLevel,
    ) {}

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
