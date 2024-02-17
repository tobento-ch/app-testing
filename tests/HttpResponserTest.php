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
use Tobento\Service\Routing\RouterInterface;
use Tobento\Service\Requester\RequesterInterface;
use Tobento\Service\Responser\ResponserInterface;
use Tobento\Service\Session\SessionInterface;
use Psr\Http\Message\ServerRequestInterface;

class HttpResponserTest extends \Tobento\App\Testing\TestCase
{
    public function createApp(): AppInterface
    {
        $app = $this->createTmpApp(rootDir: __DIR__.'/..');
        $app->boot(\Tobento\App\Http\Boot\Routing::class);
        $app->boot(\Tobento\App\Http\Boot\Session::class);
        $app->boot(\Tobento\App\Http\Boot\RequesterResponser::class);
        $app->boot(\Tobento\App\Http\Boot\Cookies::class);    
        $app->boot(\Tobento\App\Language\Boot\Language::class);
        $app->boot(\Tobento\App\Translation\Boot\Translation::class);
        $app->boot(\Tobento\App\Event\Boot\Event::class);
        
        $app->on(RouterInterface::class, static function(RouterInterface $router): void {
            $router->get('input', function (ResponserInterface $responser) {
                return $responser->getInput();
            });

            $router->get('messages', function (ResponserInterface $responser) {
                return $responser->messages()->toArray();
            });
        });
        
        return $app;
    }
    
    public function testFollowingRedirectsWithInput()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'redirects');
        
        $app = $this->bootingApp();
        $app->get(RouterInterface::class)->get('redirects', function (ResponserInterface $responser) {
            $responser->messages()->add('error', 'Error message');            
            return $responser->withInput(['key' => 'value'])->redirect(uri: 'input');
        });
        
        $http->response()->assertStatus(302);
        $http->followRedirects()->assertStatus(200)->assertBodySame('{"key":"value"}');
        
        $http->request(method: 'GET', uri: 'input');
        $http->response()->assertStatus(200)->assertBodySame('[]');
    }
    
    public function testFollowingRedirectsWithMessages()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'redirects');
        
        $app = $this->bootingApp();
        $app->get(RouterInterface::class)->get('redirects', function (ResponserInterface $responser) {
            $responser->messages()->add('error', 'Error message');            
            return $responser->redirect(uri: 'messages');
        });
        
        $http->response()->assertStatus(302);
        $http->followRedirects()->assertStatus(200)->assertBodyContains('Error message');
        
        $http->request(method: 'GET', uri: 'messages');
        $http->response()->assertStatus(200)->assertBodySame('[]');
    }
}