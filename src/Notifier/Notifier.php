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
use Psr\EventDispatcher\EventDispatcherInterface;
use Tobento\App\Notifier\NotificationsInterface;
use Tobento\App\Notifier\Notifier as DefaultNotifier;
use Tobento\Service\Notifier\NotifierInterface;
use Tobento\Service\Notifier\ChannelsInterface;
use Tobento\Service\Notifier\ChannelMessagesInterface;
use Tobento\Service\Notifier\NotificationInterface;
use Tobento\Service\Notifier\RecipientInterface;
use Tobento\Service\Notifier\Exception\NotifierException;
use Tobento\Service\Notifier\QueueHandlerInterface;
use Tobento\Service\Iterable\Iter;

final class Notifier implements NotifierInterface
{
    private NotifierInterface $notifier;
    
    private array $notifications = [];
    
    /**
     * Create a new Notifier.
     *
     * @param NotificationsInterface $notifications
     * @param ChannelsInterface $channels
     * @param null|QueueHandlerInterface $queueHandler
     * @param null|EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        NotificationsInterface $notifications,
        ChannelsInterface $channels,
        null|QueueHandlerInterface $queueHandler = null,
        null|EventDispatcherInterface $eventDispatcher = null,
    ) {
        // we create a new Notifier without queue handler and event dispatcher:
        $this->notifier = new DefaultNotifier(
            notifications: $notifications,
            channels: $channels,
            queueHandler: $queueHandler,
            eventDispatcher: $eventDispatcher,
        );
    }
    
    /**
     * Send the notification to the specified recipients.
     *
     * @param NotificationInterface $notification
     * @param RecipientInterface ...$recipients
     * @return iterable<int, ChannelMessagesInterface>
     * @throws NotifierException
     */
    public function send(NotificationInterface $notification, RecipientInterface ...$recipients): iterable
    {
        $messages = $this->notifier->send($notification, ...$recipients);
        $messagesArr = Iter::toArray(iterable: $messages);
        
        if (empty($messagesArr)) {
            return $messages;
        }
        
        foreach (array_keys($recipients) as $key) {
            $this->notifications[$notification::class][] = $messagesArr[$key] ?? null;
        }
        
        return $messages;
    }
    
    /**
     * Returns the notifications.
     *
     * @param null|string $notification
     * @return array
     */
    public function getNotifications(null|string $notification = null): array
    {
        if (is_null($notification)) {
            return $this->notifications;
        }
        
        return $this->notifications[$notification] ?? [];
    }
}