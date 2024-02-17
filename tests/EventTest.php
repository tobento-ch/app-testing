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
use Tobento\Service\Event\EventsInterface;
use Tobento\Service\Event\Test\Mock\FooListener;
use Tobento\Service\Event\Test\Mock\FooEvent;
use Tobento\Service\Event\Test\Mock\BarEvent;
use Psr\Http\Message\ServerRequestInterface;

class EventTest extends \Tobento\App\Testing\TestCase
{
    public function createApp(): AppInterface
    {
        $app = $this->createTmpApp(rootDir: __DIR__.'/..');
        $app->boot(\Tobento\App\Http\Boot\Routing::class);
        $app->boot(\Tobento\App\Http\Boot\RequesterResponser::class);
        $app->boot(\Tobento\App\Event\Boot\Event::class);
        
        $app->on(RouterInterface::class, static function(RouterInterface $router): void {
            $router->get('foo', function (ResponserInterface $responser, EventsInterface $events) {
                $events->dispatch(new FooEvent());
                return $responser->redirect(uri: 'bar');
            });

            $router->get('bar', function (ResponserInterface $responser, EventsInterface $events) {
                $events->dispatch(new BarEvent());
                return 'bar';
            });
        });
        
        return $app;
    }

    public function testDispatchEvent()
    {
        $events = $this->fakeEvents();
        $http = $this->fakeHttp();
        $http->request(method: 'POST', uri: 'blog');
        
        $this->getApp()->on(EventsInterface::class, static function(EventsInterface $events): void {
            $events->listen(FooListener::class);
        });
        
        $this->getApp()->on(RouterInterface::class, static function(RouterInterface $router): void {
            $router->post('blog', function (ServerRequestInterface $request, EventsInterface $events) {
                $events->dispatch(new FooEvent());
                $events->dispatch(new FooEvent());
                return 'response';
            });
        });
        
        $this->runApp();
        
        $events
            ->assertDispatched(FooEvent::class)
            ->assertDispatched(FooEvent::class, static function(FooEvent $event): bool {
                return $event->messages() === [FooListener::class];
            })
            ->assertDispatchedTimes(FooEvent::class, 2)
            ->assertNotDispatched(BarEvent::class)
            ->assertNotDispatched(FooEvent::class, static function(FooEvent $event): bool {
                return $event->messages() !== [FooListener::class];
            })
            ->assertListening(FooEvent::class, FooListener::class);
    }
    
    public function testDispatchEventWithConfig()
    {
        $config = $this->fakeConfig();
        $config->with('event.listeners', [
            FooEvent::class => [
                FooListener::class,
            ],
        ]);
        
        $events = $this->fakeEvents();
        $http = $this->fakeHttp();
        $http->request(method: 'POST', uri: 'blog');
        
        $this->getApp()->on(RouterInterface::class, static function(RouterInterface $router): void {
            $router->post('blog', function (ServerRequestInterface $request, EventsInterface $events) {
                $events->dispatch(new FooEvent());
                $events->dispatch(new FooEvent());
                return 'response';
            });
        });
        
        $this->runApp();
        
        $events
            ->assertDispatched(FooEvent::class)
            ->assertDispatchedTimes(FooEvent::class, 2)
            ->assertNotDispatched(BarEvent::class)
            ->assertListening(FooEvent::class, FooListener::class);
    }
    
    public function testAssertDispatchedMethodThrowsException()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('The expected [Tobento\Service\Event\Test\Mock\FooEvent] event was not dispatched.');
        
        $events = $this->fakeEvents();
        $http = $this->fakeHttp();
        $this->runApp();
        $events->assertDispatched(FooEvent::class);
    }
    
    public function testAssertDispatchedTimesMethodThrowsException()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('The expected [Tobento\Service\Event\Test\Mock\FooEvent] event was dispatched 0 times instead of 2 times.');
        
        $events = $this->fakeEvents();
        $http = $this->fakeHttp();
        $this->runApp();
        $events->assertDispatchedTimes(FooEvent::class, 2);
    }
    
    public function testAssertNotDispatchedMethodThrowsException()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('The unexpected [Tobento\Service\Event\Test\Mock\FooEvent] event was dispatched.');
        
        $config = $this->fakeConfig();
        $config->with('event.listeners', [
            FooEvent::class => [
                FooListener::class,
            ],
        ]);
        
        $events = $this->fakeEvents();
        $http = $this->fakeHttp();
        $http->request(method: 'POST', uri: 'blog');
        
        $this->getApp()->on(RouterInterface::class, static function(RouterInterface $router): void {
            $router->post('blog', function (ServerRequestInterface $request, EventsInterface $events) {
                $events->dispatch(new FooEvent());
                return 'response';
            });
        });
        
        $this->runApp();
        $events->assertNotDispatched(FooEvent::class);
    }
    
    public function testAssertListeningMethodThrowsException()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('Event [Tobento\Service\Event\Test\Mock\FooEvent] does not have the [Tobento\Service\Event\Test\Mock\FooListener] listener attached to it.');
        
        $events = $this->fakeEvents();
        $http = $this->fakeHttp();
        $this->runApp();
        $events->assertListening(FooEvent::class, FooListener::class);
    }
    
    public function testFollowingRedirects()
    {
        $events = $this->fakeEvents();
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'foo');
        
        $http->response()->assertStatus(302);
        $events->assertDispatched(FooEvent::class);
        $events->assertNotDispatched(BarEvent::class);
        
        $http->followRedirects()->assertStatus(200)->assertBodySame('bar');
        $this->fakeEvents()
            ->assertNotDispatched(FooEvent::class)
            ->assertDispatched(BarEvent::class);
    }
}