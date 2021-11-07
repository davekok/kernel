<?php

declare(strict_types=1);

namespace davekok\stream;

class StreamInfo
{
    public function __construct(
        public readonly Url $url,           // the Url of the stream this activity came from
        public readonly Url $localUrl,      // the local Url
        public readonly Url $remoteUrl,     // the remote Url
        public bool $cryptoEnabled = false, // whether crypto is enabled
    ) {}
}
