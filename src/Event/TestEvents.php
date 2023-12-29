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

use PHPUnit\Framework\TestCase;
use Tobento\Service\Event\EventsInterface;
use Tobento\Service\Event\Events;
use Tobento\Service\Event\ListenersInterface;
use Tobento\Service\Event\Listener;
use Closure;

class TestEvents implements EventsInterface
{
    protected null|EventsInterface $events = null;
    
    protected array $dispatchedEvents = [];
    
    protected null|array $listenersToEvents = null;
    
    /**
     * Set the events.
     *
     * @param EventsInterface $events
     * @return static $this
     */
    public function setEvents(EventsInterface $events): static
    {
        $this->events = $events;
        return $this;
    }
    
    /**
     * Returns the events.
     *
     * @return EventsInterface
     */
    public function getEvents(): EventsInterface
    {
        if (is_null($this->events)) {
            $this->events = new Events();
        }
        
        return $this->events;
    }
    
    /**
     * Add a listener.
     *
     * @param mixed $listener
     * @return Listener
     */
    public function listen(mixed $listener): Listener
    {
        return $this->getEvents()->listen($listener);
    }
    
    /**
     * Returns the listeners.
     *
     * @return ListenersInterface
     */
    public function listeners(): ListenersInterface
    {
        return $this->getEvents()->listeners();
    }
    
    /**
     * Provide all relevant listeners with an event to process.
     *
     * @param object $event
     * @return object
     *   The Event that was passed, now modified by listeners.
     */
    public function dispatch(object $event): object
    {
        if (! isset($this->dispatchedEvents[$event::class])) {
            $this->dispatchedEvents[$event::class] = [];
        }

        $this->dispatchedEvents[$event::class][] = $event;
        
        return $this->getEvents()->dispatch($event);
    }
    
    /**
     * Get all the events matching a truth-test callback.
     *
     * @param class-string $event
     * @param null|Closure $callback
     * @return array
     * @psalm-suppress TooManyArguments
     */
    public function dispatched(string $event, null|Closure $callback = null): array
    {
        if (! $this->hasDispatched($event)) {
            return [];
        }

        $callback = $callback ?: static fn(): bool => true;

        return array_filter(
            $this->dispatchedEvents[$event],
            static fn(object $event): bool => $callback($event)
        );
    }

    /**
     * Determine if the given event has been dispatched.
     *
     * @param class-string $event
     * @return bool
     */
    public function hasDispatched(string $event): bool
    {
        return isset($this->dispatchedEvents[$event]) && $this->dispatchedEvents[$event] !== [];
    }
    
    /**
     * Assert if an event was dispatched based on a truth-test callback.
     *
     * @param class-string $event
     * @param null|Closure $callback
     * @return static $this
     */
    public function assertDispatched(string $event, null|Closure $callback = null): static
    {
        TestCase::assertTrue(
            count($this->dispatched($event, $callback)) > 0,
            sprintf('The expected [%s] event was not dispatched.', $event)
        );
        
        return $this;
    }
    
    /**
     * Assert if an event was dispatched a number of times.
     *
     * @param class-string $event
     * @param positive-int $times
     * @return static $this
     */
    public function assertDispatchedTimes(string $event, int $times = 1): static
    {
        $count = count($this->dispatched($event));

        TestCase::assertSame(
            $times,
            $count,
            sprintf(
                'The expected [%s] event was dispatched %d times instead of %d times.',
                $event,
                $count,
                $times
            )
        );
        
        return $this;
    }
    
    /**
     * Determine if an event was not dispatched based on a truth-test callback.
     *
     * @param class-string $event
     * @param null|Closure $callback
     * @return static $this
     */
    public function assertNotDispatched(string $event, null|Closure $callback = null): static
    {
        TestCase::assertCount(
            0,
            $this->dispatched($event, $callback),
            sprintf('The unexpected [%s] event was dispatched.', $event)
        );
        
        return $this;
    }

    /**
     * Assert that no events were dispatched.
     *
     * @return static $this
     */
    public function assertNothingDispatched(): static
    {
        $count = count($this->dispatchedEvents);

        TestCase::assertSame(
            0,
            $count,
            sprintf('%d unexpected events were dispatched.', $count)
        );
        
        return $this;
    }

    /**
     * Assert if an event has a listener attached to it.
     *
     * @param class-string $expectedEvent
     * @param class-string $expectedListener
     * @return static $this
     */
    public function assertListening(string $expectedEvent, string $expectedListener): static
    {
        if (is_null($this->listenersToEvents)) {
            foreach($this->listeners()->all() as $listener) {

                if ($listener->getListener() !== $expectedListener) {
                    continue;
                }

                $this->listenersToEvents[$expectedListener] = array_keys($listener->getListenerEvents());
            }            
        }
        
        if (
            isset($this->listenersToEvents[$expectedListener])
            && in_array($expectedEvent, $this->listenersToEvents[$expectedListener])
        ) {
            TestCase::assertTrue(true);
        } else {
            TestCase::assertTrue(
                false,
                sprintf(
                    'Event [%s] does not have the [%s] listener attached to it.',
                    $expectedEvent,
                    $expectedListener
                )
            );            
        }
        
        return $this;
    }
}