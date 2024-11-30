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

namespace Tobento\App\Testing\Logging;

use Closure;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;

final class TestLogger extends AbstractLogger
{
    /**
     * @var array<array-key, LogEntry>
     */
    private array $logs = [];
    
    /**
     * Create a new TestLogger.
     *
     * @param string $loggerName
     */
    public function __construct(
        private string $loggerName
    ) {}
    
    /**
     * Logs with an arbitrary level.
     *
     * @param mixed  $level
     * @param string|\Stringable $message
     * @param array  $context
     *
     * @return void
     *
     * @throws \Psr\Log\InvalidArgumentException
     */
    public function log($level, string|\Stringable $message, array $context = []): void
    {
        $this->logs[] = new LogEntry($level, $message, $context, $this->loggerName);
    }
    
    /**
     * @psalm-suppress TooManyArguments
     */
    private function filterLogs(Closure $callback): array
    {
        return array_filter($this->logs, static function (LogEntry $log) use ($callback) {
            return $callback($log);
        });
    }
    
    public function assertLogged(Closure $callback, null|string $message = null): static
    {
        $logs = $this->filterLogs($callback);

        TestCase::assertTrue(
            count($logs) > 0,
            $message ?? sprintf('Expected log was not created in the [%s] logger.', $this->loggerName)
        );

        return $this;
    }
    
    public function assertNotLogged(Closure $callback, null|string $message = null): static
    {
        TestCase::assertCount(
            0,
            $this->filterLogs($callback),
            $message ?? sprintf('Unexpected log was created in the [%s] logger.', $this->loggerName)
        );
        
        return $this;
    }
    
    public function assertNothingLogged(null|string $message = null): static
    {
        TestCase::assertCount(
            0,
            $this->logs,
            $message ?? sprintf(
                'Expected [0] logs to be created instead of [%d] in the [%s] logger.',
                count($this->logs),
                $this->loggerName
            )
        );
        
        return $this;
    }

    public function assertLoggedTimes(Closure $callback, int $times = 1, null|string $message = null): static
    {
        $logs = $this->filterLogs($callback);
        
        TestCase::assertCount(
            $times,
            $logs,
            $message ?? sprintf(
                'Expected log was created %d times instead of %d times in the [%s] logger.',
                count($logs),
                $times,
                $this->loggerName
            )
        );

        return $this;
    }
}