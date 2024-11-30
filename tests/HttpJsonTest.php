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
use Tobento\App\Testing\Http\AssertableJson;
use Tobento\Service\Routing\RouterInterface;

class HttpJsonTest extends \Tobento\App\Testing\TestCase
{
    public function createApp(): AppInterface
    {
        $app = $this->createTmpApp(rootDir: __DIR__.'/..');
        $app->boot(\Tobento\App\Http\Boot\Routing::class);
        $app->boot(\Tobento\App\Http\Boot\Session::class);
        $app->boot(\Tobento\App\Http\Boot\RequesterResponser::class);
        $app->boot(\Tobento\App\Http\Boot\Cookies::class);
        return $app;
    }

    public function testAssertJson()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'user');
        
        $this->getApp()->on(RouterInterface::class, static function(RouterInterface $router): void {
            $router->get('user', function () {
                return ['name' => 'John'];
            });
        });
        
        $http->response()->assertJson(['name' => 'John']);
    }
    
    public function testAssertJsonThrowsIfNotMatching()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('Json object does not match the expected value.');
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'user');
        
        $this->getApp()->on(RouterInterface::class, static function(RouterInterface $router): void {
            $router->get('user', function () {
                return ['name' => 'John'];
            });
        });
        
        $http->response()->assertJson(['name' => 'Ben']);
    }
    
    public function testAssertJsonWithClosure()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'user');
        
        $this->getApp()->on(RouterInterface::class, static function(RouterInterface $router): void {
            $router->get('user', function () {
                return ['name' => 'John'];
            });
        });
        
        $http->response()->assertJson(fn (AssertableJson $json) =>
            $json->has(key: 'name')
        );
    }
    
    public function testAssertJsonWithClosureThrows()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('Json property [street] does not exist.');
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'user');
        
        $this->getApp()->on(RouterInterface::class, static function(RouterInterface $router): void {
            $router->get('user', function () {
                return ['name' => 'John'];
            });
        });
        
        $http->response()->assertJson(fn (AssertableJson $json) =>
            $json->has(key: 'street')
        );
    }
}