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
use Tobento\App\AppInterface;
use Tobento\App\Testing\Database\RefreshDatabases;
use Tobento\App\User\AddressRepositoryInterface;
use Tobento\App\User\Authentication\AuthInterface;
use Tobento\App\User\Middleware\Authenticated;
use Tobento\Service\Acl\AclInterface;
use Tobento\Service\Responser\ResponserInterface;
use Tobento\Service\Routing\RouterInterface;

class AuthPermissionsTest extends \Tobento\App\Testing\TestCase
{
    use RefreshDatabases;
    
    public function createApp(): AppInterface
    {
        $app = $this->createTmpApp(rootDir: __DIR__.'/..');
        $app->boot(\Tobento\App\Http\Boot\Routing::class);
        $app->boot(\Tobento\App\Http\Boot\RequesterResponser::class);
        $app->boot(\Tobento\App\User\Boot\HttpUserErrorHandler::class);
        $app->boot(\Tobento\App\User\Boot\User::class);
        return $app;
    }

    public function testPermissionsAreAppliedImmediately()
    {
        $http = $this->fakeHttp();
        $http->request('GET', 'dashboard');
        $auth = $this->fakeAuth();

        // Route checks permission directly from ACL
        $this->getApp()->on(RouterInterface::class, function(RouterInterface $router, AclInterface $acl) {
            // Declare the permission rule
            $acl->rule('articles.edit');
            
            $router->get('dashboard', function(AclInterface $acl) {
                return $acl->can('articles.edit') ? 'ok' : 'no';
            });
        });

        $app = $this->bootingApp();

        $user = $auth->getUserRepository()->create(['username' => 'tom']);
        $auth->authenticatedAs($user);

        $auth->addPermissions(['articles.edit']);

        $http->response()->assertBodySame('ok');
    }

    public function testPermissionsPersistAcrossRedirects()
    {
        $this->onCreateApp(function(AppInterface $app) {
            $app->on(RouterInterface::class, function(RouterInterface $router, AclInterface $acl) {
                $acl->rule('articles.own');

                $router->get('dashboard', function(ResponserInterface $responser) {
                    return $responser->redirect(uri: 'dashboard-final');
                });

                $router->get('dashboard-final', function(AclInterface $acl) {
                    return $acl->can('articles.own') ? 'ok' : 'no';
                });
            });
        });
        
        $http = $this->fakeHttp();
        $http->request('GET', 'dashboard');
        $auth = $this->fakeAuth();

        $app = $this->bootingApp();

        $user = $auth->getUserRepository()->create(['username' => 'tom']);
        $auth->authenticatedAs($user);

        $auth->addPermissions(['articles.own']);
        
        $http->followRedirects()->assertBodySame('ok');
    }

    public function testMultiplePermissions()
    {
        $http = $this->fakeHttp();
        $http->request('GET', 'dashboard');
        $auth = $this->fakeAuth();
        
        $this->getApp()->on(RouterInterface::class, function(RouterInterface $router, AclInterface $acl) {
            // Declare the permission rule
            $acl->rule('articles.edit');
            $acl->rule('articles.publish');
            
            $router->get('dashboard', function(AclInterface $acl) {
                return $acl->can('articles.publish') &&
                       $acl->can('articles.edit')
                       ? 'ok' : 'no';
            });
        });

        $app = $this->bootingApp();

        $user = $auth->getUserRepository()->create(['username' => 'tom']);
        $auth->authenticatedAs($user);

        $auth->addPermissions([
            'articles.publish',
            'articles.edit',
        ]);

        $http->response()->assertBodySame('ok');
    }
}