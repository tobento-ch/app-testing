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
use Tobento\App\User\Middleware\Authenticated;
use Tobento\App\User\Authentication\AuthInterface;
use Tobento\App\User\UserRepositoryInterface;
use Tobento\App\Seeding\User\UserFactory;
use Psr\Http\Message\ServerRequestInterface;

class AuthTest extends \Tobento\App\Testing\TestCase
{
    public function createApp(): AppInterface
    {
        $app = $this->createTmpApp(rootDir: __DIR__.'/..');
        $app->boot(\Tobento\App\Http\Boot\Routing::class);
        $app->boot(\Tobento\App\Http\Boot\RequesterResponser::class);
        $app->boot(\Tobento\App\User\Boot\HttpUserErrorHandler::class);
        $app->boot(\Tobento\App\User\Boot\User::class);
        return $app;
    }
    
    public function tearDown(): void
    {
        // delete all users after each test:
        $this->getApp()->get(UserRepositoryInterface::class)->delete(where: []);
    }

    public function testAuthenticatedWithInMemoryTokenStorage()
    {
        $http = $this->fakeHttp();
        $http->request('GET', 'profile');
        $auth = $this->fakeAuth();
        $auth->tokenStorage('inmemory');
        
        $this->getApp()->on(RouterInterface::class, static function(RouterInterface $router): void {
            $router->get('profile', function (ServerRequestInterface $request) {
                $authenticated = $request->getAttribute(AuthInterface::class)->getAuthenticated();
                return $authenticated?->user()?->username();
            })->middleware(Authenticated::class);
        });
        
        $app = $this->bootingApp();
        $user = $auth->getUserRepository()->create(['username' => 'tom']);
        $auth->authenticatedAs($user);
        
        $http->response()->assertStatus(200)->assertBodySame('tom');
        $auth->assertAuthenticated();
    }
    
    public function testAuthenticatedWithStorageTokenStorage()
    {
        $http = $this->fakeHttp();
        $http->request('GET', 'profile');
        $auth = $this->fakeAuth();
        $auth->tokenStorage('storage');
        
        $this->getApp()->on(RouterInterface::class, static function(RouterInterface $router): void {
            $router->get('profile', function (ServerRequestInterface $request) {
                $authenticated = $request->getAttribute(AuthInterface::class)->getAuthenticated();
                return $authenticated?->user()?->username();
            })->middleware(Authenticated::class);
        });
        
        $app = $this->bootingApp();
        $user = $auth->getUserRepository()->create(['username' => 'tom']);
        $auth->authenticatedAs($user);
        
        $http->response()->assertStatus(200)->assertBodySame('tom');
        $auth->assertAuthenticated();
    }
    
    public function testAuthenticatedWithSessionTokenStorage()
    {
        $http = $this->fakeHttp();
        $http->request('GET', 'profile');
        $auth = $this->fakeAuth();
        $auth->tokenStorage('session');
        
        $this->getApp()->boot(\Tobento\App\Http\Boot\Session::class);
        
        $this->getApp()->on(RouterInterface::class, static function(RouterInterface $router): void {
            $router->get('profile', function (ServerRequestInterface $request) {
                $authenticated = $request->getAttribute(AuthInterface::class)->getAuthenticated();
                return $authenticated?->user()?->username();
            })->middleware(Authenticated::class);
        });
        
        $app = $this->bootingApp();
        $user = $auth->getUserRepository()->create(['username' => 'tom']);
        $auth->authenticatedAs($user);
        
        $http->response()->assertStatus(200)->assertBodySame('tom');
        $auth->assertAuthenticated();
    }
    
    public function testAuthenticatedWithUserFactory()
    {
        $http = $this->fakeHttp();
        $http->request('GET', 'profile');
        $auth = $this->fakeAuth();
        $auth->tokenStorage('inmemory');
        
        $this->getApp()->boot(\Tobento\App\Seeding\Boot\Seeding::class);
        
        $this->getApp()->on(RouterInterface::class, static function(RouterInterface $router): void {
            $router->get('profile', function (ServerRequestInterface $request) {
                $authenticated = $request->getAttribute(AuthInterface::class)->getAuthenticated();
                return $authenticated?->user()?->username();
            })->middleware(Authenticated::class);
        });
        
        $app = $this->bootingApp();
        $user = UserFactory::new()->withUsername('tom')->withPassword('123456')->createOne();
        $auth->authenticatedAs($user);
        
        $http->response()->assertStatus(200)->assertBodySame('tom');
        $auth->assertAuthenticated();
    }
    
    public function testAuthenticatedWithToken()
    {
        $http = $this->fakeHttp();
        $http->request('GET', 'profile');
        $auth = $this->fakeAuth();
        $auth->tokenStorage('inmemory');
        
        $this->getApp()->on(RouterInterface::class, static function(RouterInterface $router): void {
            $router->get('profile', function (ServerRequestInterface $request) {
                $authenticated = $request->getAttribute(AuthInterface::class)->getAuthenticated();
                return $authenticated?->user()?->username();
            })->middleware(['auth', 'via' => 'loginform|loginlink']);
        });
        
        $app = $this->bootingApp();
        $user = $auth->getUserRepository()->create(['username' => 'tom']);
        $token = $auth->getTokenStorage()->createToken(
            payload: ['userId' => $user->id(), 'passwordHash' => $user->password()],
            authenticatedVia: 'loginform',
            authenticatedBy: 'testing',
        );
        $auth->authenticatedAs($token);
        
        $http->response()->assertStatus(200)->assertBodySame('tom');
        $auth->assertAuthenticated();
        
        $this->assertSame('testing', $auth->getAuthenticated()->by());
    }
    
    public function testThrowsExceptionIfNotAuthenticated()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('The user is not authenticated');
        
        $http = $this->fakeHttp();
        $http->request('GET', 'profile');
        $auth = $this->fakeAuth();
        $auth->tokenStorage('inmemory');
        
        $this->getApp()->on(RouterInterface::class, static function(RouterInterface $router): void {
            $router->get('profile', function (ServerRequestInterface $request) {
                $authenticated = $request->getAttribute(AuthInterface::class)->getAuthenticated();
                return $authenticated?->user()?->username();
            })->middleware(Authenticated::class);
        });
        
        $this->runApp();
        
        $auth->assertAuthenticated();
    }
    
    public function testThrowsExceptionIfUserIsAuthenticated()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('The user is authenticated');
        
        $http = $this->fakeHttp();
        $http->request('GET', 'profile');
        $auth = $this->fakeAuth();
        $auth->tokenStorage('inmemory');
        
        $this->getApp()->boot(\Tobento\App\Seeding\Boot\Seeding::class);
        
        $this->getApp()->on(RouterInterface::class, static function(RouterInterface $router): void {
            $router->get('profile', function (ServerRequestInterface $request) {
                $authenticated = $request->getAttribute(AuthInterface::class)->getAuthenticated();
                return $authenticated?->user()?->username();
            })->middleware(Authenticated::class);
        });
        
        $app = $this->bootingApp();
        $user = UserFactory::new()->withUsername('tom')->withPassword('123456')->createOne();
        $auth->authenticatedAs($user);
        
        $this->runApp();
        
        $auth->assertNotAuthenticated();
    }
}