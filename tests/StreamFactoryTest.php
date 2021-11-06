<?php

declare(strict_types=1);

namespace davekok\stream\tests;

use davekok\stream\StreamFactory;
use davekok\stream\StreamKernel;
use davekok\stream\TimeOut;
use davekok\stream\context\Options;
use davekok\stream\context\SocketOptions;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * @coversDefaultClass \davekok\stream\StreamFactory
 * @covers ::__construct
 * @uses \davekok\stream\ActiveSocketStream
 * @uses \davekok\stream\context\Options
 * @uses \davekok\stream\context\SocketOptions
 * @uses \davekok\stream\FileStream
 * @uses \davekok\stream\PassiveSocketStream
 * @uses \davekok\stream\StreamContext
 * @uses \davekok\stream\StreamKernel
 */
class StreamFactoryTest extends TestCase
{
    /**
     * @covers ::createStreamKernel
     */
    public function testCreateKernel(): void
    {
        $factory = new StreamFactory();
        static::assertEquals(new StreamKernel(new NullLogger(), null), $factory->createStreamKernel());
    }

    /**
     * @covers ::createStreamKernel
     */
    public function testCreateKernelWithTimeout(): void
    {
        $timeout = $this->createMock(TimeOut::class);
        $factory = new StreamFactory($timeout);
        static::assertEquals(new StreamKernel(new NullLogger(), $timeout), $factory->createStreamKernel());
    }

    /**
     * @covers ::createStreamContext
     * @covers ::createContext
     */
    public function testCreateContextFromNull(): void
    {
        $factory = new StreamFactory();
        $context = $factory->createStreamContext();
        $actual = $context->handle;
        $expected = stream_context_get_default();
        static::assertEquals($expected, $actual);
    }

    /**
     * @covers ::createStreamContext
     * @covers ::createContext
     */
    public function testCreateContextFromExistingContext(): void
    {
        $factory = new StreamFactory();
        $context1 = $factory->createStreamContext();
        $context2 = $factory->createStreamContext($context1);
        $actual = $context2->handle;
        $expected = stream_context_get_default();
        static::assertEquals($expected, $actual);
    }

    /**
     * @covers ::createStreamContext
     * @covers ::createContext
     */
    public function testCreateContextFromOptions(): void
    {
        $backLog = random_int(10, 50);
        $factory = new StreamFactory();
        $context = $factory->createStreamContext(new Options(socket: new SocketOptions(backLog: $backLog)));
        $options = $context->getOptions();
        static::assertEquals($backLog, $options->socket->backLog);
    }

    /**
     * @covers ::createStreamContext
     * @covers ::createContext
     */
    public function testCreateContextFromArray(): void
    {
        $backLog = random_int(10, 50);
        $factory = new StreamFactory();
        $context = $factory->createStreamContext(["socket"=>["backlog"=>$backLog]]);
        $options = $context->getOptions();
        static::assertEquals($backLog, $options->socket->backLog);
    }
}
