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
use Tobento\Service\Responser\ResponserInterface;
use Psr\Http\Message\ServerRequestInterface;
use Tobento\Service\Config\ConfigInterface;

class ConfigTest extends \Tobento\App\Testing\TestCase
{
    public function createApp(): AppInterface
    {
        $app = $this->createTmpApp(rootDir: __DIR__.'/..');
        $app->boot(\Tobento\App\Http\Boot\Routing::class);
        $app->boot(\Tobento\App\Http\Boot\RequesterResponser::class);
        
        $app->on(RouterInterface::class, static function(RouterInterface $router): void {
            $router->get('foo', function (ResponserInterface $responser) {
                return $responser->redirect(uri: 'bar');
            });

            $router->get('bar', function () {
                return 'bar';
            });
        });
        
        return $app;
    }

    public function testConfig()
    {
        $config = $this->fakeConfig();
        $config->with('http.hosts', ['example.de']);
        $http = $this->fakeHttp();
        
        $this->runApp();

        $config
            ->assertExists(key: 'http.hosts')
            ->assertSame(key: 'http.hosts', value: ['example.de']);
    }
    
    public function testFollowingRedirects()
    {
        $config = $this->fakeConfig();
        $config->with('app.environment', 'testing');
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'foo');
        
        $http->response()->assertStatus(302);
        $config->assertSame(key: 'app.environment', value: 'testing');
        
        $http->followRedirects()->assertStatus(200)->assertBodySame('bar');
        $this->fakeConfig()->assertSame(key: 'app.environment', value: 'testing');
    }    
}