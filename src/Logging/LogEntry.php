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

use Stringable;

final class LogEntry
{
    /**
     * Create a new LogEntry instance.
     *
     * @param mixed $level
     * @param string|Stringable $message
     * @param array<array-key, mixed> $context
     * @param string $loggerName
     */
    public function __construct(
        public mixed $level,
        public string|Stringable $message,
        public array $context,
        public string $loggerName,
    ) {}
}