<?php

declare(strict_types=1);

namespace ReactParallel\Tests\Tests;

use ReactParallel\Contracts\PoolInterface;
use ReactParallel\EventLoop\EventLoopBridge;
use ReactParallel\Tests\AbstractPoolTest;

final class AbstractPoolTestTest extends AbstractPoolTest
{
    protected function createPool(): PoolInterface
    {
        return new ImmidiatePool(new EventLoopBridge());
    }
}
