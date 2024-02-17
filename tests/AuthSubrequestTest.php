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
use Tobento\Service\Requester\RequesterInterface;
use Tobento\Service\Requester\ResponserInterface;
use Tobento\App\User\Middleware\Authenticated;
use Tobento\App\User\Middleware\Unauthenticated;
use Tobento\App\User\Authentication\AuthInterface;
use Tobento\App\User\UserRepositoryInterface;
use Tobento\App\User\AddressRepositoryInterface;
use Tobento\App\Seeding\User\UserFactory;
use Psr\Http\Message\ServerRequestInterface;

class AuthSubrequestTest extends \Tobento\App\Testing\TestCase
{
    public function tearDown(): void
    {
        // delete all users after each test:
        $this->getApp()->get(UserRepositoryInterface::class)->delete(where: []);
        $this->getApp()->get(AddressRepositoryInterface::class)->delete(where: []);
    }
    
    public function createApp(): AppInterface
    {
        $app = $this->createTmpApp(rootDir: __DIR__.'/..');
        $app->boot(\Tobento\App\Http\Boot\Routing::class);
        $app->boot(\Tobento\App\Http\Boot\Cookies::class);
        $app->boot(\Tobento\App\Http\Boot\RequesterResponser::class);
        $app->boot(\Tobento\App\User\Boot\HttpUserErrorHandler::class);
        $app->boot(\Tobento\App\User\Boot\User::class);        
        $app->boot(\Tobento\App\Language\Boot\Language::class);
        $app->boot(\Tobento\App\Translation\Boot\Translation::class);
        
        $app->on(RouterInterface::class, static function(RouterInterface $router): void {
            $router->get('login', function (ServerRequestInterface $request) {
                return 'login';
            })->middleware([Unauthenticated::class, 'redirectUri' => 'profile']);
            
            $router->get('foo', function () {
                return 'foo';
            })->middleware([Unauthenticated::class, 'redirectUri' => 'login']);   
            
            $router->get('profile', function (ServerRequestInterface $request) {
                $authenticated = $request->getAttribute(AuthInterface::class)->getAuthenticated();
                return $authenticated?->user()?->username();
            })->middleware(Authenticated::class);
        });
        
        return $app;
    }
    
    public function testAuthenticatedSubrequest()
    {
        $http = $this->fakeHttp();
        $http->request('GET', 'login');
        $auth = $this->fakeAuth()->tokenStorage('storage');
        
        $app = $this->bootingApp();
        $user = $auth->getUserRepository()->create(['username' => 'tom']);
        $auth->authenticatedAs($user);
        
        $http->response()->assertStatus(302);
        $auth->assertAuthenticated();
        
        // new request
        $http->request(method: 'GET', uri: 'profile');
        $http->response()->assertStatus(200);
        $this->fakeAuth()->assertAuthenticated();
    }
    
    public function testAuthenticatedMultipleSubrequest()
    {
        $http = $this->fakeHttp();
        $http->request('GET', 'login');
        $auth = $this->fakeAuth()->tokenStorage('storage');
        
        $app = $this->bootingApp();
        $user = $auth->getUserRepository()->create(['username' => 'tom']);
        $auth->authenticatedAs($user);
        
        $http->response()->assertStatus(302);
        $auth->assertAuthenticated();
        
        // new request
        $http->request(method: 'GET', uri: 'profile');
        $http->response()->assertStatus(200);
        $this->fakeAuth()->assertAuthenticated();
        
        // new request
        $http->request(method: 'GET', uri: 'profile');
        $http->response()->assertStatus(200);
        $this->fakeAuth()->assertAuthenticated();
        
        // new request
        $http->request(method: 'GET', uri: 'login');
        $http->response()->assertStatus(302);
        
        $http->followRedirects()->assertStatus(200)->assertBodySame('tom');
        $this->fakeAuth()->assertAuthenticated();
    }
    
    public function testAuthenticatedFollowingRedirects()
    {
        $http = $this->fakeHttp();
        $http->request('GET', 'foo');
        $auth = $this->fakeAuth()->tokenStorage('storage');
        
        $app = $this->bootingApp();
        $user = $auth->getUserRepository()->create(['username' => 'tom']);
        $auth->authenticatedAs($user);
        
        $http->response()->assertStatus(302);
        $auth->assertAuthenticated();
        
        $http->followRedirects()->assertStatus(200)->assertBodySame('tom');
        $this->fakeAuth()->assertAuthenticated();
    }
}