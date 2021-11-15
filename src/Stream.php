<?php

declare(strict_types=1);

namespace davekok\stream;

use davekok\stream\context\Options;

interface Stream
{
    public function getId(): int;
    public function getUrl(): Url;
    public function getOptions(): Options;
    public function setOptions(Options $options): void;
}
