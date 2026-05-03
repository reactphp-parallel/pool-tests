<?php

declare(strict_types=1);

namespace ReactParallel\Tests;

use Closure;
use Money\Money;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
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
    final public static function provideCallablesAndTheirExpectedResults(): iterable
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

        /** @phpstan-ignore argument.type,argument.type,argument.type */
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
            /** @phpstan-ignore wyrihaximus.reactphp.blocking.function.sleep */
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
     */
    #[Test]
    #[DataProvider('provideCallablesAndTheirExpectedResults')]
    final public function fullRunThrough(Closure $callable, array $args, mixed $expectedResult): void
    {
        $pool = $this->createPool();

        try {
            /** @phpstan-ignore method.unresolvableReturnType */
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
     */
    #[Test]
    #[DataProvider('provideCallablesAndTheirExpectedResults')]
    final public function fullRunThroughMultipleConsecutiveCalls(Closure $callable, array $args, mixed $expectedResult): void
    {
        $pool = $this->createPool();

        try {
            $results = [];
            foreach (range(0, 8) as $i) {
                /** @phpstan-ignore method.unresolvableReturnType */
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
     */
    #[Test]
    #[DataProvider('provideCallablesAndTheirExpectedResults')]
    final public function closedPoolShouldNotRunClosures(Closure $callable, array $args, mixed $expectedResult): void
    {
        self::expectException(ClosedException::class);

        $pool = $this->createPool();
        self::assertTrue($pool->close());

        /** @phpstan-ignore method.unresolvableReturnType */
        $pool->run($callable, $args);
    }

    #[Test]
    final public function killingPoolWhileRunningClosuresShouldNotYieldValidResult(): void
    {
        self::expectException(KilledRuntime::class);

        $pool = $this->createPool();

        Loop::futureTick(static function () use ($pool): void {
            $pool->kill();
        });

        /** @phpstan-ignore staticMethod.alreadyNarrowedType */
        self::assertSame(123, $pool->run(static function (): int {
            /** @phpstan-ignore wyrihaximus.reactphp.blocking.function.sleep */
            sleep(1);

            return 123;
        }));
    }

    abstract protected function createPool(): PoolInterface;
}
