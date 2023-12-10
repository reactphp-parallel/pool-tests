<?php

declare(strict_types=1);

namespace ReactParallel\Tests;

use Closure;
use Money\Money;
use React\EventLoop\Loop;
use ReactParallel\Contracts\ClosedException;
use ReactParallel\Contracts\PoolInterface;
use ReactParallel\EventLoop\KilledRuntime;
use WyriHaximus\AsyncTestUtilities\AsyncTestCase;

use function range;
use function sleep;

abstract class AbstractPoolTest extends AsyncTestCase
{
    /** @return iterable<mixed> */
    final public function provideCallablesAndTheirExpectedResults(): iterable
    {
        $mathFunc = static function (int ...$ints): int {
            $result = 0;

            foreach ($ints as $int) {
                $result += $int;
            }

            return $result;
        };

        yield 'math' => [
            $mathFunc,
            [
                1,
                2,
                3,
            ],
            6,
        ];

        $moneySameCurrentcyFunc = static fn (Money $euro, Money $usd): bool => $euro->isSameCurrency($usd);

        yield 'money-same-currency' => [
            $moneySameCurrentcyFunc,
            [
                Money::EUR(512),
                Money::USD(512),
            ],
            false,
        ];

        $moneyAddFunc = static function (Money ...$euros): int {
            $total = Money::EUR(0);

            foreach ($euros as $euro) {
                $total = $total->add($euro);
            }

            return (int) $total->getAmount();
        };

        yield 'money-add' => [
            $moneyAddFunc,
            [
                Money::EUR(512),
                Money::EUR(512),
            ],
            1024,
        ];

        $sleepFunc = static function (): bool {
            sleep(1);

            return true;
        };

        yield 'sleep' => [
            $sleepFunc,
            [],
            true,
        ];
    }

    /**
     * @param (Closure():T) $callable
     * @param mixed[]       $args
     *
     * @template T
     * @dataProvider provideCallablesAndTheirExpectedResults
     * @test
     */
    final public function fullRunThrough(Closure $callable, array $args, mixed $expectedResult): void
    {
        $pool = $this->createPool();

        try {
            $result = $pool->run($callable, $args);
        } finally {
            $pool->close();
        }

        self::assertSame($expectedResult, $result);
    }

    /**
     * @param (Closure():T) $callable
     * @param mixed[]       $args
     *
     * @template T
     * @dataProvider provideCallablesAndTheirExpectedResults
     * @test
     */
    final public function fullRunThroughMultipleConsecutiveCalls(Closure $callable, array $args, mixed $expectedResult): void
    {
        $pool = $this->createPool();

        try {
            $results = [];
            foreach (range(0, 8) as $i) {
                $results[$i] = $pool->run($callable, $args);
            }
        } finally {
            $pool->close();
        }

        foreach ($results as $result) {
            self::assertSame($expectedResult, $result);
        }
    }

    /**
     * @param (Closure():T) $callable
     * @param mixed[]       $args
     *
     * @template T
     * @dataProvider provideCallablesAndTheirExpectedResults
     * @test
     */
    final public function closedPoolShouldNotRunClosures(Closure $callable, array $args, mixed $expectedResult): void
    {
        self::expectException(ClosedException::class);

        $pool = $this->createPool();
        self::assertTrue($pool->close());

        $pool->run($callable, $args);
    }

    /** @test */
    final public function killingPoolWhileRunningClosuresShouldNotYieldValidResult(): void
    {
        self::expectException(KilledRuntime::class);

        $pool = $this->createPool();

        Loop::futureTick(static function () use ($pool): void {
            $pool->kill();
        });

        /** @phpstan-ignore-next-line */
        self::assertSame(123, $pool->run(static function (): int {
            sleep(1);

            return 123;
        }));
    }

    abstract protected function createPool(): PoolInterface;
}
