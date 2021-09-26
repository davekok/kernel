<?php

declare(strict_types=1);

namespace DaveKok\Stream;

use DaveKok\Stream\StreamContext\Options;

class StreamContext
{
    protected function __construct(
        protected readonly mixed $handle
    ) {}

    public static function getDefaultContext(): self
    {
        return new self(stream_context_get_default());
    }

    public static function createContext(Options ...$arrayOfOptions): self
    {
        $array = [];
        foreach ($arrayOfOptions as $options) {
            $this->buildArray($options, $array);
        }
        return new self(stream_context_create($array));
    }

    private function buildArray(Options $options, array &$array): void
    {
        $wrapper = $options::WRAPPER;
        $indexNames = $options::INDEX_NAMES;
        foreach ($options as $key => $value) {
            if ($value !== null) {
                $array[$wrapper][$indexNames[$key]] = $value;
            }
        }
    }

    private function buildOptions(Options $options, array &$array): void
    {
        $wrapper = $options::WRAPPER;
        $indexNames = $options::INDEX_NAMES;
        foreach ($options as $key => $value) {
            if ($value !== null) {
                $array[$wrapper][$indexNames[$key]] = $value;
            }
        }
    }
}
