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

namespace Tobento\App\Testing\Test\Addon;

use Psr\Http\Message\ServerRequestInterface;
use Tobento\App\AppInterface;
use Tobento\App\Testing\Test\Mock\Backend;
use Tobento\App\Testing\Test\Mock\Frontend;
use Tobento\Apps\AppsInterface;

class ForwardRequestAddonTest extends \Tobento\App\Testing\TestCase
{
    use \Tobento\App\Testing\Addon\ForwardRequestAddon;

    public function createApp(): AppInterface
    {
        $app = $this->createTmpApp(rootDir: __DIR__.'/../..');
        $app->boot(Backend::class);
        $app->boot(Frontend::class);
        $app->booting();

        // The app under test is the backend sub app, not booted yet:
        return $app->get(AppsInterface::class)->get('backend')->app();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->deleteAppDirectory();
    }

    /**
     * Fakes the request, forwards it to the frontend and returns
     * the request the frontend app resolves.
     */
    protected function forwardedRequest(string $method, string $uri): ServerRequestInterface
    {
        $http = $this->fakeHttp();
        $http->request(method: $method, uri: $uri);

        $frontend = $this->forwardRequestToApp(app: $this->getApp(), appId: 'frontend', prefix: 'app/frontend/');

        $this->bootingApp();
        $frontend->booting();

        return $frontend->get(ServerRequestInterface::class);
    }

    public function testReturnsTheSubApp()
    {
        $app = $this->getApp();

        $frontend = $this->forwardRequestToApp(app: $app, appId: 'frontend', prefix: 'app/frontend/');

        $this->assertSame($app->get(AppsInterface::class)->get('frontend')->app(), $frontend);
    }

    public function testThrowsIfSubAppIsNotRegistered()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot forward request to sub-app "unknown": it is not registered.');

        $this->forwardRequestToApp(app: $this->getApp(), appId: 'unknown', prefix: 'app/frontend/');
    }

    public function testPrefixIsStrippedFromTheRequestPath()
    {
        $request = $this->forwardedRequest('GET', 'app/frontend/about');

        $this->assertSame('about', $request->getUri()->getPath());
    }

    public function testNestedPathIsKept()
    {
        $request = $this->forwardedRequest('GET', 'app/frontend/blog/post-1');

        $this->assertSame('blog/post-1', $request->getUri()->getPath());
    }

    public function testMethodAndQueryAreKept()
    {
        $request = $this->forwardedRequest('POST', 'app/frontend/blog?page=2');

        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('blog', $request->getUri()->getPath());
        $this->assertSame('page=2', $request->getUri()->getQuery());
    }

    public function testRequestFakedAfterForwardingIsUsed()
    {
        // Same order as in the cms: forward first, fake the request later.
        $frontend = $this->forwardRequestToApp(app: $this->getApp(), appId: 'frontend', prefix: 'app/frontend/');

        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'app/frontend/about');

        $this->bootingApp();
        $frontend->booting();

        $this->assertSame('about', $frontend->get(ServerRequestInterface::class)->getUri()->getPath());
    }
}