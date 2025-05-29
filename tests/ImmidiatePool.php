<?php

declare(strict_types=1);

namespace ReactParallel\Tests\Tests;

use Closure;
use ReactParallel\Contracts\ClosedException;
use ReactParallel\Contracts\PoolInterface;
use ReactParallel\EventLoop\EventLoopBridge;
use ReactParallel\Runtime\Runtime;
use WyriHaximus\PoolInfo\Info;

use function spl_object_id;

final class ImmidiatePool implements PoolInterface
{
    private int $activeThreads = 0;
    private bool $closed       = false;
    /** @var array<Runtime> */
    private array $runtimes = [];

    public function __construct(private readonly EventLoopBridge $eventLoopBridge)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function info(): iterable
    {
        yield Info::TOTAL => $this->activeThreads;
        yield Info::BUSY => $this->activeThreads;
        yield Info::CALLS => 0;
        yield Info::IDLE  => 0;
        yield Info::SIZE  => $this->activeThreads;
    }

    /**
     * {@inheritDoc}
     */
    public function run(Closure $callable, array $args = []): mixed
    {
        if ($this->closed) {
            throw ClosedException::create();
        }

        $runtime                                 = Runtime::create($this->eventLoopBridge);
        $this->runtimes[spl_object_id($runtime)] = $runtime;
        $this->activeThreads++;
        try {
            /** @phpstan-ignore return.type */
            return $runtime->run($callable, $args);
        } finally {
            unset($this->runtimes[spl_object_id($runtime)]);
            $this->activeThreads--;
        }
    }

    public function close(): bool
    {
        $this->closed = true;

        foreach ($this->runtimes as $runtime) {
            $runtime->close();
        }

        return true;
    }

    public function kill(): bool
    {
        $this->closed = true;

        foreach ($this->runtimes as $runtime) {
            $runtime->kill();
        }

        return true;
    }
}
