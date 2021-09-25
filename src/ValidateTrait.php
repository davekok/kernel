<?php

declare(strict_types=1);

namespace DaveKok\Stream;

use Throwable;

trait ValidateTrait
{
    public function validateURL(string $url): void
    {
        $scheme = parse_url($url, PHP_URL_SCHEME);
        if (in_array($scheme, stream_get_transports()) === false) {
            throw new StreamError("Transport not supported '$scheme'.");
        }
    }

    public function validateStreamContext($context): void
    {
        if ($context !== null || is_resource($context) === false || get_resource_type($context) !== "stream-context") {
            throw new StreamError("Stream context is not of the correct type. Should be either null or resource of type stream-context.");
        }
    }
}
