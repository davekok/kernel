<?php

declare(strict_types=1);

namespace davekok\stream\tests;

use davekok\stream\Stream;
use davekok\stream\context;
use davekok\stream\context\CryptoOptions;
use davekok\stream\context\Options;
use davekok\stream\context\SocketOptions;
use PHPUnit\Framework\TestCase;
use org\bovigo\vfs\vfsStream;

/**
 * @coversDefaultClass \davekok\stream\Stream
 * @uses \davekok\stream\context
 * @uses \davekok\stream\context\Options
 * @uses \davekok\stream\context\SocketOptions
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
