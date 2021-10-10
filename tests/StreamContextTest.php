<?php

declare(strict_types=1);

namespace DaveKok\Stream\Tests;

use DaveKok\Stream\StreamContext;
use DaveKok\Stream\StreamContext\CryptoOptions;
use DaveKok\Stream\StreamContext\Options;
use DaveKok\Stream\StreamContext\SocketOptions;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \DaveKok\Stream\StreamContext
 * @uses \DaveKok\Stream\StreamContext\CryptoOptions
 * @uses \DaveKok\Stream\StreamContext\Options
 * @uses \DaveKok\Stream\StreamContext\SocketOptions
 */
class StreamContextTest extends TestCase
{
    /**
     * @covers ::__construct
     * @covers ::createStreamContext
     * @covers ::getOptions
     */
    public function testCreateStreamContext(): void
    {
        $context = StreamContext::createStreamContext(new Options(crypto: new CryptoOptions(peerName: "davekok.nl")));
        $options = $context->getOptions();
        static::assertSame("davekok.nl", $options->crypto->peerName);
    }

    /**
     * @covers ::__construct
     * @covers ::createStreamContext
     * @covers ::getOptions
     * @covers ::setOptions
     */
    public function testSetOptions(): void
    {
        $context = StreamContext::createStreamContext(new Options(crypto: new CryptoOptions(peerName: "davekok.nl")));
        $context->setOptions(new Options(crypto: new CryptoOptions(peerName: "davekok.example"), socket: new SocketOptions(backLog: 128)));
        $options = $context->getOptions();
        static::assertSame("davekok.example", $options->crypto->peerName);
        static::assertSame(128, $options->socket->backLog);
    }
}
