<?php

declare(strict_types=1);

namespace davekok\stream\tests;

use davekok\stream\StreamContext;
use davekok\stream\context\CryptoOptions;
use davekok\stream\context\Options;
use davekok\stream\context\SocketOptions;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \davekok\stream\StreamContext
 * @uses \davekok\stream\context\CryptoOptions
 * @uses \davekok\stream\context\Options
 * @uses \davekok\stream\context\SocketOptions
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
