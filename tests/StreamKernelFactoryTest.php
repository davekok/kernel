<?php

declare(strict_types=1);

namespace DaveKok\Stream\Tests;

use DaveKok\Stream\StreamClientKernel;
use DaveKok\Stream\StreamKernelFactory;
use DaveKok\Stream\StreamServerKernel;
use DaveKok\Stream\TimeOutInterface;
use PHPUnit\Framework\TestCase;

class StreamKernelFactoryTest extends TestCase
{
    public function testCreateClient(): void
    {
        $factory = new StreamKernelFactory();
        static::assertEquals(new StreamClientKernel(), $factory->createClientKernel());
    }

    public function testCreateServer(): void
    {
        $factory = new StreamKernelFactory();
        static::assertEquals(new StreamServerKernel(), $factory->createServerKernel());
    }

    public function testCreateServerWithTimeout(): void
    {
        $timeout = $this->createMock(TimeOutInterface::class);
        $factory = new StreamKernelFactory();
        static::assertEquals(new StreamServerKernel($timeout), $factory->createServerKernel($timeout));
    }
}
