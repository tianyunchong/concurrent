<?php
namespace Icicle\Tests\Concurrent\Threading;

use Icicle\Concurrent\Sync\Semaphore as SyncSemaphore;
use Icicle\Concurrent\Threading\{Semaphore, Thread};
use Icicle\Coroutine;
use Icicle\Loop;
use Icicle\Tests\Concurrent\Sync\AbstractSemaphoreTest;

/**
 * @group threading
 * @requires extension pthreads
 */
class SemaphoreTest extends AbstractSemaphoreTest
{
    public function createSemaphore($locks)
    {
        return new Semaphore($locks);
    }

    public function testAcquireInMultipleThreads()
    {
        Coroutine\create(function () {
            $this->semaphore = $this->createSemaphore(1);

            $thread1 = new Thread(function (SyncSemaphore $semaphore) {
                $lock = yield from $semaphore->acquire();

                usleep(1e5);

                $lock->release();

                return 0;
            }, $this->semaphore);

            $thread2 = new Thread(function (SyncSemaphore $semaphore) {
                $lock = yield from $semaphore->acquire();

                usleep(1e5);

                $lock->release();

                return 1;
            }, $this->semaphore);

            $start = microtime(true);

            $thread1->start();
            $thread2->start();

            yield from $thread1->join();
            yield from $thread2->join();

            $this->assertGreaterThan(1, microtime(true) - $start);
        });

        Loop\run();
    }
}
