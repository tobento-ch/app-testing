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

use PHPUnit\Framework\TestCase;
use Tobento\Service\Queue\QueueInterface;
use Tobento\Service\Queue\JobInterface;
use Tobento\Service\Queue\JobProcessorInterface;
use Closure;

final class TestQueue implements QueueInterface
{
    /**
     * @var array<array-key, array<array-key, JobInterface>>
     */
    private array $jobs = [];
    
    /**
     * @var array
     */
    private array $jobsByName = [];
    
    /**
     * Create a new TestQueue.
     *
     * @param string $name
     * @param JobProcessorInterface $jobProcessor
     * @param int $priority
     */
    public function __construct(
        private string $name,
        private JobProcessorInterface $jobProcessor,
        private int $priority = 100,
    ) {}
    
    /**
     * Returns the queue name.
     *
     * @return string
     */
    public function name(): string
    {
        return $this->name;
    }
    
    /**
     * Returns the queue priority.
     *
     * @return int
     */
    public function priority(): int
    {
        return $this->priority;
    }
    
    /**
     * Push a new job onto the queue.
     *
     * @param JobInterface $job
     * @return string The job id
     */
    public function push(JobInterface $job): string
    {
        $job = $this->jobProcessor->processPushingJob($job, $this);
        
        $this->jobs[$job->getName()][$job->getId()] = $job;
        
        return $job->getId();
    }
    
    /**
     * Pop the next job off of the queue.
     *
     * @return null|JobInterface
     * @throws \Throwable
     */
    public function pop(): null|JobInterface
    {
        return null;
    }
    
    /**
     * Returns the job or null if not found.
     *
     * @param string $id The job id.
     * @return null|JobInterface
     */
    public function getJob(string $id): null|JobInterface
    {
        foreach($this->jobs as $jobs) {
            if (isset($jobs[$id])) {
                return $jobs[$id];
            }
        }
        
        return null;
    }
    
    /**
     * Returns all jobs.
     *
     * @return iterable<array-key, JobInterface>
     */
    public function getAllJobs(): iterable
    {
        return array_merge(...$this->jobs);
    }
    
    /**
     * Returns the number of jobs in queue.
     *
     * @return int
     */
    public function size(): int
    {
        return count(array_merge(...$this->jobs));
    }
    
    /**
     * Deletes all jobs from the queue.
     *
     * @return bool True if the queue was successfully cleared. False if there was an error.
     */
    public function clear(): bool
    {
        $this->jobs = [];
        
        return true;
    }
    
    /**
     * @psalm-suppress TooManyArguments
     */
    private function filterJobs(string $name, null|Closure $callback = null): array
    {
        $jobs = $this->jobs[$name] ?? [];

        $callback = $callback ?: static fn(): bool => true;

        return array_filter($jobs, static function (JobInterface $job) use ($callback) {
            return $callback($job);
        });
    }
    
    public function assertPushed(string $name, Closure $callback = null): static
    {
        $jobs = $this->filterJobs($name, $callback);

        TestCase::assertTrue(
            count($jobs) > 0,
            sprintf('The expected job [%s] was not pushed.', $name)
        );

        return $this;
    }
    
    public function assertNotPushed(string $name, null|Closure $callback = null): static
    {
        TestCase::assertCount(
            0,
            $this->filterJobs($name, $callback),
            sprintf('The unexpected job [%s] was pushed.', $name)
        );
        
        return $this;
    }
    
    public function assertNothingPushed(): static
    {
        $jobs = implode(', ', array_keys($this->jobs));

        TestCase::assertCount(
            0,
            $this->jobs,
            sprintf('The following jobs were pushed unexpectedly: %s', $jobs)
        );
        
        return $this;
    }

    public function assertPushedTimes(string $name, int $times = 1): static
    {
        $jobs = $this->filterJobs($name);

        TestCase::assertCount(
            $times,
            $jobs,
            sprintf(
                'The expected job [%s] was sent %d times instead of %d times.',
                $name,
                count($jobs),
                $times
            )
        );

        return $this;
    }
}