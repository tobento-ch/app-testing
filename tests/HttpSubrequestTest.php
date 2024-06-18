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
use Tobento\App\Http\Middleware\SecurePolicyHeaders;
use Tobento\Service\Routing\RouterInterface;
use Tobento\Service\Requester\RequesterInterface;
use Tobento\Service\Responser\ResponserInterface;
use Tobento\Service\Session\SessionInterface;
use Tobento\Service\Uri\PreviousUriInterface;
use Psr\Http\Message\ServerRequestInterface;

class HttpSubrequestTest extends \Tobento\App\Testing\TestCase
{
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
            $router->get('redirects-to-article', function (ResponserInterface $responser) {
                return $responser->redirect(uri: 'article');
            });
        
            $router->get('article', function () {
                return 'article';
            })->name('article')->middleware(SecurePolicyHeaders::class);
            
            $router->get('session-flash', function (ServerRequestInterface $request) {
                $session = $request->getAttribute(SessionInterface::class);
                $session->flash('key', 'value');
                return 'session-flash';
            });
            
            $router->get('session-flashed', function (ServerRequestInterface $request) {
                $session = $request->getAttribute(SessionInterface::class);
                return $session->get('key', '');
            });
        });
        
        return $app;
    }
    
    public function bootingApp(): AppInterface
    {
        $app = $this->getApp()->booting();
        $app->set('isBootingAppCalled', true);
        $app->get(RouterInterface::class)->get('route-booting', function () {
            return 'route-booting';
        })->name('route-booting');
        
        return $app;
    }

    public function testFollowingRedirects()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'redirects-to-article');
        $http->response()->assertStatus(302);
        $http->followRedirects()->assertStatus(200)->assertBodySame('article');
    }
    
    public function testFollowingRedirectsMultiple()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'redirects');
        
        $app = $this->bootingApp();
        $app->get(RouterInterface::class)->get('redirects', function (ResponserInterface $responser) {
            return $responser->redirect(uri: 'redirects-to-article');
        });
        
        $http->response()->assertStatus(302);
        $http->followRedirects()->assertStatus(200)->assertBodySame('article');
    }
    
    public function testFollowingRedirectsKeepsWithoutMiddleware()
    {
        $http = $this->fakeHttp();
        $http->withoutMiddleware(SecurePolicyHeaders::class);
        $http->request(method: 'GET', uri: 'redirects-to-article');
        
        $http->response()->assertStatus(302);
        $http->followRedirects()
            ->assertStatus(200)
            ->assertBodySame('article')
            ->assertHeaderMissing('Strict-Transport-Security');
    }
    
    public function testFollowingRedirectsPreviousUri()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'POST', uri: 'redirects-to-prev-uri');

        $this->getApp()->on(RouterInterface::class, static function(RouterInterface $router): void {
            $router->post('redirects-to-prev-uri', function (PreviousUriInterface $previousUri) {
                return (string)$previousUri;
            });
        });
        
        $app = $this->bootingApp();
        $http->previousUri($app->routeUrl('article'));
        
        $http->followRedirects()
            ->assertStatus(200)
            ->assertBodySame('article');
    }
    
    public function testFollowingRedirectsIsBootingAppMethodCalled()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'redirects-to-article');
        $http->response()->assertStatus(302);
        $http->followRedirects()->assertStatus(200)->assertBodySame('article');
        
        $this->assertTrue($this->getApp()->has('isBootingAppCalled'));
    }
    
    public function testFollowingRedirectsKeepsServerParams()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'redirects-to-article', server: ['REMOTE_ADDR' => 'addr']);
        
        $http->followRedirects()->assertStatus(200);
        
        $request = $http->request(method: 'GET', uri: 'redirects-to-article');
        $this->assertSame(['REMOTE_ADDR' => 'addr'], $request->getRequest()->getServerParams());
    }
    
    public function testSubrequest()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'article');
        $http->response()->assertStatus(200)->assertBodySame('article');
        
        $http->request(method: 'GET', uri: 'redirects-to-article');
        $http->response()->assertStatus(302);
    }
    
    public function testSubrequestSessionFlash()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'session-flash');
        $http->response()->assertStatus(200)->assertHasSession('key')->assertBodySame('session-flash');
        
        $http->request(method: 'GET', uri: 'session-flashed');
        $http->response()->assertStatus(200)->assertSessionMissing('key')->assertBodySame('value');
        
        $http->request(method: 'GET', uri: 'session-flashed');
        $http->response()->assertStatus(200)->assertSessionMissing('key')->assertBodySame('');   
    }
    
    public function testSubrequestKeepsWithoutMiddleware()
    {
        $http = $this->fakeHttp();
        $http->withoutMiddleware(SecurePolicyHeaders::class);
        $http->request(method: 'GET', uri: 'article');
        $http->response()->assertStatus(200)->assertBodySame('article')->assertHeaderMissing('Strict-Transport-Security');
        
        $http->request(method: 'GET', uri: 'redirects-to-article');
        $http->response()->assertStatus(302)->assertHeaderMissing('Strict-Transport-Security');
    }
    
    public function testSubrequestIsBootingAppMethodCalled()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'article');
        
        $http->response()->assertStatus(200)->assertBodySame('article');
        
        $http->request(method: 'GET', uri: 'route-booting');
        $http->response()->assertStatus(200)->assertBodySame('route-booting');
        
        $this->assertTrue($this->getApp()->has('isBootingAppCalled'));
    }
    
    public function testSubrequestKeepsAndOverwritesServerParams()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'article', server: ['REMOTE_ADDR' => 'addr']);
        $http->response()->assertStatus(200)->assertBodySame('article');
        
        $request = $http->request(method: 'GET', uri: 'article', server: ['REMOTE_ADDR' => 'addr']);
        $this->assertSame(['REMOTE_ADDR' => 'addr'], $request->getRequest()->getServerParams());
        
        $request = $http->request(method: 'GET', uri: 'article', server: ['REMOTE_ADDR' => 'addr-new']);
        $this->assertSame(['REMOTE_ADDR' => 'addr-new'], $request->getRequest()->getServerParams());
    }
}