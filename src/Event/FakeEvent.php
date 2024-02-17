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

use Tobento\App\Testing\FakerInterface;
use Tobento\App\AppInterface;
use Tobento\Service\Event\EventsInterface;

final class FakeEvent implements FakerInterface
{
    protected null|TestEvents $testEvents = null;
    
    /**
     * Create a new FakeQueue.
     *
     * @param AppInterface $app
     */
    public function __construct(
        private AppInterface $app,
    ) {
        /*$app->on(
            EventsInterface::class,
            function(EventsInterface $events): EventsInterface {
                if ($this->testEvents) {
                    return $this->testEvents->setEvents(events: $events);
                }
                return $events;
            }
        )->priority(-1500);*/
    }
    
    /**
     * Returns a new instance.
     *
     * @param AppInterface $app
     * @return static
     */
    public function new(AppInterface $app): static
    {
        $fakeEvent = new static($app);
        $fakeEvent->events();
        return $fakeEvent;
    }

    /**
     * Returns the test events.
     *
     * @return TestEvents
     */
    public function events(): TestEvents
    {
        if (is_null($this->testEvents)) {
            $this->testEvents = new TestEvents();
        }

        $this->app->on(
            EventsInterface::class,
            function(EventsInterface $events): EventsInterface {
                return $this->testEvents->setEvents(events: $events);
            }
        )->priority(-1500);
        
        return $this->testEvents;
    }
}