<?php

declare(strict_types=1);

namespace DaveKok\Stream;

interface StreamInterface
{
    public function open(string $url, string $mode = null, $context = null): void;

    public function accept(): ?Stream;

    public function read(int $length): string;

    public function readLine(int $length, string $ending = "\n"): string;

    public function readCSV(int $length = 0, string $delimiter = ",", string $enclosure = '"', string $escape = "\\"): array;

    public function readAll(int $maxlength = -1, int $offset = -1): string;

    public function receive(int $length, int $flags = 0, string &$address = null): string;

    public function write(string $text, int $length = null): int;

    public function writeLine(string $text): int;

    public function writeCSV(array $fields, string $delimiter = ",", string $enclosure = '"', string $escape = "\\"): int;

    public function send(string $data, int $flags = 0, string $address = null): string;

    public function truncate(int $size = 0): void;

    /**
     * Let the stream flow, either to the default output stream
     * or to the given stream.
     *
     * @param Stream|null $dest  the destination stream
     * @return int  the number of bytes that flowed.
     */
    public function flow(?Stream $dest = null): int;

    public function close(): void;

    public function shutdown(int $how): void;

    public function setBlocking(bool $block): void;

    public function setChunkSize(int $size): int;

    public function setReadBufferSize(int $size): int;

    public function setWriteBufferSize(int $size): int;

    public function setTimeout(int $timeout, int $microseconds = -1): void;

    public function eof(): bool;

    public function tell(): int;

    public function seek(int $offset, int $whence = SEEK_SET): void;

    public function flush(): void;

    public function lock(int $operation, int &$wouldblock = null): void;

    public function supportsLocking(): bool;

    public function status(): array;

    public function meta(): array;

    public function getName(bool $want_peer = true): string;

    public function isTTY(): bool;

    public function isLocal(): bool;

    public function tls(bool $enable, int $crypto_type = null, ?Stream $reference = null): bool;
}
