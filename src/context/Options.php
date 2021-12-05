<?php

declare(strict_types=1);

namespace davekok\kernel\context;

use ReflectionClass;

class Options
{
    public function __construct(
        public SocketOptions|null $socket = null,
        public CryptoOptions|null $crypto = null
    )
    {}

    public static function createContext(Options|array|null $context): mixed
    {
        return match (true) {
            $context instanceof Options => stream_context_create($context->toArray()),
            is_array($context) => stream_context_create($context),
            is_null($context) => stream_context_get_default(),
        };
    }

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
