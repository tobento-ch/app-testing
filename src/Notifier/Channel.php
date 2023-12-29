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
use Tobento\Service\Notifier\ChannelInterface;
use Tobento\Service\Notifier\NotificationInterface;
use Tobento\Service\Notifier\RecipientInterface;
use Tobento\Service\Notifier\Message;
use Tobento\Service\Notifier\Address;
use Tobento\Service\Notifier\Exception\UndefinedMessageException;
use Tobento\Service\Notifier\Exception\UndefinedAddressException;
use Tobento\Service\Mail\MailerInterface;
use Tobento\Service\Mail\Message as MailMessage;
use Tobento\Service\Mail\Address as Adr;
use Tobento\Service\Autowire\Autowire;
use Psr\Container\ContainerInterface;

class Channel implements ChannelInterface
{
    /**
     * Create a new Channel.
     *
     * @param string $name
     * @param ContainerInterface $container
     */
    public function __construct(
        private string $name,
        private ContainerInterface $container,
    ) {}
    
    /**
     * Returns the channel name.
     *
     * @return string
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * Send the notification to the specified recipient.
     *
     * @param NotificationInterface $notification
     * @param RecipientInterface $recipient
     * @return object The sent message.
     * @throws \Throwable
     */
    public function send(NotificationInterface $notification, RecipientInterface $recipient): object
    {
        return match (true) {
            str_starts_with($this->name(), 'mail') => $this->mailMessage($notification, $recipient),
            str_starts_with($this->name(), 'sms') => $this->smsMessage($notification, $recipient),
            str_starts_with($this->name(), 'chat') => $this->pushMessage($notification, $recipient),
            str_starts_with($this->name(), 'push') => $this->pushMessage($notification, $recipient),
            str_starts_with($this->name(), 'storage') => $this->storageMessage($notification, $recipient),
            default => throw new \RuntimeException(
                sprintf('Unsupported channel %s while testing', $this->name())
            ),
        };
    }
    
    protected function mailMessage(NotificationInterface $notification, RecipientInterface $recipient): object
    {
        if (! $notification instanceof Message\ToMail) {
            throw new UndefinedMessageException($this->name(), $notification, $recipient);
        }
        
        $message = (new Autowire($this->container))->call(
            $notification->toMailHandler(),
            ['recipient' => $recipient, 'channel' => $this->name()]
        );
        
        if (! $message instanceof MailMessage) {
            throw new UndefinedMessageException(
                channel: $this->name(),
                notification: $notification,
                recipient: $recipient,
                message: sprintf('Mail message needs to be an instanceof %s', MailMessage::class),
            );
        }
        
        // Ensure to address:        
        if ($message->getTo()->empty()) {
            $address = $recipient->getAddressForChannel(name: $this->name(), notification: $notification);

            if (! $address instanceof Address\EmailInterface) {
                throw new UndefinedAddressException($this->name(), $notification, $recipient);
            }
            
            $message->to(new Adr($address->email(), $address->name()));
        }
        
        return $message;
    }
    
    protected function smsMessage(NotificationInterface $notification, RecipientInterface $recipient): object
    {
        if (! $notification instanceof Message\ToSms) {
            throw new UndefinedMessageException($this->name(), $notification, $recipient);
        }
        
        $message = (new Autowire($this->container))->call(
            $notification->toSmsHandler(),
            ['recipient' => $recipient, 'channel' => $this->name()]
        );
        
        if (! $message instanceof Message\SmsInterface) {
            throw new UndefinedMessageException(
                channel: $this->name(),
                notification: $notification,
                recipient: $recipient,
                message: sprintf('Sms message needs to be an instanceof %s', Message\SmsInterface::class),
            );
        }
        
        if (is_null($message->getTo())) {
            $address = $recipient->getAddressForChannel(name: $this->name(), notification: $notification);

            if (! $address instanceof Address\PhoneInterface) {
                throw new UndefinedAddressException($this->name(), $notification, $recipient);
            }
            
            $message->to($address);
        }
        
        return $message;
    }

    protected function chatMessage(NotificationInterface $notification, RecipientInterface $recipient): object
    {
        if (! $notification instanceof Message\ToChat) {
            throw new UndefinedMessageException($this->name(), $notification, $recipient);
        }
        
        $message = (new Autowire($this->container))->call(
            $notification->toChatHandler(),
            ['recipient' => $recipient, 'channel' => $this->name()]
        );
        
        if (! $message instanceof Message\ChatInterface) {
            throw new UndefinedMessageException(
                channel: $this->name(),
                notification: $notification,
                recipient: $recipient,
                message: sprintf('Chat message needs to be an instanceof %s', Message\ChatInterface::class),
            );
        }
        
        return $message;
    }
    
    protected function pushMessage(NotificationInterface $notification, RecipientInterface $recipient): object
    {
        if (! $notification instanceof Message\ToPush) {
            throw new UndefinedMessageException($this->name(), $notification, $recipient);
        }
        
        $message = (new Autowire($this->container))->call(
            $notification->toPushHandler(),
            ['recipient' => $recipient, 'channel' => $this->name()]
        );
        
        if (! $message instanceof Message\PushInterface) {
            throw new UndefinedMessageException(
                channel: $this->name(),
                notification: $notification,
                recipient: $recipient,
                message: sprintf('Push message needs to be an instanceof %s', Message\PushInterface::class),
            );
        }
        
        return $message;
    }
    
    protected function storageMessage(NotificationInterface $notification, RecipientInterface $recipient): object
    {
        if (is_null($recipient->getId())) {
            throw new UndefinedAddressException($this->name(), $notification, $recipient);
        }
        
        if (! $notification instanceof Message\ToStorage) {
            throw new UndefinedMessageException($this->name(), $notification, $recipient);
        }
        
        $message = (new Autowire($this->container))->call(
            $notification->toStorageHandler(),
            ['recipient' => $recipient, 'channel' => $this->name()]
        );
        
        if (! $message instanceof Message\StorageInterface) {
            throw new UndefinedMessageException($this->name(), $notification, $recipient);
        }
        
        return (object) [
            'name' => $notification->getName(),
            'recipient_id' => $recipient->getId(),
            'recipient_type' => $recipient->getType(),
            'data' => $message->getData(),
            //'read_at' => null,
            'created_at' => null,
        ];
    }
}