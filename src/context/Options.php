<?php

declare(strict_types=1);

namespace davekok\stream\context;

use ReflectionClass;

class Options
{
    public function __construct(
        public SocketOptions|null $socket = null,
        public CryptoOptions|null $crypto = null
    )
    {}

    public static function createFromArray(array $options): self
    {
        $self       = new self();
        $reflection = new ReflectionClass($self);
        foreach ($reflection->getProperties() as $property) {
            $class = $property->getType()->getName();
            if (isset($options[$class::WRAPPER]) === false)
                continue;

            $object      = new $class();
            $name        = $property->getName();
            $self->$name = $object;
            foreach ($object as $key => $value) {
                $object->$key = $options[$class::WRAPPER][$class::INDEX_NAMES[$key]] ?? null;
            }
        }
        return $self;
    }

    public function toArray(): array
    {
        $options = [];
        foreach ($this as $wrapperOptions) {
            if ($wrapperOptions === null)
                continue;

            foreach ($wrapperOptions as $key => $value) {
                if ($value === null)
                    continue;

                $options[$wrapperOptions::WRAPPER][$wrapperOptions::INDEX_NAMES[$key]] = $value;
            }
        }
        return $options;
    }
}
