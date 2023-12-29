<?php

/**
 * TOBENTO
 *
 * @copyright   Tobias Strub, TOBENTO
 * @license     MIT License, see LICENSE file distributed with this source code.
 * @author      Tobias Strub
 * @link        https://www.tobento.ch
 */

declare(strict_types=1);

namespace Tobento\App\Testing\Queue;

use Tobento\App\AppInterface;
use Tobento\Service\Queue\QueuesInterface;
use Tobento\Service\Queue\QueueInterface;
use Tobento\Service\Queue\JobProcessorInterface;
use Tobento\Service\Queue\Queues;

final class FakeQueue
{
    /**
     * Create a new FakeQueue.
     *
     * @param AppInterface $app
     */
    public function __construct(
        private AppInterface $app,
    ) {
        $app->on(
            QueuesInterface::class,
            function(QueuesInterface $queues): QueuesInterface {
                
                $fakeQueues = [];

                foreach($queues->names() as $name) {
                    $fakeQueues[] = $this->createQueue($name);
                }
                
                return new Queues(...$fakeQueues);
            }
        );
    }

    /**
     * Returns the queues.
     *
     * @return QueuesInterface
     */
    public function queues(): QueuesInterface
    {
        return $this->app->get(QueuesInterface::class);
    }
    
    /**
     * Returns the queue.
     *
     * @param string $name
     * @return QueueInterface
     */
    public function queue(string $name = null): QueueInterface
    {
        return $this->app->get(QueuesInterface::class)->queue($name);
    }
    
    /**
     * Create a new queue.
     *
     * @param string $name
     * @return QueueInterface
     */
    private function createQueue(string $name): QueueInterface
    {
        return new TestQueue(
            name: $name,
            jobProcessor: $this->app->get(JobProcessorInterface::class),
        );
    }
}