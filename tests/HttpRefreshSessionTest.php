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

use Tobento\App\AppInterface;
use Tobento\App\Testing\Http\RefreshSession;
use Tobento\Service\Routing\RouterInterface;
use Tobento\Service\Session\SessionInterface;
use Psr\Http\Message\ServerRequestInterface;

class HttpRefreshSessionTest extends \Tobento\App\Testing\TestCase
{
    use RefreshSession;
    
    public function createApp(): AppInterface
    {
        $app = $this->createTmpApp(rootDir: __DIR__.'/..');
        $app->boot(\Tobento\App\Http\Boot\Routing::class);
        $app->boot(\Tobento\App\Http\Boot\RequesterResponser::class);
        $app->boot(\Tobento\App\Http\Boot\Session::class);
        $app->boot(\Tobento\App\Http\Boot\Cookies::class);    
        $app->boot(\Tobento\App\Language\Boot\Language::class);
        $app->boot(\Tobento\App\Translation\Boot\Translation::class);
        
        $app->on(RouterInterface::class, static function(RouterInterface $router): void {
            $router->get('foo', function (ServerRequestInterface $request) {
                $session = $request->getAttribute(SessionInterface::class);
                $session->set('foo', 'Foo');
                return 'foo';
            });
            
            $router->get('bar', function (ServerRequestInterface $request) {
                $session = $request->getAttribute(SessionInterface::class);
                $session->set('bar', 'Bar');
                return 'bar';
            });
        });
        
        return $app;
    }

    public function testSessionRefreshes()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'foo');
        
        $http->response()
            ->assertStatus(200)
            ->assertHasSession('foo')
            ->assertHasSession('foo', 'Foo')
            ->assertSessionMissing('bar');
    }
    
    public function testSessionRefreshesAgain()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'bar');
        
        $http->response()
            ->assertStatus(200)
            ->assertHasSession('bar')
            ->assertHasSession('bar', 'Bar')
            ->assertSessionMissing('foo');
    }
}