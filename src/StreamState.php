<?php

declare(strict_types=1);

namespace DaveKok\Stream;

class StreamState
{
    private StreamReadyState $currentReadyState = StreamReadyState::NotReady;
    private StreamReadyState $nextReadyState = StreamReadyState::NotReady;
    private bool $currentCryptoState = false;
    private array $nextCryptoState;
    private bool $currentRunning = false;
    private bool $nextRunning = false;

    public function __construct(
        public readonly string $localName,
        public readonly string $remoteName
    ) {}

    public function setReadyState(StreamReadyState $readyState): void
    {
        $this->nextReadyState = $readyState;
    }

    public function getReadyState(): StreamReadyState
    {
        return $this->currentReadyState;
    }

    public function setCryptoState(bool $enable, int|null $cryptoType = null): void
    {
        $this->nextCryptoState = ["enable" => $enable, "cryptoType" => $cryptoType];
    }

    public function getCryptoState(): bool
    {
        return $this->currentCryptoState;
    }

    /**
     * Set whether the stream kernel should continue running.
     * Effects all streams.
     */
    public function setRunningState(bool $running): void
    {
        $this->nextRunning = $running;
    }

    public function getRunningState(): bool
    {
        return $this->currentRunning;
    }

    /**
     * Called by the stream kernel to get the state changes.
     */
    public function getStateChanges(): array
    {
        return [
            "readyState"  => $this->nextReadyState            !== $this->currentReadyState  ? $this->nextReadyState  : null,
            "cryptoState" => $this->nextCryptoState["enable"] !== $this->currentCryptoState ? $this->nextCryptoState : null,
            "running"     => $this->nextRunning               !== $this->currentRunning     ? $this->nextRunning     : null,
        ];
    }

    /**
     * Called by the stream kernel to commit the new state.
     */
    public function commitState(array $state): void
    {
        if (isset($state["readyState"]) === true) {
            $this->currentReadyState = $state["readyState"];
        }
        if (isset($state["cryptoState"]) === true) {
            $this->currentCryptoState = $state["cryptoState"];
        }
        if (isset($state["running"]) === true) {
            $this->currentRunning = $state["running"];
        }
    }
}
