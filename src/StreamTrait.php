<?php

declare(strict_types=1);

namespace davekok\stream;

use davekok\stream\context\Options;

trait StreamTrait
{
    public function __construct(
        public readonly Url $url,
        public readonly mixed $handle,
    ) {}

    public function __destruct()
    {
        fclose($this->handle);
    }

    public function getId(): int
    {
        return get_resource_id($this->handle);
    }

    public function getUrl(): Url
    {
        return $this->url;
    }

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
