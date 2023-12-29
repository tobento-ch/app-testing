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
use Tobento\Service\Notifier\NotifierInterface;
use Tobento\Service\Notifier\Notifier as DefaultNotifier;
use Tobento\Service\Notifier\ChannelsInterface;
use Tobento\Service\Notifier\ChannelMessagesInterface;
use Tobento\Service\Notifier\NotificationInterface;
use Tobento\Service\Notifier\RecipientInterface;
use Tobento\Service\Notifier\Exception\NotifierException;
use Tobento\Service\Iterable\Iter;

final class Notifier implements NotifierInterface
{
    private NotifierInterface $notifier;
    
    private array $notifications = [];
    
    /**
     * Create a new Notifier.
     *
     * @param ChannelsInterface $channels
     */
    public function __construct(
        ChannelsInterface $channels,
    ) {
        // we create a new Notifier without queue handler and event dispatcher:
        $this->notifier = new DefaultNotifier(channels: $channels);
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
        
        foreach (array_keys($recipients) as $key) {
            $this->notifications[$notification::class][] = $messagesArr[$key];
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