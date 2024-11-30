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

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Tobento\App\AppInterface;
use Tobento\App\Logging\LazyLoggers;
use Tobento\App\Logging\LoggersInterface;
use Tobento\App\Testing\FakerInterface;

final class FakeLogging implements FakerInterface
{
    /**
     * Create a new FakeLogging.
     *
     * @param AppInterface $app
     */
    public function __construct(
        private AppInterface $app,
    ) {
        $app->on(
            LoggersInterface::class,
            function(LoggersInterface $loggers, ContainerInterface $container): LoggersInterface {
                
                $fakeLoggers = [];

                foreach($loggers->names() as $name) {
                    $fakeLoggers[$name] = $this->createLogger($name);
                }
                
                return new LazyLoggers(
                    container: $container,
                    loggers: $fakeLoggers,
                    aliases: $loggers->aliases(),
                );
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
     * Returns the loggers.
     *
     * @return LoggersInterface
     */
    public function loggers(): LoggersInterface
    {
        return $this->app->get(LoggersInterface::class);
    }
    
    /**
     * Returns the logger.
     *
     * @param null|string $name
     * @return LoggerInterface
     */
    public function logger(null|string $name = null): LoggerInterface
    {
        return $this->app->get(LoggersInterface::class)->logger($name);
    }
    
    /**
     * Create a new logger.
     *
     * @param string $name
     * @return LoggerInterface
     */
    private function createLogger(string $name): LoggerInterface
    {
        return new TestLogger(loggerName: $name);
    }
}