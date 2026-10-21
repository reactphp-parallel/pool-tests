<?php

declare(strict_types=1);

namespace ReactParallel\Tests\Tests;

use PHPUnit\Framework\Attributes\Test;
use ReactParallel\Contracts\PoolInterface;
use ReactParallel\EventLoop\EventLoopBridge;
use ReactParallel\Tests\AbstractPoolTest;

final class AbstractPoolTestTest extends AbstractPoolTest
{
    protected function createPool(): PoolInterface
    {
        return new ImmidiatePool(new EventLoopBridge());
    }

    #[Test]
    public function provideCallablesAndTheirExpectedResultsAssert(): void
    {
        foreach (self::provideCallablesAndTheirExpectedResults() as $key => $values) {
            /** @phpstan-ignore staticMethod.alreadyNarrowedType */
            self::assertIsString($key);

            [$callable, $params, $expectedResult] = $values;
            /** @phpstan-ignore staticMethod.alreadyNarrowedType */
            self::assertIsCallable($callable);
            self::assertSame($expectedResult, $callable(...$params));
        }
    }
}
