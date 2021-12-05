<?php

declare(strict_types=1);

namespace davekok\kernel;

class Crypter implements Action
{
    public function __construct(
        private readonly Activity $activity,
        private readonly mixed $handle,
        private readonly bool $cryptoEnable,
        private readonly int $cryptoType,
        private readonly callable $setCryptoEnabled,
    ) {}

    public function execute(): void
    {
        try {
            stream_set_blocking($this->handle, true);
            $ret = match(true) {
                $this->cryptoType !== null => stream_socket_enable_crypto($this->handle, $this->cryptoEnable, $this->cryptoType),
                default => stream_socket_enable_crypto($this->handle, $this->cryptoEnable),
            };
            stream_set_blocking($this->handle, false);

            if ($ret === true) {
                $this->setCryptoEnabled($this->cryptoEnable);
                return;
            }

            $id = get_resource_id($this->handle);

            if ($ret === false) {
                throw new KernelException("Negotiation failed for stream '{$id}'.");
            }

            throw new KernelException("Not enough data please try again for stream '{$id}'.");
        } catch (Throwable $throwable) {
            $this->activity->logger()->error($throwable);
        } finally {
            $this->activity->next();
        }
    }
}
