<?php

declare(strict_types=1);

namespace davekok\stream;

use davekok\stream\context\Options;
use davekok\stream\context\SocketOptions;
use davekok\stream\context\CryptoOptions;

class StreamContext
{
    public function __construct(
        public readonly mixed $handle = null
    ) {}

    public function setOptions(Options $options): void
    {
        foreach ($options->toArray() as $wrapper => $wrapperOptions) {
            foreach ($wrapperOptions as $key => $value) {
                stream_context_set_option($this->handle, $wrapper, $key, $value);
            }
        }
    }

    public function getOptions(): Options
    {
        return Options::createFromArray(stream_context_get_options($this->handle));
    }
}
