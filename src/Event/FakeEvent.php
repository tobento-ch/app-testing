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

namespace Tobento\App\Testing\Event;

use Tobento\App\AppInterface;
use Tobento\Service\Event\EventsInterface;

final class FakeEvent
{
    /**
     * Create a new FakeQueue.
     *
     * @param AppInterface $app
     */
    public function __construct(
        private AppInterface $app,
    ) {}

    /**
     * Returns the test events.
     *
     * @return TestEvents
     */
    public function events(): TestEvents
    {
        $testEvents = new TestEvents();

        $this->app->on(
            EventsInterface::class,
            function(EventsInterface $events) use ($testEvents): EventsInterface {
                return $testEvents->setEvents(events: $events);
            }
        )->priority(-1500);
        
        return $testEvents;
    }
}