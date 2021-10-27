<?php

declare(strict_types=1);

namespace davekok\stream\tests;

use davekok\stream\context;
use davekok\stream\context\CryptoOptions;
use davekok\stream\context\Options;
use davekok\stream\context\SocketOptions;
use davekok\stream\Stream;
use davekok\stream\StreamFactory;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \davekok\stream\FileStream
 * @covers \davekok\stream\Stream::__destruct
 * @uses \davekok\stream\StreamFactory
 * @uses \davekok\stream\StreamContext
 * @uses \davekok\stream\context\Options
 * @uses \davekok\stream\context\SocketOptions
 */
class FileStreamTest extends TestCase
{
    /**
     * @covers \davekok\stream\Stream::getId
     * @covers ::read
     * @covers ::endOfStream
     */
    public function testRead(): void
    {
        vfsStream::setup("root", structure: ["test" => __FUNCTION__]);
        $stream = (new StreamFactory())->createFileStream(vfsStream::url("root/test"), "r");
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
        (new StreamFactory())->createFileStream(vfsStream::url("root/test"), "w")->write(__FUNCTION__);
        static::assertSame(__FUNCTION__, file_get_contents(vfsStream::url("root/test")));
    }
}
