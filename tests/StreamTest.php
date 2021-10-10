<?php

declare(strict_types=1);

namespace DaveKok\Stream\Tests;

use DaveKok\Stream\Stream;
use DaveKok\Stream\StreamContext;
use DaveKok\Stream\StreamContext\CryptoOptions;
use DaveKok\Stream\StreamContext\Options;
use DaveKok\Stream\StreamContext\SocketOptions;
use PHPUnit\Framework\TestCase;
use org\bovigo\vfs\vfsStream;

/**
 * @coversDefaultClass \DaveKok\Stream\Stream
 * @uses \DaveKok\Stream\StreamContext
 * @uses \DaveKok\Stream\StreamContext\Options
 * @uses \DaveKok\Stream\StreamContext\SocketOptions
 * @covers ::__construct
 * @covers ::createStream
 * @covers ::__destruct
 */
class StreamTest extends TestCase
{
    /**
     * @covers ::getId
     * @covers ::read
     * @covers ::endOfStream
     */
    public function testRead(): void
    {
        vfsStream::setup("root", structure: ["test" => __FUNCTION__]);
        $stream = Stream::createStream(vfsStream::url("root/test"), "r");
        static::assertIsInt($stream->getId());
        static::assertSame(__FUNCTION__, $stream->read());
        static::assertTrue($stream->endOfStream());
    }

    /**
     * @covers ::write
     */
    public function testWrite(): void
    {
        vfsStream::setup("root");
        Stream::createStream(vfsStream::url("root/test"), "w")->write(__FUNCTION__);
        static::assertSame(__FUNCTION__, file_get_contents(vfsStream::url("root/test")));
    }
}
