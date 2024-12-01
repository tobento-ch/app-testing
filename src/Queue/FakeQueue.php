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

use Tobento\App\Testing\FakerInterface;
use Tobento\App\AppInterface;
use Tobento\Service\Console\ConsoleInterface;
use Tobento\Service\Queue\Console\ClearCommand;
use Tobento\Service\Queue\JobInterface;
use Tobento\Service\Queue\JobProcessorInterface;
use Tobento\Service\Queue\QueueInterface;
use Tobento\Service\Queue\Queues;
use Tobento\Service\Queue\QueuesInterface;

final class FakeQueue implements FakerInterface
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
     * Returns a new instance.
     *
     * @param AppInterface $app
     * @return static
     */
    public function new(AppInterface $app): static
    {
        return new static($app);
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
    public function queue(string $name): QueueInterface
    {
        return $this->app->get(QueuesInterface::class)->queue($name);
    }
    
    /**
     * Runs the given jobs.
     *
     * @param iterable<JobInterface> $jobs
     * @return array<array-key, JobInterface> The processed jobs.
     */
    public function runJobs(iterable $jobs): array
    {
        $jobProcessor = $this->app->get(JobProcessorInterface::class);
        $processed = [];
        
        foreach($jobs as $job) {
            $jobProcessor->processJob($job);
            $processed[] = $jobProcessor->afterProcessJob($job);
        }
        
        return $processed;
    }
    
    /**
     * Clear the given queue.
     *
     * @param QueueInterface $queue
     * @return bool True on success, otherwise false.
     */
    public function clearQueue(QueueInterface $queue): bool
    {
        $console = $this->app->get(ConsoleInterface::class);

        $executed = $console->execute(
            command: ClearCommand::class,
            input: ['--queue' => [$queue->name()]],
        );
        
        return $executed->code() === 0 ? true : false;
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