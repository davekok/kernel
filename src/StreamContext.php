<?php

declare(strict_types=1);

namespace DaveKok\Stream;

class StreamContext
{
    protected function __construct(
        protected readonly mixed $handle
    ) {}

    public static function getDefaultContext(): self
    {
        return new self(stream_context_get_default());
    }

    public static function createContext(): self
    {
        return new self(stream_context_create());
    }

    public function setOption(string $wrapper, string $option, mixed $value): void
    {
        if (stream_context_set_option($this->handle, $wrapper, $option, $value) === false) {
            throw new StreamError("Failed to set option $wrapper::$option = '$value'");
        }
    }

    public function getOptions(): array
    {
        return stream_context_get_options($this->handle);
    }

    public function setParameters(array $parameters): void
    {
        if (stream_context_set_params($this->handle, $parameters) === false) {
            throw new StreamError("Failed to set option parameters");
        }
    }

    public function getParameters(): array
    {
        return stream_context_get_params($this->handle);
    }
}
