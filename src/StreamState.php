<?php

declare(strict_types=1);

namespace DaveKok\Stream;

/**
 * Façade for this package.
 */
class StreamState
{
    public string $buffer = "";
    private StreamReadyState $readyState = StreamReadyState::NotReady;
    private bool $cryptoState = false;

    public function __construct(
        private readonly StreamKernelInterface $kernel,
        private readonly $stream,
    ) {}

    public function setReadyState(StreamReadyState $readyState): void
    {
        $this->readyState = $this->kernel->updateReadyState($this->stream, $readyState);
    }

    public function getReadyState(): StreamReadyState
    {
        return $this->readyState;
    }

    public function setCryptoState(bool $enable, int|null $cryptoType = null): void
    {
        $this->cryptoEnabled = $this->kernel->updateCryptoState($this->stream, $enable, $cryptoType);
    }

    public function getCryptoState(): bool
    {
        return $this->cryptoState;
    }

    public function getLocalName(): string
    {
        return $this->kernel->getLocalName($this->stream);
    }

    public function getRemoteName(): string
    {
        return $this->kernel->getRemoteName($this->stream);
    }

    public function quitApplication(): void
    {
        $this->kernel->quit();
    }
}
