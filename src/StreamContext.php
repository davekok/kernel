<?php

declare(strict_types=1);

namespace DaveKok\Stream;

use DaveKok\Stream\StreamContext\Options;
use DaveKok\Stream\StreamContext\SocketOptions;
use DaveKok\Stream\StreamContext\CryptoOptions;

class StreamContext
{
    protected function __construct(
        protected mixed $handle = null
    ) {}

    public static function createStreamContext(Options $options): self
    {
        return new self(stream_context_create($options->toArray()));
    }

    public function setOptions(Options $options): void
    {
        foreach ($options->toArray() as $wrapper => $wrapperOptions) {
            foreach ($wrapperOptions as $key => $value) {
                if ($value !== null) {
                    stream_context_set_option($wrapper, $key, $value);
                }
            }
        }
    }

    public function getOptions(): Options
    {
        return Options::createFromArray(stream_context_get_options($this->handle));
    }
}
