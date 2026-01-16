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

use Nyholm\Psr7\Response;
use PHPUnit\Framework\ExpectationFailedException;
use Psr\Http\Message\ResponseFactoryInterface;
use Tobento\App\AppInterface;
use Tobento\App\Http\SimpleResponseEmitterInterface;
use Tobento\Service\Responser\ResponserInterface;
use Tobento\Service\Routing\RouterInterface;

class HttpResponseEmitterTest extends \Tobento\App\Testing\TestCase
{
    public function createApp(): AppInterface
    {
        $app = $this->createTmpApp(rootDir: __DIR__.'/..');
        $app->boot(\Tobento\App\Http\Boot\Routing::class);
        $app->boot(\Tobento\App\Http\Boot\RequesterResponser::class);
        return $app;
    }

    /**
     * Helper to emit a response through the app's SimpleResponseEmitterInterface.
     */
    protected function emit(Response $response): Response
    {
        $app = $this->getApp();
        $emitter = $app->get(SimpleResponseEmitterInterface::class);

        return $emitter->emit($response);
    }

    public function testResponseIsCaptured(): void
    {
        $emitter = $this->fakeHttpResponseEmitter();

        $this->getApp()->booting();

        $this->emit(new Response(200));

        $response = $emitter->response();
        $response->assertStatus(200);
    }

    public function testAssertHeader(): void
    {
        $emitter = $this->fakeHttpResponseEmitter();

        $this->getApp()->booting();

        $this->emit(
            (new Response(200))
                ->withHeader('Content-Type', 'application/pdf')
        );

        $response = $emitter->response();
        $response->assertHasHeader('Content-Type', 'application/pdf');
    }

    public function testAssertBody(): void
    {
        $emitter = $this->fakeHttpResponseEmitter();

        $this->getApp()->booting();

        $this->emit(
            new Response(200, [], 'Hello World')
        );

        $response = $emitter->response();
        $response->assertBodySame('Hello World', escape: false);
    }

    public function testFakeResponse(): void
    {
        $emitter = $this->fakeHttpResponseEmitter();

        $this->getApp()->booting();

        // Fake the emitted response:
        $emitter->emit(new Response(201));

        $response = $emitter->response();
        $response->assertStatus(201);
    }

    public function testFakeResponseUsingFactory(): void
    {
        $emitter = $this->fakeHttpResponseEmitter();

        $this->getApp()->booting();

        $factory = $this->getApp()->get(ResponseFactoryInterface::class);

        $emitter->emit($factory->createResponse(202));

        $response = $emitter->response();
        $response->assertStatus(202);
    }

    public function testAssertThrowsWhenNoResponseEmitted(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No response was emitted.');

        $emitter = $this->fakeHttpResponseEmitter();
        $this->getApp()->booting();

        // No response emitted → calling response() should fail
        $emitter->response();
    }

    public function testLastResponseIsReturned(): void
    {
        $emitter = $this->fakeHttpResponseEmitter();

        $this->getApp()->booting();

        $this->emit(new Response(200));
        $this->emit(new Response(404));

        $response = $emitter->response();
        $response->assertStatus(404);
    }

    public function testAssertBodyFails(): void
    {
        $this->expectException(ExpectationFailedException::class);

        $emitter = $this->fakeHttpResponseEmitter();

        $this->getApp()->booting();

        $this->emit(new Response(200, [], 'foo'));

        $response = $emitter->response();
        $response->assertBodySame('bar', escape: false);
    }
    
    public function testHttpResponseEmitterPersistsAcrossRedirects(): void
    {
        // Register routes that trigger a redirect
        $this->onCreateApp(function(AppInterface $app) {
            $app->on(RouterInterface::class, function($router) {
                $router->get('start', function(ResponserInterface $responser) {
                    return $responser->redirect(uri: 'final');
                });

                $router->get('final', function(SimpleResponseEmitterInterface $emitter) {
                    // Emit a response during redirect
                    $emitter->emit(new Response(201));
                    return 'ok';
                });
            });
        });

        // Fake incoming HTTP
        $http = $this->fakeHttp();
        $http->request('GET', 'start');
        $emitter = $this->fakeHttpResponseEmitter();

        // Follow redirect
        $http->followRedirects()->assertBodySame('ok');

        $emitter = $this->fakeHttpResponseEmitter();

        // Assert the emitted response was captured
        $response = $emitter->response();
        $response->assertStatus(201);
    }
}