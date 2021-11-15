<?php

declare(strict_types=1);

namespace davekok\stream;

enum LogLevel: int
{
    case EMERGENCY = 0;
    case ALERT     = 1;
    case CRITICAL  = 2;
    case ERROR     = 3;
    case WARNING   = 4;
    case NOTICE    = 5;
    case INFO      = 6;
    case DEBUG     = 7;

    public function filter(self $other): bool
    {
        return $this->value <= $other->value;
    }

    public function label(): string
    {
        return match ($this) {
            self::EMERGENCY => "emergency",
            self::ALERT     => "alert",
            self::CRITICAL  => "critical",
            self::ERROR     => "error",
            self::WARNING   => "warning",
            self::NOTICE    => "notice",
            self::INFO      => "info",
            self::DEBUG     => "debug",
        };
    }
}
