<?php

declare(strict_types=1);

namespace davekok\stream;

class Connection
{
    private ScanBuffer $scanBuffer;
    private array $scannerStack;
    private Scanner|null $currentScanner;
    private array $formatterStack;
    private Formatter|null $currentFormatter;
    private array $closers;
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

    public function pushScanner(Scanner $scanner): void
    {
        if ($this->currentScanner !== null) {
            $this->scannerStack[] = $this->currentScanner;
        }
        $this->currentScanner = $scanner;
    }

    public function popScanner(): void
    {
        $this->currentScanner = array_pop($this->scannerStack);
    }

    public function getScanner(): Scanner
    {
        return $this->currentScanner;
    }

    public function pushFormatter(Formatter $formatter): void
    {
        if ($this->currentFormatter !== null) {
            $this->formatterStack[] = $this->currentFormatter;
        }
        $this->currentFormatter = $formatter;
    }

    public function popFormatter(): void
    {
        $this->currentFormatter = array_pop($this->formatterStack);
    }

    public function getFormatter(): Formatter
    {
        return $this->currentFormatter;
    }

    public function addCloser(Closer $closer): void
    {
        $this->closers[] = $closer;
    }

    public function removeCloser(CloseEventListener $closer): void
    {
        $key = array_search($closer, $this->closers, true);
        if ($key === false) {
            return;
        }
        unset($this->closers[$key]);
    }

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


// internal package functions

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

    public function scan(string $input): void
    {
        if ($this->currentScanner === null) {
            throw new StreamError("No current scanner.");
        }
        $this->currentScanner->scan($this->scanBuffer->add($input));
    }

    public function endOfInput(): void
    {
        $this->currentScanner->endOfInput($this->scanBuffer);
    }

    public function format(): string
    {
        if ($this->currentFormatter === null) {
            throw new StreamError("No current formatter.");
        }
        return $this->currentFormatter->format();
    }

    public function close(): void
    {
        $this->nextReadyState = ReadyState::CLOSE;
        $this->scannerStack = [];
        $this->formatterStack = [];
        $this->currentScanner = null;
        $this->currentFormatter = null;
        foreach ($this->closers as $closer) {
            $closer->close();
        }
        $this->closers = [];
    }
}
