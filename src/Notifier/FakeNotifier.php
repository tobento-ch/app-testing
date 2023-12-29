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

namespace Tobento\App\Testing\Notifier;

use PHPUnit\Framework\TestCase;
use Tobento\App\AppInterface;
use Tobento\Service\Notifier\NotifierInterface;
use Tobento\Service\Notifier\ChannelsInterface;
use Tobento\Service\Notifier\ChannelInterface;
use Tobento\Service\Notifier\Channels;
use Closure;

final class FakeNotifier
{
    /**
     * Create a new FakeNotifier.
     *
     * @param AppInterface $app
     */
    public function __construct(
        private AppInterface $app,
    ) {
        $app->on(
            NotifierInterface::class,
            function(): NotifierInterface {
                return new Notifier($this->app->get(ChannelsInterface::class));
            }
        );

        $app->on(
            ChannelsInterface::class,
            function(ChannelsInterface $channels): ChannelsInterface {
                
                $fakeChannels = [];

                foreach($channels->names() as $name) {
                    $fakeChannels[] = $this->createChannel($name);
                }
                
                return new Channels(...$fakeChannels);
            }
        );
    }
    
    /**
     * @psalm-suppress TooManyArguments
     */
    private function filterNotifications(string $notification, null|Closure $callback = null): array
    {
        $notifications = $this->notifier()->getNotifications($notification);

        $callback = $callback ?: static fn(): bool => true;

        return array_filter($notifications, static function ($messages) use ($callback) {
            return $callback($messages);
        });
    }    
    
    public function assertSent(string $notification, null|Closure $callback = null): static
    {
        $notifications = $this->filterNotifications($notification, $callback);

        TestCase::assertTrue(
            count($notifications) > 0,
            sprintf('The expected notification [%s] was not sent.', $notification)
        );

        return $this;
    }
    
    public function assertNotSent(string $notification, null|Closure $callback = null): static
    {
        TestCase::assertCount(
            0,
            $this->filterNotifications($notification, $callback),
            sprintf('The unexpected notification [%s] was sent.', $notification)
        );

        return $this;
    }
    
    public function assertNothingSent(): static
    {
        $notifications = $this->notifier()->getNotifications();
        
        $names = implode(', ', array_keys($notifications));

        TestCase::assertCount(
            0,
            $notifications,
            sprintf('The following notifications were sent unexpectedly: %s', $names)
        );
        
        return $this;
    }
    
    public function assertSentTimes(string $notification, int $times = 1): static
    {
        $notifications = $this->filterNotifications($notification);

        TestCase::assertCount(
            $times,
            $notifications,
            sprintf(
                'The expected notification [%s] was sent %d times instead of %d times.',
                $notification,
                count($notifications),
                $times
            )
        );

        return $this;
    }

    /**
     * Returns the notifier.
     *
     * @return Notifier
     */
    public function notifier(): Notifier
    {
        return $this->app->get(NotifierInterface::class);
    }
    
    /**
     * Returns the channels.
     *
     * @return ChannelsInterface
     */
    public function channels(): ChannelsInterface
    {
        return $this->app->get(ChannelsInterface::class);
    }
    
    /**
     * Returns the channel.
     *
     * @param string $name
     * @return ChannelInterface
     */
    public function channel(string $name): ChannelInterface
    {
        return $this->app->get(ChannelsInterface::class)->get($name);
    }
    
    /**
     * Create a new channel.
     *
     * @param string $name
     * @return ChannelInterface
     */
    private function createChannel(string $name): ChannelInterface
    {
        return new Channel(
            name: $name,
            container: $this->app->container(),
        );
    }
}