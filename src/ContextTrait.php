<?php

declare(strict_types=1);

namespace davekok\kernel;

use davekok\kernel\context\Options;

trait ContextTrait
{
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
