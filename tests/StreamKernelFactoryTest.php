<?php

declare(strict_types=1);

namespace davekok\stream\tests;

use davekok\stream\StreamClientKernel;
use davekok\stream\StreamKernelFactory;
use davekok\stream\StreamServerKernel;
use davekok\stream\TimeOutInterface;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \davekok\stream\StreamKernelFactory
 * @uses \davekok\stream\StreamClientKernel
 * @uses \davekok\stream\StreamServerKernel
 */
class StreamKernelFactoryTest extends TestCase
{
    /**
     * @covers ::createClientKernel
     */
    public function testCreateClient(): void
    {
        $factory = new StreamKernelFactory();
        static::assertEquals(new StreamClientKernel(), $factory->createClientKernel());
    }

    /**
     * @covers ::createServerKernel
     */
    public function testCreateServer(): void
    {
        $factory = new StreamKernelFactory();
        static::assertEquals(new StreamServerKernel(), $factory->createServerKernel());
    }

    /**
     * @covers ::createServerKernel
     */
    public function testCreateServerWithTimeout(): void
    {
        $timeout = $this->createMock(TimeOutInterface::class);
        $factory = new StreamKernelFactory();
        static::assertEquals(new StreamServerKernel($timeout), $factory->createServerKernel($timeout));
    }
}
