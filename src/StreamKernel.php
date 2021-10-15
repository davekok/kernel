<?php

declare(strict_types=1);

namespace davekok\stream;

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerAwareInterface;
use Throwable;

abstract class StreamKernel implements LoggerAwareInterface
{
    public const CHUCK_SIZE = 1400;
    protected readonly LoggerInterface $logger;
    protected bool $running = false;

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    private function updateRunningState(array &$stateChanges): void
    {
        // if no state change then update to current state
        if (isset($stateChanges["running"]) === false) {
            $stateChanges["running"] = $this->running;
            return;
        }
        // running state can only be set to false, once false it remains false
        $stateChanges["running"] = $this->running = ($this->running && $stateChanges["running"]);
    }

    private function updateCryptoState(Stream $stream, array &$stateChanges): void
    {
        try {
            if (isset($stateChanges["cryptoState"]) === false) {
                return;
            }

            ["enable" => $enable, "cryptoType" => $cryptoType] = $stateChanges["cryptoState"];

            $stream->setBlocking($stream, true);
            $stream->enableCrypto($enable, $cryptoType);
            $stream->setBlocking($stream, false);
        } catch (Throwable $e) {
            $stateChanges["cryptoState"] = !$enable;
            throw $e;
        }
    }
}
