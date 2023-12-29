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

namespace Tobento\App\Testing\Test;

use PHPUnit\Framework\ExpectationFailedException;
use Tobento\App\AppInterface;
use Tobento\Service\Routing\RouterInterface;
use Psr\Http\Message\ServerRequestInterface;
use Tobento\Service\Notifier\NotifierInterface;
use Tobento\Service\Notifier\ChannelMessagesInterface;
use Tobento\Service\Notifier\Notification;
use Tobento\Service\Notifier\Recipient;

class NotifierTest extends \Tobento\App\Testing\TestCase
{
    public function createApp(): AppInterface
    {
        $app = $this->createTmpApp(rootDir: __DIR__.'/..');
        $app->boot(\Tobento\App\Http\Boot\Routing::class);
        $app->boot(\Tobento\App\Notifier\Boot\Notifier::class);
        return $app;
    }

    public function testIsNotified()
    {
        // fakes:
        $notifier = $this->fakeNotifier();
        $http = $this->fakeHttp();
        $http->request(method: 'POST', uri: 'notify');
        
        $this->getApp()->on(RouterInterface::class, static function(RouterInterface $router): void {
            $router->post('notify', function (ServerRequestInterface $request, NotifierInterface $notifier) {
                
                $notification = new Notification(
                    subject: 'New Invoice',
                    content: 'You got a new invoice for 15 EUR.',
                    channels: ['mail', 'sms', 'storage'],
                );

                // The receiver of the notification:
                $recipient = new Recipient(
                    email: 'mail@example.com',
                    phone: '15556666666',
                    id: 5,
                );

                $notifier->send($notification, $recipient);
                
                return 'response';
            });
        });
        
        // run the app:
        $this->runApp();
        
        // assertions:
        $notifier
            ->assertSent(Notification::class)
            ->assertSent(Notification::class, function (ChannelMessagesInterface $messages): bool {
                $notification = $messages->notification();
                $recipient = $messages->recipient();

                $mail = $messages->get('mail')->message();
                $this->assertSame('New Invoice', $mail->getSubject());

                return $notification->getSubject() === 'New Invoice'
                    && $messages->successful()->channelNames() === ['mail', 'sms', 'storage']
                    && $messages->get('sms')->message()->getTo()->phone() === '15556666666'
                    && $recipient->getAddressForChannel('mail', $notification)?->email() === 'mail@example.com';
            })
            ->assertSentTimes(Notification::class, 1)
            ->assertNotSent(Notification::class, static function(ChannelMessagesInterface $messages): bool {
                $notification = $messages->notification();
                return $notification->getSubject() !== 'New Invoice';
            });
    }
    
    public function testNothingSent()
    {
        // fakes:
        $notifier = $this->fakeNotifier();
        $http = $this->fakeHttp();
        
        // run the app:
        $this->runApp();
        
        // assertions:
        $notifier->assertNothingSent();
    }
    
    public function testAssertSentMethodThrowsException()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('The expected notification [Tobento\Service\Notifier\Notification] was not sent.');
        
        $notifier = $this->fakeNotifier();
        $http = $this->fakeHttp();
        $this->runApp();
        $notifier->assertSent(Notification::class);
    }
    
    public function testAssertSentTimesMethodThrowsException()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('The expected notification [Tobento\Service\Notifier\Notification] was sent 0 times instead of 2 times.');
        
        $notifier = $this->fakeNotifier();
        $http = $this->fakeHttp();
        $this->runApp();
        $notifier->assertSentTimes(Notification::class, 2);
    }
    
    public function testAssertNotSentMethodThrowsException()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('The unexpected notification [Tobento\Service\Notifier\Notification] was sent.');
        
        $notifier = $this->fakeNotifier();
        $http = $this->fakeHttp();
        $http->request(method: 'POST', uri: 'notify');
        
        $this->getApp()->on(RouterInterface::class, static function(RouterInterface $router): void {
            $router->post('notify', function (ServerRequestInterface $request, NotifierInterface $notifier) {
                
                $notification = new Notification(
                    subject: 'New Invoice',
                );

                // The receiver of the notification:
                $recipient = new Recipient(
                    email: 'mail@example.com',
                );

                $notifier->send($notification, $recipient);
                
                return 'response';
            });
        });
        
        $this->runApp();
        $notifier->assertNotSent(Notification::class);
    }
    
    public function testAssertNothingSentMethodThrowsException()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('The following notifications were sent unexpectedly: Tobento\Service\Notifier\Notification');
        
        $notifier = $this->fakeNotifier();
        $http = $this->fakeHttp();
        $http->request(method: 'POST', uri: 'notify');
        
        $this->getApp()->on(RouterInterface::class, static function(RouterInterface $router): void {
            $router->post('notify', function (ServerRequestInterface $request, NotifierInterface $notifier) {
                
                $notification = new Notification(
                    subject: 'New Invoice',
                );

                // The receiver of the notification:
                $recipient = new Recipient(
                    email: 'mail@example.com',
                );

                $notifier->send($notification, $recipient);
                
                return 'response';
            });
        });
        
        $this->runApp();
        $notifier->assertNothingSent();
    }
}