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

use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\ExpectationFailedException;
use Tobento\App\AppInterface;
use Tobento\App\Logging\LoggersInterface;
use Tobento\App\Logging\LoggerTrait;
use Tobento\App\Testing\Logging\LogEntry;
use Tobento\Service\Responser\ResponserInterface;
use Tobento\Service\Routing\RouterInterface;

class LoggingTest extends \Tobento\App\Testing\TestCase
{
    public function createApp(): AppInterface
    {
        $app = $this->createTmpApp(rootDir: __DIR__.'/..');
        $app->boot(\Tobento\App\Http\Boot\Routing::class);
        $app->boot(\Tobento\App\Http\Boot\RequesterResponser::class);
        $app->boot(\Tobento\App\Logging\Boot\Logging::class);
        
        $app->on(RouterInterface::class, static function(RouterInterface $router): void {
            $router->get('foo', function (ResponserInterface $responser, LoggerInterface $logger) {
                $logger->info('Foo');
                return $responser->redirect(uri: 'bar');
            });

            $router->get('bar', function (LoggerInterface $logger) {
                $logger->info('Bar');
                return 'bar';
            });
        });
        
        return $app;
    }

    public function testIsLogged()
    {
        // fakes:
        $fakeLogging = $this->fakeLogging();
        $http = $this->fakeHttp();
        $http->request(method: 'POST', uri: 'login');
        
        $this->getApp()->on(RouterInterface::class, static function(RouterInterface $router): void {
            $router->post('login', function (ServerRequestInterface $request, LoggerInterface $logger) {    
                $logger->info('User logged in.', ['user_id' => 3]);
                return 'response';
            });
        });
        
        // run the app:
        $this->runApp();
        
        // assertions using default logger:
        $fakeLogging->logger()
            ->assertLogged(fn (LogEntry $log): bool =>
                $log->level === 'info'
                && $log->message === 'User logged in.' 
                && $log->context === ['user_id' => 3]
            )
            ->assertNotLogged(
                fn (LogEntry $log): bool => $log->level === 'error'
            )
            ->assertLoggedTimes(
                fn (LogEntry $log): bool => $log->level === 'info',
                1
            );
        
        // specific logger:
        $fakeLogging->logger(name: 'error')->assertNothingLogged();
    }
    
    public function testAssertLoggedMethodThrowsException()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('Expected log was not created in the [daily] logger.');
        
        $fakeLogging = $this->fakeLogging();
        $http = $this->fakeHttp();
        $this->runApp();
        $fakeLogging->logger()->assertLogged(
            fn (LogEntry $log): bool => $log->level === 'error'
        );
    }
    
    public function testAssertLoggedMethodThrowsExceptionUsingCustomMessage()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('Custom message');
        
        $fakeLogging = $this->fakeLogging();
        $http = $this->fakeHttp();
        $this->runApp();
        $fakeLogging->logger()->assertLogged(
            fn (LogEntry $log): bool => $log->level === 'error',
            'Custom message'
        );
    }
    
    public function testAssertNotLoggedMethodThrowsException()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('Unexpected log was created in the [daily] logger.');
        
        $fakeLogging = $this->fakeLogging();
        $http = $this->fakeHttp();
        $http->request(method: 'POST', uri: 'log');
        
        $this->getApp()->on(RouterInterface::class, static function(RouterInterface $router): void {
            $router->post('log', function (ServerRequestInterface $request, LoggerInterface $logger) {    
                $logger->error('Message', ['key' => 'value']);
                return 'response';
            });
        });
        
        $this->runApp();
        $fakeLogging->logger()->assertNotLogged(
            fn (LogEntry $log): bool => $log->level === 'error'
        );
    }
    
    public function testAssertNotLoggedMethodThrowsExceptionUsingCustomMessage()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('CustomMessage');
        
        $fakeLogging = $this->fakeLogging();
        $http = $this->fakeHttp();
        $http->request(method: 'POST', uri: 'log');
        
        $this->getApp()->on(RouterInterface::class, static function(RouterInterface $router): void {
            $router->post('log', function (ServerRequestInterface $request, LoggerInterface $logger) {    
                $logger->error('Message', ['key' => 'value']);
                return 'response';
            });
        });
        
        $this->runApp();
        $fakeLogging->logger()->assertNotLogged(
            fn (LogEntry $log): bool => $log->level === 'error',
            'CustomMessage'
        );
    }
    
    public function testAssertLoggedTimesMethodThrowsException()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('Expected log was created 0 times instead of 2 times in the [daily] logger.');
        
        $fakeLogging = $this->fakeLogging();
        $http = $this->fakeHttp();
        $this->runApp();
        $fakeLogging->logger()->assertLoggedTimes(
            fn (LogEntry $log): bool => $log->level === 'error',
            2
        );
    }
    
    public function testAssertLoggedTimesMethodThrowsExceptionUsingCustomMessage()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('CustomMessage');
        
        $fakeLogging = $this->fakeLogging();
        $http = $this->fakeHttp();
        $this->runApp();
        $fakeLogging->logger()->assertLoggedTimes(
            fn (LogEntry $log): bool => $log->level === 'error',
            2,
            'CustomMessage'
        );
    }
    
    public function testAssertNothingLoggedMethodThrowsException()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('Expected [0] logs to be created instead of [1] in the [daily] logger.');
        
        $fakeLogging = $this->fakeLogging();
        $http = $this->fakeHttp();
        $http->request(method: 'POST', uri: 'log');
        
        $this->getApp()->on(RouterInterface::class, static function(RouterInterface $router): void {
            $router->post('log', function (ServerRequestInterface $request, LoggerInterface $logger) {    
                $logger->error('Message', ['key' => 'value']);
                return 'response';
            });
        });
        
        $this->runApp();
        $fakeLogging->logger()->assertNothingLogged();
    }
    
    public function testAssertNothingLoggedMethodThrowsExceptionUsingCustomMessage()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('CustomMessage');
        
        $fakeLogging = $this->fakeLogging();
        $http = $this->fakeHttp();
        $http->request(method: 'POST', uri: 'log');
        
        $this->getApp()->on(RouterInterface::class, static function(RouterInterface $router): void {
            $router->post('log', function (ServerRequestInterface $request, LoggerInterface $logger) {    
                $logger->error('Message', ['key' => 'value']);
                return 'response';
            });
        });
        
        $this->runApp();
        $fakeLogging->logger()->assertNothingLogged('CustomMessage');
    }

    public function testWithNamedLogger()
    {
        $fakeLogging = $this->fakeLogging();
        $http = $this->fakeHttp();
        $http->request(method: 'POST', uri: 'log');
        
        $this->getApp()->on(RouterInterface::class, static function(RouterInterface $router): void {
            $router->post('log', function (ServerRequestInterface $request, LoggersInterface $loggers) {    
                $loggers->logger('error')->critical('Message', ['key' => 'value']);
                return 'response';
            });
        });
        
        $this->runApp();
        
        $fakeLogging->logger(name: 'error')->assertLogged(
            fn (LogEntry $log): bool => $log->level === 'critical'
        );
    }
    
    public function testWithAliasedLogger()
    {
        $config = $this->fakeConfig();
        $config->with('logging.aliases', [
            LoggerController::class => 'error',
        ]);
        
        $fakeLogging = $this->fakeLogging();
        $http = $this->fakeHttp();
        $http->request(method: 'POST', uri: 'log');
        
        $this->getApp()->on(RouterInterface::class, static function(RouterInterface $router): void {
            $router->post('log', LoggerController::class);
        });
        
        $this->runApp();
        
        $fakeLogging->logger(name: 'error')->assertLogged(
            fn (LogEntry $log): bool => $log->level === 'critical'
        );
    }
    
    public function testFollowingRedirects()
    {
        $fakeLogging = $this->fakeLogging();
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'foo');
        
        $http->response()->assertStatus(302);
        $fakeLogging->logger()->assertLogged(
            fn (LogEntry $log): bool => $log->message === 'Foo'
        );
        
        $http->followRedirects()->assertStatus(200)->assertBodySame('bar');
        $this->fakeLogging()->logger()->assertLogged(
            fn (LogEntry $log): bool => $log->message === 'Bar'
        );
    }
}

final class LoggerController
{
    use LoggerTrait;
    
    public function __invoke()
    {
        $this->getLogger()->critical('Loged From Aliased Logger');
    }
}