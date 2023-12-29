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
use Tobento\Service\Queue\QueueInterface;
use Tobento\Service\Queue\JobInterface;
use Tobento\Service\Queue\Job;

class QueueTest extends \Tobento\App\Testing\TestCase
{
    public function createApp(): AppInterface
    {
        $app = $this->createTmpApp(rootDir: __DIR__.'/..');
        $app->boot(\Tobento\App\Http\Boot\Routing::class);
        $app->boot(\Tobento\App\Queue\Boot\Queue::class);
        return $app;
    }

    public function testIsQueued()
    {
        // fakes:
        $fakeQueue = $this->fakeQueue();
        $http = $this->fakeHttp();
        $http->request(method: 'POST', uri: 'queue');
        
        $this->getApp()->on(RouterInterface::class, static function(RouterInterface $router): void {
            $router->post('queue', function (ServerRequestInterface $request, QueueInterface $queue) {
                
                $queue->push(new Job(
                    name: 'sample',
                    payload: ['key' => 'value'],
                ));
                
                return 'response';
            });
        });
        
        // run the app:
        $this->runApp();
        
        // assertions:
        $fakeQueue->queue(name: 'sync')
            ->assertPushed('sample')
            ->assertPushed('sample', function (JobInterface $job): bool {
                return $job->getPayload()['key'] === 'value';
            })
            ->assertNotPushed('sample:foo')
            ->assertNotPushed('sample', function (JobInterface $job): bool {
                return $job->getPayload()['key'] === 'invalid';
            })
            ->assertPushedTimes('sample', 1);
        
        $fakeQueue->queue(name: 'file')
            ->assertNothingPushed();
    }
    
    public function testAssertPushedMethodThrowsException()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('The expected job [sample] was not pushed.');
        
        $fakeQueue = $this->fakeQueue();
        $http = $this->fakeHttp();
        $this->runApp();
        $fakeQueue->queue(name: 'sync')->assertPushed('sample');
    }
    
    public function testAssertNotPushedMethodThrowsException()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('The unexpected job [sample] was pushed.');
        
        $fakeQueue = $this->fakeQueue();
        $http = $this->fakeHttp();
        $http->request(method: 'POST', uri: 'queue');
        
        $this->getApp()->on(RouterInterface::class, static function(RouterInterface $router): void {
            $router->post('queue', function (ServerRequestInterface $request, QueueInterface $queue) {
                
                $queue->push(new Job(
                    name: 'sample',
                    payload: ['key' => 'value'],
                ));
                
                return 'response';
            });
        });
        
        $this->runApp();
        $fakeQueue->queue(name: 'sync')->assertNotPushed('sample');
    }
    
    public function testAssertPushedTimesMethodThrowsException()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('The expected job [sample] was sent 0 times instead of 2 times');
        
        $fakeQueue = $this->fakeQueue();
        $http = $this->fakeHttp();
        $this->runApp();
        $fakeQueue->queue(name: 'sync')->assertPushedTimes('sample', 2);
    }
    
    public function testAssertNothingPushedMethodThrowsException()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('The following jobs were pushed unexpectedly: sample');
        
        $fakeQueue = $this->fakeQueue();
        $http = $this->fakeHttp();
        $http->request(method: 'POST', uri: 'queue');
        
        $this->getApp()->on(RouterInterface::class, static function(RouterInterface $router): void {
            $router->post('queue', function (ServerRequestInterface $request, QueueInterface $queue) {
                
                $queue->push(new Job(
                    name: 'sample',
                    payload: ['key' => 'value'],
                ));
                
                return 'response';
            });
        });
        
        $this->runApp();
        $fakeQueue->queue(name: 'sync')->assertNothingPushed();
    }    
}