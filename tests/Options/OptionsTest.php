<?php

declare(strict_types=1);

namespace DaveKok\Stream\Tests\Options;

use DaveKok\Stream\StreamContext\Options;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \DaveKok\Stream\StreamContext\Options
 * @covers ::__construct
 * @covers \DaveKok\Stream\StreamContext\CryptoOptions::__construct
 * @covers \DaveKok\Stream\StreamContext\SocketOptions::__construct
 */
class OptionsTest extends TestCase
{
    private const DATASET1 = [
        "socket" => [
            "backlog" => 128,
        ],
        "ssl" => [
            "peer_name" => "davekok.nl",
            "verify_peer" => false,
        ],
    ];

    private const DATASET2 = [
        "ssl" => [
            "peer_name" => "davekok.nl",
            "verify_peer" => false,
        ],
        "unknown_wrapper" => [
            "somekey" => "value",
        ],
    ];

    /**
     * @covers ::createFromArray
     */
    public function testCreateFromArray(): void
    {
        $options = Options::createFromArray(self::DATASET1);
        static::assertSame("davekok.nl", $options->crypto->peerName);
        static::assertSame(false, $options->crypto->verifyPeer);
        static::assertSame(128, $options->socket->backLog);
    }

    /**
     * @covers ::createFromArray
     */
    public function testCreateFromArrayWithUnknownWrapper(): void
    {
        $options = Options::createFromArray(self::DATASET2);
        static::assertSame("davekok.nl", $options->crypto->peerName);
        static::assertSame(false, $options->crypto->verifyPeer);
        static::assertNull($options->socket);
    }

    /**
     * @covers ::createFromArray
     * @covers ::toArray
     */
    public function testToArray(): void
    {
        static::assertSame(self::DATASET1, Options::createFromArray(self::DATASET1)->toArray());
        $expected = self::DATASET2;
        unset($expected["unknown_wrapper"]);
        static::assertSame($expected, Options::createFromArray(self::DATASET2)->toArray());
    }
}
