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
use Tobento\Service\Responser\ResponserInterface;
use Psr\Http\Message\ServerRequestInterface;
use Tobento\Service\Mail\MailerInterface;
use Tobento\Service\Mail\MessageInterface;
use Tobento\Service\Mail\Message;
use Tobento\Service\Mail\Parameter;
use Tobento\Service\Mail\Address;

class MailTest extends \Tobento\App\Testing\TestCase
{
    public function createApp(): AppInterface
    {
        $app = $this->createTmpApp(rootDir: __DIR__.'/..');
        $app->boot(\Tobento\App\Http\Boot\Routing::class);
        $app->boot(\Tobento\App\Http\Boot\RequesterResponser::class);
        $app->boot(\Tobento\App\View\Boot\View::class);
        $app->boot(\Tobento\App\Mail\Boot\Mail::class);
        
        $app->on(RouterInterface::class, static function(RouterInterface $router): void {
            $router->get('foo', function (ResponserInterface $responser, MailerInterface $mailer) {
                $message = (new Message())
                    ->from('from@example.com')
                    ->to('foo@example.com')
                    ->subject('Subject')
                    ->html('<p>Lorem Ipsum</p>');
                $mailer->send($message);
                return $responser->redirect(uri: 'bar');
            });

            $router->get('bar', function (MailerInterface $mailer) {
                $message = (new Message())
                    ->from('from@example.com')
                    ->to('bar@example.com')
                    ->subject('Subject')
                    ->html('<p>Lorem Ipsum</p>');
                $mailer->send($message);
                return 'bar';
            });
        });
        
        return $app;
    }

    public function testMailSent()
    {
        // fakes:
        $fakeMail = $this->fakeMail();
        $http = $this->fakeHttp();
        $http->request(method: 'POST', uri: 'mail');
        
        // we use the tests views from the mail service.
        $this->getApp()->dirs()->dir(
            dir: $this->getApp()->dir('vendor').'tobento/service-mail/tests/views/',
            name: 'theme',
            group: 'views',
            priority: 500,
        );
        
        $this->getApp()->on(RouterInterface::class, static function(RouterInterface $router): void {
            $router->post('mail', function (ServerRequestInterface $request, MailerInterface $mailer) {
                
                $message = (new Message())
                    ->from('from@example.com')
                    ->to(new Address('to@example.com', 'ToName'), new Address('john@example.com', 'John'))
                    ->cc('cc@example.com')
                    ->bcc('bcc@example.com')
                    ->replyTo('replyTo@example.com')
                    ->subject('Subject')
                    ->parameter(new Parameter\Queue(delay: 30))
                    ->htmlTemplate('welcome', ['name' => 'John']);

                $mailer->send($message);
                
                return 'response';
            });
        });
        
        // run the app:
        $this->runApp();
        
        // assertions:
        $fakeMail->mailer(name: 'default')
            ->sent(Message::class)
            ->assertFrom('from@example.com')
            ->assertHasTo('to@example.com', 'ToName')
            ->assertHasTo('john@example.com', 'John')
            ->assertHasCc('cc@example.com')
            ->assertHasBcc('bcc@example.com')
            ->assertReplyTo('replyTo@example.com')
            ->assertSubject('Subject')
            ->assertTextContains('Welcome, John')
            ->assertHtmlContains('Welcome, John')
            ->assertIsQueued()
            ->assertHasParameter(Parameter\Queue::class, fn (Parameter\Queue $p) => $p->delay() === 30)
            ->assertTimes(1);
    }
    
    public function testSentMethodThrowsException()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('The expected [Tobento\Service\Mail\Message] message was not sent.');
        
        $fakeMail = $this->fakeMail();
        $http = $this->fakeHttp();
        $this->runApp();
        $fakeMail->mailer(name: 'default')->sent(Message::class);
    }
    
    public function testAssertFromThrowsException()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('The expected [Tobento\Service\Mail\Message] message was not sent from: <john@example.com> address.');
        
        $fakeMail = $this->fakeMail();
        $http = $this->fakeHttp();
        $http->request(method: 'POST', uri: 'mail');
        
        $this->getApp()->on(RouterInterface::class, static function(RouterInterface $router): void {
            $router->post('mail', function (ServerRequestInterface $request, MailerInterface $mailer) {
                
                $message = (new Message())
                    ->from('mail@example.com');

                $mailer->send($message);
                
                return 'response';
            });
        });
        
        $this->runApp();
        $fakeMail->mailer(name: 'default')
            ->sent(Message::class)
            ->assertFrom('john@example.com');
    }
    
    public function testAssertHasToThrowsException()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('The expected [Tobento\Service\Mail\Message] message was not sent to: <john@example.com> address.');
        
        $fakeMail = $this->fakeMail();
        $http = $this->fakeHttp();
        $http->request(method: 'POST', uri: 'mail');
        
        $this->getApp()->on(RouterInterface::class, static function(RouterInterface $router): void {
            $router->post('mail', function (ServerRequestInterface $request, MailerInterface $mailer) {
                
                $message = (new Message())
                    ->to('mail@example.com');

                $mailer->send($message);
                
                return 'response';
            });
        });
        
        $this->runApp();
        $fakeMail->mailer(name: 'default')
            ->sent(Message::class)
            ->assertHasTo('john@example.com');
    }
    
    public function testAssertHasCcThrowsException()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('The expected [Tobento\Service\Mail\Message] message has no Cc: <john@example.com> address.');
        
        $fakeMail = $this->fakeMail();
        $http = $this->fakeHttp();
        $http->request(method: 'POST', uri: 'mail');
        
        $this->getApp()->on(RouterInterface::class, static function(RouterInterface $router): void {
            $router->post('mail', function (ServerRequestInterface $request, MailerInterface $mailer) {
                
                $message = (new Message())
                    ->cc('mail@example.com');

                $mailer->send($message);
                
                return 'response';
            });
        });
        
        $this->runApp();
        $fakeMail->mailer(name: 'default')
            ->sent(Message::class)
            ->assertHasCc('john@example.com');
    }
    
    public function testAssertHasBccThrowsException()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('The expected [Tobento\Service\Mail\Message] message has no Bcc: <john@example.com> address.');
        
        $fakeMail = $this->fakeMail();
        $http = $this->fakeHttp();
        $http->request(method: 'POST', uri: 'mail');
        
        $this->getApp()->on(RouterInterface::class, static function(RouterInterface $router): void {
            $router->post('mail', function (ServerRequestInterface $request, MailerInterface $mailer) {
                
                $message = (new Message())
                    ->bcc('mail@example.com');

                $mailer->send($message);
                
                return 'response';
            });
        });
        
        $this->runApp();
        $fakeMail->mailer(name: 'default')
            ->sent(Message::class)
            ->assertHasBcc('john@example.com');
    }
    
    public function testAssertReplyToThrowsException()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('The expected [Tobento\Service\Mail\Message] message has no replyTo: <john@example.com> address.');
        
        $fakeMail = $this->fakeMail();
        $http = $this->fakeHttp();
        $http->request(method: 'POST', uri: 'mail');
        
        $this->getApp()->on(RouterInterface::class, static function(RouterInterface $router): void {
            $router->post('mail', function (ServerRequestInterface $request, MailerInterface $mailer) {
                
                $message = (new Message())
                    ->replyTo('mail@example.com');

                $mailer->send($message);
                
                return 'response';
            });
        });
        
        $this->runApp();
        $fakeMail->mailer(name: 'default')
            ->sent(Message::class)
            ->assertReplyTo('john@example.com');
    }
    
    public function testAssertSubjectThrowsException()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('The expected [Tobento\Service\Mail\Message] message has no subject: ipsum');
        
        $fakeMail = $this->fakeMail();
        $http = $this->fakeHttp();
        $http->request(method: 'POST', uri: 'mail');
        
        $this->getApp()->on(RouterInterface::class, static function(RouterInterface $router): void {
            $router->post('mail', function (ServerRequestInterface $request, MailerInterface $mailer) {
                
                $message = (new Message())
                    ->subject('Lorem');

                $mailer->send($message);
                
                return 'response';
            });
        });
        
        $this->runApp();
        $fakeMail->mailer(name: 'default')
            ->sent(Message::class)
            ->assertSubject('ipsum');
    }
    
    public function testAssertTextContainsThrowsException()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('The expected [Tobento\Service\Mail\Message] message text does not contain: ipsum');
        
        $fakeMail = $this->fakeMail();
        $http = $this->fakeHttp();
        $http->request(method: 'POST', uri: 'mail');
        
        $this->getApp()->on(RouterInterface::class, static function(RouterInterface $router): void {
            $router->post('mail', function (ServerRequestInterface $request, MailerInterface $mailer) {
                
                $message = (new Message())
                    ->text('Lorem');

                $mailer->send($message);
                
                return 'response';
            });
        });
        
        $this->runApp();
        $fakeMail->mailer(name: 'default')
            ->sent(Message::class)
            ->assertTextContains('ipsum');
    }
    
    public function testAssertHtmlContainsThrowsException()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('The expected [Tobento\Service\Mail\Message] message html does not contain: ipsum');
        
        $fakeMail = $this->fakeMail();
        $http = $this->fakeHttp();
        $http->request(method: 'POST', uri: 'mail');
        
        $this->getApp()->on(RouterInterface::class, static function(RouterInterface $router): void {
            $router->post('mail', function (ServerRequestInterface $request, MailerInterface $mailer) {
                
                $message = (new Message())
                    ->html('Lorem');

                $mailer->send($message);
                
                return 'response';
            });
        });
        
        $this->runApp();
        $fakeMail->mailer(name: 'default')
            ->sent(Message::class)
            ->assertHtmlContains('ipsum');
    }
    
    public function testAssertIsQueuedThrowsException()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('The expected [Tobento\Service\Mail\Message] message has no parameter: Tobento\Service\Mail\Parameter\Queue');
        
        $fakeMail = $this->fakeMail();
        $http = $this->fakeHttp();
        $http->request(method: 'POST', uri: 'mail');
        
        $this->getApp()->on(RouterInterface::class, static function(RouterInterface $router): void {
            $router->post('mail', function (ServerRequestInterface $request, MailerInterface $mailer) {
                
                $message = (new Message());

                $mailer->send($message);
                
                return 'response';
            });
        });
        
        $this->runApp();
        $fakeMail->mailer(name: 'default')
            ->sent(Message::class)
            ->assertIsQueued();
    }
    
    public function testAssertHasParameterThrowsException()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('The expected [Tobento\Service\Mail\Message] message has no parameter: Tobento\Service\Mail\Parameter\File');
        
        $fakeMail = $this->fakeMail();
        $http = $this->fakeHttp();
        $http->request(method: 'POST', uri: 'mail');
        
        $this->getApp()->on(RouterInterface::class, static function(RouterInterface $router): void {
            $router->post('mail', function (ServerRequestInterface $request, MailerInterface $mailer) {
                
                $message = (new Message());

                $mailer->send($message);
                
                return 'response';
            });
        });
        
        $this->runApp();
        $fakeMail->mailer(name: 'default')
            ->sent(Message::class)
            ->assertHasParameter(
                Parameter\File::class,
                fn (Parameter\File $f) => $f->file()->getBasename() === 'image.jpg'
            );
    }
    
    public function testAssertTimesThrowsException()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('The expected [Tobento\Service\Mail\Message] message was sent 1 times instead of 2 times.');
        
        $fakeMail = $this->fakeMail();
        $http = $this->fakeHttp();
        $http->request(method: 'POST', uri: 'mail');
        
        $this->getApp()->on(RouterInterface::class, static function(RouterInterface $router): void {
            $router->post('mail', function (ServerRequestInterface $request, MailerInterface $mailer) {
                
                $message = (new Message());

                $mailer->send($message);
                
                return 'response';
            });
        });
        
        $this->runApp();
        $fakeMail->mailer(name: 'default')
            ->sent(Message::class)
            ->assertTimes(2);
    }
    
    public function testFollowingRedirects()
    {
        $fakeMail = $this->fakeMail();
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'foo');
        
        $http->response()->assertStatus(302);
        $fakeMail->mailer(name: 'default')
            ->sent(Message::class)
            ->assertHasTo('foo@example.com');
        
        $http->followRedirects()->assertStatus(200)->assertBodySame('bar');
        $this->fakeMail()->mailer(name: 'default')
            ->sent(Message::class)
            ->assertHasTo('bar@example.com');
    }
}